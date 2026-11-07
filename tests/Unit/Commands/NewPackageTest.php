<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\Tests\PackagerTestCase;
use JeroenG\Packager\Tests\Support\CommandRunnerExpectation;
use JeroenG\Packager\Tests\Support\MocksFileHandler;
use JeroenG\Packager\Wrapping;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

final class NewPackageTest extends PackagerTestCase
{
    use MocksFileHandler;

    private const TEMP = '/app/packages/Acme/.packager-temp-0123456789abcdef';

    private const ARCHIVE = '/app/package0123.zip';

    /** Replaces placeholders like the real Wrapping, but never fills files. */
    private Wrapping&MockInterface $wrapping;

    /** @var list<string> */
    private array $downloads = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFileHandler();
        $this->files->allows('checkIfPackageExists')->byDefault();
        $this->files->allows('makeDir')->andReturnTrue()->byDefault();
        $this->files->allows('tempPath')->andReturn(self::TEMP)->byDefault();
        $this->files->allows('getArchiveExtension')->andReturn('zip')->byDefault();
        $this->files->allows('makeFilename')->andReturn(self::ARCHIVE)->byDefault();
        $this->files->allows('download')->andReturnUsing(function (string $archive, string $url): MockInterface {
            $this->downloads[] = $url;

            return $this->files;
        })->byDefault();
        $this->files->allows('extract')->andReturnSelf()->byDefault();
        $this->files->allows('directories')->with(self::TEMP)->andReturn(['packager-skeleton-master'])->byDefault();
        $this->files->allows('move')->andReturnTrue()->byDefault();
        $this->files->allows('exists')->andReturnTrue()->byDefault();
        $this->files->allows('renameFiles')->byDefault();
        $this->files->allows('cleanUpRules')->byDefault();
        $this->files->allows('cleanUp')->andReturnSelf()->byDefault();
        $this->files->allows('removeDir')->with(self::TEMP)->andReturnTrue()->byDefault();

        $this->wrapping = Mockery::mock(Wrapping::class)->makePartial();
        $this->wrapping->allows('fill')->byDefault();
        app()->instance(Wrapping::class, $this->wrapping);

        $this->commandRunner->getMock()->allows('run')->andReturnTrue()->byDefault();
    }

    public function test_it_creates_a_package_from_the_skeleton(): void
    {
        config(['packager.author_name' => 'Wile E. Coyote', 'packager.license' => 'MIT']);
        $path = '/app/packages/Acme/RocketSkates';

        $this->files->expects('checkIfPackageExists')->with('Acme', 'RocketSkates');
        $this->files->expects('makeDir')->with('/app/packages')->andReturnTrue();
        $this->files->expects('makeDir')->with('/app/packages/Acme')->andReturnTrue();
        $this->files->expects('getArchiveExtension')->with(config('packager.skeleton'))->andReturn('zip');
        $this->files->expects('makeFilename')->with('zip')->andReturn(self::ARCHIVE);
        $this->files->expects('extract')->with(self::ARCHIVE, self::TEMP)->andReturnSelf();
        $this->files->expects('move')->with(self::TEMP.'/packager-skeleton-master', $path)->andReturnTrue();
        $this->files->expects('exists')->with($path.'/rewriteRules.php')->andReturnTrue();
        $this->files->expects('renameFiles')->with('Acme', 'RocketSkates', 'Acme', 'RocketSkates');
        $this->files->expects('cleanUpRules')->with('Acme', 'RocketSkates');
        $this->files->expects('cleanUp')->with(self::ARCHIVE)->andReturnSelf();
        // Only its own temporary directory is removed, never other directories of the vendor.
        $this->files->expects('removeDir')->with(self::TEMP)->andReturnTrue();
        $this->wrapping->expects('fill')->with($path);
        $this->commandRunner = CommandRunnerExpectation::create();
        app()->instance(CommandRunnerInterface::class, $this->commandRunner->getMock());
        $this->commandRunner->expectRun([
            'composer',
            'config',
            'repositories.acme/rocketskates',
            json_encode(['type' => 'path', 'url' => $path, 'options' => ['symlink' => true]], JSON_THROW_ON_ERROR),
            '--file',
            'composer.json',
        ]);
        $this->commandRunner->expectRun(['composer', 'require', 'acme/rocketskates:@dev']);

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'RocketSkates'])
            ->expectsOutputToContain('Package created successfully!')
            ->assertExitCode(0);

        $this->assertSame([config('packager.skeleton')], $this->downloads);
        $this->assertReplacements([
            ':uc:vendor' => 'Acme',
            ':uc:package' => 'RocketSkates',
            ':lc:vendor' => 'acme',
            ':lc:package' => 'rocketskates',
            ':kc:vendor' => 'acme',
            ':kc:package' => 'rocket-skates',
            ':author_name' => 'Wile E. Coyote',
            ':license' => 'MIT',
        ]);
    }

    public function test_it_skips_cleaning_up_rule_files_when_the_skeleton_has_none(): void
    {
        $this->files->expects('exists')->with('/app/packages/Acme/Rocket/rewriteRules.php')->andReturnFalse();
        $this->files->shouldNotReceive('cleanUpRules');

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->expectsOutputToContain('Package created successfully!')
            ->assertExitCode(0);
    }

    public function test_it_accepts_vendor_and_name_separated_by_a_slash(): void
    {
        $this->files->expects('checkIfPackageExists')->with('Acme', 'RocketSkates');
        $this->files->expects('move')->with(self::TEMP.'/packager-skeleton-master', '/app/packages/Acme/RocketSkates')->andReturnTrue();

        $this->command('packager:new', ['vendor' => 'Acme/RocketSkates'])
            ->assertExitCode(0);
    }

    public function test_it_uses_a_custom_skeleton(): void
    {
        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket', '--skeleton' => 'https://example.com/skeleton.zip'])
            ->assertExitCode(0);

        $this->assertSame(['https://example.com/skeleton.zip'], $this->downloads);
    }

    public function test_it_rejects_invalid_names(): void
    {
        $this->files->shouldNotReceive('checkIfPackageExists', 'makeDir', 'download');

        $this->command('packager:new', ['vendor' => '1234-invalid', 'name' => 'Rocket'])
            ->expectsOutputToContain('The package vendor must conform to a valid PHP classname.')
            ->assertExitCode(1);
    }

    public function test_it_does_not_start_the_progress_bar_for_invalid_names(): void
    {
        $this->command('packager:new', ['vendor' => 'Acme', 'name' => '1-invalid'])
            ->doesntExpectOutputToContain('0/6')
            ->assertExitCode(1);
    }

    public function test_it_refuses_to_overwrite_an_existing_package(): void
    {
        $this->files->expects('checkIfPackageExists')->with('Acme', 'Rocket')->andThrow(new RuntimeException('Package already exists'));
        $this->files->shouldNotReceive('makeDir', 'download');

        $this->expectExceptionMessage('Package already exists');

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])->run();
    }

    public function test_it_asks_for_names_skeleton_and_metadata_in_interactive_mode(): void
    {
        config(['packager.author_name' => 'Default Author']);

        $this->command('packager:new', ['--i' => true])
            ->expectsQuestion('What will be the vendor name?', 'Acme')
            ->expectsQuestion('What will be the package name?', 'Rocket')
            ->expectsQuestion('What package skeleton would you like to use?', 'https://example.com/custom.zip')
            ->expectsQuestion('Who is the author?', 'Wile E. Coyote')
            ->expectsQuestion('What is the author\'s e-mail?', 'wile@acme.test')
            ->expectsQuestion('What is the author\'s website?', 'https://acme.test')
            ->expectsQuestion('How would you describe the package?', 'Rocket powered roller skates')
            ->expectsQuestion('Under which license will it be released?', 'MIT')
            ->expectsOutputToContain('Package created successfully!')
            ->assertExitCode(0);

        $this->assertSame(['https://example.com/custom.zip'], $this->downloads);
        $this->assertReplacements([
            ':author_name' => 'Wile E. Coyote',
            ':author_email' => 'wile@acme.test',
            ':author_homepage' => 'https://acme.test',
            ':package_description' => 'Rocket powered roller skates',
            ':license' => 'MIT',
        ]);
    }

    public function test_it_reports_a_failing_composer_install(): void
    {
        // Replace the runner from setUp(), which lets every command succeed.
        $this->commandRunner = CommandRunnerExpectation::create();
        app()->instance(CommandRunnerInterface::class, $this->commandRunner->getMock());
        $this->commandRunner->getMock()->allows('run')->with(Mockery::on(static fn (array $command): bool => $command[1] === 'config'))->andReturnTrue();
        $this->commandRunner->getMock()->allows('run')->with(Mockery::on(static fn (array $command): bool => $command[1] === 'require'))->andReturnFalse();

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->expectsOutputToContain('Composer could not install the package. Its files are kept in /app/packages/Acme/Rocket, run `php artisan packager:enable Acme Rocket`')
            ->expectsOutputToContain('Run the composer command yourself')
            ->doesntExpectOutputToContain('Package created successfully!')
            ->assertExitCode(1);
    }

    public function test_it_moves_the_only_directory_of_the_archive(): void
    {
        // GitHub tarballs also hold a pax_global_header file, which directories() leaves out.
        $this->files->expects('getArchiveExtension')->with('https://github.com/acme/rocket/archive/master.tar.gz')->andReturn('tar.gz');
        $this->files->expects('makeFilename')->with('tar.gz')->andReturn(self::ARCHIVE);
        $this->files->expects('directories')->with(self::TEMP)->andReturn(['rocket-master']);
        $this->files->expects('move')->with(self::TEMP.'/rocket-master', '/app/packages/Acme/Rocket')->andReturnTrue();

        $this->command('packager:new', [
            'vendor' => 'Acme',
            'name' => 'Rocket',
            '--skeleton' => 'https://github.com/acme/rocket/archive/master.tar.gz',
        ])->assertExitCode(0);
    }

    public function test_it_rejects_an_archive_without_exactly_one_directory(): void
    {
        $this->files->expects('directories')->with(self::TEMP)->andReturn(['one', 'two']);
        $this->files->shouldNotReceive('move');
        $this->expectCleanUp();

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->expectsOutputToContain('Unable to download '.config('packager.skeleton'))
            ->expectsOutputToContain('The archive should contain exactly one directory')
            ->doesntExpectOutputToContain('Package created successfully!')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_package_cannot_be_moved_into_place(): void
    {
        $this->files->expects('move')->andReturnFalse();
        $this->files->shouldNotReceive('renameFiles');
        $this->expectCleanUp();

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket'])
            ->expectsOutputToContain('Unable to move the downloaded package to /app/packages/Acme/Rocket')
            ->doesntExpectOutputToContain('Package created successfully!')
            ->assertExitCode(1);
    }

    public function test_it_reports_a_failing_skeleton_download(): void
    {
        $this->files->expects('download')->andThrow(new ConnectException("Connection refused\nmore details", new Request('GET', 'https://example.com/missing.zip')));
        $this->files->shouldNotReceive('extract', 'move');
        $this->expectCleanUp();

        $this->command('packager:new', ['vendor' => 'Acme', 'name' => 'Rocket', '--skeleton' => 'https://example.com/missing.zip'])
            ->expectsOutputToContain('Unable to download https://example.com/missing.zip')
            ->expectsOutputToContain('Connection refused')
            ->doesntExpectOutputToContain('more details')
            ->doesntExpectOutputToContain('Package created successfully!')
            ->assertExitCode(1);
    }

    /**
     * The downloaded archive and the temporary directory are removed, whatever happened.
     */
    private function expectCleanUp(): void
    {
        $this->files->expects('cleanUp')->with(self::ARCHIVE)->andReturnSelf();
        $this->files->expects('removeDir')->with(self::TEMP)->andReturnTrue();
    }

    /**
     * @param  array<string, string>  $expected  Replacements keyed by their placeholder
     */
    private function assertReplacements(array $expected): void
    {
        $actual = array_combine($this->wrapping->placeholders, $this->wrapping->replacements);

        foreach ($expected as $placeholder => $replacement) {
            $this->assertSame($replacement, $actual[$placeholder] ?? null, "Replacement of {$placeholder}");
        }
    }
}
