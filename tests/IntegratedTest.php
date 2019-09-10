<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class IntegratedTest extends TestCase
{
    public function test_new_package_is_created()
    {
        // Also test downloading package-skeleton (the other test will use a cached copy)
        config()->set('packager.cache_skeleton', false);
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        $this->seeInConsoleOutput('Package created successfully!');
        $this->assertComposerPackageInstalled('MyVendor/MyPackage');
        // Save the generated package for use in later tests
        $this->storePackageAsFake();
    }

    public function test_get_existing_package()
    {
        $this->assertComposerPackageNotInstalled('MyVendor/MyPackage');
        Artisan::call('packager:get', [
            'url' => 'https://github.com/Jeroen-G/packager-skeleton',
            'vendor' => 'MyVendor',
            'name' => 'MyPackage'
        ]);
        $this->seeInConsoleOutput('Package downloaded successfully!');
        $this->assertComposerPackageInstalled('MyVendor/MyPackage');
    }

    /**
     * @depends test_new_package_is_created
     */
    public function test_list_packages()
    {
        $this->installFakePackageFromPath();
        Artisan::call('packager:list');
        $this->seeInConsoleOutput(['MyVendor', 'MyPackage', 'Not initialized']);
    }

    /**
     * @depends test_new_package_is_created
     */
    public function test_removing_package()
    {
        $this->installFakePackageFromPath();
        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);
        $this->seeInConsoleOutput('Package removed successfully!');
        $this->assertComposerPackageNotInstalled('MyVendor/MyPackage');
    }

    public function test_adding_git_package()
    {
        Artisan::call('packager:git', [
            'url' => 'https://github.com/Jeroen-G/packager-skeleton',
            'vendor' => 'MyVendor',
            'name' => 'MyPackage'
        ]);
        $this->seeInConsoleOutput('Package cloned successfully!');
        $this->assertComposerPackageInstalled('MyVendor/MyPackage');
        Artisan::call('packager:list');
        $this->seeInConsoleOutput(['MyVendor', 'MyPackage', 'Up to date']);
        $package_path = base_path('packages/MyVendor/MyPackage');
        (new Process(['touch', 'new-file.txt'], $package_path))->run();
        (new Process(['git', 'add', '.'], $package_path))->run();
        (new Process(['git', 'commit', '-m', 'New commit'], $package_path))->run();
        Artisan::call('packager:list');
        $this->seeInConsoleOutput(['MyVendor', 'MyPackage', 'Ahead 1']);
        (new Process(['git', 'reset', '--hard', 'HEAD~2'], $package_path))->run();
        Artisan::call('packager:list');
        $this->seeInConsoleOutput(['MyVendor', 'MyPackage', 'Behind 1']);
    }

    public function test_warning_shown_when_security_checker_not_installed()
    {
        $this->installFakePackageFromPath();
        Artisan::call('packager:check', ['vendor' => 'MyPackage', 'name' => 'MyPackage']);
        $this->seeInConsoleOutput('SensioLabs Security Checker is not installed.');
        // It's possible to install security-checker in the testbench, but it's currently not possible to use it.
    }
}
