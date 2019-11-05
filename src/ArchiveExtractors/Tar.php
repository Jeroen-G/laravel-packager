<?php

namespace JeroenG\Packager\ArchiveExtractors;

use PharData;

class Tar extends Extractor
{
    /**
     * @inheritDoc
     */
    function extract($pathToArchive, $pathToDirectory)
    {
        $phar = new PharData($pathToArchive);
        $phar->extractTo($pathToDirectory);
    }
}
