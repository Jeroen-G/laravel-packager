<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit;

use JeroenG\Packager\Tests\UnitTestCase;
use JeroenG\Packager\Wrapping;

final class WrappingTest extends UnitTestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'packager-'.uniqid('', true);
        mkdir($this->workDir.'/nested', 0777, true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->workDir.'/nested/*') ?: []);
        array_map('unlink', glob($this->workDir.'/*.*') ?: []);
        rmdir($this->workDir.'/nested');
        rmdir($this->workDir);
        parent::tearDown();
    }

    public function test_it_collects_placeholders_and_replacements(): void
    {
        $wrapping = new Wrapping();

        $result = $wrapping->replace(':vendor', 'Acme')
            ->replace([':package', ':author'], ['Rocket', 'Wile']);

        $this->assertSame($wrapping, $result);
        $this->assertSame([':vendor', ':package', ':author'], $wrapping->placeholders);
        $this->assertSame(['Acme', 'Rocket', 'Wile'], $wrapping->replacements);
    }

    public function test_it_fills_a_file_in_place(): void
    {
        $file = $this->workDir.'/template.txt';
        file_put_contents($file, 'Hello :name');

        $result = (new Wrapping())->replace(':name', 'World')->fillInFile($file);

        $this->assertInstanceOf(Wrapping::class, $result);
        $this->assertSame('Hello World', file_get_contents($file));
    }

    public function test_it_fills_a_file_into_a_destination(): void
    {
        $template = $this->workDir.'/template.txt';
        $destination = $this->workDir.'/destination.txt';
        file_put_contents($template, 'Hello :name');

        (new Wrapping())->replace(':name', 'World')->fillInFile($template, $destination);

        $this->assertSame('Hello :name', file_get_contents($template));
        $this->assertSame('Hello World', file_get_contents($destination));
    }

    public function test_it_fills_all_files_in_a_directory_recursively(): void
    {
        file_put_contents($this->workDir.'/a.txt', ':vendor/:package');
        file_put_contents($this->workDir.'/nested/b.txt', ':package');

        (new Wrapping())->replace([':vendor', ':package'], ['acme', 'rocket'])->fill($this->workDir);

        $this->assertSame('acme/rocket', file_get_contents($this->workDir.'/a.txt'));
        $this->assertSame('rocket', file_get_contents($this->workDir.'/nested/b.txt'));
    }
}
