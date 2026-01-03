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
        // Special pages
        if ($rule === 'special:home') {
            return !empty($context['is_front_page']);
        }

        if ($rule === 'special:blog') {
            return !empty($context['is_home']);
        }

        // Specific post by ID
        if (str_starts_with($rule, 'post:')) {
            $id = (int) substr($rule, 5);
            return isset($context['post_id']) && $context['post_id'] === $id;
        }

        // Post type
        if (str_starts_with($rule, 'post_type:')) {
            $type = substr($rule, 10);
            return isset($context['post_type']) && $context['post_type'] === $type;
        }

        // Taxonomy term (format: term:taxonomy:term_id)
        if (str_starts_with($rule, 'term:')) {
            $parts = explode(':', $rule);
            if (count($parts) !== 3) {
                return false;
            }

            $taxonomy = $parts[1];
            $term_id = (int) $parts[2];

            if (!isset($context['terms'][$taxonomy])) {
                return false;
            }

            return in_array($term_id, $context['terms'][$taxonomy], true);
        }

        return false;
    }

    /**
     * Build context array from current WordPress query
     * 
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

