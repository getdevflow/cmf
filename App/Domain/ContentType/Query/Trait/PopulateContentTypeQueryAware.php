<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Query\Trait;

use function Qubus\Security\Helpers\esc_html;

trait PopulateContentTypeQueryAware
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
            'id' => esc_html(string: $data['content_type_id']) ?? null,
            'title' => esc_html(string: $data['content_type_title']) ?? null,
            'slug' => esc_html(string: $data['content_type_slug']) ?? null,
            'description' => esc_html(string: $data['content_type_description']) ?? null,
        ];
    }
}
