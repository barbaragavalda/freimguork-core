<?php

// normally defined by a consuming app's public/index.php before anything else runs;
// tests bypass that entry point, so define harmless placeholders here instead.
// IS_DEV can only be defined once per process - tests that need the opposite
// value (e.g. a prod-mode branch) must run with @runInSeparateProcess.
define('DIR_ROOT', __DIR__ . '/../');
define('IS_DEV', true);

require __DIR__ . '/../vendor/autoload.php';
