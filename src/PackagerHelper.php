<?php

namespace JeroenG\Packager;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;

class PackagerHelper
{
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    public function barSetup($bar)
    {
        // the finished part of the bar
        $bar->setBarCharacter('<comment>=</comment>');

        // the unfinished part of the bar
        $bar->setEmptyBarCharacter('-');

        // the progress character
        $bar->setProgressCharacter('>');

        // the 'layout' of the bar
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% ');

        return $bar;
    }

    public function replaceAndSave($oldFile, $search, $replace, $newFile = null)
    {
        $newFile = ($newFile == null) ? $oldFile : $newFile ;
        $file = $this->files->get($oldFile);
        $replacing = str_replace($search, $replace, $file);
        $this->files->put($newFile, $replacing);
    }

    public function checkExistingPackage($path, $vendor, $name)
    {
        if(is_dir($path.$vendor.'/'.$name)){
            throw new RuntimeException('Package already exists');
        }
    }

    public function makeDir($path)
    {
        if(!is_dir($path)) {
            return mkdir($path);
        }
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    public function makeFilename()
    {
        return getcwd().'/package'.md5(time().uniqid()).'.zip';
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @return $this
     */
    public function download($zipFile, $source)
    {
        $response = (new Client)->get($source);
        file_put_contents($zipFile, $response->getBody());
        return $this;
    }

    /**
     * Extract the zip file into the given directory.
     *
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    public function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;
        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();
        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param  string  $zipFile
     * @return $this
     */
    public function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);
        return $this;
    }
}