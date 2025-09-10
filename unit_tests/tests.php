<?php

////////////////////////////////////////////////////////

$dir = dirname(__FILE__);
// Make the tests work from the CLI in any dir
chdir($dir);
include("$dir/../sluz.class.php");

$sluz               = new sluz;
$sluz->debug        = 0;
$sluz->in_unit_test = true;

$simple = 0;
if (in_array('--simple', $argv ?? [])) {
	$simple = 1;
}

// Check if there is a filter at the command line
$filter = $argv[1] ?? $_GET['filter'] ?? "";

// If we passed `--simple` we don't want to set that as the filter
if (str_starts_with($filter, '--')) {
	$filter = '';
}

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

$sluz->assign('x'           , '7');
$sluz->assign('y'           , [2,4,6]);
$sluz->assign('key'         , 'val');
$sluz->assign('first'       , "Scott");
$sluz->assign('last'        , "Baker");
$sluz->assign('animal'      , "Kitten");
$sluz->assign('word'        , "cRaZy");
$sluz->assign('debug'       , 1);
$sluz->assign('array'       , ['one', 'two', 'three']);
$sluz->assign('cust'        , ['first' => 'Scott', 'last' => 'Baker']);
$sluz->assign('number'      , 15);
$sluz->assign('zero'        , 0);
$sluz->assign('members'     , [['first' => 'Scott', 'last' => 'Baker'], ['first' => 'Jason', 'last' => 'Doolis']]);
$sluz->assign('subarr'      , ['one' => [2,4,6], 'two' => [3,6,9]]);
$sluz->assign('arrayd'      , [[1,2],[3,4],[5,6]]);
$sluz->assign('empty'       , []);
$sluz->assign('php_version' , phpversion());
$sluz->assign('sluz_version', $sluz->version);
$sluz->assign('empty_string', '');
$sluz->assign('null'        , null);
$sluz->assign('true'        , true);
$sluz->assign('false'       , false);
$sluz->assign('conf'		, [ 'main' => true, 'debug' => false ]);
$sluz->assign($hash);

$test_output = [];

sluz_test('Hello there'                            , 'Hello there', 'Basic #1 - Static string');
sluz_test('{$first}'                               , 'Scott'      , 'Basic #2 - Basic variable');
sluz_test("{  \$first\t}"                          , 'Scott'      , 'Basic #3 - Whitespace');
sluz_test('{$bogus_var}'                           , ''           , 'Basic #4 - Missing variable');
sluz_test('{$animal|strtoupper}'                   , 'KITTEN'     , 'Basic #5 - PHP Modifier');
sluz_test('{$cust.first}'                          , 'Scott'      , 'Basic #6 - Hash Lookup');
sluz_test('{$array.1}'                             , 'two'        , 'Basic #7 - Array Lookup');
sluz_test('{$array|count}'                         , '3'          , 'Basic #8 - PHP Modifier array');
sluz_test('{$number + 3}'                          , '18'         , 'Basic #9 - Addition');
sluz_test('{$first . "foo"}'                       , "Scottfoo"   , 'Basic #10 - Concat');
sluz_test('{$number * $debug}'                     , '15'         , 'Basic #11 - Multiplication of two vars');
sluz_test('{3}'                                    , '3'          , 'Basic #12 - Number literal');
sluz_test('{"Scott"}'                              , "Scott"      , 'Basic #13 - String literal');
sluz_test('{"Scott" . "Baker"}'                    , "ScottBaker" , 'Basic #14 - String concat');
sluz_test('{$color . $age}'                        , "yellow43"   , 'Basic #15 - Variable concat');
sluz_test('{$x}'                                   , "7"          , 'Basic #16 - Single Character variable');
sluz_test('{$array[1]}'                            , 'two'        , 'Basic #17 - Array Lookup - PHP Syntax');
sluz_test('{$cust["last"]}'                        , 'Baker'      , 'Basic #18 - Hash Lookup - PHP Syntax');
sluz_test('{$last|default:\'123\'}'                , 'Baker'      , 'Basic #19 - Default - Not Used');
sluz_test('{$zero|default:\'123\'}'                , '0'          , 'Basic #20 - Default - Zero Not Used');
sluz_test('{$empty_string|default:\'123\'}'        , '123'        , 'Basic #21 - Default - Empty String');
sluz_test('{$null|default:\'123\'}'                , '123'        , 'Basic #22 - Default - Null');
sluz_test('{$bogus_var|default:"?*%.|"}'           , '?*%.|'      , 'Basic #23 - Default - non word char');
sluz_test('{foo'                                   , 'ERROR-45821', 'Basic #24 - Unclosed block');
sluz_test('{$first'                                , 'ERROR-45821', 'Basic #25 - Unclosed block variable');
sluz_test('{$cust.first|default:\'Jason\'}'        , 'Scott'      , 'Basic #26 - Hash with default value, not used');
sluz_test('{$cust.foo|default:\'Jason\'}'          , 'Jason'      , 'Basic #27 - Hash with default value, used');
sluz_test('{$array}'                               , 'Array'      , 'Basic #29 - Array used as a scalar');
sluz_test('{$word|truncate:3}'                     , 'cRa'        , 'Basic #30 - Modifier with param');
sluz_test('{$word|strtolower|ucfirst}'             , 'Crazy'      , 'Basic #31 - Chaining modifiers');
sluz_test('{$last|truncate:4|truncate:2}'          , 'Ba'         , 'Basic #32 - Two modifiers with params');
sluz_test('{$first|substr:2}'                      , 'ott'        , 'Basic #33 - PHP function with one param');
sluz_test('{$first|substr:2,2}'                    , 'ot'         , 'Basic #34 - PHP function with two params');
sluz_test('{if !$cust.age}unknown{else}{$age}{/if}', 'unknown'    , 'Basic #35 - Negated hash lookup');
sluz_test('{$y|join_comma}'                        , '2, 4, 6'    , 'Basic #36 - Function with default param');
sluz_test('{$y|join_comma:9}'                      , '29496'      , 'Basic #37 - Function with integer param');
sluz_test('{$y|join_comma:"*"}'                    , '2*4*6'      , 'Basic #38 - Function with string param');
sluz_test('{$y|join_comma:"|"}'                    , '2|4|6'      , 'Basic #39 - Function with string param pipe');
sluz_test('{$y|join_comma:","}'                    , '2,4,6'      , 'Basic #40 - Function with string param pipe comma');
sluz_test('{$y|join_comma:"\'"}'                   , "2'4'6"      , 'Basic #41 - Function with string param pipe single quote');
sluz_test('{1.1234 + 2.3456}'                      , "3.469"      , 'Basic #42 - Simple math that returns floating point');

// Bare functions must return a string
sluz_test('{hello_world()}' , "Hello world", 'Function #1 - Hello world');
sluz_test('{return_false()}', "ERROR-18933", 'Function #2 - Return false');
sluz_test('{return_null()}' , "ERROR-18933", 'Function #3 - Return null');

// Generic blocks EXPECTED to return an error
sluz_test('{junk}'           , "ERROR-73467", 'Error #1 - bare string');
sluz_test('{junk(}'          , "ERROR-18933", 'Error #2 - string with action char');
sluz_test('{$number + array}', "ERROR-18933", 'Error #3 - syntax error');
sluz_test('{if debug}'       , "ERROR-73467", 'Error #4 - syntax error');

sluz_test('{if $debug}DEBUG{/if}'                                , 'DEBUG'   , 'If #1 - Simple');
sluz_test('{if $bogus_var}DEBUG{/if}'                            , ''        , 'If #2 - Missing var');
sluz_test('{if $debug}{$first}{/if}'                             , 'Scott'   , 'If #3 - Variable as payload');
sluz_test('{if $debug}{if $debug}FOO{/if}{/if}'                  , 'FOO'     , 'If #4 - Nested');
sluz_test('{if $x}{if $null}yes{else}no{/if}{/if}'               , 'no'      , 'If #5 - Nested with else');
sluz_test('{if $one}{if $name}Yes{else}No{/if}{else}Unknown{/if}', 'Unknown' , 'If #6 - Nested with two elses');
sluz_test('{if $bogus_var}YES{else}NO{/if}'                      , 'NO'      , 'If #7 - Else');
sluz_test('{if $cust.first}{$cust.first}{/if}'                   , 'Scott'   , 'If #8 - Hash lookup');
sluz_test('{if $number > 10}GREATER{/if}'                        , 'GREATER' , 'If #9 - Comparison');
sluz_test('{if $bogus_var || $key}KEY{/if}'                      , 'KEY'     , 'If #10 - ||');
sluz_test('{if $number === 15 && $debug}YES{/if}'                , 'YES'     , 'If #11 - Two comparisons');
sluz_test('{if !$verbose}QUIET{/if}'                             , 'QUIET'   , 'If #12 - Negated comparison');
sluz_test('{if ($zero || $number > 10)}YES{/if}'                 , 'YES'     , 'If #13 - Parens');
sluz_test('{if count($array) > 2}YES{/if}'                       , 'YES'     , 'If #14 - PHP function conditional');
sluz_test('{if $debug}{$key}{$last}{/if}'                        , 'valBaker', 'If #15 - Two block payload');
sluz_test('{if $debug}ONE{else}TWO{/if}'                         , 'ONE'     , 'If #16 - Else not needed');
sluz_test('{if $zero}1{elseif $debug}2{else}3{/if}'              , '2'       , 'If #17 - Elseif');
sluz_test('{if $key}{if $one}one{elseif $x}X{else}ELSE{/if}{/if}', 'X'       , 'If #18 - Nested if with elseif');
sluz_test('{if $number}1{if $key}2{/if}3{/if}'                   , '123'     , 'If #19 - Nested if leading/trailing chars');
sluz_test('{if $true}123{else}456{/if}'                          , '123'     , 'If #20 - Boolean');
sluz_test('{if !$true}123{else}456{/if}'                         , '456'     , 'If #21 - Boolean inverted');
sluz_test('{if $conf.main}123{else}456{/if}'                     , '123'     , 'If #22 - Hash boolean');
sluz_test('{if !$conf.main}123{else}456{/if}'                    , '456'     , 'If #23 - Hash boolean inverted');
sluz_test('{if $x}{if $y}yes{/if}{else}no{/if}'                  , 'yes'     , 'If #24 - Nested if with an else');
sluz_test('{if true}a{else}b{if true}c{/if}{/if}'                , 'a'       , 'If #25 - Nested with true');
sluz_test('{if false}a{else}b{if true}c{/if}{/if}'               , 'bc'      , 'If #26 - Nested with false');
sluz_test('{if true}{/if}'                                       , ''        , 'If #27 - If with "" for payload');

sluz_test('{foreach $array as $num}{$num}{/foreach}'                         , 'onetwothree'            , 'Foreach #1 - Simple');
sluz_test("{foreach \$array as \$num}\n{\$num}\n{/foreach}"                  , "one\ntwo\nthree\n"      , 'Foreach #2 - Simple with whitespace');
sluz_test('{foreach $members as $x}{$x.first}{/foreach}'                     , 'ScottJason'             , 'Foreach #3 - Hash');
sluz_test('{foreach $arrayd as $x}{$x.1}{/foreach}'                          , '246'                    , 'Foreach #4 - Array');
sluz_test('{foreach $arrayd as $key => $val}{$key}:{$val.0}{/foreach}'       , '0:11:32:5'              , 'Foreach #6 - Key/val array');
sluz_test('{foreach $members as $id => $x}{$id}{$x.first}{/foreach}'         , '0Scott1Jason'           , 'Foreach #7 - Key/val hash');
sluz_test('{foreach $subarr.one as $id}{$id}{/foreach}'                      , '246'                    , 'Foreach #8 - Hash key');
sluz_test('{foreach $bogus_var as $x}one{/foreach}'                          , ''                       , 'Foreach #9 - Missing var');
sluz_test('{foreach $empty as $x}one{/foreach}'                              , ''                       , 'Foreach #10 - Empty array');
sluz_test('{foreach $array as $i => $x}{$i}{$x}{/foreach}'                   , '0one1two2three'         , 'Foreach #11 - One char variables');
sluz_test('{foreach $array as $i => $x}{if $x}{$x}{/if}{/foreach}'           , 'onetwothree'            , 'Foreach #12 - Foreach with nested if');
sluz_test('{foreach $arrayd as $i => $x}{if $x.1}{$x.1}{/if}{/foreach}'      , '246'                    , 'Foreach #13 - Foreach with nested if (array)');
sluz_test('{foreach $null as $x}one{/foreach}'                               , ''                       , 'Foreach #14 - Null');
sluz_test('{foreach $first as $x}{$first}{/foreach}'                         , 'Scott'                  , 'Foreach #15 - Scalar');
sluz_test('{foreach $array as $i}{foreach $array as $i}x{/foreach}{/foreach}', 'xxxxxxxxx'              , 'Foreach #16 - Nested');
// These tests make sure that the foreach above that sets $i and $x don't persist after
sluz_test('{$x}'                                                             , '7'                      , 'Foreach #17 - NOT overwrite variable - previously set');
sluz_test('{$i}'                                                             , ''                       , 'Foreach #18 - NOT overwrite variable - no initial value');
// End of persistence foreach tests
sluz_test('{foreach $y as $z}{$z}{/foreach}'                                   , '246'             , 'Foreach #19 - Foreach one char key');
sluz_test('{foreach $array as $x}{if $__FOREACH_FIRST}FIRST{/if}{$x}{/foreach}', 'FIRSTonetwothree', 'Foreach #20 - Foreach FIRST item');
sluz_test('{foreach $array as $x}{$x}{if $__FOREACH_LAST}LAST{/if}{/foreach}'  , 'onetwothreeLAST' , 'Foreach #21 - Foreach LAST item');
sluz_test('{foreach $array as $x}{$x}{$__FOREACH_INDEX}{/foreach}'             , 'one0two1three2'  , 'Foreach #22 - Foreach index');

sluz_test('Scott'           , 'Scott'           , 'Plain text #1 - Static text');
sluz_test('<div>Scott</div>', '<div>Scott</div>', 'Plain text #2 - HTML');

// Don't parse blocks that have whitespacing
sluz_test(' {$first} ', ' Scott '    , 'Bad block #1 - Padding with whitespace');
sluz_test('{first}'   , 'ERROR-73467', 'Bad block #2 - {word}'); // Literal (no $)

sluz_test('{literal}{{/literal}'                  , '{'                  , 'Literal #1 - {');
sluz_test('{literal}}{/literal}'                  , '}'                  , 'Literal #2 - }');
sluz_test('{literal}{}{/literal}'                 , '{}'                 , 'Literal #3 - Literal + {}');
sluz_test('{literal}{foreach}{/literal}'          , '{foreach}'          , 'Literal #4 - {literal}');
sluz_test('{literal}{literal}{/literal}{/literal}', '{literal}{/literal}', 'Literal #5 - Meta literal');
sluz_test(' { '                                   , ' { '                , 'Literal #6 - { with whitespace');
sluz_test('{}'                                    , '{}'                 , 'Literal #7 - Raw {}');

sluz_test("{\$x}{\$x}"                                   , '77'           , 'Whitespace input/output #1');
sluz_test("{\$x} {\$x}"                                  , '7 7'          , 'Whitespace input/output #2');
sluz_test("{\$x}\n{\$x}"                                 , "7\n7"         , 'Whitespace input/output #3');
sluz_test("{foreach \$y as \$x}{\$x}{/foreach}"          , "246"          , 'Whitespace input/output #4');
sluz_test("{foreach \$y as \$x}\n{\$x}\n{/foreach}"      , "2\n4\n6\n"    , 'Whitespace input/output #5');
sluz_test("{if \$x}{\$x}{/if}"                           , "7"            , 'Whitespace input/output #6');
sluz_test("{if \$x}\n{\$x}\n{/if}"                       , "7\n"          , 'Whitespace input/output #7');
sluz_test("{foreach \$y as \$x}\n{\$x}\n{/foreach}\nlast", "2\n4\n6\nlast", 'Whitespace input/output #8');

sluz_test('{* Comment *}'           , '', 'Comment #1 - With text');
sluz_test('{* ********* *}'         , '', 'Comment #2 - ******');
sluz_test('{**}'                    , '', 'Comment #3 - No whitespace');
sluz_test('{*{$array|count}*}'      , '', 'Comment #4 - Variable inside');
sluz_test('{* {* nested *} *}'      , '', 'Comment #5 - Nested');
sluz_test('{* {* {* nested *} *} *}', '', 'Comment #6 - Triple Nested');

sluz_test("{include file='tpls/extra.stpl'}"                 , '/e1ab49cf/' , 'Include #1 - file=\'extra.stpl\'');
sluz_test("{include 'tpls/extra.stpl'}"                      , '/e1ab49cf/' , 'Include #2 - \'extra.stpl\'');
sluz_test('{include}'                                        , 'ERROR-73467', 'Include #3 - No payload');
sluz_test("{include file='tpls/extra.stpl' secret='eca4906'}", '/eca4906/'  , 'Include #4 - With variable');

sluz_test(['{$a}{$b}{$c}']                                                     , 3, 'Get blocks #1 - Basic variables');
sluz_test(['{if $a}{$a}{/if}']                                                 , 1, 'Get blocks #2 - Basic variables');
sluz_test(['Jason{$a}Baker{$b}']                                               , 4, 'Get blocks #3 - Basic variables');
sluz_test(['function(foo) { $i = 10; }']                                       , 1, 'Get blocks #4 - javascript function');
sluz_test(['{* Comment *}ABC{* Comment *}']                                    , 1, 'Get blocks #5 - Comments');
sluz_test(['   {$x}   ']                                                       , 3, 'Get blocks #6 - Whitespace around variable');
sluz_test(['{foreach $arr as $i => $x}{if $x.1}{$x.1}{/if}{/foreach}']         , 1, 'Get blocks #7 - Lots of brackets');
sluz_test(['{*{$first}*}']                                                     , 0, 'Get blocks #8 - Comment with variable');
sluz_test(['{*{$first} {$last}*}']                                             , 0, 'Get blocks #9 - Comments with variables');
sluz_test([' {* {$foo} *} ']                                                   , 2, 'Get blocks #10 - Comments with variables and whitespace');
sluz_test(['{foreach $array as $i}{foreach $array as $i}x{/foreach}{/foreach}'], 1, 'Get blocks #11 - Nested foreach');
sluz_test(["{\$foo}\n{\$bar}"]                                                 , 3, 'Get blocks #12 - Only whitespace block');
sluz_test(["{\$foo}\n\n{\$bar}"]                                               , 3, 'Get blocks #13 - Double whitespace block');

//////////////////////////////////////////////////////////////////////////////////////////

$sluz->open_char  = '<';
$sluz->close_char = '>';

sluz_test('<* Comment *>'                   , ''                       , 'Alternate Delimiter #1');
sluz_test('{"name": "Scott"}'               , '{"name": "Scott"}'      , 'Alternate Delimiter #2');
sluz_test('{"last": "<$last>"}'             , '{"last": "Baker"}'      , 'Alternate Delimiter #3');
sluz_test('<if $debug>DEBUG</if>'           , 'DEBUG'                  , 'Alternate Delimiter #4');
sluz_test('<foreach $y as $i><$i></foreach>', '246'                    , 'Alternate Delimiter #5');
sluz_test('<literal><<</literal>'           , '<<'                     , 'Alternate Delimiter #6');
sluz_test('<* <* nested *> *>'              , ''                       , 'Alternate Delimiter #7');
sluz_test("<include 'tpls/json.stpl'>"      , "{\"name\": \"Scott\"}\n", 'Alternate Delimiter #8');

// We have to reset the delimters for the HTML tests to run
$sluz->open_char  = '{';
$sluz->close_char = '}';
// Fetch tests
//////////////////////////////////////////////////////////////////////////////////////////

sluz_fetch_test(["tpls/extra.stpl"], "/extra.stpl/s"    , "Fetch #1 - Simple fetch");
sluz_fetch_test(["tpls/child.stpl" , "tpls/parent.stpl"], "/0fd197af.*21c1a4c5/s", "Parent/Child #1 - Fetch with two params");

// Set and then reset the parent tpl
$x = $sluz->parent_tpl("tpls/parent.stpl");
sluz_fetch_test(["tpls/child.stpl"], "/0fd197af.*21c1a4c5/s", "Parent/Child #2 - Fetch with preset parent");
$sluz->parent_tpl = "";

//////////////////////////////////////////////////////////////////////////////////////////

$total = $pass_count + $fail_count;

if ($is_cli) {
	if (!$simple) {
		print "\n";
	}

	printf("Tests run on PHP %s\n", phpversion());
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

	// If the first word of this test, and the last test are NOT
	// the same it's a new section.
	$last = "";
	foreach ($test_output as &$x) {
		$words = preg_split("/ /", $x[0], 2);
		$word  = $words[0] ?? "";

		if ($last && $word != $last) {
			$x['new'] = 1;
		} else {
			$x['new'] = 0;
		}

		$last = $word;
	}

	$sluz->assign("tests", $test_output);
	$sluz->tpl_file = "tpls/tests.stpl";
	print $sluz->fetch("tpls/tests.stpl");
}

////////////////////////////////////////////////////////

function sluz_fetch_test($files, $pattern, $test_name) {
	global $sluz;
	global $pass_count;
	global $fail_count;
	global $test_output;
	global $filter;
	global $is_cli;
	global $ok_str;
	global $fail_str;
	global $simple;

	if (!empty($filter) && !preg_match("/$filter/i", $test_name)) { return; }

	////////////////////////////////////////

	$lead = "Test '$test_name' ";
	$pad  = str_repeat(" ", 80 - (strlen($lead)));

	$out = "";
	if ($is_cli) {
		$out = "$lead $pad";
	}

	$ok    = "\033[32m";
	$fail  = "\033[31m";
	$reset = "\033[0m";
	$white = "\033[1m";

	$l = $white . "[" . $reset;
	$r = $white . "]" . $reset;

	////////////////////////////////////////

	$child  = $files[0] ?? "";
	$parent = $files[1] ?? null;

	$str = $sluz->fetch($child, $parent);

	if (preg_match($pattern, $str)) {
		$pass_count++;
		$test_output[] = [$test_name,0];

		if ($is_cli) {
			$out .= $ok_str . "\n";
		}
	} else {
		if ($is_cli) {
			$out .= $fail_str . "\n";
		}
		$fail_count++;
		$test_output[] = [$test_name, "Expected $pattern"];
	}

	if (!$simple) {
		print $out;
	}
}

function sluz_test($input, $expected, $test_name) {
	global $sluz;
	global $pass_count;
	global $fail_count;
	global $ok_str;
	global $fail_str;
	global $filter;
	global $is_cli;
	global $test_output;
	global $simple;

	if (!empty($filter) && !preg_match("/$filter/i", $test_name)) { return; }

	if (is_array($input)) {
		$res  = $sluz->get_blocks($input[0]);
		$html = count($res);
	} else {
		$blocks = $sluz->get_blocks($input);
		$html   = '';

		foreach ($blocks as $x) {
			$input = $x[0];
			$pos   = $x[1];
			$html .= $sluz->process_block($input, $pos);
		}
	}

	$lead = "Test '$test_name' ";
	$pad  = str_repeat(" ", 80 - (strlen($lead)));

	$out = "";
	if ($is_cli) {
		$out = "$lead $pad";
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

	// Make the \n in the unit tests visible
	$expected = preg_replace("/\n/", "\\n", $expected);
	$html     = preg_replace("/\n/", "\\n", $html);

	if ($is_regexp && preg_match($expected, $html)) {
		if ($is_cli) {
			$out .= $ok_str . "\n";
		}
		$test_output[] = [$test_name,0];
		$pass_count++;
	} elseif ($is_regexp && !preg_match($expected, $html)) {
		$d = debug_backtrace();
		$file = $d[0]['file'];
		$line = $d[0]['line'];

		if ($is_cli) {
			$out .= $fail_str . "\n";
			$out .= "  * Expected $expected but got $html (from: $file #$line)\n";
		}

		$test_output[] = [$test_name,"Expected <code>$expected</code> but got <code>$html</code><br />(from: $file #$line)"];

		$fail_count++;
	} elseif ($html === $expected) {
		if ($is_cli) {
			$out .= $ok_str . "\n";
		}

		$test_output[] = [$test_name,0];

		$pass_count++;
	} else {
		$d = debug_backtrace();
		$file = $d[0]['file'];
		$line = $d[0]['line'];

		if ($is_cli) {
			$out .= $fail_str . "\n";
			$out .= "  * Expected $expected but got $html (from: $file #$line)\n";
		}

		$test_output[] = [$test_name,"Expected <code>$expected</code> but got <code>$html</code><br />(from: $file #$line)"];

		$fail_count++;
	}

	if (!$simple) {
		print $out;
	}
}

// This is just to test user functions/modifiers
function truncate($str, int $len) {
	$ret = substr($str, 0, $len);
	return $ret;
}

// Join an array with commas or custom separator
function join_comma(array $arr, string $separator = ", ") {
	return join($separator, $arr);
}

function hello_world() {
	return "Hello world";
}

function return_false() {
	return false;
}

function return_null() {
	return null;
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
