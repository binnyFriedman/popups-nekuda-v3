<?php
/**
 * Test utilities - lightweight test runner
 *
 * Usage:
 *   require_once __DIR__ . '/utils.php';
 *
 *   describe('My Feature', function () {
 *       t('does something', fn () => true);
 *   });
 */

$__test_passed = 0;
$__test_failed = 0;
$__test_failures = [];
$__test_currentDescribe = '';
$__test_indent = 0;

// Auto-run summary when script ends
register_shutdown_function('__test_summary');

/**
 * Group related tests
 */
function describe(string $name, callable $fn): void {
    global $__test_currentDescribe, $__test_indent;

    $prevDescribe = $__test_currentDescribe;
    $__test_currentDescribe = $prevDescribe ? "$prevDescribe → $name" : $name;

    $padding = str_repeat('  ', $__test_indent);
    echo "\n{$padding}$name\n";

    $__test_indent++;
    $fn();
    $__test_indent--;

    $__test_currentDescribe = $prevDescribe;
}

/**
 * Run a single test
 */
function t(string $name, callable $fn): void {
    global $__test_passed, $__test_failed, $__test_failures, $__test_currentDescribe, $__test_indent;

    $fullName = $__test_currentDescribe ? "$__test_currentDescribe → $name" : $name;
    $padding = str_repeat('  ', $__test_indent);

    try {
        $result = $fn();
        if ($result === true) {
            $__test_passed++;
            echo "{$padding}\033[32m✓\033[0m $name\n";
        } else {
            $__test_failed++;
            $__test_failures[] = $fullName;
            echo "{$padding}\033[31m✗\033[0m $name\n";
        }
    } catch (Throwable $e) {
        $__test_failed++;
        $__test_failures[] = "$fullName → {$e->getMessage()}";
        echo "{$padding}\033[31m✗\033[0m $name (error)\n";
    }
}

/**
 * Print summary (called automatically on shutdown)
 */
function __test_summary(): void {
    global $__test_passed, $__test_failed, $__test_failures;

    // Skip if no tests ran
    if ($__test_passed === 0 && $__test_failed === 0) {
        return;
    }

    echo "\n";

    if (!empty($__test_failures)) {
        echo "\033[31mFailures:\033[0m\n";
        foreach ($__test_failures as $f) {
            echo "  ✗ $f\n";
        }
        echo "\n";
    }

    $total = $__test_passed + $__test_failed;
    $color = $__test_failed > 0 ? "\033[31m" : "\033[32m";
    echo "{$color}$total tests, $__test_passed passed, $__test_failed failed\033[0m\n";

    if ($__test_failed > 0) {
        exit(1);
    }
}