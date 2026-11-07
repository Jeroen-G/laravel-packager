<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class GetPackageTest extends CommandTestCase
{
    public function test_it_downloads_and_installs_a_package_from_github(): void
    {
        // GitHub names the directory in the archive after the repository as it is cased on GitHub.
        $this->fakeDownloads($this->makeZip('rocket.zip', ['Rocket-HEAD/src/Rocket.php' => '<?php']));
        $path = $this->packagePath('acme', 'rocket');

        $this->commandRunner->expectRun([
            'composer',
            'config',
            'repositories.acme/rocket',
            json_encode(['type' => 'path', 'url' => $path, 'options' => ['symlink' => true]], JSON_THROW_ON_ERROR),
            '--file',
            'composer.json',
        ]);
        $this->commandRunner->expectRun(['composer', 'require', 'acme/rocket:@dev']);

        $this->command('packager:get', ['url' => 'https://github.com/Acme/Rocket/'])
            ->expectsOutputToContain('Creating package acme\rocket...')
            ->expectsOutputToContain('Package downloaded successfully!')
            ->assertExitCode(0);

        $this->assertSame(['https://github.com/acme/rocket/archive/HEAD.zip'], $this->downloadedUrls());
        $this->assertFileExists($path.'/src/Rocket.php');
        $this->assertNoTemporaryDirectories('acme');
    }

    public function test_it_downloads_a_branch_with_the_given_vendor_and_name(): void
    {
        $this->fakeDownloads($this->makeZip('rocket.zip', ['rocket-feature-boost/src/Rocket.php' => '<?php']));
        $this->commandRunner->getMock()->allows('run')->andReturnTrue();

        $this->command('packager:get', [
            'url' => 'https://github.com/acme/rocket',
            'vendor' => 'MyVendor',
            'name' => 'MyPackage',
            '--branch' => 'feature/boost',
        ])->assertExitCode(0);

        $this->assertSame(['https://github.com/acme/rocket/archive/feature/boost.zip'], $this->downloadedUrls());
        $this->assertFileExists($this->packagePath('MyVendor', 'MyPackage').'/src/Rocket.php');
    }

    public function test_it_refuses_an_archive_without_a_single_directory(): void
    {
        $this->fakeDownloads($this->makeZip('rocket.zip', ['a/one.php' => '<?php', 'b/two.php' => '<?php']));

        $this->expectExceptionMessage('The archive should contain exactly one directory');

        try {
            $this->command('packager:get', ['url' => 'https://github.com/acme/rocket'])->run();
        } finally {
            $this->assertNoTemporaryDirectories('acme');
            $this->assertDirectoryDoesNotExist($this->packagePath('acme', 'rocket'));
        }
    }

    public function test_it_refuses_to_overwrite_an_existing_package(): void
    {
        mkdir($this->packagePath('acme', 'rocket'), 0777, true);

        $this->expectExceptionMessage('Package already exists');

        $this->command('packager:get', ['url' => 'https://github.com/acme/rocket'])->run();
    }

    public function test_it_strips_the_git_suffix_from_the_url(): void
    {
        $this->fakeDownloads($this->makeZip('rocket.zip', ['rocket-HEAD/src/Rocket.php' => '<?php']));
        $this->commandRunner->getMock()->allows('run')->andReturnTrue();

        $this->command('packager:get', ['url' => 'https://github.com/acme/rocket.git'])
            ->expectsOutputToContain('Creating package acme\rocket...')
            ->assertExitCode(0);

        $this->assertSame(['https://github.com/acme/rocket/archive/HEAD.zip'], $this->downloadedUrls());
        $this->assertFileExists($this->packagePath('acme', 'rocket').'/src/Rocket.php');
    }

    public function test_it_reports_a_failing_composer_install(): void
    {
        $this->fakeDownloads($this->makeZip('rocket.zip', ['rocket-HEAD/src/Rocket.php' => '<?php']));
        $this->commandRunner->getMock()->allows('run')->andReturnFalse();

        $this->command('packager:get', ['url' => 'https://github.com/acme/rocket'])
            ->expectsOutputToContain('Composer could not install the package.')
            ->doesntExpectOutputToContain('Package downloaded successfully!')
            ->assertExitCode(1);

        $this->assertFileExists($this->packagePath('acme', 'rocket').'/src/Rocket.php');
    }

    public function test_it_installs_the_package_by_the_name_in_its_composer_json(): void
    {
        $this->fakeDownloads($this->makeZip('rocket.zip', [
            'rocket-HEAD/composer.json' => '{"name": "acme/rocket-skates"}',
        ]));
        $this->commandRunner->getMock()->allows('run')->with(\Mockery::on(static fn (array $command): bool => $command[1] === 'config'))->andReturnTrue();
        $this->commandRunner->expectRun(['composer', 'require', 'acme/rocket-skates:@dev']);

        $this->command('packager:get', ['url' => 'https://github.com/acme/rocket', 'vendor' => 'MyVendor', 'name' => 'MyPackage'])
            ->assertExitCode(0);
    }

    public function test_it_falls_back_to_the_given_names_when_composer_json_has_no_valid_name(): void
    {
        $this->fakeDownloads($this->makeZip('rocket.zip', [
            'rocket-HEAD/composer.json' => '{"name": ":lc:vendor/:lc:package"}',
        ]));
        $this->commandRunner->getMock()->allows('run')->with(\Mockery::on(static fn (array $command): bool => $command[1] === 'config'))->andReturnTrue();
        $this->commandRunner->expectRun(['composer', 'require', 'myvendor/mypackage:@dev']);

        $this->command('packager:get', ['url' => 'https://github.com/acme/rocket', 'vendor' => 'MyVendor', 'name' => 'MyPackage'])
            ->assertExitCode(0);
    }

    public function test_it_reports_a_failing_download(): void
    {
        $this->fakeFailedDownload(404);

        $this->command('packager:get', ['url' => 'https://github.com/acme/rocket', '--branch' => 'missing'])
            ->expectsOutputToContain('Unable to download https://github.com/acme/rocket/archive/missing.zip')
            ->doesntExpectOutputToContain('Package downloaded successfully!')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->packagePath('acme', 'rocket'));
        $this->assertNoTemporaryDirectories('acme');
    }
}
