<?php

namespace JeroenG\Packager;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Wrapping
{
    /**
     * Placeholders.
     * @var array
     */
    public $placeholders = [];

    /**
     * Replacements.
     * @var array
     */
    public $replacements = [];

    /**
     * Open haystack, find and replace needles, save haystack.
     *
     * @param  string|array  $placeholder  String or array to look for (the needles)
     * @param  string|array $replacement What to replace the needles for?
     * @return $this
     */
    public function replace($placeholder, $replacement)
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
     * @param  string $path The directory of the files containing placeholders
     * @return void
     */
    public function fill($path)
    {
        $files = new RecursiveDirectoryIterator($path);
        foreach (new RecursiveIteratorIterator($files) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $this->fillInFile($file->getPath().'/'.$file->getFilename());
        }
    }

    /**
     * Fill placeholders in a single file.
     *
     * @param  string $template     The file with the generic placeholders in it
     * @param  string|null $destiniation    Where to save, defaults to $template
     * @return $this
     */
    public function fillInFile($template, $destination = null)
    {
        $destination = ($destination === null) ? $template : $destination;

        $filledFile = str_replace($this->placeholders, $this->replacements, file_get_contents($template));
        file_put_contents($destination, $filledFile);

        return $this;
    }

    /**
     * Add the package to composer.json.
     *
     * @param  string $vendor
     * @param  string $package
     * @return $this
     */
    public function addToComposer($vendor, $package)
    {
        [$vendor, $package] = $this->formatVars($vendor, $package);

        return $this->replace('"psr-4": {', '"psr-4": {
            "'.$vendor.'\\\\'.$package.'\\\\": "packages/'.$vendor.'/'.$package.'/src",')
                    ->fillInFile(base_path('composer.json'));
    }

    /**
     * Remove the package from composer.json.
     *
     * @param  string $vendor
     * @param  string $package
     * @return $this
     */
    public function removeFromComposer($vendor, $package)
    {
        return $this->replace('"'.$vendor.'\\\\'.$package.'\\\\": "packages/'.$vendor.'/'.$package.'/src",', '')
                    ->fillInFile(base_path('composer.json'));
    }

    /**
     * Add the package to the providers in config/app.php.
     *
     * @param  string $vendor
     * @param  string $package
     * @return $this
     */
    public function addToProviders($vendor, $package)
    {
        [$vendor, $package] = $this->formatVars($vendor, $package);

        return $this->replace('
         * Package Service Providers...
         */', '
         * Package Service Providers...
         */
        '.$vendor.'\\'.$package.'\\'.$package.'ServiceProvider::class,')
                    ->fillInFile(config_path('app.php'));
    }

    /**
     * Remove the package from the providers in config/app.php.
     *
     * @param  string $vendor
     * @param  string $package
     * @return $this
     */
    public function removeFromProviders($vendor, $package)
    {
        return $this->replace($vendor.'\\'.$package.'\\'.$package.'ServiceProvider::class,', '')
                    ->fillInFile(config_path('app.php'));
    }

    /**
     * Format vendor and package strings to camel case.
     *
     * @param  string $vendor
     * @param  string $package
     * @return array
     */
    protected function formatVars($vendor, $package)
    {
        foreach (['vendor', 'package'] as $var) {
            ${$var} = collect(explode('-', ${$var}))->map(function ($segment, $key) {
                return ucfirst($segment);
            })->implode('');
        }

        return [$vendor, $package];
    }
}
