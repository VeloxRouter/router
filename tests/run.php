<?php

declare(strict_types=1);

assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_EXCEPTION, true);

echo "\033[1m========================================\033[0m\n";
echo "\033[1m  VeloxRouter v1.0.0 - Native Test Suite  \033[0m\n";
echo "\033[1m========================================\033[0m\n\n";

require_once __DIR__ . '/RouterTest.php';
require_once __DIR__ . '/ParamsTest.php';
require_once __DIR__ . '/MiddlewareTest.php';

echo "\033[32m\033[1m✔ All test suites passed with zero dependencies!\033[0m\n";
