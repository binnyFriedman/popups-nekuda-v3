#!/usr/bin/env php
<?php
/**
 * Fields tests
 *
 * Run: php tests/fields.test.php
 */

define('ABSPATH', '/fake/path/');
define('POPUPS_NEKUDA_TEXT_DOMAIN', 'popups-nekuda');

// Mock WordPress i18n function
function __($text, $domain = 'default') {
    return $text;
}

function sprintf_alt(...$args) {
    return call_user_func_array('sprintf', $args);
}

// Mock post type objects storage
$__mock_post_types = [];
$__mock_posts = [];
$__mock_terms = [];
$__mock_taxonomies = [];

/**
 * Mock get_post_type_object
 */
function get_post_type_object($post_type) {
    global $__mock_post_types;
    return $__mock_post_types[$post_type] ?? null;
}

/**
 * Mock get_post
 */
function get_post($id) {
    global $__mock_posts;
    return $__mock_posts[$id] ?? null;
}

/**
 * Mock get_term
 */
function get_term($term_id, $taxonomy) {
    global $__mock_terms;
    $key = "{$taxonomy}:{$term_id}";
    return $__mock_terms[$key] ?? null;
}

/**
 * Mock get_taxonomy
 */
function get_taxonomy($taxonomy) {
    global $__mock_taxonomies;
    return $__mock_taxonomies[$taxonomy] ?? null;
}

/**
 * Mock is_wp_error
 */
function is_wp_error($thing) {
    return $thing instanceof WP_Error;
}

/**
 * Simple WP_Error mock
 */
class WP_Error {
    public function __construct($code = '', $message = '') {}
}

/**
 * Helper to create mock post type object
 */
function mock_post_type(string $name, string $singular, string $plural): void {
    global $__mock_post_types;
    $__mock_post_types[$name] = (object)[
        'name' => $name,
        'labels' => (object)[
            'name' => $plural,
            'singular_name' => $singular,
        ],
    ];
}

/**
 * Helper to create mock post
 */
function mock_post(int $id, string $title, string $type): void {
    global $__mock_posts;
    $__mock_posts[$id] = (object)[
        'ID' => $id,
        'post_title' => $title,
        'post_type' => $type,
    ];
}

/**
 * Helper to create mock term
 */
function mock_term(string $taxonomy, int $id, string $name): void {
    global $__mock_terms;
    $key = "{$taxonomy}:{$id}";
    $__mock_terms[$key] = (object)[
        'term_id' => $id,
        'name' => $name,
        'taxonomy' => $taxonomy,
    ];
}

/**
 * Helper to create mock taxonomy
 */
function mock_taxonomy(string $name, string $singular, string $plural): void {
    global $__mock_taxonomies;
    $__mock_taxonomies[$name] = (object)[
        'name' => $name,
        'labels' => (object)[
            'name' => $plural,
            'singular_name' => $singular,
        ],
    ];
}

/**
 * Reset all mocks
 */
function reset_mocks(): void {
    global $__mock_post_types, $__mock_posts, $__mock_terms, $__mock_taxonomies;
    $__mock_post_types = [];
    $__mock_posts = [];
    $__mock_terms = [];
    $__mock_taxonomies = [];
}

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../includes/class-displayrules.php';
require_once __DIR__ . '/../includes/class-fields.php';

use PopupsNekuda\Fields;

// Tests

describe('Fields', function () {

describe('getRuleLabel', function () {

    describe('Special Pages', function () {
        t('special:home returns "Homepage"', function () {
            reset_mocks();
            return Fields::getRuleLabel('special:home') === 'Homepage';
        });

        t('special:blog returns "Blog Page"', function () {
            reset_mocks();
            return Fields::getRuleLabel('special:blog') === 'Blog Page';
        });

        t('special:unknown returns original string as fallback', function () {
            reset_mocks();
            return Fields::getRuleLabel('special:unknown') === 'special:unknown';
        });
    });

    describe('Post Types', function () {
        t('post_type:page returns "All Pages" when post type exists', function () {
            reset_mocks();
            mock_post_type('page', 'Page', 'Pages');
            return Fields::getRuleLabel('post_type:page') === 'All Pages';
        });

        t('post_type:post returns "All Posts" when post type exists', function () {
            reset_mocks();
            mock_post_type('post', 'Post', 'Posts');
            return Fields::getRuleLabel('post_type:post') === 'All Posts';
        });

        t('post_type:car returns "All Cars" for custom post type', function () {
            reset_mocks();
            mock_post_type('car', 'Car', 'Cars');
            return Fields::getRuleLabel('post_type:car') === 'All Cars';
        });

        t('post_type:unknown returns original string when post type not found', function () {
            reset_mocks();
            return Fields::getRuleLabel('post_type:unknown') === 'post_type:unknown';
        });
    });

    describe('Specific Posts', function () {
        t('post:123 returns "{title} ({type})" when post exists', function () {
            reset_mocks();
            mock_post(123, 'About Us', 'page');
            mock_post_type('page', 'Page', 'Pages');
            return Fields::getRuleLabel('post:123') === 'About Us (Page)';
        });

        t('post with custom post type shows correct type label', function () {
            reset_mocks();
            mock_post(456, 'Tesla Model S', 'car');
            mock_post_type('car', 'Car', 'Cars');
            return Fields::getRuleLabel('post:456') === 'Tesla Model S (Car)';
        });

        t('post:999 returns "Post #999" when post not found', function () {
            reset_mocks();
            return Fields::getRuleLabel('post:999') === 'Post #999';
        });

        t('post with no post_type object falls back to raw type', function () {
            reset_mocks();
            mock_post(789, 'Test Post', 'custom_type');
            // No mock_post_type call - simulates missing post type object
            return Fields::getRuleLabel('post:789') === 'Test Post (custom_type)';
        });
    });

    describe('Taxonomy Terms', function () {
        t('term:category:5 returns "{term name} ({taxonomy})" when term exists', function () {
            reset_mocks();
            mock_term('category', 5, 'Technology');
            mock_taxonomy('category', 'Category', 'Categories');
            return Fields::getRuleLabel('term:category:5') === 'Technology (Category)';
        });

        t('term:product_cat:10 works for custom taxonomy', function () {
            reset_mocks();
            mock_term('product_cat', 10, 'Electronics');
            mock_taxonomy('product_cat', 'Product Category', 'Product Categories');
            return Fields::getRuleLabel('term:product_cat:10') === 'Electronics (Product Category)';
        });

        t('term:category:999 returns fallback when term not found', function () {
            reset_mocks();
            mock_taxonomy('category', 'Category', 'Categories');
            return Fields::getRuleLabel('term:category:999') === 'term:category:999';
        });

        t('term with no taxonomy object falls back to raw taxonomy name', function () {
            reset_mocks();
            mock_term('custom_tax', 5, 'Term Name');
            // No mock_taxonomy call
            return Fields::getRuleLabel('term:custom_tax:5') === 'Term Name (custom_tax)';
        });

        t('term:category (missing term_id) returns original string', function () {
            reset_mocks();
            return Fields::getRuleLabel('term:category') === 'term:category';
        });
    });

    describe('Invalid Rules', function () {
        t('invalid prefix returns original string', function () {
            reset_mocks();
            return Fields::getRuleLabel('invalid:rule') === 'invalid:rule';
        });

        t('unknown prefix returns original string', function () {
            reset_mocks();
            return Fields::getRuleLabel('foo:bar:baz') === 'foo:bar:baz';
        });

        t('empty string returns empty string', function () {
            reset_mocks();
            return Fields::getRuleLabel('') === '';
        });

        t('string without colon returns original string', function () {
            reset_mocks();
            return Fields::getRuleLabel('norule') === 'norule';
        });
    });

});

});

