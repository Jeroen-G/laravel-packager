<?php

namespace JeroenG\Packager\ArchiveExtractors;

use PharData;

class Tar extends Extractor
{
    /**
     * {@inheritdoc}
     */
    public function extract(string $pathToArchive, string $pathToDirectory): void
    {
        $archive = new PharData($pathToArchive);
        $archive->extractTo($pathToDirectory);
    }
}
