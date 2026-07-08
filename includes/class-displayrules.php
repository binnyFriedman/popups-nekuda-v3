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

    // URL match types
    public const URL_STARTS_WITH = 'starts_with';
    public const URL_ENDS_WITH   = 'ends_with';
    public const URL_EXACT       = 'exact';
    public const URL_CONTAINS    = 'contains';

    /** @var string[] */
    public const URL_MATCH_TYPES = [
        self::URL_STARTS_WITH,
        self::URL_ENDS_WITH,
        self::URL_EXACT,
        self::URL_CONTAINS,
    ];

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
     * Evaluate content and URL include/exclude rules together
     *
     * @param array $include       Content include rules
     * @param array $exclude       Content exclude rules
     * @param array $url_include   URL include rules [{type, value}, ...]
     * @param array $url_exclude   URL exclude rules [{type, value}, ...]
     * @param array $context       Current page context (must include 'url' for URL rules)
     * @return bool
     */
    public static function evaluate(
        array $include,
        array $exclude,
        array $url_include,
        array $url_exclude,
        array $context
    ): bool {
        $has_include = !empty($include) || !empty($url_include);
        $url = $context['url'] ?? '';

        if ($has_include) {
            $content_match = !empty($include) && self::matches_any($include, $context);
            $url_match = !empty($url_include) && self::url_matches_any($url_include, $url);

            if (!$content_match && !$url_match) {
                return false;
            }
        }

        if (!empty($exclude) && self::matches_any($exclude, $context)) {
            return false;
        }

        if (!empty($url_exclude) && self::url_matches_any($url_exclude, $url)) {
            return false;
        }

        return true;
    }

    /**
     * Check if any URL rule matches the given URL (OR logic)
     *
     * @param array  $rules URL rules [{type, value}, ...]
     * @param string $url   Full request URL
     * @return bool
     */
    public static function url_matches_any(array $rules, string $url): bool {
        foreach ($rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $type = $rule['type'] ?? '';
            $value = $rule['value'] ?? '';

            if (self::url_matches($type, $value, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a single URL rule matches
     *
     * @param string $type  Match type (starts_with, ends_with, exact, contains)
     * @param string $value Rule value
     * @param string $url   Full request URL
     * @return bool
     */
    public static function url_matches(string $type, string $value, string $url): bool {
        if (!in_array($type, self::URL_MATCH_TYPES, true)) {
            return false;
        }

        $value = trim($value);
        $url = trim($url);

        if ($value === '' || $url === '') {
            return false;
        }

        $value_lower = strtolower($value);
        $url_lower = strtolower($url);

        return match ($type) {
            self::URL_STARTS_WITH => str_starts_with($url_lower, $value_lower),
            self::URL_ENDS_WITH   => str_ends_with($url_lower, $value_lower),
            self::URL_EXACT       => $url_lower === $value_lower,
            self::URL_CONTAINS    => str_contains($url_lower, $value_lower),
            default               => false,
        };
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
        $host = $_SERVER['HTTP_HOST'] ?? wp_parse_url(home_url(), PHP_URL_HOST);
        $scheme = is_ssl() ? 'https' : 'http';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        $context = [
            'is_front_page' => is_front_page(),
            'is_home'       => is_home() && !is_front_page(),
            'post_id'       => null,
            'post_type'     => null,
            'terms'         => [],
            'url'           => $scheme . '://' . $host . $request_uri,
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