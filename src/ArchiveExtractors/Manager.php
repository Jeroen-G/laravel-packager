<?php

namespace JeroenG\Packager\ArchiveExtractors;

use Illuminate\Support\Arr;
use InvalidArgumentException;

final class Manager
{
    /** @var Extractor[] */
    protected $extractorsMap = [];

    public function __construct()
    {
        $this->addExtractor('zip', new Zip())
             ->addExtractor('tar', new Tar())
             ->addExtractor('tar.gz', new TarGz());
    }

    /**
     * @param string $archiveExtension
     *
     * @return Extractor
     * @throws InvalidArgumentException
     */
    public function getExtractor(string $archiveExtension): Extractor
    {
        $extractor = Arr::get($this->extractorsMap, $archiveExtension);

        if (! $extractor) {
            throw new InvalidArgumentException("There are no extractors for extension '{$archiveExtension}'!");
        }

        return $extractor;
    }

    /**
     * @param string $archiveExtension
     * @param Extractor $instance
     *
     * @return self
     */
    public function addExtractor(string $archiveExtension, Extractor $instance): self
    {
        $this->extractorsMap[$archiveExtension] = $instance;

        return $this;
    }
}
