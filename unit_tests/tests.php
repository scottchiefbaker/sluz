<?php

////////////////////////////////////////////////////////

$dir = dirname(__FILE__);
include("$dir/../sluz.class.php");

$sluz = new sluz;
$sluz->debug = 0;
$sluz->in_unit_test = 1;

// Check if there is a filter at the command line
$filter = $argv[1] ?? $_GET['filter'] ?? "";

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

$hash = ['color' => 'yellow', 'age' => 43, 'book' => 'Dark Tower'];

$is_cli = (php_sapi_name() == "cli");

$sluz->assign('x'      , '7');
$sluz->assign('key'    , 'val');
$sluz->assign('first'  , "Scott");
$sluz->assign('last'   , "Baker");
$sluz->assign('animal' , "Kitten");
$sluz->assign('debug'  , 1);
$sluz->assign('array'  , ['one', 'two', 'three']);
$sluz->assign('cust'   , ['first' => 'Scott', 'last' => 'Baker']);
$sluz->assign('number' , 15);
$sluz->assign('zero'   , 0);
$sluz->assign('members', [['first' => 'Scott', 'last' => 'Baker'], ['first' => 'Jason', 'last' => 'Doolis']]);
$sluz->assign('subarr' , ['one' => [2,4,6], 'two' => [3,6,9]]);
$sluz->assign('arrayd' , [[1,2],[3,4],[5,6]]);
$sluz->assign('empty'  , []);
$sluz->assign($hash);

$test_output = [];

sluz_test('Hello there'         , 'Hello there', 'Basic #1 - Static string');
sluz_test('{$first}'            , 'Scott'      , 'Basic #2 - Basic variable');
sluz_test("{  \$first\t}"       , 'Scott'      , 'Basic #3 - Whitespace');
sluz_test('{$bogus_var}'        , ''           , 'Basic #4 - Missing variable');
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
sluz_test('{$color . $age}'     , "yellow43"   , 'Basic #15 - Hash group assignment');
sluz_test('{$x}'                , "7"          , 'Basic #16 - Single Character variable');

sluz_test('{if $debug}DEBUG{/if}'                , 'DEBUG'   , 'If #1 - Simple');
sluz_test('{if $bogus_var}DEBUG{/if}'            , ''        , 'If #2 - Missing var');
sluz_test('{if $debug}{$first}{/if}'             , 'Scott'   , 'If #3 - Variable as payload');
sluz_test('{if $debug}{if $debug}FOO{/if}{/if}'  , 'FOO'     , 'If #4 - Nested');
sluz_test('{if $bogus_var}YES{else}NO{/if}'      , 'NO'      , 'If #5 - Else');
sluz_test('{if $cust.first}{$cust.first}{/if}'   , 'Scott'   , 'If #6 - Hash lookup');
sluz_test('{if $number > 10}GREATER{/if}'        , 'GREATER' , 'If #7 - Comparison');
sluz_test('{if $bogus_var || $key}KEY{/if}'      , 'KEY'     , 'If #8 - ||');
sluz_test('{if $number === 15 && $debug}YES{/if}', 'YES'     , 'If #9 - Two comparisons');
sluz_test('{if !$verbose}QUIET{/if}'             , 'QUIET'   , 'If #10 - Negated comparison');
sluz_test('{if ($zero || $number > 10)}YES{/if}' , 'YES'     , 'If #11 - Parens');
sluz_test('{if count($array) > 2}YES{/if}'       , 'YES'     , 'If #12 - PHP function conditional');
sluz_test('{if $debug}{$key}{$last}{/if}'        , 'valBaker', 'If #13 - Two block payload');

sluz_test('{foreach $array as $num}{$num}{/foreach}'                   , 'onetwothree'            , 'Foreach #1 - Simple');
sluz_test('{foreach $array as $num}\n{$num}\n{/foreach}'               , '\none\n\ntwo\n\nthree\n', 'Foreach #2 - Simple with whitespace');
sluz_test('{foreach $members as $x}{$x.first}{/foreach}'               , 'ScottJason'             , 'Foreach #3 - Hash');
sluz_test('{foreach $arrayd as $x}{$x.1}{/foreach}'                    , '246'                    , 'Foreach #4 - Array');
sluz_test('{foreach $arrayd as $key => $val}{$key}:{$val.0}{/foreach}' , '0:11:32:5'              , 'Foreach #6 - Key/val array');
sluz_test('{foreach $members as $id => $x}{$id}{$x.first}{/foreach}'   , '0Scott1Jason'           , 'Foreach #7 - Key/val hash');
sluz_test('{foreach $subarr.one as $id}{$id}{/foreach}'                , '246'                    , 'Foreach #8 - Hash key');
sluz_test('{foreach $bogus_var as $x}one{/foreach}'                    , 'ERROR-85824'            , 'Foreach #9 - Missing var');
sluz_test('{foreach $empty as $x}one{/foreach}'                        , ''                       , 'Foreach #10 - Empty array');
sluz_test('{foreach $array as $i => $x}{$i}{$x}{/foreach}'             , '0one1two2three'         , 'Foreach #11 - One char variables');
sluz_test('{foreach $arrayd as $i => $x}{if $x.1}{$x.1}{/if}{/foreach}', '246'                    , 'Foreach #12 - Foreach with nested if (array)');
sluz_test('{foreach $array as $i => $x}{if $x}{$x}{/if}{/foreach}'     , 'onetwothree'            , 'Foreach #13 - Foreach with nested if');

sluz_test('Scott'           , 'Scott'           , 'Plain text #1 - Static text');
sluz_test('<div>Scott</div>', '<div>Scott</div>', 'Plain text #2 - HTML');

// Don't parse blocks that have whitespacing
sluz_test(' {$first} ', ' {$first} ' , 'Bad block #1 - Padding with whitespace');
sluz_test('{first}'   , 'ERROR-73467', 'Bad block #2 - {word}'); // Literal (no $)

sluz_test('{literal}{{/literal}'                  , '{'                  , 'Literal #1 - {');
sluz_test('{literal}}{/literal}'                  , '}'                  , 'Literal #2 - }');
sluz_test('{literal}{}{/literal}'                 , '{}'                 , 'Literal #3 - {}');
sluz_test('{literal}{literal}{/literal}'          , '{literal}'          , 'Literal #4 - {literal}');
sluz_test('{literal}{literal}{/literal}{/literal}', '{literal}{/literal}', 'Literal #5 - Meta literal');

sluz_test('{* Comment *}'  , '', 'Comment #1 - With text');
sluz_test('{* ********* *}', '', 'Comment #2 - ******');
sluz_test('{**}'           , '', 'Comment #3 - No whitespace');

sluz_test('{include file=\'extra.stpl\'}', '/e1ab49cf/' , 'Include #1 - file=\'extra.stpl\'');
sluz_test('{include \'extra.stpl\'}'     , '/e1ab49cf/' , 'Include #2 - \'extra.stpl\'');
sluz_test('{include}'                    , 'ERROR-73467', 'Include #3 - No payload');

$total = $pass_count + $fail_count;

if ($is_cli) {
	print "\n";
	if ($total === 0) {
		print $red . "Warning:$reset no tests were run?\n";
		exit(3);
	} elseif ($fail_count === 0) {
		$msg = sprintf("All %d tests passed successfully", $total);
		print $msg . str_repeat(" ", 81 - strlen($msg)) . $ok_str . "\n";
		exit(0);
	} else {
		$msg = sprintf("%d tests failed (%.1f%% failure rate)", $fail_count, ($fail_count / $total) * 100);
		print $msg . str_repeat(" ", 81 - strlen($msg)) . $fail_str . "\n";
		exit(1);
	}
} else {
	$sluz->assign("fail_count", $fail_count);
	$sluz->assign("pass_count", $pass_count);
	$sluz->assign("total", $total);

	$sluz->assign("tests", $test_output);
	$sluz->tpl_file = "tpls/tests.stpl";
	print $sluz->parse();
}

////////////////////////////////////////////////////////

function sluz_test($input, $expected, $test_name) {
	global $sluz;
	global $pass_count;
	global $fail_count;
	global $ok_str;
	global $fail_str;
	global $filter;
	global $is_cli;
	global $test_output;

	if (!empty($filter) && !preg_match("/$filter/i", $test_name)) { return; }

	$html = $sluz->process_block($input);

	$lead = "Test '$test_name' ";
	$pad  = str_repeat(" ", 80 - (strlen($lead)));

	if ($is_cli) {
		print "$lead $pad";
	}

	$ok    = "\033[32m";
	$fail  = "\033[31m";
	$reset = "\033[0m";
	$white = "\033[1m";

	$l = $white . "[" . $reset;
	$r = $white . "]" . $reset;

	$is_regexp = preg_match("|^/(.+?)/$|", $expected ?? "");
	$html      = var_export($html, true);

	if (!$is_regexp) { $expected = var_export($expected, true); }

	if ($is_regexp && preg_match($expected, $html)) {
		if ($is_cli) {
			print $ok_str . "\n";
		}
		$test_output[] = [$test_name,0];
		$pass_count++;
	} elseif ($is_regexp && !preg_match($expected, $html)) {
		if ($is_cli) {
			print $fail_str . "\n";
			print "  * Expected $expected but got $html (from: $file #$line)\n";
		}

		$test_output[] = [$test_name,"Expected $expected but got $html<br />(from: $file #$line)"];

		$fail_count++;
	} elseif ($html === $expected) {
		if ($is_cli) {
			print $ok_str . "\n";
		}

		$test_output[] = [$test_name,0];

		$pass_count++;
	} else {
		$d = debug_backtrace();
		$file = $d[0]['file'];
		$line = $d[0]['line'];

		if ($is_cli) {
			print $fail_str . "\n";
			print "  * Expected $expected but got $html (from: $file #$line)\n";
		}

		$test_output[] = [$test_name,"Expected $expected but got $html<br />(from: $file #$line)"];

		$fail_count++;
	}
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
