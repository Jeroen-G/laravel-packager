<?php

namespace JeroenG\Packager;

use Symfony\Component\Console\Helper\ProgressBar as SymfonyProgressBar;

trait ProgressBar
{
    protected ?SymfonyProgressBar $bar = null;

    /**
     * Setting custom formatting for the progress bar.
     *
     * @param int $steps The number of steps the progress bar has.
     * @return void
     */
    public function startProgressBar(int $steps): void
    {
        // create the bar
        $this->bar = $this->output->createProgressBar($steps);

        // the finished part of the bar
        $this->bar->setBarCharacter('<comment>=</comment>');

        // the unfinished part of the bar
        $this->bar->setEmptyBarCharacter('-');

        // the progress character
        $this->bar->setProgressCharacter('>');

        // the 'layout' of the bar
        $this->bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% ');

        // Start the progress bar
        $this->bar->start();
    }

    /**
     * Advance the progress bar with a step.
     *
     * @return void
     */
    public function makeProgress(): void
    {
        $this->bar->advance();
    }

    /**
     * Finalise the progress, output the (last) message.
     *
     * @param string $message
     * @return void
     */
    public function finishProgress(string $message): void
    {
        $this->bar->finish();
        $this->info($message);
        $this->output->newLine(2);
        $this->bar = null;
    }
}
