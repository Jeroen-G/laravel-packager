<?php

return [

    /*
     * The following skeleton will be downloaded for each new package.
     * Default: http://github.com/Jeroen-G/packager-skeleton/archive/master.zip
     */
    'skeleton' => 'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip',

    'curl_verify_cert' => env('CURL_VERIFY', true),

    /*
     * You can set defaults for the following placeholders.
     */
    'author_name' => 'author name',
    'author_email' => 'author email',
    'author_homepage' => 'author homepage',
    'license' => 'license',
];
