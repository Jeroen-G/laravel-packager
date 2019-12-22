<?php

namespace JeroenG\Packager\ArchiveExtractors;

abstract class Extractor
{
    /**
     * @param string $pathToArchive
     * @param string $pathToDirectory
     *
     * @return void
     */
    abstract public function extract(string $pathToArchive, string $pathToDirectory): void;
}
