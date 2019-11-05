<?php

namespace JeroenG\Packager\ArchiveExtractors;

use Illuminate\Support\Arr;
use InvalidArgumentException;

final class Manager
{
    /** @var Extractor[] */
    protected $extractorsMap = [];

    /**
     * @param string $archiveExtension
     *
     * @return Extractor
     * @throws InvalidArgumentException
     */
    public function getExtractor($archiveExtension)
    {
        $extractor = Arr::get($this->extractorsMap, $archiveExtension);

        if (!$extractor) {
            throw new InvalidArgumentException("There is no extractors for extension '{$archiveExtension}'!");
        }

        return $extractor;
    }

    /**
     * @param string $archiveExtension
     * @param Extractor $instance
     *
     * @return self
     */
    public function extend($archiveExtension, Extractor $instance)
    {
        $this->extractorsMap[$archiveExtension] = $instance;

        return $this;
    }
}
