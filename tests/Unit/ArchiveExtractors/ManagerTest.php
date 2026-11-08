<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\ArchiveExtractors;

use InvalidArgumentException;
use JeroenG\Packager\ArchiveExtractors\Extractor;
use JeroenG\Packager\ArchiveExtractors\Manager;
use JeroenG\Packager\ArchiveExtractors\Tar;
use JeroenG\Packager\ArchiveExtractors\TarGz;
use JeroenG\Packager\ArchiveExtractors\Zip;
use JeroenG\Packager\Tests\UnitTestCase;

final class ManagerTest extends UnitTestCase
{
    public function test_it_has_default_extractors(): void
    {
        $manager = new Manager();

        $this->assertInstanceOf(Zip::class, $manager->getExtractor('zip'));
        $this->assertInstanceOf(Tar::class, $manager->getExtractor('tar'));
        $this->assertInstanceOf(TarGz::class, $manager->getExtractor('tar.gz'));
    }

    public function test_it_throws_for_an_unknown_extension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("There are no extractors for extension 'rar'!");

        (new Manager())->getExtractor('rar');
    }

    public function test_an_extractor_can_be_added_or_replaced(): void
    {
        $manager = new Manager();
        $custom = \Mockery::mock(Extractor::class);

        $this->assertSame($manager, $manager->addExtractor('zip', $custom));
        $this->assertSame($custom, $manager->getExtractor('zip'));
    }
}
