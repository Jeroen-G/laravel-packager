<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class NewPackageTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDownloads($this->makeZip('skeleton.zip', [
            'packager-skeleton-master/composer.json' => '{"name": ":lc:vendor/:lc:package", "author": ":author_name"}',
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
        $this->assertDirectoryDoesNotExist($this->basePath.'/packages/Acme/temp');
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
}
