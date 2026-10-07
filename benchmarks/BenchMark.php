<?php

declare(strict_types=1);

namespace VeloxRouter\Benchmark;

class Benchmark
{
    /**
     * Measure the performance of a given callback function.
     *
     * @param string   $label      Name or description of the test
     * @param callable $callback   The function to be tested
     * @param int      $iterations Number of test repetitions
     * @return array Collected metrics
     */
    public static function measure(string $label, callable $callback, int $iterations = 1000): array
    {
        // Ensure garbage collector doesn't skew memory measurements
        if (gc_enabled()) {
            gc_collect_cycles();
        }

        $startMemory = memory_get_usage(true);
        $startTime = microtime(true);

        // Execute the block to be tested N times
        for ($i = 0; $i < $iterations; $i++) {
            $callback($i);
        }

        $endTime = microtime(true);
        $endMemory = memory_get_peak_usage(true);

        $totalTimeMs = ($endTime - $startTime) * 1000;
        $avgTimeMs = $totalTimeMs / $iterations;
        $avgTimeUs = $avgTimeMs * 1000; // microseconds
        $memoryUsed = $endMemory - $startMemory;
        $operationsPerSec = $totalTimeMs > 0 ? $iterations / ($totalTimeMs / 1000) : 0;

        $metrics = [
            'label' => $label,
            'iterations' => $iterations,
            'total_time_ms' => $totalTimeMs,
            'avg_time_ms' => $avgTimeMs,
            'avg_time_us' => $avgTimeUs,
            'memory_peak_kb' => max(0, $memoryUsed / 1024),
            'ops_per_sec' => $operationsPerSec,
        ];

        self::printReport($metrics);

        return $metrics;
    }

    /**
     * Print a formatted and colored report in the CLI terminal.
     */
    private static function printReport(array $m): void
    {
        echo "\n\033[33m[BENCHMARK]\033[0m \033[1m{$m['label']}\033[0m\n";
        echo "----------------------------------------\n";
        echo "  Iterations:       " . number_format($m['iterations']) . "\n";
        echo "  Total Time:       " . number_format($m['total_time_ms'], 4) . " ms\n";
        echo "  Average per Exec: " . number_format($m['avg_time_us'], 2) . " µs (" . number_format($m['avg_time_ms'], 6) . " ms)\n";
        echo "  Ops per Second:   " . number_format($m['ops_per_sec'], 2) . " ops/s\n";
        echo "  Memory (Peak):    " . number_format($m['memory_peak_kb'], 2) . " KB\n";
        echo "----------------------------------------\n";
    }
}
