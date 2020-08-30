<?php

return [
    /*
     * You can set the default packages folder path.
     */
    'packages_path' => env('PACKAGES_PATH', base_path('packages')),

    /*
     * The following skeleton will be downloaded for each new package.
     * Default: http://github.com/Jeroen-G/packager-skeleton/archive/master.zip
     */
    'skeleton' => 'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip',

    /*
     * When you run into issues downloading the skeleton, this might be because of
     * a file regarding SSL certificates missing on the (Windows) OS.
     * This can be solved by setting the verification of the certificate to false.
     * Of course this means it will be less secure.
     */
    'curl_verify_cert' => env('CURL_VERIFY', true),

    /*
     * You can set defaults for the following placeholders.
     */
    'author_name' => 'author name',
    'author_email' => 'author email',
    'author_homepage' => 'author homepage',
    'license' => 'license',

    /*
     * For other public repositories. Enter the host and the associated url template
     * to allow zip files to be downloaded.
     */
    'repositories' => [
        //'personal.repo.com' => 'https://:host/:vendor/:name/archive/:branch.zip',
    ],
];
