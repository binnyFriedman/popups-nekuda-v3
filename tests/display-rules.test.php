#!/usr/bin/env php
<?php
/**
 * DisplayRules tests
 *
 * Run: php tests/display-rules.test.php
 */

define('ABSPATH', '/fake/path/');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../includes/class-displayrules.php';

use PopupsNekuda\DisplayRules;

// Helpers

function passes(array $include, array $exclude, array $ctx): bool {
    return DisplayRules::passes($include, $exclude, $ctx);
}

function singular(string $postType, int $id): array {
    return ['post_id' => $id, 'post_type' => $postType];
}

function homepage(): array {
    return ['is_front_page' => true, 'post_id' => 2, 'post_type' => 'page'];
}

function blogArchive(): array {
    return ['is_home' => true, 'post_id' => null];
}

function withTerms(array $ctx, string $taxonomy, array $termIds): array {
    $ctx['terms'][$taxonomy] = $termIds;
    return $ctx;
}

// Tests

describe('DisplayRules', function () {

describe('Empty Rules', function () {
    t('empty include = show everywhere', fn () => passes([], [], singular('page', 42)));
    t('empty rules + empty context = show', fn () => passes([], [], []));
});

describe('Specific Post/Page by ID', function () {
    t('include post:ID - matches', fn () => passes(['post:42'], [], singular('page', 42)));
    t('include post:ID - different ID', fn () => !passes(['post:42'], [], singular('page', 99)));
    t('include post:ID - works for any post type', fn () => passes(['post:100'], [], singular('product', 100)));
});

describe('Special: Homepage', function () {
    t('on homepage', fn () => passes(['special:home'], [], homepage()));
    t('not on homepage', fn () => !passes(['special:home'], [], singular('page', 42)));
});

describe('Special: Blog Archive', function () {
    t('on blog archive', fn () => passes(['special:blog'], [], blogArchive()));
    t('not on blog archive', fn () => !passes(['special:blog'], [], singular('post', 42)));
});

describe('Post Type', function () {
    t('matches same type', fn () => passes(['post_type:product'], [], singular('product', 100)));
    t('different type', fn () => !passes(['post_type:product'], [], singular('page', 100)));
    t('standard pages', fn () => passes(['post_type:page'], [], singular('page', 5)));
    t('standard posts', fn () => passes(['post_type:post'], [], singular('post', 10)));
});

describe('Taxonomy Terms', function () {
    t('matches term', fn () =>
        passes(['term:category:5'], [], withTerms(singular('post', 100), 'category', [5, 8])));

    t('post has different term', fn () =>
        !passes(['term:category:5'], [], withTerms(singular('post', 100), 'category', [99])));

    t('taxonomy not on post', fn () =>
        !passes(['term:category:5'], [], singular('post', 100)));

    t('custom taxonomy', fn () =>
        passes(['term:product_cat:12'], [], withTerms(singular('product', 50), 'product_cat', [12, 15])));
});

describe('Multiple Include Rules (OR)', function () {
    t('first rule matches', fn () => passes(['post:42', 'post:99'], [], singular('page', 42)));
    t('second rule matches', fn () => passes(['post:42', 'post:99'], [], singular('page', 99)));
    t('no rule matches', fn () => !passes(['post:42', 'post:99'], [], singular('page', 1)));
    t('mixed rule types', fn () => passes(['special:home', 'post_type:product'], [], singular('product', 50)));
});

describe('Exclude Rules', function () {
    t('exclude overrides empty include', fn () => !passes([], ['post:42'], singular('page', 42)));
    t('multiple excludes (OR)', fn () => !passes([], ['post:42', 'post:99'], singular('page', 42)));
    t('no match = show', fn () => passes([], ['post:99'], singular('page', 42)));
});

describe('Include + Exclude Combined', function () {
    t('include type, exclude specific ID', fn () =>
        !passes(['post_type:page'], ['post:42'], singular('page', 42)));

    t('include type, exclude ID - other ID shows', fn () =>
        passes(['post_type:page'], ['post:42'], singular('page', 100)));

    t('include type, exclude term - has term', fn () =>
        !passes(['post_type:post'], ['term:category:5'], withTerms(singular('post', 42), 'category', [5])));

    t('include type, exclude term - different term', fn () =>
        passes(['post_type:post'], ['term:category:5'], withTerms(singular('post', 42), 'category', [99])));
});

describe('Complex Scenarios', function () {
    t('homepage OR any product - on homepage', fn () =>
        passes(['special:home', 'post_type:product'], [], homepage()));

    t('homepage OR any product - on product', fn () =>
        passes(['special:home', 'post_type:product'], [], singular('product', 100)));

    t('homepage OR any product - on regular page', fn () =>
        !passes(['special:home', 'post_type:product'], [], singular('page', 50)));

    t('all posts except category 5', fn () =>
        passes(['post_type:post'], ['term:category:5'], withTerms(singular('post', 10), 'category', [8])));
});

describe('Invalid Rules', function () {
    t('invalid prefix ignored', fn () => !passes(['invalid:rule'], [], singular('page', 42)));

    t('malformed term (missing term_id)', fn () =>
        !passes(['term:category'], [], withTerms(singular('page', 42), 'category', [5])));

    t('empty string ignored', fn () => !passes([''], [], singular('page', 42)));
});

});