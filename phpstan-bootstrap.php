<?php

// DIR_ROOT and IS_DEV are always defined by a consuming app's entry script
// before any framework code runs (see CLAUDE.md - this package is never run
// standalone) - declared here purely so PHPStan knows they exist, this file
// is never included at runtime.
define('DIR_ROOT', __DIR__ . '/');
define('IS_DEV', true);
