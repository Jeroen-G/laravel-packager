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

    public function test_it_ignores_leftovers_of_an_interrupted_download(): void
    {
        mkdir($this->basePath.'/packages/Acme/temp/stale-skeleton', 0777, true);

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->assertExitCode(0);

        $this->assertFileExists($this->packagePath('Acme', 'Rocket').'/composer.json');
        $this->assertDirectoryDoesNotExist($this->basePath.'/packages/Acme/temp');
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
