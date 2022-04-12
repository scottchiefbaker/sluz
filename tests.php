<?php

////////////////////////////////////////////////////////

include('sluz.class.php');

$sluz = new sluz;
$sluz->debug = 0;

$sluz->assign('key'      , 'val');
$sluz->assign('first'    , "Scott");
$sluz->assign('last'     , "Baker");
$sluz->assign('animal'   , "Kitten");
$sluz->assign('heading'  , "Test heading");
$sluz->assign('debug'    , 1);
$sluz->assign('array'    , ['one', 'two', 'three']);

sluz_test('Hello there', 'Hello there', 'Basic #1');
sluz_test('{$first}', 'Scott', 'Basic #2');
sluz_test('{$bogus_var}', '', 'Basic #3');
sluz_test('{$animal|strtoupper}', 'KITTEN', 'Basic #4 - PHP Modifier');

sluz_test('{if $debug}DEBUG{/if}', 'DEBUG', 'if #1');
sluz_test('{if $debugz}DEBUG{/if}', '', 'if #2');
sluz_test('{if $debug}{$first}{/if}', 'Scott', 'if #3 (variable)');
sluz_test('{if $debug}{if $debug}FOO{/if}{/if}', 'FOO', 'if #4 nested');
sluz_test('{if $bogus_var}YES{else}NO{/if}', 'NO', 'if #5 else');

sluz_test('{foreach $array as $num}{$num}{/foreach}', 'onetwothree', 'foreach #1');
sluz_test('{foreach $array as $num}\n{$num}\n{/foreach}', '\none\n\ntwo\n\nthree\n', 'foreach #2');

////////////////////////////////////////////////////////

function sluz_test($input, $expected, $test_name) {
	global $sluz;
	$html = $sluz->process_block($input);

	$lead = "Test '$test_name' ";
	$pad  = str_repeat(" ", 80 - (strlen($lead)));

	print "$lead $pad";

	$ok    = "\033[32m";
	$fail  = "\033[31m";
	$reset = "\033[0m";

	if ($html === $expected) {
		print $ok . "OK" . $reset . "\n";
	} else {
		print $fail . "FAIL" . $reset . "\n";
		print "  * Expected '$expected', got '$html'\n";
	}
}
