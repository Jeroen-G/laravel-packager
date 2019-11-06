<?php

namespace JeroenG\Packager\ArchiveExtractors;

use PharData;

class Tar extends Extractor
{
    /**
     * {@inheritdoc}
     */
    public function extract($pathToArchive, $pathToDirectory)
    {
        $phar = new PharData($pathToArchive);
        $phar->extractTo($pathToDirectory);
    }
}
