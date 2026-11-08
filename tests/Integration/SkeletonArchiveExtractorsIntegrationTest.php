<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Integration;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Artisan;
use JeroenG\Packager\Tests\IntegrationTestCase;

class SkeletonArchiveExtractorsIntegrationTest extends IntegrationTestCase
{
    public function test_new_package_is_created_with_tar_gz_skeleton(): void
    {
        Artisan::call('packager:new', ['vendor' => 'MyVendor', 'name' => 'MyPackage']);

        $this->seeInConsoleOutput('Package created successfully!');
    }

    protected function getEnvironmentSetUp($app): void
    {
        /** @var Repository $config */
        $config = $app['config'];

        // Change the extension in github archive url form .zip to .tar.gz
        $originalZipUrl = $config->get('packager.skeleton');
        $tarGzUrl = str_replace('.zip', '.tar.gz', $originalZipUrl);

        $config->set('packager.skeleton', $tarGzUrl);
    }
}
