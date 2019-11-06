<?php

namespace JeroenG\Packager\ArchiveExtractors;

use PharData;

class TarGz extends Extractor
{
    /**
     * {@inheritdoc}
     */
    public function extract($pathToArchive, $pathToDirectory)
    {
        $phar = new PharData($pathToArchive);
        $phar->decompress();

        // Remove .gz
        $pathToTarArchive = str_replace('.gz', '', $pathToArchive);

        $phar = new PharData($pathToTarArchive);
        $phar->extractTo($pathToDirectory);

        unlink($pathToTarArchive);
    }
}
