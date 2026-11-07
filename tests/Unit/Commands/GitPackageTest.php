<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\Commands\GitPackage;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\Tests\UnitTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

final class GitPackageTest extends UnitTestCase
{
    /**
     * @return array<string, list<string>>
     */
    public static function origins(): array
    {
        return [
            'https' => ['https://github.com/jeroen-g/widget', 'jeroen-g', 'widget'],
            'https with .git' => ['https://github.com/jeroen-g/widget.git', 'jeroen-g', 'widget'],
            'ssh' => ['git@github.com:jeroen-g/widget.git', 'jeroen-g', 'widget'],
            'ssh without .git' => ['git@github.com:jeroen-g/widget', 'jeroen-g', 'widget'],
            'ssh name ending in git characters' => ['git@github.com:jeroen-g/digit.git', 'jeroen-g', 'digit'],
            'http' => ['http://git.example.com/jeroen-g/widget', 'jeroen-g', 'widget'],
            'ssh url with port' => ['ssh://git@git.example.com:2222/jeroen-g/widget.git', 'jeroen-g', 'widget'],
            'gitlab subgroup' => ['https://gitlab.com/jeroen-g/tools/widget.git', 'tools', 'widget'],
            'local file url' => ['file:///home/me/repositories/jeroen-g/widget.git', 'jeroen-g', 'widget'],
            'local path' => ['/home/me/repositories/jeroen-g/widget', 'jeroen-g', 'widget'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function originsWithoutVendor(): array
    {
        return [
            'only a host' => ['https://github.com'],
            'no vendor' => ['https://github.com/widget'],
            'ssh without vendor' => ['git@github.com:widget.git'],
            'file url without vendor' => ['file:///widget.git'],
            'only .git' => ['https://github.com/jeroen-g/.git'],
            'parent directory as vendor' => ['file:///repositories/../widget.git'],
            'parent directory as package' => ['file:///repositories/jeroen-g/..'],
        ];
    }

    #[DataProvider('originsWithoutVendor')]
    public function test_it_cannot_derive_vendor_and_package_from_every_origin(string $origin): void
    {
        $command = new GitPackage(
            new Conveyor(Mockery::mock(CommandRunnerInterface::class), Mockery::mock(FileHandlerInterface::class)),
            Mockery::mock(FileHandlerInterface::class),
            Mockery::mock(CommandRunnerInterface::class),
        );

        $this->assertFalse((new ReflectionMethod($command, 'setGitVendorAndPackage'))->invoke($command, $origin));
    }

    #[DataProvider('origins')]
    public function test_it_derives_vendor_and_package_from_the_origin(string $origin, string $vendor, string $package): void
    {
        $conveyor = new Conveyor(
            Mockery::mock(CommandRunnerInterface::class),
            Mockery::mock(FileHandlerInterface::class),
        );
        $command = new GitPackage(
            $conveyor,
            Mockery::mock(FileHandlerInterface::class),
            Mockery::mock(CommandRunnerInterface::class),
        );

        $this->assertTrue((new ReflectionMethod($command, 'setGitVendorAndPackage'))->invoke($command, $origin));

        $this->assertSame($vendor, $conveyor->vendor());
        $this->assertSame($package, $conveyor->package());
    }
}
