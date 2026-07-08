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

describe('Rule Helpers', function () {
    t('make_rule creates special:home', fn () =>
        DisplayRules::make_rule(DisplayRules::PREFIX_SPECIAL, DisplayRules::SPECIAL_HOME) === 'special:home');

    t('make_rule creates post:42', fn () =>
        DisplayRules::make_rule(DisplayRules::PREFIX_POST, 42) === 'post:42');

    t('make_rule creates post_type:product', fn () =>
        DisplayRules::make_rule(DisplayRules::PREFIX_POST_TYPE, 'product') === 'post_type:product');

    t('make_rule creates term:category:5', fn () =>
        DisplayRules::make_rule(DisplayRules::PREFIX_TERM, 'category', 5) === 'term:category:5');

    t('parse_rule extracts prefix and parts from special:home', function () {
        $parsed = DisplayRules::parse_rule('special:home');
        return $parsed['prefix'] === DisplayRules::PREFIX_SPECIAL && $parsed['parts'] === [DisplayRules::SPECIAL_HOME];
    });

    t('parse_rule extracts prefix and parts from term:category:5', function () {
        $parsed = DisplayRules::parse_rule('term:category:5');
        return $parsed['prefix'] === DisplayRules::PREFIX_TERM && $parsed['parts'] === ['category', '5'];
    });
});

describe('URL Matching', function () {
    $url = 'https://Site.com/Models/XC90/?Campaign=Summer';

    t('starts_with - matches', fn () =>
        DisplayRules::url_matches(DisplayRules::URL_STARTS_WITH, 'https://site.com/models/', $url));

    t('starts_with - no match', fn () =>
        !DisplayRules::url_matches(DisplayRules::URL_STARTS_WITH, 'https://other.com/', $url));

    t('ends_with - matches', fn () =>
        DisplayRules::url_matches(DisplayRules::URL_ENDS_WITH, '?campaign=summer', $url));

    t('ends_with - no match', fn () =>
        !DisplayRules::url_matches(DisplayRules::URL_ENDS_WITH, '/other/', $url));

    t('exact - matches (case-insensitive)', fn () =>
        DisplayRules::url_matches(DisplayRules::URL_EXACT, 'https://site.com/models/xc90/?campaign=summer', $url));

    t('exact - no match', fn () =>
        !DisplayRules::url_matches(DisplayRules::URL_EXACT, 'https://site.com/models/xc90/', $url));

    t('contains - matches', fn () =>
        DisplayRules::url_matches(DisplayRules::URL_CONTAINS, '/models/xc90', $url));

    t('contains - no match', fn () =>
        !DisplayRules::url_matches(DisplayRules::URL_CONTAINS, '/v90/', $url));

    t('invalid type returns false', fn () =>
        !DisplayRules::url_matches('invalid', 'https://site.com/', $url));

    t('empty value returns false', fn () =>
        !DisplayRules::url_matches(DisplayRules::URL_CONTAINS, '', $url));

    t('trims whitespace', fn () =>
        DisplayRules::url_matches(DisplayRules::URL_STARTS_WITH, '  https://site.com/  ', '  ' . $url . '  '));
});

describe('evaluate (content + URL rules)', function () {
    $ctx = ['url' => 'https://site.com/models/xc90/'];

    t('empty rules = show everywhere', fn () =>
        DisplayRules::evaluate([], [], [], [], $ctx));

    t('url include only - matches', fn () =>
        DisplayRules::evaluate([], [], [['type' => 'starts_with', 'value' => 'https://site.com/models/']], [], $ctx));

    t('url include only - no match', fn () =>
        !DisplayRules::evaluate([], [], [['type' => 'starts_with', 'value' => 'https://other.com/']], [], $ctx));

    t('url exclude overrides url include', fn () =>
        !DisplayRules::evaluate(
            [],
            [],
            [['type' => 'starts_with', 'value' => 'https://site.com/']],
            [['type' => 'contains', 'value' => '/models/']],
            $ctx
        ));

    t('content include OR url include - content matches', fn () =>
        DisplayRules::evaluate(
            ['post:42'],
            [],
            [['type' => 'starts_with', 'value' => 'https://other.com/']],
            [],
            array_merge($ctx, ['post_id' => 42])
        ));

    t('content include OR url include - url matches', fn () =>
        DisplayRules::evaluate(
            ['post:99'],
            [],
            [['type' => 'contains', 'value' => '/models/']],
            [],
            $ctx
        ));

    t('content include OR url include - neither matches', fn () =>
        !DisplayRules::evaluate(
            ['post:99'],
            [],
            [['type' => 'starts_with', 'value' => 'https://other.com/']],
            [],
            $ctx
        ));

    t('url exclude only - matches hides', fn () =>
        !DisplayRules::evaluate([], [], [], [['type' => 'contains', 'value' => 'xc90']], $ctx));

    t('url exclude only - no match shows', fn () =>
        DisplayRules::evaluate([], [], [], [['type' => 'contains', 'value' => 'v90']], $ctx));
});

});
