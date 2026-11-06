<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Wrapping
{
    /** @var list<string> */
    public array $placeholders = [];

    /** @var list<string> */
    public array $replacements = [];

    /**
     * Open haystack, find and replace needles, save haystack.
     *
     * @param  string|list<string>  $placeholder  String or array to look for (the needles)
     * @param  string|null|list<string|null>  $replacement  What to replace the needles for? Null replaces with an empty string.
     * @return $this
     */
    public function replace(string|array $placeholder, string|array|null $replacement): self
    {
        if (is_array($placeholder)) {
            $this->placeholders = array_merge($this->placeholders, $placeholder);
        } else {
            $this->placeholders[] = $placeholder;
        }
        if (is_array($replacement)) {
            $this->replacements = array_merge($this->replacements, array_map(static fn (?string $value): string => $value ?? '', $replacement));
        } else {
            $this->replacements[] = $replacement ?? '';
        }

        return $this;
    }

    /**
     * Fill all placeholders with their replacements.
     *
     * @param  string  $path  The directory of the files containing placeholders
     * @return void
     */
    public function fill(string $path): void
    {
        $files = new RecursiveDirectoryIterator($path);
        foreach (new RecursiveIteratorIterator($files) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $this->fillInFile($file->getPath().'/'.$file->getFilename());
        }
    }

    /**
     * Fill placeholders in a single file.
     *
     * @param  string  $template  The file with the generic placeholders in it
     * @param  string|null  $destination  Where to save, defaults to $template
     * @return $this
     */
    public function fillInFile(string $template, ?string $destination = null): self
    {
        $destination = $destination ?? $template;

        $contents = file_get_contents($template);

        if ($contents === false) {
            throw new RuntimeException("Unable to read {$template}");
        }

        $filledFile = str_replace($this->placeholders, $this->replacements, $contents);
        file_put_contents($destination, $filledFile);

        return $this;
    }
}
