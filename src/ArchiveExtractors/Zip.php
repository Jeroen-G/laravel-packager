<?php

namespace JeroenG\Packager\ArchiveExtractors;

use ZipArchive;

class Zip extends Extractor
{
    /**
     * @inheritDoc
     */
    function extract($pathToArchive, $pathToDirectory)
    {
        $archive = new ZipArchive;
        $archive->open($pathToArchive);
        $archive->extractTo($pathToDirectory);
        $archive->close();
    }
}
