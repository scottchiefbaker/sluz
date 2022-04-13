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
$sluz->assign('array'  , ['one', 'two', 'three']);
$sluz->assign('cust'   , ['first' => 'Scott', 'last' => 'Baker']);
$sluz->assign('number' , 15);
$sluz->assign('members', [['first' => 'Scott', 'last' => 'Baker'], ['first' => 'Jason', 'last' => 'Doolis']]);
$sluz->assign('arrayd' , [[1,2],[3,4],[5,6]]);

sluz_test('Hello there'         , 'Hello there', 'Basic #1');
sluz_test('{$first}'            , 'Scott'      , 'Basic #2');
sluz_test("{  \$first\t}"       , 'Scott'      , 'Basic #3 - whitespace');
sluz_test('{$bogus_var}'        , ''           , 'Basic #4 - missing variable');
sluz_test('{$animal|strtoupper}', 'KITTEN'     , 'Basic #5 - PHP Modifier');
sluz_test('{$cust.first}'       , 'Scott'      , 'Basic #6 - Hash Lookup');
sluz_test('{$array.1}'          , 'two'        , 'Basic #7 - Array Lookup');
sluz_test('{$array|count}'      , 3            , 'Basic #8 - PHP Modifier array');

sluz_test('{if $debug}DEBUG{/if}'                , 'DEBUG'  , 'if #1');
sluz_test('{if $bogus_var}DEBUG{/if}'            , ''       , 'if #2');
sluz_test('{if $debug}{$first}{/if}'             , 'Scott'  , 'if #3 (variable)');
sluz_test('{if $debug}{if $debug}FOO{/if}{/if}'  , 'FOO'    , 'if #4 nested');
sluz_test('{if $bogus_var}YES{else}NO{/if}'      , 'NO'     , 'if #5 else');
sluz_test('{if $cust.first}{$cust.first}{/if}'   , 'Scott'  , 'if #6 hash lookup');
sluz_test('{if $number > 10}GREATER{/if}'        , 'GREATER', 'if #7 comparison');
sluz_test('{if $bogus_var || $key}KEY{/if}'      , 'KEY'    , 'if #8 two var comparison');
sluz_test('{if $number === 15 && $debug}YES{/if}', 'YES'    , 'if #9 two comparisons');
sluz_test('{if !$verbose}QUIET{/if}'             , 'QUIET'  , 'if #10 negated comparison');
sluz_test('{if ($debug || $number > 20)}YES{/if}', 'YES'    , 'if #11 parens');
sluz_test('{if count($array) > 2}YES{/if}'       , 'YES'    , 'if #12 PHP function conditional');

sluz_test('{foreach $array as $num}{$num}{/foreach}'                  , 'onetwothree'            , 'foreach #1');
sluz_test('{foreach $array as $num}\n{$num}\n{/foreach}'              , '\none\n\ntwo\n\nthree\n', 'foreach #2');
sluz_test('{foreach $members as $x}{$x.first}{/foreach}'              , 'ScottJason'             , 'foreach #3 hash');
sluz_test('{foreach $arrayd as $x}{$x.1}{/foreach}'                   , '246'                    , 'foreach #4 hash');
sluz_test('{foreach $arrayd as $key => $val}{$key}:{$val.0}{/foreach}', '0:11:32:5'              , 'foreach #6 key/val array');
sluz_test('{foreach $members as $id => $x}{$id}{$x.first}{/foreach}'  , '0Scott1Jason'           , 'foreach #3 key/val hash');

sluz_test('Scott'           , 'Scott'           , 'Plain text #1');
sluz_test('<div>Scott</div>', '<div>Scott</div>', 'Plain text #2 - HTML');

// Don't parse blocks that have whitespacing
sluz_test(' {$first} '  , ' {$first} ', 'Bad block #1');
sluz_test('{$first + 3}', ''          , 'Bad block #2');
sluz_test('{first}'     , '{first}'   , 'Bad block #3'); // Literal (no $)

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

	$expected = var_export($expected, true);
	$html     = var_export($html, true);

	if ($html === $expected) {
		print $ok . "OK" . $reset . "\n";
	} else {
		$d = debug_backtrace();
		$file = $d[0]['file'];
		$line = $d[0]['line'];
		print $fail . "FAIL" . $reset . "\n";
		print "  * Expected $expected but got $html (from: $file #$line)\n";
	}
}
