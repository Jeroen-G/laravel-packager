<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\Commands\GitPackage;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\Tests\UnitTestCase;
use JeroenG\Packager\Wrapping;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

final class GitPackageTest extends UnitTestCase
{
    public static function origins(): array
    {
        return [
            'https' => ['https://github.com/jeroen-g/widget', 'jeroen-g', 'widget'],
            'https with .git' => ['https://github.com/jeroen-g/widget.git', 'jeroen-g', 'widget'],
            'ssh' => ['git@github.com:jeroen-g/widget.git', 'jeroen-g', 'widget'],
            'ssh without .git' => ['git@github.com:jeroen-g/widget', 'jeroen-g', 'widget'],
            'ssh name ending in git characters' => ['git@github.com:jeroen-g/digit.git', 'jeroen-g', 'digit'],
        ];
    }

    #[DataProvider('origins')]
    public function test_it_derives_vendor_and_package_from_the_origin(string $origin, string $vendor, string $package): void
    {
        $conveyor = new Conveyor(
            Mockery::mock(CommandRunnerInterface::class),
            Mockery::mock(FileHandlerInterface::class),
        );
        $command = new GitPackage($conveyor, new Wrapping(), Mockery::mock(FileHandlerInterface::class));

        (new ReflectionMethod($command, 'setGitVendorAndPackage'))->invoke($command, $origin);

        $this->assertSame($vendor, $conveyor->vendor());
        $this->assertSame($package, $conveyor->package());
    }
}
