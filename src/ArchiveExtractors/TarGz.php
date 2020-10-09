<?php

namespace JeroenG\Packager\ArchiveExtractors;

use PharData;

class TarGz extends Extractor
{
    /**
     * {@inheritdoc}
     */
    public function extract(string $pathToArchive, string $pathToDirectory): void
    {
        $pathToTarArchive = $this->extractTarPathFromGz($pathToArchive);
        $archive = new PharData($pathToTarArchive);
        $archive->extractTo($pathToDirectory);

        unlink($pathToTarArchive);
    }

    /**
     * Get the path to the tar within the gz folder.
     *
     * @param string $pathToArchive
     * @return string
     */
    private function extractTarPathFromGz(string $pathToArchive): string
    {
        $phar = new PharData($pathToArchive);
        $phar->decompress();

        // Remove .gz and return path.
        return str_replace('.gz', '', $pathToArchive);
    }
}
