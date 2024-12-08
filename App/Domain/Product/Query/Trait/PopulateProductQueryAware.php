<?php

declare(strict_types=1);

namespace App\Domain\Product\Query\Trait;

use function Codefy\Framework\Helpers\config;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\purify_html;

trait PopulateProductQueryAware
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
            'id' => esc_html(string: $data['product_id']) ?? null,
            'slug' => esc_html(string: $data['product_slug']) ?? null,
            'title' => esc_html(string: $data['product_title']) ?? null,
            'body' => purify_html($data['product_body']) ?? null,
            'author' => isset($data['product_author']) ? esc_html(string: $data['product_author']) : null,
            'sku' => isset($data['product_sku']) ? esc_html(string: (string) $data['product_sku']) : null,
            'price' => esc_html(string: (string) $data['product_price']) ?? null,
            'currency' => esc_html(string: (string) $data['product_currency']) ?? null,
            'purchaseUrl' => isset($data['product_purchase_url']) ?
                    esc_html(string: (string) $data['product_purchase_url']) :
                    null,
            'showInMenu' => esc_html(string: (string) $data['product_show_in_menu']) ?? null,
            'showInSearch' => esc_html(string: (string) $data['product_show_in_search']) ?? null,
            'featuredImage' => isset($data['product_featured_image']) ?
                    esc_html(string: $data['product_featured_image']) :
                    null,
            'status' => esc_html(string: $data['product_status']) ?? null,
            'created' => esc_html(string: $data['product_created']) ?? null,
            'createdGmt' => esc_html(string: $data['product_created_gmt']) ?? null,
            'published' => esc_html(string: $data['product_published']) ?? null,
            'publishedGmt' => esc_html(string: $data['product_published_gmt']) ?? null,
            'modified' => isset($data['product_modified']) ? esc_html(string: $data['product_modified']) : null,
            'modifiedGmt' => isset($data['product_modified_gmt']) ?
                    esc_html(string: $data['product_modified_gmt']) : null,
        ];
    }
}
