<?php

namespace JeroenG\Packager;

use Symfony\Component\Console\Helper\ProgressBar;

trait ProgressBarTrait
{
    private function createProgressBar($max = 0)
    {
        if (version_compare($this->laravel->version(), '5.1', '>=')) {
            return $this->output->createProgressBar($max);
        } else {
            return new ProgressBar($this->output, $max);
        }
    }
}
