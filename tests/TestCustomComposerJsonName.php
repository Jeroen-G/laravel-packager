<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Support\Facades\Artisan;

class TestCustomComposerJsonName extends TestCase
{
    public function test_new_package_is_created_with_custom_composer_json()
    {
        // Create custom composer.json
        $composerJsonPath = self::TEST_APP.'/composer.local.json';
        file_put_contents($composerJsonPath, '{}');

        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');

        // Check repositories entry in composer.local.json
        $composerJsonContent = json_decode(file_get_contents($composerJsonPath), true);
        $this->assertArrayHasKey('repositories', $composerJsonContent);
        $this->assertIsArray($composerJsonContent['repositories']['myvendor-mypackage']);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('packager.composer_json_filename', 'composer.local.json');
    }
}
