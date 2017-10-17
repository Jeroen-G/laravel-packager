<?php

namespace JeroenG\Packager;

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
     *
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
     * @param  string $path The directory of the files containing placeholders
     * @return void
     */
    public function fill($path)
    {
        $templates = array_merge(
            glob($path.'/composer.json'),
            glob($path.'/*.md')
        );
        foreach ($templates as $file) {
            $this->fillInFile($file);
        }
    }

    /**
     * Fill placeholders in a single file.
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

    public function addToComposer($vendor, $package)
    {
        return $this->replace('"psr-4": {', '"psr-4": {
        "'.$vendor.'\\\\'.$package.'\\\\": "packages/'.$vendor.'/'.$package.'/src",')
                    ->fillInFile(base_path('composer.json'));
    }

    public function removeFromComposer($vendor, $package)
    {
        return $this->replace('"'.$vendor.'\\\\'.$package.'\\\\": "packages/'.$vendor.'/'.$package.'/src",', '')
                    ->fillInFile(base_path('composer.json'));
    }

    public function addToProviders($vendor, $package)
    {
        return $this->replace('
        /*
         * Package Service Providers...
         */', '
        /*
         * Package Service Providers...
         */
        '.$vendor.'\\'.$package.'\\'.$package.'ServiceProvider::class,')
                    ->fillInFile(config_path('app.php'));
    }

    public function removeFromProviders($vendor, $package)
    {
        return $this->replace($vendor.'\\'.$package.'\\'.$package.'ServiceProvider::class,', '')
                    ->fillInFile(config_path('app.php'));
    }
}
