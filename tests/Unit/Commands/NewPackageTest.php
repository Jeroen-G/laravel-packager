<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\FileHandler;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\Tests\CommandTestCase;
use ZipArchive;

final class NewPackageTest extends CommandTestCase
{
    private string $skeleton;

    /** @var string[] */
    private array $downloads = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->skeleton = $this->basePath.'/skeleton.zip';
        $zip = new ZipArchive();
        $zip->open($this->skeleton, ZipArchive::CREATE);
        $zip->addFromString('packager-skeleton-master/composer.json', '{"name": ":lc:vendor/:lc:package", "author": ":author_name"}');
        $zip->addFromString('packager-skeleton-master/src/MyPackage.php', 'namespace :uc:vendor\\:uc:package; // :kc:package');
        $zip->addFromString('packager-skeleton-master/rewriteRules.php', '<?php');
        $zip->close();

        // Serve the skeleton from disk instead of downloading it.
        $test = $this;
        $this->app->instance(FileHandlerInterface::class, new class($test) extends FileHandler {
            public function __construct(private NewPackageTest $test)
            {
            }

            public function download(string $filePath, string $sourceFileUrl): FileHandler
            {
                $this->test->recordDownload($sourceFileUrl);
                copy($this->test->skeletonPath(), $filePath);

                return $this;
            }
        });

        $this->commandRunner->getMock()->allows('run')->andReturnTrue();
    }

    public function recordDownload(string $url): void
    {
        $this->downloads[] = $url;
    }

    public function skeletonPath(): string
    {
        return $this->skeleton;
    }

    public function test_it_creates_a_package_from_the_skeleton(): void
    {
        config(['packager.author_name' => 'Wile E. Coyote']);
        $path = $this->packagePath('Acme', 'RocketSkates');

        $this->artisan('packager:new', ['vendor' => 'Acme', 'name' => 'RocketSkates'])
            ->expectsOutputToContain('Package created successfully!')
            ->assertExitCode(0);

        $this->assertSame([config('packager.skeleton')], $this->downloads);
        $this->assertSame(
            '{"name": "acme/rocketskates", "author": "Wile E. Coyote"}',
            file_get_contents($path.'/composer.json')
        );
        $this->assertSame(
            'namespace Acme\\RocketSkates; // rocket-skates',
            file_get_contents($path.'/src/RocketSkates.php')
        );
        $this->assertFileDoesNotExist($path.'/rewriteRules.php');
        $this->assertDirectoryDoesNotExist($this->basePath.'/packages/Acme/temp');
    }

    public function test_it_accepts_vendor_and_name_separated_by_a_slash(): void
    {
        $this->artisan('packager:new', ['vendor' => 'Acme/RocketSkates'])
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->packagePath('Acme', 'RocketSkates'));
    }

    public function test_it_uses_a_custom_skeleton(): void
    {
        $this->artisan('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket', '--skeleton' => 'https://example.com/skeleton.zip'])
            ->assertExitCode(0);

        $this->assertSame(['https://example.com/skeleton.zip'], $this->downloads);
    }

    public function test_it_rejects_invalid_names(): void
    {
        $this->artisan('packager:new', ['vendor' => '1234-invalid', 'name' => 'Rocket'])
            ->expectsOutputToContain('The package vendor must conform to a valid PHP classname.')
            ->assertExitCode(1);

        $this->assertSame([], $this->downloads);
        $this->assertDirectoryDoesNotExist($this->basePath.'/packages');
    }

    public function test_it_refuses_to_overwrite_an_existing_package(): void
    {
        mkdir($this->packagePath('Acme', 'Rocket'), 0777, true);

        $this->expectExceptionMessage('Package already exists');

        $this->artisan('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])->run();
    }
}
