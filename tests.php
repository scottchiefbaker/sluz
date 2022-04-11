<?php

////////////////////////////////////////////////////////

include('sluz.class.php');

$sluz = new sluz;
$sluz->debug = 0;

$sluz->assign('key'    , 'val');
$sluz->assign('first'  , "Scott");
$sluz->assign('last'   , "Baker");
$sluz->assign('animal' , "Kitten");
$sluz->assign('heading', "Test heading");
$sluz->assign('debug'  , 1);
$sluz->assign('array', ['one', 'two', 'three']);

sluz_test('Hello there', 'Hello there', 'Basic #1');
sluz_test('{$first}', 'Scott', 'Basic #2');
sluz_test('{$bogus_var}', '', 'Basic #3');

sluz_test('{if $debug}DEBUG{/if}', 'DEBUG', 'if #1');
sluz_test('{if $debugz}DEBUG{/if}', '', 'if #2');
sluz_test('{if $debug}{$first}{/if}', 'Scott', 'if #3 (variable)');
sluz_test('{if $debug}{if $debug}FOO{/if}{/if}', 'FOO', 'if #4 nested');
sluz_test('{if $bogus_var}YES{else}NO{/if}', 'NO', 'if #5 else');

////////////////////////////////////////////////////////

function sluz_test($input, $expected, $test_name) {
	global $sluz;
	$html = $sluz->process_block($input);

	if ($html === $expected) {
		print "Test '$test_name' OK\n";
	} else {
		print "Test '$test_name' FAIL\n";
		print "  * Expected '$expected', got '$html'\n";
	}
}
