<?php

declare(strict_types=1);

namespace App\Domain\Content\Query\Trait;

use function Codefy\Framework\Helpers\config;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\purify_html;

trait PopulateContentQueryAware
{
    /**
     * Populate an array of values from result query.
     *
     * @param array|null $data
     * @return array|null
     */
    private function populate(?array $data = []): ?array
    {
        if (config(key: 'cms.relative_url') === 'contenttype') {
            $relativeUrl = $data['content_type'] . '/' . $data['content_slug'] . '/';
        } else {
            $relativeUrl = $data['content_slug'] . '/';
        }

        return [
            'id' => esc_html(string: $data['content_id']) ?? null,
            'slug' => esc_html(string: $data['content_slug']) ?? null,
            'title' => esc_html(string: $data['content_title']) ?? null,
            'body' => purify_html($data['content_body']) ?? null,
            'author' => esc_html(string: $data['content_author']) ?? null,
            'contentType' => esc_html(string: $data['content_type']) ?? null,
            'parent' => isset($data['content_parent']) ? esc_html(string: $data['content_parent']) : null,
            'sidebar' => esc_html(string: (string) $data['content_sidebar']) ?? null,
            'showInMenu' => esc_html(string: (string) $data['content_show_in_menu']) ?? null,
            'showInSearch' => esc_html(string: (string) $data['content_show_in_search']) ?? null,
            'relativeUrl' => esc_html(string: $relativeUrl) ?? null,
            'featuredImage' => esc_html(string: $data['content_featured_image']) ?? null,
            'status' => esc_html(string: $data['content_status']) ?? null,
            'created' => esc_html(string: $data['content_created']) ?? null,
            'createdGmt' => esc_html(string: $data['content_created_gmt']) ?? null,
            'published' => esc_html(string: $data['content_published']) ?? null,
            'publishedGmt' => esc_html(string: $data['content_published_gmt']) ?? null,
            'modified' => isset($data['content_modified']) ? esc_html(string: $data['content_modified']) : null,
            'modifiedGmt' => isset($data['content_modified_gmt']) ?
                    esc_html(string: $data['content_modified_gmt']) : null,
        ];
    }
}
