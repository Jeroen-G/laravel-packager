<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Support;

use JeroenG\Packager\FileHandlerInterface;
use Mockery;
use Mockery\MockInterface;

/**
 * Binds a mocked FileHandlerInterface in the container, so commands never touch the real filesystem.
 * Packages live in /app/packages; every other filesystem call must be allowed or expected by the test.
 */
trait MocksFileHandler
{
    protected FileHandlerInterface&MockInterface $files;

    protected function mockFileHandler(): void
    {
        $this->files = Mockery::mock(FileHandlerInterface::class);
        $this->files->allows('packagesPath')->andReturn('/app/packages')->byDefault();
        $this->files->allows('vendorPath')->andReturnUsing(static fn (string $vendor): string => '/app/packages/'.$vendor)->byDefault();
        $this->files->allows('packagePath')->andReturnUsing(static fn (string $vendor, string $package): string => '/app/packages/'.$vendor.'/'.$package)->byDefault();
        // Packages without a composer.json are installed by their lowercase vendor/package.
        $this->files->allows('read')->andReturnNull()->byDefault();

        app()->instance(FileHandlerInterface::class, $this->files);
    }
}
