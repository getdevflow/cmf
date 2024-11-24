<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Define your Content Security Policies.
|--------------------------------------------------------------------------
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Server
    |
    | Note: when server is empty string, it will not add to the response
    | header.
    |--------------------------------------------------------------------------
    */
    'server' => '',

    /*
    |--------------------------------------------------------------------------
    | X-Content-Type-Options
    |
    | Available Value: 'nosniff'
    |--------------------------------------------------------------------------
    */
    'x-content-type-options' => 'nosniff',

    /*
    |--------------------------------------------------------------------------
    | X-Download-Options
    |
    | Available Value: 'noopen'
    |--------------------------------------------------------------------------
    */
    'x-download-options' => 'noopen',

    /*
    |--------------------------------------------------------------------------
    | X-Frame-Options
    |
    | Available Value: 'deny', 'sameorigin', 'allow-from <uri>'
    |--------------------------------------------------------------------------
    */
    'x-frame-options' => 'sameorigin',

    /*
    |--------------------------------------------------------------------------
    | X-Permitted-Cross-Domain-Policies
    |
    | Available Value: 'all', 'none', 'master-only', 'by-content-type',
    | 'by-ftp-filename'
    |--------------------------------------------------------------------------
    */
    'x-permitted-cross-domain-policies' => 'none',

    /*
    |--------------------------------------------------------------------------
    | X-Powered-By
    |
    | Note: it will not add to response header if the value is empty string.
    |
    | Also, verify that expose_php is turned Off in php.ini.
    | Otherwise the header will still be included in the response.
    |--------------------------------------------------------------------------
    */
    'x-powered-by' => sprintf('Devflow-%s', \App\Application\Devflow::inst()->release()),

    /*
    |--------------------------------------------------------------------------
    | X-XSS-Protection
    |
    | Available Value: '1', '0', '1; mode=block'
    |--------------------------------------------------------------------------
    */
    'x-xss-protection' => '0',

    /*
    |--------------------------------------------------------------------------
    | Referrer-Policy
    |
    | Available Value: 'no-referrer', 'no-referrer-when-downgrade', 'origin',
    |                  'origin-when-cross-origin', 'same-origin', 'strict-origin',
    |                  'strict-origin-when-cross-origin', 'unsafe-url'
    |--------------------------------------------------------------------------
    */
    'referrer-policy' => 'no-referrer',

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin-Embedder-Policy
    |
    | Available Value: 'unsafe-none', 'require-corp'
    |--------------------------------------------------------------------------
    */
    'cross-origin-embedder-policy' => 'unsafe-none',

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin-Opener-Policy
    |
    | Available Value: 'unsafe-none', 'same-origin-allow-popups', 'same-origin'
    |--------------------------------------------------------------------------
    */
    'cross-origin-opener-policy' => 'unsafe-none',

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin-Resource-Policy
    |
    | Available Value: 'same-site', 'same-origin', 'cross-origin'
    |--------------------------------------------------------------------------
    */
    'cross-origin-resource-policy' => 'cross-origin',

    /*
    |--------------------------------------------------------------------------
    | Clear-Site-Data
    |--------------------------------------------------------------------------
    */
    'clear-site-data' => [
        'enable' => false,

        'all' => false,

        'cache' => true,

        'cookies' => true,

        'storage' => true,

        'executionContexts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Strict Transport Security
    |
    | Please ensure your website had set up ssl/tls before enable hsts.
    |--------------------------------------------------------------------------
    */
    'hsts' => [
        'enable' => false,

        'max-age' => 31536000,

        'include-sub-domains' => false,

        'preload' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Expect-CT
    |--------------------------------------------------------------------------
    */
    'expect-ct' => [
        'enable' => false,

        'max-age' => 2147483648,

        'enforce' => false,

        // report uri must be absolute-URI
        'report-uri' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Policy
    |
    | https://developer.mozilla.org/en-US/docs/Web/HTTP/Permissions_Policy
    |--------------------------------------------------------------------------
    */
    'permissions-policy' => [
        'enable' => true,

        'accelerometer' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'ambient-light-sensor' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'autoplay' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'battery' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'camera' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'cross-origin-isolated' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'display-capture' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'document-domain' => [
            'none' => false,

            '*' => true,

            'self' => false,

            'origins' => [],
        ],

        'encrypted-media' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'execution-while-not-rendered' => [
            'none' => false,

            '*' => true,

            'self' => false,

            'origins' => [],
        ],

        'execution-while-out-of-viewport' => [
            'none' => false,

            '*' => true,

            'self' => false,

            'origins' => [],
        ],

        'fullscreen' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'geolocation' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'gyroscope' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'magnetometer' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'microphone' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'midi' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'navigation-override' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'payment' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'picture-in-picture' => [
            'none' => false,

            '*' => true,

            'self' => false,

            'origins' => [],
        ],

        'publickey-credentials-get' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'screen-wake-lock' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'sync-xhr' => [
            'none' => false,

            '*' => true,

            'self' => false,

            'origins' => [],
        ],

        'usb' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'web-share' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],

        'xr-spatial-tracking' => [
            'none' => false,

            '*' => false,

            'self' => true,

            'origins' => [],
        ],
    ],

    /*
    |------------------------------------------------------------------------------------
    | Content Security Policy
    |
    | https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/
    |------------------------------------------------------------------------------------
    */
    'csp' => [
        'enable' => true,

        'report-only' => false,

        'report-to' => '',

        'report-uri' => [
            // uri
        ],

        /**
         * This directive is deprecated but is here for completion.
         * It is recommended to remove this directive for new
         * websites.
         */
        'block-all-mixed-content' => false,

        'upgrade-insecure-requests' => false,

        'base-uri' => [
            //
        ],

        'child-src' => [
            //
        ],

        'connect-src' => [
            //
        ],

        'default-src' => [
            //
        ],

        'font-src' => [
            //
        ],

        'form-action' => [
            //
        ],

        'frame-ancestors' => [
            //
        ],

        'frame-src' => [
            //
        ],

        'img-src' => [
            //
        ],

        'manifest-src' => [
            //
        ],

        'media-src' => [
            //
        ],

        'navigate-to' => [
            'unsafe-allow-redirects' => false,
        ],

        'object-src' => [
            //
        ],

        'prefetch-src' => [
            //
        ],

        'require-trusted-types-for' => [
            'script' => false,
        ],

        'script-src' => [
            'none' => false,

            'self' => false,

            'report-sample' => false,

            'allow' => [
                // 'url',
            ],

            'schemes' => [
                // 'data:',
                // 'https:',
            ],

            /* The following only work for `script` and `style` related directives. */

            'unsafe-inline' => false,

            'unsafe-eval' => false,

            'unsafe-hashes' => false,

            /*
            |------------------------------------------------------------------------------------
            | Enabling `strict-dynamic` will *ignore* `self`, `unsafe-inline`,
            | `allow` and `schemes`.
            |------------------------------------------------------------------------------------
            */
            'strict-dynamic' => false,

            'hashes' => [
                'sha256' => [
                    // 'sha256-hash-value-with-base64-encode',
                ],

                'sha384' => [
                    // 'sha384-hash-value-with-base64-encode',
                ],

                'sha512' => [
                    // 'sha512-hash-value-with-base64-encode',
                ],
            ],
        ],

        'script-src-attr' => [
            //
        ],

        'script-src-elem' => [
            //
        ],

        'style-src' => [
            //
        ],

        'style-src-attr' => [
            //
        ],

        'style-src-elem' => [
            //
        ],

        'trusted-types' => [
            'enable' => false,

            'allow-duplicates' => false,

            'default' => false,

            'policies' => [
                //
            ],
        ],

        'worker-src' => [
            //
        ],
    ],
];
