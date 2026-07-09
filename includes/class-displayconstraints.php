<?php
/**
 * Display constraint defaults and normalization
 */

namespace PopupsNekuda;

if (!defined('ABSPATH')) {
    exit;
}

class DisplayConstraints {

    public const MAX_WIDTH_VW_DEFAULT = 80;
    public const MAX_WIDTH_MOBILE_VW_DEFAULT = 90;
    public const MAX_HEIGHT_VH_DEFAULT = 90;
    public const MIN_PERCENT = 1;
    public const MAX_PERCENT = 100;

    public static function normalizeWidth($value, int $default = self::MAX_WIDTH_VW_DEFAULT): int {
        $value = absint($value);
        return $value === 0 ? $default : self::clamp($value);
    }

    public static function normalizeHeight($value, int $default = self::MAX_HEIGHT_VH_DEFAULT): int {
        $value = absint($value);
        return $value === 0 ? $default : self::clamp($value);
    }

    public static function clamp(int $value): int {
        return max(self::MIN_PERCENT, min(self::MAX_PERCENT, $value));
    }
}
