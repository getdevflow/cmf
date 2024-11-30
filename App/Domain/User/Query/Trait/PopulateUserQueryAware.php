<?php

declare(strict_types=1);

namespace App\Domain\User\Query\Trait;

use function Qubus\Security\Helpers\esc_html;
use function Qubus\Support\Helpers\is_null__;

trait PopulateUserQueryAware
{
    /**
     * Populate an array of values from result query.
     *
     * @param array|null $data
     * @return array|null
     */
    private function populate(?array $data = []): ?array
    {
        return [
            'id' => esc_html(string: $data['user_id']) ?? null,
            'login' => esc_html(string: $data['user_login']) ?? null,
            'token' => esc_html(string: $data['user_token']) ?? null,
            'fname' => esc_html(string: $data['user_fname']) ?? null,
            'mname' => esc_html(string: $data['user_mname']) ?? null,
            'lname' => esc_html($data['user_lname']) ?? null,
            'email' => esc_html(string: $data['user_email']) ?? null,
            'pass' => esc_html(string: $data['user_pass']) ?? null,
            'url' => esc_html(string: $data['user_url']) ?? null,
            'role' => esc_html(string: $data['role']) ?? null,
            'timezone' => esc_html(string: $data['user_timezone']) ?? null,
            'dateFormat' => esc_html(string: $data['user_date_format']) ?? null,
            'timeFormat' => esc_html(string: $data['user_time_format']) ?? null,
            'locale' => esc_html(string: $data['user_locale']) ?? null,
            'registered' => isset($data['user_registered']) ? esc_html(string: $data['user_registered']) : null,
            'modified' => isset($data['user_modified']) ? esc_html(string: $data['user_modified']) : null,
            'activationKey' => is_null__($data['user_activation_key'])
                    ? '' :
                    esc_html(string: $data['user_activation_key']),
        ];
    }
}
