<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Filesystem\Filesystem;
use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\FileHandler;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\PackagerServiceProvider;
use JeroenG\Packager\Tests\Support\CommandRunnerExpectation;
use JeroenG\Packager\Tests\Support\RunsCommands;
use Orchestra\Testbench\TestCase as TestBench;
use Phar;
use PharData;
use Psr\Http\Message\RequestInterface;
use ZipArchive;

/**
 * Runs Packager commands in a lightweight application whose base path is a temporary
 * directory and whose external commands (git, composer) are mocked.
 */
abstract class CommandTestCase extends TestBench
{
    use RunsCommands;

    protected string $basePath;

    protected CommandRunnerExpectation $commandRunner;

    /**
     * The HTTP requests made through faked downloads, each with 'request' and 'options' keys.
     *
     * @var list<array{request: RequestInterface, options: array<string, mixed>}>
     */
    protected array $downloads = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'packager-'.uniqid('', true);
        mkdir($this->basePath);
        app()->setBasePath($this->basePath);

        $this->commandRunner = CommandRunnerExpectation::create();
        app()->instance(CommandRunnerInterface::class, $this->commandRunner->getMock());
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [PackagerServiceProvider::class];
    }

    protected function packagePath(string $vendor, string $package): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.$vendor.DIRECTORY_SEPARATOR.$package;
    }

    /**
     * Answer the next downloads with the contents of the given archives, in order.
     */
    protected function fakeDownloads(string ...$archives): void
    {
        $responses = [];
        foreach ($archives as $archive) {
            $responses[] = new Response(200, [], (new Filesystem())->get($archive));
        }

        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(function (callable $next): callable {
            return function (RequestInterface $request, array $options) use ($next) {
                $this->downloads[] = ['request' => $request, 'options' => $options];

                return $next($request, $options);
            };
        });

        app()->instance(FileHandlerInterface::class, new FileHandler(new Client(['handler' => $handler])));
    }

    /**
     * Answer the next download with an HTTP error.
     */
    protected function fakeFailedDownload(int $status): void
    {
        $handler = HandlerStack::create(new MockHandler([new Response($status)]));

        app()->instance(FileHandlerInterface::class, new FileHandler(new Client(['handler' => $handler])));
    }

    /**
     * Create a zip archive in the base path with the given files.
     *
     * @param  array<string, string>  $files  Contents keyed by their path inside the archive
     */
    protected function makeZip(string $name, array $files): string
    {
        $path = $this->basePath.DIRECTORY_SEPARATOR.$name;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        foreach ($files as $file => $contents) {
            $zip->addFromString($file, $contents);
        }
        $zip->close();

        return $path;
    }

    /**
     * Create a tar.gz archive in the base path with the given files.
     *
     * @param  array<string, string>  $files  Contents keyed by their path inside the archive
     */
    protected function makeTarGz(string $name, array $files): string
    {
        $tar = $this->basePath.DIRECTORY_SEPARATOR.basename($name, '.gz');
        $archive = new PharData($tar);
        foreach ($files as $file => $contents) {
            $archive->addFromString($file, $contents);
        }
        $archive->compress(Phar::GZ);
        unset($archive);
        unlink($tar);

        return $tar.'.gz';
    }

    protected function assertNoTemporaryDirectories(string $vendor): void
    {
        $this->assertSame([], glob($this->basePath.'/packages/'.$vendor.'/.packager-temp-*', GLOB_ONLYDIR) ?: []);
    }

    /**
     * @return string[]
     */
    protected function downloadedUrls(): array
    {
        return array_map(static fn (array $download): string => (string) $download['request']->getUri(), $this->downloads);
    }

    /**
     * @param  array<string, mixed>  $contents
     */
    protected function writeComposerJson(array $contents): void
    {
        file_put_contents($this->basePath.'/composer.json', json_encode($contents, JSON_PRETTY_PRINT));
    }
}
