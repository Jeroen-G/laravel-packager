<?php

return [

    /*
     * The following skeleton will be downloaded for each new package.
     * Default: http://github.com/Jeroen-G/packager-skeleton/archive/master.zip
     */
    'skeleton' => 'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip',

    /*
     * Here you may change the filename of the composer dependencies file.
     * It can be useful for example when you want to use the composer merge-plugin.
     */
    'composer_json_filename' => 'composer.json',

    /*
     * If you run into issues downloading the skeleton, this might be because of
     * a file regarding SSL certificates missing on your system. This can be solved by
     * setting the verification of the certificate to false, but this means it will be less secure.
     */
    'curl_verify_cert' => env('CURL_VERIFY', true),

    /*
     * You can set defaults for the following placeholders.
     */
    'author_name' => 'author name',
    'author_email' => 'author email',
    'author_homepage' => 'author homepage',
    'license' => 'license',
];
