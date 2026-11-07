<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\Tests\CommandTestCase;
use JeroenG\Packager\Tests\Support\CommandRunnerExpectation;
use Mockery;

final class NewPackageTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDownloads($this->makeZip('skeleton.zip', [
            'packager-skeleton-master/composer.json' => '{"name": ":lc:vendor/:lc:package", "author": ":author_name"}',
            'packager-skeleton-master/readme.md' => ':author_name <:author_email> :author_homepage - :package_description (:license)',
            'packager-skeleton-master/src/MyPackage.php' => 'namespace :uc:vendor\\:uc:package; // :kc:package',
            'packager-skeleton-master/rewriteRules.php' => '<?php',
        ]));

        $this->commandRunner->getMock()->allows('run')->andReturnTrue();
    }

    public function test_it_creates_a_package_from_the_skeleton(): void
    {
        config(['packager.author_name' => 'Wile E. Coyote']);
        $path = $this->packagePath('Acme', 'RocketSkates');

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'RocketSkates'])
            ->expectsOutputToContain('Package created successfully!')
            ->assertExitCode(0);

        $this->assertSame([config('packager.skeleton')], $this->downloadedUrls());
        $this->assertSame(
            '{"name": "acme/rocketskates", "author": "Wile E. Coyote"}',
            file_get_contents($path.'/composer.json')
        );
        $this->assertSame(
            'namespace Acme\\RocketSkates; // rocket-skates',
            file_get_contents($path.'/src/RocketSkates.php')
        );
        $this->assertFileDoesNotExist($path.'/rewriteRules.php');
        $this->assertNoTemporaryDirectories('Acme');
    }

    public function test_it_downloads_with_the_configured_certificate_verification(): void
    {
        config(['packager.curl_verify_cert' => false]);

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])->assertExitCode(0);

        $this->assertFalse($this->downloads[0]['options']['verify']);
    }

    public function test_it_accepts_vendor_and_name_separated_by_a_slash(): void
    {
        $this->command('packager:new', ['vendor' => 'Acme/RocketSkates'])
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->packagePath('Acme', 'RocketSkates'));
    }

    public function test_it_uses_a_custom_skeleton(): void
    {
        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket', '--skeleton' => 'https://example.com/skeleton.zip'])
            ->assertExitCode(0);

        $this->assertSame(['https://example.com/skeleton.zip'], $this->downloadedUrls());
    }

    public function test_it_rejects_invalid_names(): void
    {
        $this->command('packager:new', ['vendor' => '1234-invalid', 'name' => 'Rocket'])
            ->expectsOutputToContain('The package vendor must conform to a valid PHP classname.')
            ->assertExitCode(1);

        $this->assertSame([], $this->downloads);
        $this->assertDirectoryDoesNotExist($this->basePath.'/packages');
    }

    public function test_it_refuses_to_overwrite_an_existing_package(): void
    {
        mkdir($this->packagePath('Acme', 'Rocket'), 0777, true);

        $this->expectExceptionMessage('Package already exists');

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])->run();
    }

    public function test_it_asks_for_names_skeleton_and_metadata_in_interactive_mode(): void
    {
        config(['packager.author_name' => 'Default Author']);

        $this->command('packager:new', ['--i' => true])
            ->expectsQuestion('What will be the vendor name?', 'Acme')
            ->expectsQuestion('What will be the package name?', 'Rocket')
            ->expectsQuestion('What package skeleton would you like to use?', 'https://example.com/custom.zip')
            ->expectsQuestion('Who is the author?', 'Wile E. Coyote')
            ->expectsQuestion('What is the author\'s e-mail?', 'wile@acme.test')
            ->expectsQuestion('What is the author\'s website?', 'https://acme.test')
            ->expectsQuestion('How would you describe the package?', 'Rocket powered roller skates')
            ->expectsQuestion('Under which license will it be released?', 'MIT')
            ->expectsOutputToContain('Package created successfully!')
            ->assertExitCode(0);

        $this->assertSame(['https://example.com/custom.zip'], $this->downloadedUrls());
        $this->assertSame(
            'Wile E. Coyote <wile@acme.test> https://acme.test - Rocket powered roller skates (MIT)',
            file_get_contents($this->packagePath('Acme', 'Rocket').'/readme.md')
        );
    }

    public function test_it_leaves_other_directories_of_the_vendor_alone(): void
    {
        // A package that happens to be called "temp", and leftovers of an interrupted run.
        mkdir($this->packagePath('Acme', 'temp').'/src', 0777, true);
        mkdir($this->basePath.'/packages/Acme/.packager-temp-0123456789abcdef/stale', 0777, true);

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->assertExitCode(0);

        $this->assertFileExists($this->packagePath('Acme', 'Rocket').'/composer.json');
        $this->assertDirectoryExists($this->packagePath('Acme', 'temp').'/src');
        $this->assertSame(
            ['.packager-temp-0123456789abcdef', 'Rocket', 'temp'],
            array_values(array_diff(scandir($this->basePath.'/packages/Acme') ?: [], ['.', '..']))
        );
    }

    public function test_it_reports_a_failing_composer_install(): void
    {
        // Replace the runner from setUp(), which lets every command succeed.
        $this->commandRunner = CommandRunnerExpectation::create();
        app()->instance(CommandRunnerInterface::class, $this->commandRunner->getMock());
        $this->commandRunner->getMock()->allows('run')->with(Mockery::on(static fn (array $command): bool => $command[1] === 'config'))->andReturnTrue();
        $this->commandRunner->getMock()->allows('run')->with(Mockery::on(static fn (array $command): bool => $command[1] === 'require'))->andReturnFalse();

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->expectsOutputToContain('Composer could not install the package. Its files are kept in '.$this->packagePath('Acme', 'Rocket').', run `php artisan packager:enable Acme Rocket`')
            ->expectsOutputToContain('Run the composer command yourself')
            ->doesntExpectOutputToContain('Package created successfully!')
            ->assertExitCode(1);

        $this->assertDirectoryExists($this->packagePath('Acme', 'Rocket'));
    }

    public function test_it_does_not_start_the_progress_bar_for_invalid_names(): void
    {
        $this->command('packager:new', ['vendor' => 'Acme', 'name' => '1-invalid'])
            ->doesntExpectOutputToContain('0/6')
            ->assertExitCode(1);
    }

    public function test_it_ignores_the_pax_header_of_a_github_tarball(): void
    {
        $this->fakeDownloads($this->makeTarGz('rocket.tar.gz', [
            'pax_global_header' => 'comment=abc',
            'rocket-master/src/Rocket.php' => '<?php',
        ]));

        $this->command('packager:new', [
            'vendor' => 'acme',
            'name' => 'rocket',
            '--skeleton' => 'https://github.com/acme/rocket/archive/master.tar.gz',
        ])->assertExitCode(0);

        $this->assertFileExists($this->packagePath('acme', 'rocket').'/src/Rocket.php');
        $this->assertFileDoesNotExist($this->packagePath('acme', 'rocket').'/pax_global_header');
    }
}
