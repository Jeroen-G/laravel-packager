<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\Validator as ValidatorInterface;
use Illuminate\Support\Facades\Validator;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;
use JeroenG\Packager\ValidationRules\ValidClassName;
use JeroenG\Packager\Wrapping;

/**
 * Create a brand new package.
 *
 * @author JeroenG
 **/
class NewPackage extends Command
{
    use ProgressBar;

    protected $signature = 'packager:new {vendor?} {name?} {--i} {--skeleton=}';

    protected $description = 'Create a new package.';

    /**
     * Packages roll off of the conveyor.
     */
    protected Conveyor $conveyor;

    /**
     * Packages are packed in wrappings to personalise them.
     */
    protected Wrapping $wrapping;

    protected FileHandlerInterface $fileHandler;

    public function __construct(Conveyor $conveyor, Wrapping $wrapping, FileHandlerInterface $fileHandler)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->wrapping = $wrapping;
        $this->fileHandler = $fileHandler;
    }

    public function handle(): int
    {
        // Start the progress bar
        $this->startProgressBar(6);

        $vendor = $this->argument('vendor') ?? 'vendor-name';
        $name = $this->argument('name') ?? 'package-name';

        if (mb_strpos($vendor, '/') !== false) {
            [$vendor, $name] = explode('/', $vendor);
        }

        // Defining vendor/package, optionally defined interactively
        if ($this->option('i')) {
            $this->conveyor->vendor($this->ask('What will be the vendor name?', $vendor));
            $this->conveyor->package($this->ask('What will be the package name?', $name));
        } else {
            $this->conveyor->vendor($vendor);
            $this->conveyor->package($name);
        }

        // Validate the vendor and package names
        $validator = $this->validateInput($this->conveyor->vendor(), $this->conveyor->package());

        if ($validator->fails()) {
            $this->showErrors($validator);

            return 1;
        }

        // Start creating the package
        $this->info('Creating package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->fileHandler->checkIfPackageExists($this->conveyor->vendor(), $this->conveyor->package());
        $this->makeProgress();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->fileHandler->makeDir($this->fileHandler->packagesPath());
        $this->makeProgress();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->fileHandler->makeDir($this->fileHandler->vendorPath($this->conveyor->vendor()));
        $this->makeProgress();

        // Get the packager package skeleton
        $this->info('Downloading skeleton...');
        if ($this->option('i')) {
            $this->conveyor->downloadSkeleton($this->ask('What package skeleton would you like to use?', $this->option('skeleton') ?? config('packager.skeleton')));
        } else {
            $this->conveyor->downloadSkeleton($this->option('skeleton'));
        }
        $manifest = (file_exists($this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()).'/rewriteRules.php')) ? $this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()).'/rewriteRules.php' : null;
        $this->fileHandler->renameFiles(
            $this->conveyor->vendorStudly(),
            $this->conveyor->packageStudly(),
            $this->conveyor->vendor(),
            $this->conveyor->package(),
        );
        $this->makeProgress();

        // Replacing skeleton placeholders
        $this->info('Replacing skeleton placeholders...');
        $this->wrapping->replace([
            ':uc:vendor',
            ':uc:package',
            ':lc:vendor',
            ':lc:package',
            ':kc:vendor',
            ':kc:package',
        ], [
            $this->conveyor->vendorStudly(),
            $this->conveyor->packageStudly(),
            mb_strtolower($this->conveyor->vendor()),
            mb_strtolower($this->conveyor->package()),
            $this->conveyor->vendorKebab(),
            $this->conveyor->packageKebab(),
        ]);

        if ($this->option('i')) {
            $this->interactiveReplace();
        } else {
            $this->wrapping->replace([
                ':author_name',
                ':author_email',
                ':author_homepage',
                ':license',
            ], [
                config('packager.author_name'),
                config('packager.author_email'),
                config('packager.author_homepage'),
                config('packager.license'),
            ]);
        }

        // Fill all placeholders in all files with the replacements.
        $this->wrapping->fill($this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()));

        // Make sure to remove the rule files to avoid clutter.
        if ($manifest !== null) {
            $this->fileHandler->cleanUpRules($this->conveyor->vendor(), $this->conveyor->package());
        }

        $this->makeProgress();

        // Add path repository to composer.json and install package
        $this->info('Installing package...');
        $this->conveyor->installPackage();

        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package created successfully!');

        return 1;
    }

    /**
     * Use the interactive CLI to replace certain placeholders.
     *
     * @return void
     */
    protected function interactiveReplace(): void
    {
        $author = $this->ask('Who is the author?', config('packager.author_name'));
        $authorEmail = $this->ask('What is the author\'s e-mail?', config('packager.author_email'));
        $authorHomepage = $this->ask('What is the author\'s website?', config('packager.author_homepage'));
        $description = $this->ask('How would you describe the package?');
        $license = $this->ask('Under which license will it be released?', config('packager.license'));

        $this->wrapping->replace([
            ':author_name',
            ':author_email',
            ':author_homepage',
            ':package_description',
            ':license',
        ], [
            $author,
            $authorEmail,
            $authorHomepage,
            $description,
            $license,
        ]);
    }

    private function validateInput(string $vendor, string $name): ValidatorInterface
    {
        return Validator::make(compact('vendor', 'name'), [
            'vendor' => new ValidClassName,
            'name' => new ValidClassName,
        ]);
    }

    private function showErrors(ValidatorInterface $validator): void
    {
        $this->info('Package was not created. Please choose a valid name.');

        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }
    }
}
