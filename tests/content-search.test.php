#!/usr/bin/env php
<?php
/**
 * ContentSearchHandler tests
 *
 * Run: php tests/content-search.test.php
 */

define('ABSPATH', '/fake/path/');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../includes/class-displayrules.php';
require_once __DIR__ . '/../includes/Admin/Ajax/class-contentsearchhandler.php';

use PopupsNekuda\Admin\Ajax\ContentSearchHandler;

// Tests

describe('ContentSearchHandler', function () {

describe('matchesSearch', function () {

    describe('Empty Search', function () {
        t('empty search matches everything - single keyword', fn () =>
            ContentSearchHandler::matchesSearch('', ['homepage']) === true);

        t('empty search matches everything - multiple keywords', fn () =>
            ContentSearchHandler::matchesSearch('', ['homepage', 'blog', 'posts']) === true);

        t('empty search matches empty keywords array', fn () =>
            ContentSearchHandler::matchesSearch('', []) === true);
    });

    describe('Partial Match', function () {
        t('search "home" matches keyword "homepage"', fn () =>
            ContentSearchHandler::matchesSearch('home', ['homepage']) === true);

        t('search "page" matches keyword "homepage"', fn () =>
            ContentSearchHandler::matchesSearch('page', ['homepage']) === true);

        t('search "blog" matches keyword "blog"', fn () =>
            ContentSearchHandler::matchesSearch('blog', ['blog']) === true);

        t('search "log" matches keyword "blog"', fn () =>
            ContentSearchHandler::matchesSearch('log', ['blog']) === true);

        t('search "all" matches keyword "all posts"', fn () =>
            ContentSearchHandler::matchesSearch('all', ['all posts']) === true);

        t('search "pos" matches keyword "posts"', fn () =>
            ContentSearchHandler::matchesSearch('pos', ['posts']) === true);
    });

    describe('Case Insensitive', function () {
        t('uppercase search matches lowercase keyword', fn () =>
            ContentSearchHandler::matchesSearch('HOME', ['homepage']) === true);

        t('lowercase search matches uppercase keyword', fn () =>
            ContentSearchHandler::matchesSearch('home', ['HOMEPAGE']) === true);

        t('mixed case search matches mixed case keyword', fn () =>
            ContentSearchHandler::matchesSearch('HoMe', ['hOmEpAgE']) === true);

        t('uppercase partial matches', fn () =>
            ContentSearchHandler::matchesSearch('BLOG', ['my blog page']) === true);
    });

    describe('No Match', function () {
        t('search with no matching keyword returns false', fn () =>
            ContentSearchHandler::matchesSearch('contact', ['homepage', 'blog']) === false);

        t('completely different search returns false', fn () =>
            ContentSearchHandler::matchesSearch('xyz', ['homepage', 'blog', 'posts']) === false);

        t('search against empty keywords array returns false', fn () =>
            ContentSearchHandler::matchesSearch('home', []) === false);

        t('partial mismatch returns false', fn () =>
            ContentSearchHandler::matchesSearch('abc', ['homepage']) === false);
    });

    describe('Multiple Keywords (OR)', function () {
        t('matches if first keyword contains search', fn () =>
            ContentSearchHandler::matchesSearch('home', ['homepage', 'blog', 'posts']) === true);

        t('matches if middle keyword contains search', fn () =>
            ContentSearchHandler::matchesSearch('blog', ['homepage', 'blog', 'posts']) === true);

        t('matches if last keyword contains search', fn () =>
            ContentSearchHandler::matchesSearch('post', ['homepage', 'blog', 'posts']) === true);

        t('matches if any keyword contains search', fn () =>
            ContentSearchHandler::matchesSearch('page', ['about page', 'contact form']) === true);

        t('no match if none contain search', fn () =>
            ContentSearchHandler::matchesSearch('xyz', ['homepage', 'blog', 'posts']) === false);
    });

    describe('Edge Cases', function () {
        t('single character search matches', fn () =>
            ContentSearchHandler::matchesSearch('h', ['homepage']) === true);

        t('search equals keyword exactly', fn () =>
            ContentSearchHandler::matchesSearch('blog', ['blog']) === true);

        t('search longer than keyword returns false', fn () =>
            ContentSearchHandler::matchesSearch('homepage123', ['homepage']) === false);

        t('whitespace in search', fn () =>
            ContentSearchHandler::matchesSearch(' home', ['homepage']) === false);

        t('keyword with spaces matches partial', fn () =>
            ContentSearchHandler::matchesSearch('my', ['my blog']) === true);

        t('numeric search matches numeric keyword', fn () =>
            ContentSearchHandler::matchesSearch('123', ['page123']) === true);
    });

});

});

