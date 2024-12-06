<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Shared\Services\Utils;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Exception;
use Qubus\Http\Request;
use ReflectionException;

use function filter_var;
use function http_build_query;
use function in_array;
use function is_string;
use function ltrim;
use function parse_str;
use function parse_url;
use function preg_replace;
use function Qubus\Security\Helpers\esc_url;
use function Qubus\Support\Helpers\concat_ws;
use function str_starts_with;
use function trim;

use const FILTER_VALIDATE_URL;
use const PHP_URL_SCHEME;

/**
 * Sets the scheme for a URL.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $url Absolute URL that includes a scheme
 * @param string|null $scheme Optional. Scheme to give $url. Currently, 'http', 'https', 'login',
 *                            'admin', 'relative', 'rest' or null. Default null.
 * @return string $url URL with chosen scheme.
 * @throws ReflectionException
 * @throws Exception
 */
function set_url_scheme(string $url, ?string $scheme = null): string
{
    $origScheme = $scheme;

    if (!$scheme) {
        $scheme = is_ssl() ? 'https' : 'http';
    } elseif ($scheme === 'admin' || $scheme === 'login') {
        $scheme = is_ssl() ? 'https' : 'http';
    } elseif ($scheme !== 'http' && $scheme !== 'https' && $scheme !== 'relative') {
        $scheme = is_ssl() ? 'https' : 'http';
    }

    $url = trim($url);
    if (str_starts_with($url, '//')) {
        $url = 'http:' . $url;
    }

    if ('relative' === $scheme) {
        $url = ltrim(preg_replace('#^\w+://[^/]*#', '', $url));
        if ($url !== '' && $url[0] === '/') {
            $url = '/' . ltrim($url, "/ \t\n\r\0\x0B");
        }
    } else {
        $url = preg_replace('#^\w+://#', $scheme . '://', $url);
    }

    /**
     * Filters the resulting URL after setting the scheme.
     *
     * @file App/Shared/Helpers/url.php
     * @param string      $url         The complete URL including scheme and path.
     * @param string      $scheme      Scheme applied to the URL. One of 'http', 'https', or 'relative'.
     * @param string|null $origScheme Scheme requested for the URL. One of 'http', 'https', 'login',
     *                                 'admin', 'relative', or null.
     */
    return Filter::getInstance()->applyFilter('set_url_scheme', $url, $scheme, $origScheme);
}

/**
 * Retrieves a modified URL query string.
 *
 * Uses `query_arg_port` filter hook.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $key A query variable key.
 * @param string $value A query variable value, or a URL to act upon.
 * @param string $url A URL to act upon.
 * @return string Returns modified url query string.
 * @throws Exception
 * @throws ReflectionException
 */
function add_query_arg(string $key, string $value, string $url): string
{
    $uri = parse_url($url);
    $query = $uri['query'] ?? '';
    parse_str($query, $params);
    $params[$key] = $value;
    $query = http_build_query($params);
    $result = '';
    if ($uri['scheme']) {
        $result .= $uri['scheme'] . ':';
    }
    if ($uri['host']) {
        $result .= '//' . $uri['host'];
    }
    if (isset($uri['port'])) {
        $result .= Filter::getInstance()->applyFilter('query_arg_port', ':' . $uri['port']);
    }
    if ($uri['path']) {
        $result .= $uri['path'];
    }
    if ($query) {
        $result .= '?' . $query;
    }
    return $result;
}

/**
 * Returns the url based on route.
 *
 * @file App/Shared/Helpers/url.php
 * @param string|null $path Relative path.
 * @return string
 */
function url(?string $path = null): string
{
    $url = (is_ssl() ? 'https://' : 'http://') . (new Request())->getHost();

    return concat_ws($url, $path, '');
}

/**
 * Returns the url for a given site.
 *
 * Returns 'https' if `is_ssl()` evaluates to true and 'http' otherwise. If `$scheme` is
 * 'http' or 'https', `is_ssl(`) is overridden.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $path   Optional. Route name or route relative to the site url. Default '/'.
 * @param string|null $scheme Optional. Scheme to give the site URL context. Accepts
 *                       'http', 'https', 'login', 'admin', or 'relative'.
 *                       Default null.
 * @return string Site url link.
 * @throws Exception
 * @throws ReflectionException
 */
function cms_site_url(string $path = '', ?string $scheme = null): string
{
    $uri = url(path: '/');
    $url = set_url_scheme($uri, $scheme);

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }

    /**
     * Filters the site URL.
     *
     * @file App/Shared/Helpers/url.php
     * @param string $url         The site url including scheme and path.
     * @param string $path        Route relative to the site url. Blank string if no path is specified.
     * @param string|null $scheme Scheme to give the site url context. Accepts 'http', 'https', 'login',
     *                            'admin', 'relative' or null.
     */
    return Filter::getInstance()->applyFilter('site_url', $url, $path, $scheme);
}

/**
 * Returns the url to the admin area for a given site.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $path Optional. Path relative to the admin url. Default empty.
 * @param string $scheme Optional. The scheme to use. Accepts 'http' or 'https',
 *                       to force those schemes. Default 'admin'.
 * @return string Admin url link with optional path appended.
 * @throws Exception
 * @throws ReflectionException
 */
function cms_admin_url(string $path = '', string $scheme = 'admin'): string
{
    $url = cms_site_url('admin/', $scheme);

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }

    $escUrl = esc_url($url);

    /**
     * Filters the admin area url.
     *
     * @file App/Shared/Helpers/url.php
     * @param string $escUrl The complete admin area url including scheme and path after escaped.
     * @param string $url    The complete admin area url including scheme and path before escaped.
     * @param string $path   Path relative to the admin area url. Blank string if no path is specified.
     */
    return Filter::getInstance()->applyFilter('admin_url', $escUrl, $url, $path);
}

/**
 * Returns the url for a given site where the front end is accessible.
 *
 * The protocol will be 'https' if `is_ssl()` evaluates to true; If `$scheme` is
 * 'http' or 'https', `is_ssl()` is overridden.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $path Optional. Path relative to the home url. Default empty.
 * @param string|null $scheme Optional. Scheme to give the home URL context. Accepts
 *                            'http', 'https', 'relative', or null. Default null.
 * @return string Home url link with optional path appended.
 * @throws Exception
 * @throws ReflectionException
 */
function cms_home_url(string $path = '', string $scheme = null): string
{
    $origScheme = $scheme;
    $uri = url(path: '/');

    if (! in_array($scheme, [ 'http', 'https', 'relative' ])) {
        if (is_ssl() && ! Utils::isAdmin() && ! Utils::isLogin()) {
            $scheme = 'https';
        } else {
            $scheme = parse_url($uri, PHP_URL_SCHEME);
        }
    }

    $url = set_url_scheme($uri, $scheme);

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }

    $escUrl = esc_url($url);

    /**
     * Filters the home URL.
     *
     * @file App/Shared/Helpers/url.php
     * @param string      $escUrl The escaped home url.
     * @param string      $url     The home url before it was escaped.
     * @param string      $path    Route relative to the site url. Blank string if no path is specified.
     * @param string|null $scheme  Scheme to give the site url context. Accepts 'http', 'https',
     *                             'relative' or null.
     */
    return Filter::getInstance()->applyFilter('home_url', $escUrl, $url, $path, $origScheme);
}

/**
 * Returns the login url for a given site.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $redirect Path to redirect to on log in.
 * @param string $path Optional. Path relative to the login url. Default empty.
 * @param string|null $scheme Optional. Scheme to give the logout URL context. Accepts
 *                             'http', 'https', 'relative', or null. Default 'login'.
 * @return string Returns the login url.
 * @throws Exception
 * @throws ReflectionException
 */
function cms_login_url(string $redirect = '', string $path = '', ?string $scheme = 'login'): string
{
    $url = cms_site_url('login/', $scheme);

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }

    if (!empty($redirect)) {
        $loginUrl = add_query_arg('redirect_to', $redirect, $url);
    }

    /**
     * Validates the redirect url.
     */
    if (!empty($redirect) && !filter_var($redirect, FILTER_VALIDATE_URL)) {
        $loginUrl = $url;
    }

    /**
     * Last check and escape again just in case.
     */
    if (!empty($redirect)) {
        $loginUrl = esc_url($loginUrl);
    } else {
        $loginUrl = esc_url($url);
    }

    /**
     * Filters the login URL.
     *
     * @file App/Shared/Helpers/url.php
     * @param string $loginUrl    The login URL. Not HTML-encoded.
     * @param string $redirect     The path to redirect to on login, if supplied.
     * @param string $path         Route relative to the login url. Blank string if no path is specified.
     * @param string|null $scheme  Scheme to give the login url context. Accepts 'http', 'https',
     *                             'relative' or null.
     */
    return Filter::getInstance()->applyFilter('login_url', $loginUrl, $redirect, $path, $scheme);
}

/**
 * Returns the login url for a given site.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $redirect Path to redirect to on logout.
 * @param string $path Optional. Path relative to the logout url. Default empty.
 * @param string|null $scheme Optional. Scheme to give the logout URL context. Accepts
 *                             'http', 'https', 'relative', or null. Default 'logout'.
 * @return string Returns the logout url.
 * @throws Exception
 * @throws ReflectionException
 */
function cms_logout_url(string $redirect = '', string $path = '', ?string $scheme = 'logout'): string
{
    $url = cms_site_url('logout/', $scheme);

    if ($path && is_string($path)) {
        $url .= ltrim($path, '/');
    }

    if (!empty($redirect)) {
        $logoutUrl = add_query_arg('redirect_to', $redirect, $url);
    }

    /**
     * Validates the redirect url.
     */
    if (!empty($redirect) && !filter_var($redirect, FILTER_VALIDATE_URL)) {
        $logoutUrl = $url;
    }

    /**
     * Last check and escape again just in case.
     */
    if (!empty($redirect)) {
        $logoutUrl = esc_url($logoutUrl);
    } else {
        $logoutUrl = esc_url($url);
    }

    /**
     * Filters the logout URL.
     *
     * @file App/Shared/Helpers/url.php
     * @param string $logoutUrl   The logout URL. Not HTML-encoded.
     * @param string $redirect     The path to redirect to on logout, if supplied.
     * @param string $path         Route relative to the logout url. Blank string if no path is specified.
     * @param string|null $scheme  Scheme to give the logout url context. Accepts 'http', 'https',
     *                             'relative' or null.
     */
    return Filter::getInstance()->applyFilter('logout_url', $logoutUrl, $redirect, $path, $scheme);
}

/**
 * Returns the url for a given site.
 *
 * Returns 'https' if `is_ssl()` evaluates to true and 'http' otherwise. If `$scheme` is
 * 'http' or 'https', `is_ssl()` is overridden.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $path Optional. Route relative to the site url. Default '/'.
 * @param string|null $scheme Optional. Scheme to give the site URL context. Accepts
 *                        'http', 'https', 'login', 'admin', or 'relative'.
 *                        Default null.
 * @return string Site url link.
 * @throws Exception
 * @throws ReflectionException
 */
function site_url(string $path = '', ?string $scheme = null): string
{
    return esc_url(cms_site_url($path, $scheme));
}

/**
 * Returns the url to the admin area for a given site.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $path Optional. Path relative to the admin url. Default empty.
 * @param string $scheme Optional. The scheme to use. Accepts 'http' or 'https',
 *                        to force those schemes. Default 'admin'.
 * @return string Admin url link with optional path appended.
 * @throws Exception
 * @throws ReflectionException
 */
function admin_url(string $path = '', string $scheme = 'admin'): string
{
    return cms_admin_url($path, $scheme);
}

/**
 * Returns the url for a given site where the front end is accessible.
 *
 * The protocol will be 'https' if `is_ssl()` evaluates to true; If `$scheme` is
 * 'http' or 'https', `is_ssl()` is overridden.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $path Optional. Path relative to the home url. Default empty.
 * @param string|null $scheme Optional. Scheme to give the home URL context. Accepts
 *                             'http', 'https', 'relative', or null. Default null.
 * @return string Home url link with optional path appended.
 * @throws Exception
 * @throws ReflectionException
 */
function home_url(string $path = '', ?string $scheme = null): string
{
    return cms_home_url($path, $scheme);
}

/**
 * Returns the login url for a given site.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $redirect Path to redirect to on log in.
 * @param string $path Optional. Path relative to the login url. Default empty.
 * @param string|null $scheme Optional. Scheme to give the home URL context. Accepts
 *                             'http', 'https', 'relative', or null. Default 'login'.
 * @return string Returns the login url.
 * @throws Exception
 * @throws ReflectionException
 */
function login_url(string $redirect = '', string $path = '', ?string $scheme = 'login'): string
{
    return cms_login_url($redirect, $path, $scheme);
}

/**
 * Returns the login url for a given site.
 *
 * @file App/Shared/Helpers/url.php
 * @param string $redirect Path to redirect to on logout.
 * @param string $path Optional. Path relative to the logout url. Default empty.
 * @param string|null $scheme Optional. Scheme to give the logout URL context. Accepts
 *                             'http', 'https', 'relative', or null. Default 'logout'.
 * @return string Returns the logout url.
 * @throws Exception
 * @throws ReflectionException
 */
function logout_url(string $redirect = '', string $path = '', ?string $scheme = 'logout'): string
{
    return cms_logout_url($redirect, $path, $scheme);
}
