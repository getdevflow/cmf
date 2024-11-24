<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\storage_path;

final class NativePhpCookies
{
    public static function factory(): self
    {
        return new self();
    }

    /**
     * Generates a random token which is then hashed.
     *
     * @param int $length
     * @return string
     */
    public function token(int $length = 20): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return hash(config(key: 'cookies.crypt'), $randomString);
    }

    /**
     * Sets a regular cookie
     *
     * @param mixed $key
     * @param mixed $value
     * @param int|null $expires
     * @return bool
     */
    public function set(mixed $key, mixed $value, int $expires = null): bool
    {
        return setcookie(
            name: $key,
            value: $value,
            expires_or_options: ($expires === null ? time() + config(key: 'cookies.lifetime') : time() + $expires),
            path: config(key: 'cookies.path'),
            domain: config(key: 'cookies.domain'),
            secure: config(key: 'cookies.secure'),
            httponly: config(key: 'cookies.httponly') ?? true,
        );
    }

    /**
     * Retrieves a regular cookie if it is set.
     *
     * @return string Returns cookie if valid
     *
     */
    public function get(string $key): string
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }

        return '';
    }

    /**
     * Set a secure cookie that is saved
     * to the server.
     *
     * @param array $data
     * @return bool
     */
    public function setSecureCookie(array $data): bool
    {
        $token = $this->token();
        $value = $this->buildCookie($token, $data['exp']);

        file_put_contents(
            storage_path('app/cookies/cookie.' . $token),
            json_encode($data, JSON_PRETTY_PRINT)
        );

        return setcookie(
            name: $data['key'],
            value: $value,
            expires_or_options: $data['exp'],
            path: config(key: 'cookies.path'),
            domain: config(key: 'cookies.domain'),
            secure: config(key: 'cookies.secure'),
            httponly: config(key: 'cookies.httponly') ?? true,
        );
    }

    public function getSecureCookie($key)
    {
        $file = storage_path('app/cookies/cookie.' . $this->getCookieVars($key, 'data'));
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file));
            return $data;
        }
        return false;
    }

    /**
     * Unset the cookie
     *
     * @param $key
     * @return bool
     */
    public function remove($key): bool
    {
        return setcookie(
            name: $key,
            value: '',
            expires_or_options: time() - (432000 + config(key: 'cookies.lifetime')),
            path: config(key: 'cookies.path'),
            domain: config(key: 'cookies.domain'),
            secure: config(key: 'cookies.secure'),
            httponly: config(key: 'cookies.httponly') ?? true,
        );
    }

    /**
     * Generates a hardened cookie string with digest.
     *
     * @param mixed $data Cookie value: e.g. random token or hash
     */
    public function buildCookie(mixed $data, mixed $expires): string
    {
        $string = sprintf("exp=%s&data=%s", urlencode((string) $expires), urlencode((string) $data));
        $mac = hash_hmac(config(key: 'cookies.crypt'), $string, config(key: 'cookies.secret_key'));
        return $string . '&digest=' . urlencode($mac);
    }

    /**
     * Extracts data from the cookie string.
     *
     * @param string $key
     * @param mixed $str
     * @return string
     */
    public function getCookieVars(string $key, mixed $str): string
    {
        $vars = [];
        parse_str($_COOKIE[$key], $vars);
        return $vars[$str];
    }

    /**
     * Extracts the data from the cookie string.
     * This does not verify the cookie! This is just so you can get the token.
     *
     * @return string Cookie data var
     * */
    public function getCookieData($key): string
    {
        return $this->getCookieVars($key, 'data');
    }

    /**
     * Verifies the expiry and MAC for the cookie
     *
     * @param string $key String from the client
     * @return bool
     */
    public function verifySecureCookie(string $key): bool
    {
        $file = storage_path('app/cookies/cookie.' . $this->getCookieVars($key, 'data'));
        $data = $this->getSecureCookie($key);
        /**
         * If the cookie exists, and it is expired, delete it
         * from the server side.
         */
        if ($data && $data->exp < time()) {
            unlink($file);
        }

        if ($this->getCookieVars($key, 'exp') === null || $this->getCookieVars($key, 'exp') < time()) {
            // The cookie has expired
            return false;
        }

        $mac = sprintf(
            "exp=%s&data=%s",
            urlencode($this->getCookieVars($key, 'exp')),
            urlencode($this->getCookieVars($key, 'data'))
        );
        $hash = hash_hmac(config(key: 'cookies.crypt'), $mac, config(key: 'cookies.secret_key'));

        if (!hash_equals($this->getCookieVars($key, 'digest'), $hash)) {
            // The cookie has been compromised
            return false;
        }

        return true;
    }
}
