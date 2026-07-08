<?php

// normally defined by a consuming app's public/index.php before anything else runs;
// tests bypass that entry point, so define a harmless placeholder here instead
define('DIR_ROOT', __DIR__ . '/../');

require __DIR__ . '/../vendor/autoload.php';
