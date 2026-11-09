<?php

declare(strict_types=1);

namespace JeroenG\Packager\ArchiveExtractors;

abstract class Extractor
{
    abstract public function extract(string $pathToArchive, string $pathToDirectory): void;
}
