<?php

namespace JeroenG\Packager;

use GuzzleHttp\Client;
use RuntimeException;

class PackageRepository
{
    public $origin;
    public $host;
    public $vendor;
    public $name;

    public function parse($url)
    {
        $this->origin = $url;

        // get package url from Packagist API
        if (preg_match('`^([^/:@]*)/([^/\.]*)$`', $url, $m)) {
            $client = new Client(['verify' => config('packager.curl_verify_cert')]);
            try {
                $response = $client->get(sprintf('https://packagist.org/packages/%s/%s.json', $m[1], $m[2]));
                $json = json_decode($response->getBody()->getContents());
                $this->origin = $json->package->repository;
            } catch (GuzzleHttp\Exception\ClientException $e) {
                throw new RuntimeException('Package not found on packagist');
            }
        }

        // parse url
        $regex = [
            '`^https?://([^/]*)/([^/]*)/([^./]*).*$`',
            '`^git@([^:]*):([^/]*)/([^.]*)\.git$`',
        ];

        foreach ($regex as $rx) {
            if (preg_match($rx, $this->origin, $m)) {
                $this->host = $m[1];
                $this->vendor = $m[2];
                $this->name = $m[3];

                return $this;
            }
        }

        throw new RuntimeException('Unable to parse URL');
    }

    public function getZipUrl($branch = 'master')
    {
        if ($this->host === null) {
            throw new RuntimeException('You have to parse an URL');
        }

        // default hosts url templates
        $urls = [
            'github.com'    => 'https://:host/:vendor/:name/archive/:branch.zip',
            'gitlab.com'    => 'https://:host/:vendor/:name/-/archive/:branch/:name-:branch.zip',
            'bitbucket.org' => 'https://:host/:vendor/:name/get/:branch.zip',
        ];

        // merge with additionnal hosts
        $urls = array_merge($urls, config('packager.repositories'));

        if (! isset($urls[$this->host])) {
            throw new RuntimeException('Unknown repository host');
        }

        $args = [':host' => $this->host, ':vendor' => $this->vendor, ':name' => $this->name, ':branch' => $branch];

        return str_replace(array_keys($args), array_values($args), $urls[$this->host]);
    }
}
