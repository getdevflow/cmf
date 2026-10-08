<?php

declare(strict_types=1);

use App\Infrastructure\Services\Options;

use function Codefy\Framework\Helpers\app;

afterEach(function () {
    app(Options::class)->delete('site_logo');
});

it('stores, replaces and clears the native site logo without changing URL data', function () {
    $options = app(Options::class);
    $logos = [
        'https://cms.test/uploads/site%20logo.png?version=2&size=large',
        'https://cdn.example.test/logo.svg?token=a%2Fb%3Dc&width=200',
        '',
    ];
    foreach ($logos as $logo) {
        $options->update('site_logo', $logo);
        expect($options->read('site_logo', ''))->toBe($logo);
    }
});

it('does not expose unsafe schemes, credentialed URLs or malformed logo options', function () {
    $options = app(Options::class);
    $logos = [
        'javascript:alert(1)',
        'data:image/svg+xml,<svg/>',
        'https://name:password@cms.test/logo.png',
        ['image' => 'https://cms.test/uploads/logo.png'],
    ];
    foreach ($logos as $logo) {
        $options->update('site_logo', $logo);
        expect($options->read('site_logo', ''))->toBe('');
    }
});
