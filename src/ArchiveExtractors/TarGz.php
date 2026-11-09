<?php

declare(strict_types=1);

namespace JeroenG\Packager\ArchiveExtractors;

use PharData;

class TarGz extends Extractor
{
    public function extract(string $pathToArchive, string $pathToDirectory): void
    {
        $pathToTarArchive = $this->extractTarPathFromGz($pathToArchive);
        $archive = new PharData($pathToTarArchive);
        $archive->extractTo($pathToDirectory);

        unlink($pathToTarArchive);
    }

    private function extractTarPathFromGz(string $pathToArchive): string
    {
        $phar = new PharData($pathToArchive);
        $phar->decompress();

        // Remove .gz and return path.
        return str_replace('.gz', '', $pathToArchive);
    }
}
