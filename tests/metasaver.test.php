#!/usr/bin/env php
<?php
/**
 * MetaSaver tests
 *
 * Run: php tests/metasaver.test.php
 */

define('ABSPATH', '/fake/path/');

// Mock WordPress functions
function sanitize_text_field(string $str): string {
    return trim(strip_tags($str));
}

function wp_kses_post(string $content): string {
    // Simplified version - allows basic HTML tags
    return strip_tags($content, '<p><a><strong><em><img><br><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><span><div>');
}

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../includes/class-displayrules.php';
require_once __DIR__ . '/../includes/Admin/class-metasaver.php';

use PopupsNekuda\Admin\MetaSaver;

// Tests

describe('MetaSaver', function () {

describe('sanitizeRules', function () {

    describe('Valid Rules', function () {
        t('special:home passes through', fn () =>
            MetaSaver::sanitizeRules(['special:home']) === ['special:home']);

        t('special:blog passes through', fn () =>
            MetaSaver::sanitizeRules(['special:blog']) === ['special:blog']);

        t('post:123 passes through', fn () =>
            MetaSaver::sanitizeRules(['post:123']) === ['post:123']);

        t('post_type:page passes through', fn () =>
            MetaSaver::sanitizeRules(['post_type:page']) === ['post_type:page']);

        t('post_type:car passes through', fn () =>
            MetaSaver::sanitizeRules(['post_type:car']) === ['post_type:car']);

        t('term:category:5 passes through', fn () =>
            MetaSaver::sanitizeRules(['term:category:5']) === ['term:category:5']);

        t('term:product_cat:10 passes through', fn () =>
            MetaSaver::sanitizeRules(['term:product_cat:10']) === ['term:product_cat:10']);

        t('multiple valid rules preserved', fn () =>
            MetaSaver::sanitizeRules(['special:home', 'post:123', 'post_type:page']) === ['special:home', 'post:123', 'post_type:page']);
    });

    describe('Invalid Rules Rejected', function () {
        t('invalid:rule rejected', fn () =>
            MetaSaver::sanitizeRules(['invalid:rule']) === []);

        t('foo:bar rejected', fn () =>
            MetaSaver::sanitizeRules(['foo:bar']) === []);

        t('unknown:prefix:value rejected', fn () =>
            MetaSaver::sanitizeRules(['unknown:prefix:value']) === []);

        t('rule without prefix rejected', fn () =>
            MetaSaver::sanitizeRules(['norule']) === []);

        t('mixed valid and invalid - only valid kept', function () {
            $result = MetaSaver::sanitizeRules(['special:home', 'invalid:rule', 'post:42']);
            return $result === ['special:home', 'post:42'];
        });
    });

    describe('Non-String Values Filtered', function () {
        t('integer filtered out', fn () =>
            MetaSaver::sanitizeRules([123, 'special:home']) === ['special:home']);

        t('null filtered out', fn () =>
            MetaSaver::sanitizeRules([null, 'special:home']) === ['special:home']);

        t('array filtered out', fn () =>
            MetaSaver::sanitizeRules([['nested'], 'special:home']) === ['special:home']);

        t('boolean filtered out', fn () =>
            MetaSaver::sanitizeRules([true, false, 'special:home']) === ['special:home']);

        t('object filtered out', fn () =>
            MetaSaver::sanitizeRules([(object)['foo' => 'bar'], 'special:home']) === ['special:home']);

        t('all non-strings returns empty', fn () =>
            MetaSaver::sanitizeRules([123, null, true, []]) === []);
    });

    describe('Empty Strings Filtered', function () {
        t('empty string filtered out', fn () =>
            MetaSaver::sanitizeRules(['', 'special:home']) === ['special:home']);

        t('multiple empty strings filtered', fn () =>
            MetaSaver::sanitizeRules(['', '', 'post:42', '']) === ['post:42']);

        t('only empty strings returns empty', fn () =>
            MetaSaver::sanitizeRules(['', '', '']) === []);
    });

    describe('Duplicates Removed', function () {
        t('duplicate rules removed', fn () =>
            MetaSaver::sanitizeRules(['special:home', 'special:home']) === ['special:home']);

        t('multiple duplicates removed', fn () =>
            MetaSaver::sanitizeRules(['post:42', 'special:home', 'post:42', 'special:home']) === ['post:42', 'special:home']);

        t('duplicates removed preserves first occurrence order', function () {
            $result = MetaSaver::sanitizeRules(['special:blog', 'post:1', 'special:blog', 'post:2', 'post:1']);
            // array_unique preserves original keys, so use array_values for comparison
            return array_values($result) === ['special:blog', 'post:1', 'post:2'];
        });
    });

    describe('Non-Array Input', function () {
        t('string input returns empty array', fn () =>
            MetaSaver::sanitizeRules('special:home') === []);

        t('integer input returns empty array', fn () =>
            MetaSaver::sanitizeRules(123) === []);

        t('null input returns empty array', fn () =>
            MetaSaver::sanitizeRules(null) === []);

        t('object input returns empty array', fn () =>
            MetaSaver::sanitizeRules((object)['foo' => 'bar']) === []);

        t('empty array returns empty array', fn () =>
            MetaSaver::sanitizeRules([]) === []);
    });

});

describe('sanitizeSlides', function () {

    describe('HTML Content Preserved', function () {
        t('paragraph tags preserved', fn () =>
            MetaSaver::sanitizeSlides(['<p>Hello</p>']) === ['<p>Hello</p>']);

        t('anchor tags preserved', fn () =>
            MetaSaver::sanitizeSlides(['<a href="http://example.com">Link</a>']) === ['<a href="http://example.com">Link</a>']);

        t('strong tags preserved', fn () =>
            MetaSaver::sanitizeSlides(['<strong>Bold</strong>']) === ['<strong>Bold</strong>']);

        t('em tags preserved', fn () =>
            MetaSaver::sanitizeSlides(['<em>Italic</em>']) === ['<em>Italic</em>']);

        t('img tags preserved', fn () =>
            MetaSaver::sanitizeSlides(['<img src="test.jpg">']) === ['<img src="test.jpg">']);

        t('complex HTML preserved', fn () =>
            MetaSaver::sanitizeSlides(['<p><strong>Hello</strong> <em>World</em></p>']) === ['<p><strong>Hello</strong> <em>World</em></p>']);

        t('multiple slides with HTML preserved', function () {
            $input = ['<p>Slide 1</p>', '<p>Slide 2</p>'];
            $result = MetaSaver::sanitizeSlides($input);
            return $result === ['<p>Slide 1</p>', '<p>Slide 2</p>'];
        });
    });

    describe('Empty Strings Filtered', function () {
        t('empty string filtered out', fn () =>
            MetaSaver::sanitizeSlides(['', '<p>Content</p>']) === ['<p>Content</p>']);

        t('multiple empty strings filtered', fn () =>
            MetaSaver::sanitizeSlides(['', '<p>A</p>', '', '<p>B</p>', '']) === ['<p>A</p>', '<p>B</p>']);

        t('only empty strings returns empty', fn () =>
            MetaSaver::sanitizeSlides(['', '', '']) === []);
    });

    describe('Whitespace-Only Slides Filtered', function () {
        t('space-only slide filtered', fn () =>
            MetaSaver::sanitizeSlides(['   ', '<p>Content</p>']) === ['<p>Content</p>']);

        t('tab-only slide filtered', fn () =>
            MetaSaver::sanitizeSlides(["\t\t", '<p>Content</p>']) === ['<p>Content</p>']);

        t('newline-only slide filtered', fn () =>
            MetaSaver::sanitizeSlides(["\n\n", '<p>Content</p>']) === ['<p>Content</p>']);

        t('mixed whitespace slide filtered', fn () =>
            MetaSaver::sanitizeSlides([" \t\n ", '<p>Content</p>']) === ['<p>Content</p>']);

        t('only whitespace slides returns empty', fn () =>
            MetaSaver::sanitizeSlides(['   ', "\t", "\n"]) === []);
    });

    describe('Non-String Values Filtered', function () {
        t('integer filtered out', fn () =>
            MetaSaver::sanitizeSlides([123, '<p>Content</p>']) === ['<p>Content</p>']);

        t('null filtered out', fn () =>
            MetaSaver::sanitizeSlides([null, '<p>Content</p>']) === ['<p>Content</p>']);

        t('array filtered out', fn () =>
            MetaSaver::sanitizeSlides([['nested'], '<p>Content</p>']) === ['<p>Content</p>']);

        t('boolean filtered out', fn () =>
            MetaSaver::sanitizeSlides([true, '<p>Content</p>']) === ['<p>Content</p>']);

        t('all non-strings returns empty', fn () =>
            MetaSaver::sanitizeSlides([123, null, true, []]) === []);
    });

    describe('Array Re-Indexed After Filtering', function () {
        t('indexes are sequential after filtering', function () {
            $result = MetaSaver::sanitizeSlides(['', '<p>A</p>', '', '<p>B</p>']);
            // Should be ['<p>A</p>', '<p>B</p>'] with keys 0 and 1
            return array_keys($result) === [0, 1];
        });

        t('no gaps in keys', function () {
            $result = MetaSaver::sanitizeSlides([null, '<p>A</p>', 123, '<p>B</p>', '', '<p>C</p>']);
            return array_keys($result) === [0, 1, 2];
        });
    });

    describe('Non-Array Input', function () {
        t('string input returns empty array', fn () =>
            MetaSaver::sanitizeSlides('<p>Content</p>') === []);

        t('integer input returns empty array', fn () =>
            MetaSaver::sanitizeSlides(123) === []);

        t('null input returns empty array', fn () =>
            MetaSaver::sanitizeSlides(null) === []);

        t('object input returns empty array', fn () =>
            MetaSaver::sanitizeSlides((object)['foo' => 'bar']) === []);

        t('empty array returns empty array', fn () =>
            MetaSaver::sanitizeSlides([]) === []);
    });

});

describe('sanitizeUrlRules', function () {

    describe('Valid Rules', function () {
        t('starts_with rule passes through', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'starts_with', 'value' => 'https://site.com/']]) === [
                ['type' => 'starts_with', 'value' => 'https://site.com/'],
            ]);

        t('ends_with rule passes through', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'ends_with', 'value' => '/contact/']]) === [
                ['type' => 'ends_with', 'value' => '/contact/'],
            ]);

        t('exact rule passes through', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'exact', 'value' => 'https://site.com/page']]) === [
                ['type' => 'exact', 'value' => 'https://site.com/page'],
            ]);

        t('contains rule passes through', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'contains', 'value' => '?campaign=summer']]) === [
                ['type' => 'contains', 'value' => '?campaign=summer'],
            ]);

        t('multiple valid rules preserved and re-indexed', fn () =>
            MetaSaver::sanitizeUrlRules([
                ['type' => 'starts_with', 'value' => 'https://a.com/'],
                ['type' => 'contains', 'value' => 'b'],
            ]) === [
                ['type' => 'starts_with', 'value' => 'https://a.com/'],
                ['type' => 'contains', 'value' => 'b'],
            ]);
    });

    describe('Invalid Rules Rejected', function () {
        t('invalid type rejected', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'regex', 'value' => 'https://site.com/']]) === []);

        t('empty value rejected', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'starts_with', 'value' => '']]) === []);

        t('whitespace-only value rejected', fn () =>
            MetaSaver::sanitizeUrlRules([['type' => 'starts_with', 'value' => '   ']]) === []);

        t('missing type rejected', fn () =>
            MetaSaver::sanitizeUrlRules([['value' => 'https://site.com/']]) === []);

        t('non-array rule rejected', fn () =>
            MetaSaver::sanitizeUrlRules(['starts_with:https://site.com/']) === []);

        t('mixed valid and invalid - only valid kept', function () {
            $result = MetaSaver::sanitizeUrlRules([
                ['type' => 'starts_with', 'value' => 'https://site.com/'],
                ['type' => 'invalid', 'value' => 'https://other.com/'],
                ['type' => 'contains', 'value' => ''],
                ['type' => 'ends_with', 'value' => '/page/'],
            ]);
            return $result === [
                ['type' => 'starts_with', 'value' => 'https://site.com/'],
                ['type' => 'ends_with', 'value' => '/page/'],
            ];
        });
    });

    describe('Non-Array Input', function () {
        t('string input returns empty array', fn () =>
            MetaSaver::sanitizeUrlRules('https://site.com/') === []);

        t('null input returns empty array', fn () =>
            MetaSaver::sanitizeUrlRules(null) === []);

        t('empty array returns empty array', fn () =>
            MetaSaver::sanitizeUrlRules([]) === []);
    });

});

});

