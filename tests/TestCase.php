<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Contracts\Console\Kernel;
use JeroenG\Packager\ComposerHandler;
use JeroenG\Packager\FileHandler;
use Orchestra\Testbench\TestCase as TestBench;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

abstract class TestCase extends TestBench
{
    use RefreshTestbench;

    /**
     * Tell Testbench to use this package.
     *
     * @param $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['JeroenG\Packager\PackagerServiceProvider'];
    }

    /**
     * @param $expectedText
     * @throws ExpectationFailedException
     */
    protected function seeInConsoleOutput($expectedText): void
    {
        if (!is_array($expectedText)){
            $expectedText = [$expectedText];
        }
        $consoleOutput = $this->app[Kernel::class]->output();
        foreach ($expectedText as $string) {
            $this->assertStringContainsString($string, $consoleOutput,
                "Did not see `{$string}` in console output: `$consoleOutput`");
        }
    }

    /**
     * @param $unExpectedText
     * @throws ExpectationFailedException
     */
    protected function doNotSeeInConsoleOutput($unExpectedText): void
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertStringNotContainsString($unExpectedText, $consoleOutput,
            "Did not expect to see `{$unExpectedText}` in console output: `$consoleOutput`");
    }

    /**
     * @param  string  $package
     * @throws ExpectationFailedException
     */
    protected function assertComposerPackageInstalled(string $package): void
    {
        $composer = $this->getComposerJsonArray(base_path('composer.json'));
        $this->assertArrayHasKey(strtolower($package), $composer['require']);
        $path =  $this->findInstalledPath($package);
        $this->assertDirectoryIsReadable($path);
        [$vendor, $package] = explode('/', $package);
        $fullyQualifiedServiceProvider = sprintf("%s\\%s\\%sServiceProvider", $vendor, $package, $package);
        $mentions = Finder::create()
            ->in(base_path('vendor/composer'))
            ->contains(addslashes($fullyQualifiedServiceProvider))
            ->count();
        // Should be mentioned in 3 different files
        $this->assertGreaterThanOrEqual(3, $mentions);
    }

    /**
     * @param  string  $package
     * @throws ExpectationFailedException
     */
    protected function assertComposerPackageNotInstalled(string $package): void
    {
        $composer = $this->getComposerJsonArray(base_path('composer.json'));
        $this->assertArrayNotHasKey(strtolower($package), $composer['require']);
        $this->expectException(DirectoryNotFoundException::class);
        $this->findInstalledPath($package);
        [$vendor, $package] = explode('/', $package);
        $fullyQualifiedServiceProvider = sprintf("%s\\%s\\%sServiceProvider", $vendor, $package, $package);
        $mentions = Finder::create()
            ->in(base_path('vendor/composer'))
            ->contains(addslashes($fullyQualifiedServiceProvider))
            ->count();
        // Should not be mentioned anywhere
        $this->assertEquals(0, $mentions);
    }

    protected function storePackageAsFake()
    {
        $fakePackagePath = self::getLocalTestbenchPath().'/fake-package';
        $fakeComposerMetadataPath = self::getLocalTestbenchPath().'/fake-composer';
        if (!$this->pathExists($fakePackagePath) || !$this->pathExists($fakeComposerMetadataPath)){
            $this->copyDir($this->findInstalledPath('MyVendor/MyPackage'), $fakePackagePath.'/MyVendor/MyPackage');
            $this->copyDir(base_path('vendor/composer'), $fakeComposerMetadataPath);
        }
    }

    protected function installFakePackageFromPath()
    {
        $fakePath = self::getLocalTestbenchPath().'/fake-package';
        $fakeComposerMetadataPath = self::getLocalTestbenchPath().'/fake-composer';
        $destination = base_path('packages');
        $this->copyDir($fakePath, $destination);
        $this->copyDir($fakeComposerMetadataPath, base_path('vendor/composer'));
        $packagePath = base_path('packages/MyVendor/MyPackage');
        $this->makeDir(base_path('vendor/myvendor'));
        $this->createSymlink($packagePath, base_path('vendor/myvendor/mypackage'));
        $this->modifyComposerJson(function (array $composer) use ($packagePath){
            $composer['repositories']['MyVendor/MyPackage'] = [
                'type' => 'path',
                'url' => $packagePath
            ];
            $composer['require']['myvendor/mypackage'] = 'v1.0';
            return $composer;
        }, base_path());
        $this->assertComposerPackageInstalled('MyVendor/MyPackage');
    }
}
