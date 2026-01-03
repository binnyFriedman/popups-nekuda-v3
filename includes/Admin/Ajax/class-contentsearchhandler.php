<?php
/**
 * AJAX handler for Select2 content search
 *
 * Returns grouped results (ACF-style) for better organization:
 * - Special (Homepage, Blog)
 * - Post Types (All Pages, All Posts, etc.)
 * - Specific posts grouped by type
 * - Taxonomy terms grouped by taxonomy
 */

namespace PopupsNekuda\Admin\Ajax;

use PopupsNekuda\DisplayRules;

if (!defined('ABSPATH')) {
    exit;
}

class ContentSearchHandler {

    public const ACTION = 'popup_search_content';

    public static function register(): void {
        add_action('wp_ajax_' . self::ACTION, [self::class, 'handle']);
    }

    public static function handle(): void {
        check_ajax_referer('popup_admin_nonce', 'nonce');

        $search = sanitize_text_field($_GET['q'] ?? '');
        $groups = [];

        self::addSpecialPagesGroup($groups, $search);
        self::addPostTypesGroup($groups, $search);
        self::addSpecificPostsGroups($groups, $search);
        self::addTaxonomyTermsGroups($groups, $search);

        wp_send_json(['results' => $groups]);
    }

    private static function addSpecialPagesGroup(array &$groups, string $search): void {
        $children = [];

        if (self::matchesSearch($search, ['homepage', 'home'])) {
            $children[] = [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_SPECIAL, DisplayRules::SPECIAL_HOME),
                'text' => __('Homepage', POPUPS_NEKUDA_TEXT_DOMAIN),
            ];
        }

        if (self::matchesSearch($search, ['blog'])) {
            $children[] = [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_SPECIAL, DisplayRules::SPECIAL_BLOG),
                'text' => __('Blog Page', POPUPS_NEKUDA_TEXT_DOMAIN),
            ];
        }

        if (!empty($children)) {
            $groups[] = ['text' => __('Special', POPUPS_NEKUDA_TEXT_DOMAIN), 'children' => $children];
        }
    }

    private static function addPostTypesGroup(array &$groups, string $search): void {
        $postTypes = get_post_types(['public' => true], 'objects');
        $children = [];

        foreach ($postTypes as $postType) {
            if ($postType->name === 'attachment') {
                continue;
            }

            $typeName = $postType->labels->name;
            if (self::matchesSearch($search, [$typeName, 'all'])) {
                $children[] = [
                    'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_POST_TYPE, $postType->name),
                    'text' => sprintf(__('All %s', POPUPS_NEKUDA_TEXT_DOMAIN), $typeName),
                ];
            }
        }

        if (!empty($children)) {
            $groups[] = ['text' => __('Post Types', POPUPS_NEKUDA_TEXT_DOMAIN), 'children' => $children];
        }
    }

    private static function addSpecificPostsGroups(array &$groups, string $search): void {
        if (empty($search)) {
            return;
        }

        $postTypes = get_post_types(['public' => true], 'objects');
        $posts = get_posts([
            'post_type'      => array_keys($postTypes),
            'post_status'    => 'publish',
            's'              => $search,
            'posts_per_page' => 30,
            'orderby'        => 'relevance',
        ]);

        $postsByType = [];
        foreach ($posts as $post) {
            if ($post->post_type === 'attachment') {
                continue;
            }
            $postsByType[$post->post_type][] = [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_POST, $post->ID),
                'text' => $post->post_title,
            ];
        }

        foreach ($postsByType as $type => $items) {
            $typeObj = get_post_type_object($type);
            $label = $typeObj ? $typeObj->labels->name : ucfirst($type);
            $groups[] = ['text' => $label, 'children' => $items];
        }
    }

    private static function addTaxonomyTermsGroups(array &$groups, string $search): void {
        $taxonomies = get_taxonomies(['public' => true], 'objects');

        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms([
                'taxonomy'   => $taxonomy->name,
                'search'     => $search,
                'number'     => 20,
                'hide_empty' => false,
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $children = array_map(fn($term) => [
                'id'   => DisplayRules::make_rule(DisplayRules::PREFIX_TERM, $taxonomy->name, $term->term_id),
                'text' => $term->name,
            ], $terms);

            if (!empty($children)) {
                $groups[] = ['text' => $taxonomy->labels->name, 'children' => $children];
            }
        }
    }

    /**
     * Check if search term matches any of the keywords (case-insensitive partial match)
     * Made public for unit testing
     *
     * @param string $search Search term (empty = matches everything)
     * @param array $keywords Keywords to match against
     * @return bool True if search is empty or matches any keyword
     */
    public static function matchesSearch(string $search, array $keywords): bool {
        if (empty($search)) {
            return true;
        }

        foreach ($keywords as $keyword) {
            if (stripos($keyword, $search) !== false) {
                return true;
            }
        }

        return false;
    }
}

