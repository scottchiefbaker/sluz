<?php

////////////////////////////////////////////////////////

$dir = dirname(__FILE__);
include("$dir/../sluz.class.php");

$sluz = new sluz;
$sluz->debug = 0;
$sluz->in_unit_test = 1;

// Test counters
$pass_count = 0;
$fail_count = 0;
// ANSI colors and strings for testing output
$green    = "\033[38;5;10m";
$red      = "\033[38;5;9m";
$reset    = "\033[0m";
$white    = "\033[38;5;15m";
$ok_str   = $white . "[" . $green . "  OK  " . $reset . $white . "]" . $reset;
$fail_str = $white . "[" . $red   . " FAIL " . $reset . $white . "]" . $reset;

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
$sluz->assign('subarr' , ['one' => [2,4,6], 'two' => [3,6,9]]);
$sluz->assign('arrayd' , [[1,2],[3,4],[5,6]]);
$sluz->assign('empty'  , []);

if ($_SERVER['SERVER_SOFTWARE'] === "Apache") {
	$sluz->in_unit_test = 0;
	var_dump($sluz->process_block('{* Comment *}'));
	die;
}

sluz_test('Hello there'         , 'Hello there', 'Basic #1');
sluz_test('{$first}'            , 'Scott'      , 'Basic #2');
sluz_test("{  \$first\t}"       , 'Scott'      , 'Basic #3 - whitespace');
sluz_test('{$bogus_var}'        , ''           , 'Basic #4 - missing variable');
sluz_test('{$animal|strtoupper}', 'KITTEN'     , 'Basic #5 - PHP Modifier');
sluz_test('{$cust.first}'       , 'Scott'      , 'Basic #6 - Hash Lookup');
sluz_test('{$array.1}'          , 'two'        , 'Basic #7 - Array Lookup');
sluz_test('{$array|count}'      , 3            , 'Basic #8 - PHP Modifier array');
sluz_test('{$number + 3}'       , 18           , 'Basic #9 - Addition');
sluz_test('{$first . "foo"}'    , "Scottfoo"   , 'Basic #10 - Concat');
sluz_test('{$number * $debug}'  , 15           , 'Basic #11 - Multiplication of two vars');
sluz_test('{3}'                 , 3            , 'Basic #12 - Number literal');
sluz_test('{"Scott"}'           , "Scott"      , 'Basic #13 - String literall');
sluz_test('{"Scott" . "Baker"}' , "ScottBaker" , 'Basic #14 - String concat');

sluz_test('{if $debug}DEBUG{/if}'                , 'DEBUG'   , 'if #1');
sluz_test('{if $bogus_var}DEBUG{/if}'            , ''        , 'if #2');
sluz_test('{if $debug}{$first}{/if}'             , 'Scott'   , 'if #3 (variable)');
sluz_test('{if $debug}{if $debug}FOO{/if}{/if}'  , 'FOO'     , 'if #4 nested');
sluz_test('{if $bogus_var}YES{else}NO{/if}'      , 'NO'      , 'if #5 else');
sluz_test('{if $cust.first}{$cust.first}{/if}'   , 'Scott'   , 'if #6 hash lookup');
sluz_test('{if $number > 10}GREATER{/if}'        , 'GREATER' , 'if #7 comparison');
sluz_test('{if $bogus_var || $key}KEY{/if}'      , 'KEY'     , 'if #8 two var comparison');
sluz_test('{if $number === 15 && $debug}YES{/if}', 'YES'     , 'if #9 two comparisons');
sluz_test('{if !$verbose}QUIET{/if}'             , 'QUIET'   , 'if #10 negated comparison');
sluz_test('{if ($debug || $number > 20)}YES{/if}', 'YES'     , 'if #11 parens');
sluz_test('{if count($array) > 2}YES{/if}'       , 'YES'     , 'if #12 PHP function conditional');
sluz_test('{if $debug}{$key}{$last}{/if}'        , 'valBaker', 'if #13 Two block payload');

sluz_test('{foreach $array as $num}{$num}{/foreach}'                  , 'onetwothree'            , 'foreach #1');
sluz_test('{foreach $array as $num}\n{$num}\n{/foreach}'              , '\none\n\ntwo\n\nthree\n', 'foreach #2');
sluz_test('{foreach $members as $x}{$x.first}{/foreach}'              , 'ScottJason'             , 'foreach #3 hash');
sluz_test('{foreach $arrayd as $x}{$x.1}{/foreach}'                   , '246'                    , 'foreach #4 hash');
sluz_test('{foreach $arrayd as $key => $val}{$key}:{$val.0}{/foreach}', '0:11:32:5'              , 'foreach #6 key/val array');
sluz_test('{foreach $members as $id => $x}{$id}{$x.first}{/foreach}'  , '0Scott1Jason'           , 'foreach #7 key/val hash');
sluz_test('{foreach $subarr.one as $id}{$id}{/foreach}'               , '246'                    , 'foreach #8 key/val hash');
sluz_test('{foreach $bogus_var as $x}one{/foreach}'                   , null                     , 'foreach #9 missing var');
sluz_test('{foreach $empty as $x}one{/foreach}'                       , ''                       , 'foreach #10 empty array');

sluz_test('Scott'           , 'Scott'           , 'Plain text #1');
sluz_test('<div>Scott</div>', '<div>Scott</div>', 'Plain text #2 - HTML');

// Don't parse blocks that have whitespacing
sluz_test(' {$first} ', ' {$first} ', 'Bad block #1');
sluz_test('{first}'   , NULL        , 'Bad block #2'); // Literal (no $)

sluz_test('{literal}{{/literal}'                  , '{'                  , 'Literal #1');
sluz_test('{literal}}{/literal}'                  , '}'                  , 'Literal #2');
sluz_test('{literal}{}{/literal}'                 , '{}'                 , 'Literal #3');
sluz_test('{literal}{literal}{/literal}'          , '{literal}'          , 'Literal #4');
sluz_test('{literal}{literal}{/literal}{/literal}', '{literal}{/literal}', 'Literal #5 - meta literal');

sluz_test('{* Comment *}'  , '', 'Comment #1');
sluz_test('{* ********* *}', '', 'Comment #2');
sluz_test('{**}'           , '', 'Comment #3');

$total = $pass_count + $fail_count;

print "\n";
if ($fail_count === 0) {
	$msg = sprintf("All %d tests passed successfully", $total);
	print $msg . str_repeat(" ", 81 - strlen($msg)) . $ok_str . "\n";
} else {
	$msg = sprintf("%d tests failed (%.1f%% failure rate)", $fail_count, ($fail_count / $total) * 100);
	print $msg . str_repeat(" ", 81 - strlen($msg)) . $fail_str . "\n";
}

////////////////////////////////////////////////////////

function sluz_test($input, $expected, $test_name) {
	global $sluz;
	global $pass_count;
	global $fail_count;
	global $ok_str;
	global $fail_str;

	$html = $sluz->process_block($input);

	$lead = "Test '$test_name' ";
	$pad  = str_repeat(" ", 80 - (strlen($lead)));

	print "$lead $pad";

	$ok    = "\033[32m";
	$fail  = "\033[31m";
	$reset = "\033[0m";
	$white = "\033[1m";

	$l = $white . "[" . $reset;
	$r = $white . "]" . $reset;

	$is_regexp = preg_match("|^/(.+?)/$|", $expected);
	$html      = var_export($html, true);

	if (!$is_regexp) { $expected = var_export($expected, true); }

	if ($is_regexp && preg_match($expected, $html)) {
		print $ok_str . "\n";
		$pass_count++;
	} elseif ($is_regexp && !preg_match($expected, $html)) {
		print $fail_str . "\n";
		print "  * Expected $expected but got $html (from: $file #$line)\n";

		$fail_count++;
	} elseif ($html === $expected) {
		print $ok_str . "\n";
		$pass_count++;
	} else {
		$d = debug_backtrace();
		$file = $d[0]['file'];
		$line = $d[0]['line'];
		print $fail_str . "\n";
		print "  * Expected $expected but got $html (from: $file #$line)\n";

		$fail_count++;
	}
}
