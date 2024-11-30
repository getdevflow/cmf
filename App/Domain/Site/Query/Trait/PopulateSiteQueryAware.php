<?php

declare(strict_types=1);

namespace App\Domain\Site\Query\Trait;

use function Qubus\Security\Helpers\esc_html;

trait PopulateSiteQueryAware
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
            'id' => esc_html(string: $data['site_id']) ?? null,
            'key' => esc_html(string: $data['site_key']) ?? null,
            'name' => esc_html(string: $data['site_name']) ?? null,
            'slug' => esc_html(string: $data['site_slug']) ?? null,
            'domain' => esc_html(string: $data['site_domain']) ?? null,
            'mapping' => isset($data['site_mapping']) ? esc_html(string: $data['site_mapping']) : null,
            'path' => esc_html(string: $data['site_path']) ?? null,
            'owner' => esc_html(string: $data['site_owner']) ?? null,
            'status' => esc_html(string: $data['site_status']) ?? null,
            'registered' => isset($data['site_registered']) ? esc_html(string: $data['site_registered']) : null,
            'modified' => isset($data['site_modified']) ? esc_html(string: $data['site_modified']) : null,
        ];
    }
}
