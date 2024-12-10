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
    'referrer-policy' => 'same-origin',

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
    'cross-origin-resource-policy' => 'same-origin',

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
        'enable' => true,

        'max-age' => 31536000,

        'include-sub-domains' => true,

        'preload' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Expect-CT
    |--------------------------------------------------------------------------
    */
    'expect-ct' => [
        'enable' => true,

        'max-age' => 2147483648,

        'enforce' => true,

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

        'autoplay' => [
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

        'encrypted-media' => [
            'none' => false,

            '*' => false,

            'self' => true,

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

        'upgrade-insecure-requests' => true,

        'base-uri' => [
            'self' => true,
        ],

        'connect-src' => [
            'self' => true,
        ],

        'default-src' => [
            'self' => true,
        ],

        'form-action' => [
            'self' => true,
        ],

        'img-src' => [
            'none' => false,

            'self' => true,

            'data' => true,

            'report-sample' => false,

            'allow' => [
                'gravatar.com',
                'www.gravatar.com',
                'cdnjs.cloudflare.com',
            ],

            'schemes' => [
                'http:',
                'https:',
            ],

            'unsafe-inline' => true,
        ],

        'media-src' => [
            'self' => true,
        ],

        'navigate-to' => [
            'unsafe-allow-redirects' => false,
        ],

        'require-trusted-types-for' => [
            'script' => false,
        ],

        'script-src' => [
            'none' => false,

            'self' => true,

            'report-sample' => false,

            'allow' => [
                'cdnjs.cloudflare.com',
                'gitcdn.github.io',
                'code.jquery.com',
                'stackpath.bootstrapcdn.com'
            ],

            'schemes' => [
                'http:',
                'https:',
            ],

            /* The following only work for `script` and `style` related directives. */

            'unsafe-inline' => true,

            //'unsafe-eval' => false,

            //'unsafe-hashes' => false,

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

        'font-src' => [
            'none' => false,

            'self' => true,

            'report-sample' => false,

            'allow' => [
                'cdnjs.cloudflare.com',
                'fonts.googleapis.com',
                'gitcdn.github.io',
                'fonts.gstatic.com',
                'netdna.bootstrapcdn.com',
            ],

            'schemes' => [
                'http:',
                'https:',
            ],

            'unsafe-inline' => true,
        ],

        'frame-src' => [
            'none' => false,
            'self' => true,
            'allow' => [
                'cdnjs.cloudflare.com',
            ],

            'data' => true,

            'schemes' => [
                'http:',
                'https:',
            ],
        ],

        'style-src' => [
            'none' => false,

            'self' => true,

            'report-sample' => false,

            'allow' => [
                'cdnjs.cloudflare.com',
                'fonts.googleapis.com',
                'gitcdn.github.io',
                'fonts.gstatic.com',
                'netdna.bootstrapcdn.com',
            ],

            'schemes' => [
                'http:',
                'https:',
            ],

            'unsafe-inline' => true,
        ],

        'trusted-types' => [
            'enable' => false,

            'allow-duplicates' => false,

            'default' => false,

            'policies' => [
                //
            ],
        ],
    ],
];
