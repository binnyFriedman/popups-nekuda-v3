<?php
/**
 * Display rules evaluation logic
 * 
 * Provides testable static methods for checking if a popup should display
 * on the current page based on include/exclude rules.
 */

namespace PopupsNekuda;

if (!defined('ABSPATH')) {
    exit;
}

class DisplayRules {

    // Rule prefixes
    public const PREFIX_SPECIAL   = 'special';
    public const PREFIX_POST      = 'post';
    public const PREFIX_POST_TYPE = 'post_type';
    public const PREFIX_TERM      = 'term';

    // Special page identifiers
    public const SPECIAL_HOME = 'home';
    public const SPECIAL_BLOG = 'blog';

    /**
     * Check if rules pass for given context
     * 
     * @param array $include Include rules (empty = show everywhere)
     * @param array $exclude Exclude rules (runs after include)
     * @param array $context Current page context
     * @return bool
     */
    public static function passes(array $include, array $exclude, array $context): bool {
        // Empty include = show everywhere
        if (!empty($include) && !self::matches_any($include, $context)) {
            return false;
        }

        // Exclude overrides
        if (!empty($exclude) && self::matches_any($exclude, $context)) {
            return false;
        }

        return true;
    }

    /**
     * Check if any rule matches the context (OR logic)
     * 
     * @param array $rules Array of rule strings
     * @param array $context Current page context
     * @return bool
     */
    public static function matches_any(array $rules, array $context): bool {
        foreach ($rules as $rule) {
            if (self::matches_rule($rule, $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a rule string from prefix and value(s)
     * 
     * @param string $prefix Rule prefix constant (POPUP_RULE_*)
     * @param string|int ...$parts Additional parts (e.g., taxonomy, term_id)
     * @return string
     */
    public static function make_rule(string $prefix, ...$parts): string {
        return $prefix . ':' . implode(':', $parts);
    }

    /**
     * Parse a rule string into prefix and parts
     * 
     * @param string $rule Rule string
     * @return array{prefix: string, parts: array}
     */
    public static function parse_rule(string $rule): array {
        $segments = explode(':', $rule);
        return [
            'prefix' => $segments[0] ?? '',
            'parts'  => array_slice($segments, 1),
        ];
    }

    /**
     * Check if a single rule matches the context
     * 
     * Rule formats:
     * - special:home     - Homepage
     * - special:blog     - Blog page
     * - post:123         - Specific post/page by ID
     * - post_type:car    - All posts of type 'car'
     * - term:category:5  - Posts with term ID 5 in taxonomy 'category'
     * 
     * @param string $rule Rule string
     * @param array $context Current page context
     * @return bool
     */
    public static function matches_rule(string $rule, array $context): bool {
        $parsed = self::parse_rule($rule);
        $prefix = $parsed['prefix'];
        $parts = $parsed['parts'];

        // Special pages
        if ($prefix === self::PREFIX_SPECIAL) {
            $page = $parts[0] ?? '';
            if ($page === self::SPECIAL_HOME) {
                return !empty($context['is_front_page']);
            }
            if ($page === self::SPECIAL_BLOG) {
                return !empty($context['is_home']);
            }
            return false;
        }

        // Specific post by ID
        if ($prefix === self::PREFIX_POST) {
            $id = (int) ($parts[0] ?? 0);
            return isset($context['post_id']) && $context['post_id'] === $id;
        }

        // Post type
        if ($prefix === self::PREFIX_POST_TYPE) {
            $type = $parts[0] ?? '';
            return isset($context['post_type']) && $context['post_type'] === $type;
        }

        // Taxonomy term (format: term:taxonomy:term_id)
        if ($prefix === self::PREFIX_TERM) {
            if (count($parts) !== 2) {
                return false;
            }

            $taxonomy = $parts[0];
            $term_id = (int) $parts[1];

            if (!isset($context['terms'][$taxonomy])) {
                return false;
            }

            return in_array($term_id, $context['terms'][$taxonomy], true);
        }

        return false;
    }

    /**
     * Build context array from current WordPress query
     * @return array Context array for rule matching
     */
    public static function build_context(): array {
        $context = [
            'is_front_page' => is_front_page(),
            'is_home'       => is_home() && !is_front_page(),
            'post_id'       => null,
            'post_type'     => null,
            'terms'         => [],
        ];

        if (is_singular()) {
            $context['post_id'] = get_queried_object_id();
            $context['post_type'] = get_post_type();

            // Get all terms for this post
            $taxonomies = get_object_taxonomies($context['post_type']);
            foreach ($taxonomies as $taxonomy) {
                $terms = get_the_terms($context['post_id'], $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    $context['terms'][$taxonomy] = wp_list_pluck($terms, 'term_id');
                }
            }
        }

        return $context;
    }
}