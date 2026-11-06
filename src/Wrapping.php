<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Wrapping
{
    public array $placeholders = [];

    public array $replacements = [];

    /**
     * Open haystack, find and replace needles, save haystack.
     *
     * @param  string|array  $placeholder  String or array to look for (the needles)
     * @param  string|array  $replacement  What to replace the needles for?
     * @return $this
     */
    public function replace($placeholder, $replacement): self
    {
        if (is_array($placeholder)) {
            $this->placeholders = array_merge($this->placeholders, $placeholder);
        } else {
            $this->placeholders[] = $placeholder;
        }
        if (is_array($replacement)) {
            $this->replacements = array_merge($this->replacements, $replacement);
        } else {
            $this->replacements[] = $replacement;
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

        $filledFile = str_replace($this->placeholders, $this->replacements, file_get_contents($template));
        file_put_contents($destination, $filledFile);

        return $this;
    }
}
