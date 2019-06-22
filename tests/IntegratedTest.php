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
    }

    public function test_get_existing_package()
    {
        Artisan::call('packager:get',
            ['url' => 'https://github.com/Jeroen-G/packager-skeleton', 'vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package downloaded successfully!');
    }

    public function test_list_packages()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        Artisan::call('packager:list');

        $this->seeInConsoleOutput(['MyVendor', 'Not initialized']);
    }

    public function test_removing_package()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        $this->seeInConsoleOutput('MyVendor');

        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);
        $this->seeInConsoleOutput('Package removed successfully!');
    }

    public function test_adding_git_package()
    {
        Artisan::call('packager:git',
            ['url' => 'jeroen-g/testassist']);
        $this->seeInConsoleOutput('Package cloned successfully!');
        Artisan::call('packager:list');
        $this->seeInConsoleOutput('Up to date');
        $package_path = base_path('packages/jeroen-g/testassist');
        (new Process(['touch', 'new-file.txt'], $package_path))->run();
        (new Process(['git', 'add', '.'], $package_path))->run();
        (new Process(['git', 'commit', '-m', 'New commit'], $package_path))->run();
        Artisan::call('packager:list');
        $this->seeInConsoleOutput('Ahead 1');
    }
}
