<?php

namespace JeroenG\Packager\Tests;

use Artisan;

class IntegratedTest extends TestCase
{
    public function test_new_package_is_created()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');
    }

    public function test_get_existing_package()
    {
        Artisan::call('packager:get', ['url' => 'https://github.com/Jeroen-G/packager-skeleton', 'vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package downloaded successfully!');
    }

    public function test_list_packages()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        Artisan::call('packager:list');

        $this->seeInConsoleOutput('MyVendor');
    }

    public function test_removing_package()
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);
        $this->seeInConsoleOutput('MyVendor');

        Artisan::call('packager:remove', ['vendor' => 'MyVendor', 'name' => 'MyPackage', '--no-interaction' => true]);
        $this->seeInConsoleOutput('Package removed successfully!');
    }
}
