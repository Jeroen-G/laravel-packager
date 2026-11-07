<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use JeroenG\Packager\FileHandler;
use JeroenG\Packager\Tests\UnitTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * Only ever pass paths inside temporary directories to removeDir(): mutation testing runs these
 * tests against mutants that disable its guard, which would then delete whatever path is given.
 */
final class FileHandlerTest extends UnitTestCase
{
    private string $workDir;

    private FileHandler $fileHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'packager-'.uniqid('', true);
        mkdir($this->workDir);

        $this->fileHandler = Mockery::mock(FileHandler::class)->makePartial();
        $this->fileHandler->allows('packagesPath')->andReturn($this->workDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->workDir);

        parent::tearDown();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function archives(): array
    {
        return [
            'zip' => ['skeleton.zip', 'zip'],
            'tar' => ['skeleton.tar', 'tar'],
            'tar.gz' => ['skeleton.tar.gz', 'tar.gz'],
            'tar.xz' => ['skeleton.tar.xz', 'tar.xz'],
            'gz without tar' => ['skeleton.gz', 'gz'],
            'url' => ['https://github.com/Jeroen-G/packager-skeleton/archive/master.tar.gz', 'tar.gz'],
        ];
    }

    #[DataProvider('archives')]
    public function test_it_detects_the_archive_extension(string $path, string $expected): void
    {
        $this->assertSame($expected, (new FileHandler())->getArchiveExtension($path));
    }

    public function test_it_builds_package_paths(): void
    {
        $ds = DIRECTORY_SEPARATOR;

        $this->assertSame($this->workDir.$ds.'MyVendor', $this->fileHandler->vendorPath('MyVendor'));
        $this->assertMatchesRegularExpression(
            '{^'.preg_quote($this->workDir.$ds.'MyVendor'.$ds.'.packager-temp-', '{').'[0-9a-f]{16}$}',
            $this->fileHandler->tempPath('MyVendor')
        );
        $this->assertNotSame($this->fileHandler->tempPath('MyVendor'), $this->fileHandler->tempPath('MyVendor'));
        $this->assertSame($this->workDir.$ds.'MyVendor'.$ds.'MyPackage', $this->fileHandler->packagePath('MyVendor', 'MyPackage'));
    }

    public function test_it_makes_unique_filenames_in_the_working_directory(): void
    {
        $handler = new FileHandler();

        $zip = $handler->makeFilename();
        $tar = $handler->makeFilename('tar.gz');

        $this->assertStringStartsWith(getcwd().'/package', $zip);
        $this->assertStringEndsWith('.zip', $zip);
        $this->assertStringEndsWith('.tar.gz', $tar);
        $this->assertNotSame($zip, $handler->makeFilename());
    }

    public function test_it_throws_when_the_package_already_exists(): void
    {
        mkdir($this->workDir.'/MyVendor/MyPackage', 0777, true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Package already exists');

        $this->fileHandler->checkIfPackageExists('MyVendor', 'MyPackage');
    }

    public function test_it_does_not_throw_when_the_package_does_not_exist(): void
    {
        $this->fileHandler->checkIfPackageExists('MyVendor', 'MyPackage');

        $this->assertDirectoryDoesNotExist($this->workDir.'/MyVendor/MyPackage');
    }

    public function test_it_only_makes_a_directory_when_it_does_not_exist(): void
    {
        $path = $this->workDir.'/a/b';

        $this->assertTrue($this->fileHandler->makeDir($path));
        $this->assertDirectoryExists($path);
        $this->assertFalse($this->fileHandler->makeDir($path));
    }

    public function test_it_refuses_to_remove_the_packages_directory_itself(): void
    {
        $this->assertFalse($this->fileHandler->removeDir($this->workDir));
        $this->assertFalse($this->fileHandler->removeDir($this->workDir.'/'));
        $this->assertDirectoryExists($this->workDir);
    }

    public function test_it_refuses_to_remove_directories_outside_the_packages_directory(): void
    {
        mkdir($this->workDir.'/MyVendor', 0777, true);
        $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'packager-outside-'.uniqid('', true);
        mkdir($outside);

        try {
            $this->assertFalse($this->fileHandler->removeDir($this->workDir.'/MyVendor/..'));
            $this->assertFalse($this->fileHandler->removeDir($outside));
            $this->assertDirectoryExists($this->workDir.'/MyVendor');
            $this->assertDirectoryExists($outside);
        } finally {
            (new Filesystem())->deleteDirectory($outside);
        }
    }

    public function test_it_removes_symlinks_without_following_them(): void
    {
        $target = sys_get_temp_dir().DIRECTORY_SEPARATOR.'packager-target-'.uniqid('', true);
        mkdir($target);
        file_put_contents($target.'/keep.txt', 'x');
        mkdir($this->workDir.'/MyVendor/MyPackage', 0777, true);
        symlink($target, $this->workDir.'/MyVendor/MyPackage/linked');

        try {
            $this->assertTrue($this->fileHandler->removeDir($this->workDir.'/MyVendor/MyPackage'));
            $this->assertFileExists($target.'/keep.txt');
        } finally {
            (new Filesystem())->deleteDirectory($target);
        }
    }

    public function test_it_does_not_fail_on_a_missing_directory(): void
    {
        $this->assertFalse($this->fileHandler->removeDir($this->workDir.'/missing'));
    }

    public function test_it_removes_a_directory_recursively(): void
    {
        $path = $this->workDir.'/remove-me';
        mkdir($path.'/nested', 0777, true);
        file_put_contents($path.'/file.txt', 'x');
        file_put_contents($path.'/nested/file.txt', 'x');

        $this->assertTrue($this->fileHandler->removeDir($path));
        $this->assertDirectoryDoesNotExist($path);
    }

    public function test_it_cleans_up_an_archive(): void
    {
        $archive = $this->workDir.'/archive.zip';
        file_put_contents($archive, 'x');

        $this->assertSame($this->fileHandler, $this->fileHandler->cleanUp($archive));
        $this->assertFileDoesNotExist($archive);
    }

    public function test_it_renames_skeleton_files(): void
    {
        $package = $this->workDir.'/my-vendor/my-package';
        mkdir($package.'/src/Facades', 0777, true);
        file_put_contents($package.'/src/MyPackage.php', '');
        file_put_contents($package.'/src/Facades/MyPackage.php', '');
        file_put_contents($package.'/readme.md', '');

        $this->fileHandler->renameFiles('MyVendor', 'AwesomePackage', 'my-vendor', 'my-package');

        $this->assertFileExists($package.'/src/AwesomePackage.php');
        $this->assertFileExists($package.'/src/Facades/AwesomePackage.php');
        $this->assertFileDoesNotExist($package.'/src/MyPackage.php');
        $this->assertFileExists($package.'/readme.md');
    }

    public function test_it_lowercases_vendor_and_package_in_file_names(): void
    {
        $package = $this->workDir.'/My-Vendor/My-Package';
        mkdir($package.'/config', 0777, true);
        file_put_contents($package.'/config/mypackage.php', '');

        $this->fileHandler->renameFiles('MyVendor', 'MyPackage', 'My-Vendor', 'My-Package');

        $this->assertFileExists($package.'/config/my-package.php');
    }

    public function test_it_removes_existing_rule_files_only(): void
    {
        $package = $this->workDir.'/MyVendor/MyPackage';
        mkdir($package, 0777, true);
        file_put_contents($package.'/rules.php', '');
        file_put_contents($package.'/composer.json', '');

        $this->fileHandler->cleanUpRules('MyVendor', 'MyPackage');

        $this->assertFileDoesNotExist($package.'/rules.php');
        $this->assertFileDoesNotExist($package.'/rewriteRules.php');
        $this->assertFileExists($package.'/composer.json');
    }
}
