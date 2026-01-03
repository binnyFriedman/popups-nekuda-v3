<?php
/**
 * PHPUnit tests for DisplayRules class
 * 
 * Tests the rule matching logic with various include/exclude scenarios.
 * Uses context arrays instead of WordPress globals for testability.
 */

namespace PopupsNekuda\Tests;

use PopupsNekuda\DisplayRules;
use WP_UnitTestCase;

class Test_Display_Rules extends WP_UnitTestCase {

    /**
     * Empty include = show everywhere
     */
    public function test_empty_include_shows_everywhere() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        $this->assertTrue(DisplayRules::passes([], [], $context));
    }

    /**
     * Include specific post - matches
     */
    public function test_include_specific_post_matches() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        $this->assertTrue(DisplayRules::passes(['post:42'], [], $context));
    }

    /**
     * Include specific post - no match
     */
    public function test_include_specific_post_no_match() {
        $context = ['post_id' => 99, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes(['post:42'], [], $context));
    }

    /**
     * Include homepage - matches
     */
    public function test_include_homepage_matches() {
        $context = ['is_front_page' => true, 'post_id' => 2];
        $this->assertTrue(DisplayRules::passes(['special:home'], [], $context));
    }

    /**
     * Include homepage - not on homepage
     */
    public function test_include_homepage_no_match() {
        $context = ['is_front_page' => false, 'post_id' => 42];
        $this->assertFalse(DisplayRules::passes(['special:home'], [], $context));
    }

    /**
     * Exclude overrides empty include
     */
    public function test_exclude_overrides_empty_include() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes([], ['post:42'], $context));
    }

    /**
     * Include post type - matches
     */
    public function test_include_post_type_matches() {
        $context = ['post_id' => 100, 'post_type' => 'car'];
        $this->assertTrue(DisplayRules::passes(['post_type:car'], [], $context));
    }

    /**
     * Include post type - wrong type
     */
    public function test_include_post_type_no_match() {
        $context = ['post_id' => 100, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes(['post_type:car'], [], $context));
    }

    /**
     * Include taxonomy term - matches
     */
    public function test_include_taxonomy_term_matches() {
        $context = [
            'post_id'   => 100,
            'post_type' => 'post',
            'terms'     => ['category' => [5, 8]]
        ];
        $this->assertTrue(DisplayRules::passes(['term:category:5'], [], $context));
    }

    /**
     * Include taxonomy term - not in term
     */
    public function test_include_taxonomy_term_no_match() {
        $context = [
            'post_id'   => 100,
            'post_type' => 'post',
            'terms'     => ['category' => [99]]
        ];
        $this->assertFalse(DisplayRules::passes(['term:category:5'], [], $context));
    }

    /**
     * Include taxonomy term - taxonomy not present
     */
    public function test_include_taxonomy_term_taxonomy_not_present() {
        $context = [
            'post_id'   => 100,
            'post_type' => 'post',
            'terms'     => []
        ];
        $this->assertFalse(DisplayRules::passes(['term:category:5'], [], $context));
    }

    /**
     * Multiple include rules - OR logic (first matches)
     */
    public function test_multiple_includes_or_logic_first_matches() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        $this->assertTrue(DisplayRules::passes(['post:42', 'post:99'], [], $context));
    }

    /**
     * Multiple include rules - OR logic (second matches)
     */
    public function test_multiple_includes_or_logic_second_matches() {
        $context = ['post_id' => 99, 'post_type' => 'page'];
        $this->assertTrue(DisplayRules::passes(['post:42', 'post:99'], [], $context));
    }

    /**
     * Multiple include rules - OR logic (none match)
     */
    public function test_multiple_includes_or_logic_none_match() {
        $context = ['post_id' => 1, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes(['post:42', 'post:99'], [], $context));
    }

    /**
     * Include type, exclude specific post
     */
    public function test_include_type_exclude_specific_post() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes(
            ['post_type:page'],
            ['post:42'],
            $context
        ));
    }

    /**
     * Include type, exclude term - post has term
     */
    public function test_include_type_exclude_term() {
        $context = [
            'post_id'   => 42,
            'post_type' => 'page',
            'terms'     => ['category' => [5]]
        ];
        $this->assertFalse(DisplayRules::passes(
            ['post_type:page'],
            ['term:category:5'],
            $context
        ));
    }

    /**
     * Include type, exclude term - post does not have term
     */
    public function test_include_type_exclude_term_not_excluded() {
        $context = [
            'post_id'   => 42,
            'post_type' => 'page',
            'terms'     => ['category' => [99]]
        ];
        $this->assertTrue(DisplayRules::passes(
            ['post_type:page'],
            ['term:category:5'],
            $context
        ));
    }

    /**
     * Blog page special rule - matches
     */
    public function test_include_blog_page_matches() {
        $context = ['is_home' => true, 'post_id' => null];
        $this->assertTrue(DisplayRules::passes(['special:blog'], [], $context));
    }

    /**
     * Blog page special rule - not on blog page
     */
    public function test_include_blog_page_no_match() {
        $context = ['is_home' => false, 'post_id' => 42];
        $this->assertFalse(DisplayRules::passes(['special:blog'], [], $context));
    }

    /**
     * Multiple excludes - OR logic (any exclude hides)
     */
    public function test_multiple_excludes_or_logic() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes([], ['post:42', 'post:99'], $context));
    }

    /**
     * Include homepage OR specific post type
     */
    public function test_include_homepage_or_post_type() {
        // On homepage
        $context1 = ['is_front_page' => true, 'post_id' => 2, 'post_type' => 'page'];
        $this->assertTrue(DisplayRules::passes(['special:home', 'post_type:car'], [], $context1));

        // On car post
        $context2 = ['is_front_page' => false, 'post_id' => 100, 'post_type' => 'car'];
        $this->assertTrue(DisplayRules::passes(['special:home', 'post_type:car'], [], $context2));

        // On regular page (neither)
        $context3 = ['is_front_page' => false, 'post_id' => 50, 'post_type' => 'page'];
        $this->assertFalse(DisplayRules::passes(['special:home', 'post_type:car'], [], $context3));
    }

    /**
     * Invalid rule format is ignored
     */
    public function test_invalid_rule_format_ignored() {
        $context = ['post_id' => 42, 'post_type' => 'page'];
        // Invalid rule should not match
        $this->assertFalse(DisplayRules::passes(['invalid:rule'], [], $context));
    }

    /**
     * Malformed term rule (missing parts) is ignored
     */
    public function test_malformed_term_rule_ignored() {
        $context = [
            'post_id'   => 42,
            'post_type' => 'page',
            'terms'     => ['category' => [5]]
        ];
        // term:category (missing term_id) should not match
        $this->assertFalse(DisplayRules::passes(['term:category'], [], $context));
    }

    /**
     * Empty context with empty rules shows everywhere
     */
    public function test_empty_context_empty_rules() {
        $this->assertTrue(DisplayRules::passes([], [], []));
    }

    /**
     * Include all cars, exclude specific car
     */
    public function test_include_post_type_exclude_specific() {
        // Car that's not excluded
        $context1 = ['post_id' => 100, 'post_type' => 'car'];
        $this->assertTrue(DisplayRules::passes(['post_type:car'], ['post:50'], $context1));

        // Car that IS excluded
        $context2 = ['post_id' => 50, 'post_type' => 'car'];
        $this->assertFalse(DisplayRules::passes(['post_type:car'], ['post:50'], $context2));
    }
}