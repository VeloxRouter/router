<?php

declare(strict_types=1);

namespace VeloxRouter\Testing;

class Test
{
    private static int $passed = 0;
    private static int $failed = 0;

    public static function describe(string $title, callable $callback): void
    {
        echo "\033[36m[TEST] " . $title . "\033[0m\n";
        try {
            $callback();
        } catch (\Throwable $e) {
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }

    public static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            self::$failed++;
            echo "  \033[31m✖ Assertion Failed:\033[0m {$message}\n";
            throw new \Exception("Assertion Failed: {$message}");
        }
        self::$passed++;
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected [" . var_export($expected, true) . "], got [" . var_export($actual, true) . "]";
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m {$msg}\n";
            throw new \Exception($msg);
        }
        self::$passed++;
    }

    public static function ok(string $message): void
    {
        echo "  \033[32m✔\033[0m {$message}\n";
    }

    public static function summary(): void
    {
        echo "\n----------------------------------------\n";
        echo " Results: \033[32m" . self::$passed . " passed\033[0m, \033[31m" . self::$failed . " failed\033[0m\n";
        echo "----------------------------------------\n";
    }
}