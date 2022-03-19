<?php

declare(strict_types=1);

namespace JeroenG\Packager\ArchiveExtractors;

use ZipArchive;

class Zip extends Extractor
{
    /**
     * {@inheritdoc}
     */
    public function extract(string $pathToArchive, string $pathToDirectory): void
    {
        $archive = new ZipArchive;
        $archive->open($pathToArchive);
        $archive->extractTo($pathToDirectory);
        $archive->close();
    }
}
