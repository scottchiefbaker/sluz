<?php

error_reporting(E_ALL);
set_error_handler(function ($errno, $message, $file, $line) {
	throw new ErrorException($message, 0, $errno, $file, $line);
});

$scenario = $argv[1] ?? 'none';
if ($scenario === 'escape' || $scenario === 'all') {
	function escape($value) { return 'external escape'; }
}
if ($scenario === 'raw' || $scenario === 'all') {
	function raw($value) { return 'external raw'; }
}
if ($scenario === 'sluz' || $scenario === 'all') {
	function sluz($value) { return 'external sluz'; }
}
if ($scenario === 'constant' || $scenario === 'all') {
	define('SLUZ_INLINE', 'external marker');
}
if ($scenario === 'class') {
	class sluz {
		public const MARKER = 'external class';
	}
}

include __DIR__ . '/../sluz.class.php';
include __DIR__ . '/../sluz.class.php';

function expect($actual, $expected) {
	if ($actual !== $expected) {
		throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
	}
}

if ($scenario === 'class') {
	expect(sluz::MARKER, 'external class');
} else {
	expect(SLUZ_INLINE, in_array($scenario, ['constant', 'all'], true) ? 'external marker' : '__INLINE_TEMPLATE__');
	expect(escape('<'), in_array($scenario, ['escape', 'all'], true) ? 'external escape' : '&lt;');
	expect(raw('<'), in_array($scenario, ['raw', 'all'], true) ? 'external raw' : '<');
	if (in_array($scenario, ['sluz', 'all'], true)) {
		expect(sluz('test'), 'external sluz');
	} else {
		expect(function_exists('sluz'), true);
	}

	$s = new sluz();
	$s->assign('value', '<tag>');
	$s->setEscapeHtml(true);
	foreach ([false, true] as $safe) {
		$s->setSafeMode($safe);
		expect($s->parse_string('{$value|escape}'), '&lt;tag&gt;');
		expect($s->parse_string('{$value|raw}'), '<tag>');
		expect($s->parse_string('{$value|escape:"url"}'), '%3Ctag%3E');
	}
}

echo "OK\n";
