<?php
/**
 * Admin orchestrator - delegates to specialized classes
 *
 * Following Single Responsibility Principle:
 * - Meta boxes: Admin\MetaBoxes\*
 * - AJAX handlers: Admin\Ajax\*
 * - Save logic: Admin\MetaSaver
 * - Asset loading: Admin\Assets
 */

namespace PopupsNekuda;

use PopupsNekuda\Admin\MetaBoxes\TriggerMetaBox;
use PopupsNekuda\Admin\MetaBoxes\CookieMetaBox;
use PopupsNekuda\Admin\MetaBoxes\ConstraintsMetaBox;
use PopupsNekuda\Admin\MetaBoxes\RulesMetaBox;
use PopupsNekuda\Admin\MetaBoxes\ContentMetaBox;
use PopupsNekuda\Admin\Ajax\EditorHandler;
use PopupsNekuda\Admin\Ajax\ContentSearchHandler;
use PopupsNekuda\Admin\MetaSaver;
use PopupsNekuda\Admin\Assets;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public function __construct() {
        $this->registerMetaBoxes();
        $this->registerAjaxHandlers();
        $this->registerSaveHandler();
        $this->registerAssets();
    }

    private function registerMetaBoxes(): void {
        add_action('add_meta_boxes', function() {
            TriggerMetaBox::register();
            CookieMetaBox::register();
            ConstraintsMetaBox::register();
            RulesMetaBox::register();
            ContentMetaBox::register();
        });
    }

    private function registerAjaxHandlers(): void {
        EditorHandler::register();
        ContentSearchHandler::register();
    }

    private function registerSaveHandler(): void {
        MetaSaver::register();
    }

    private function registerAssets(): void {
        Assets::register();
    }
}