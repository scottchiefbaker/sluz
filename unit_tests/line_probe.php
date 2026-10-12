<?php

// Subprocess helper for tests.php. Error output outside unit-test mode calls exit(), so
// line-number checks run here. Args: <php file> <template path, or "inline">
include __DIR__ . '/../sluz.class.php';

$s = new sluz();
$s->enable_simple_mode($argv[1]);
echo $s->fetch($argv[2] === 'inline' ? SLUZ_INLINE : $argv[2]);
