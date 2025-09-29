<?php

////////////////////////////////////////////////////////

define('SLUZ_INLINE', 'INLINE_TEMPLATE'); // Just a specific string

class sluz {
	public $version       = '0.9.0';
	public $tpl_file      = null;       // The path to the TPL file
	public $inc_tpl_file  = null;       // The path to the {include} file

	public $debug         = 0;          // Enable debug mode
	public $in_unit_test  = false;      // Boolean if we are in unit testing mode
	public $tpl_vars      = [];         // Array of variables assigned to the TPL
	public $parent_tpl    = null;       // Path to parent TPL

	private $var_prefix   = "sluz_pfx"; // Variable prefix for extract()
	private $php_file     = null;       // Path to the calling PHP file
	private $php_file_dir = null;       // Path to the calling PHP directory
	private $simple_mode  = false;      // Boolean are we in simple mode
	private $fetch_called = false;      // Boolean used in simple if fetch has been called
	private $char_pos     = -1;         // Character offset in the TPL

	public function __construct() { }
	public function __destruct()  {
		// In simple mode we auto print the output
		if ($this->simple_mode && !$this->fetch_called) {
			print $this->fetch();
		}
	}

	// Assign variables to pass to the TPL
	public function assign($key, $val = null) {
		// Single item call (assign array at once)
		if (is_null($val) && is_array($key)) {
			$this->tpl_vars = array_merge($this->tpl_vars, $key);
		} else {
			$this->tpl_vars[$key] = $val;
		}
	}

	// Convert template blocks in to output strings
	public function process_block(string $str, int $char_pos = -1) {
		$ret = '';

		$this->char_pos = $char_pos;

		// Simple variable replacement {$foo} or {$foo|default:"123"}
		if (str_starts_with($str, '{$') && preg_match('/^\{\$(\w[\w\|\.\'";\t :,!@#%^&*?_-]*)\}$/', $str, $m)) {
			$ret = $this->variable_block($m[1]);
		// If statement {if $foo}{/if}
		} elseif (str_starts_with($str, '{if ') && str_ends_with($str, '{/if}')) {
			$ret = $this->if_block($str);
		// Foreach {foreach $foo as $x}{/foreach}
		} elseif (str_starts_with($str, '{foreach ') && preg_match('/^\{foreach (\$\w[\w.]*) as \$(\w+)( => \$(\w+))?\}(.+)\{\/foreach\}$/s', $str, $m)) {
			$ret = $this->foreach_block($m);
		// Include {include file='my.stpl' number='99'}
		} elseif (str_starts_with($str, '{include ')) {
			$ret = $this->include_block($str);
		// Liternal {literal}Stuff here{/literal}
		} elseif (str_starts_with($str, '{literal}') && preg_match('/^\{literal\}(.+)\{\/literal\}$/s', $str, $m)) {
			$ret = $m[1];
		// Catch all for other { $num + 3 } type of blocks
		} elseif (preg_match('/^\{(.+)}$/s', $str, $m)) {
			$ret = $this->expression_block($str, $m);
		// If it starts with '{' (from above) but does NOT contain a closing tag
		} elseif (!str_ends_with($str, '}')) {
			list($line, $col, $file) = $this->get_char_location($this->char_pos, $this->tpl_file);
			return $this->error_out("Unclosed tag <code>$str</code> in <code>$file</code> on line #$line", 45821);
		// Something went WAY wrong
		} else {
			$ret = $str;
		}

		return $ret;
	}

	// Break the text up in to tokens/blocks to process by process_block()
	public function get_blocks($str) {
		$start_time = microtime(1);

		$start  = 0;
		$blocks = [];
		$slen   = strlen($str);

		// Start looking for blocks at the first delim
		$z = strpos($str, '{');
		if ($z === false) { $z = $slen; }

		for ($i = $z; $i < $slen; $i++) {
			$char      = $str[$i];
			$is_open   = $char === "{";
			$is_closed = $char === "}";

			// If it's not an opening or closing tag we jump ahead to the next delim
			if (!$is_open && !$is_closed) {
				$next_open = strpos($str, '{', $i);
				if ($next_open === false) { $next_open = $slen; }

				$next_close = strpos($str, '}', $i);
				if ($next_close === false) { $next_close = $slen; }

				// The next char to look at is the first open/close delim
				$i = min($next_open, $next_close) -1;

				continue;
			}

			$has_len    = $start != $i;
			$is_comment = false;

			// Check to see if it's a real {} block
			if ($is_open) {
				$prev_c = $str[$i - 1];
				$next_c = $str[$i + 1];
				$chunk  = $prev_c . $char . $next_c;

				// If the { is surrounded by whitespace it's not a block
				if (preg_match("/\s[\{\}]\s/", $chunk)) {
					$is_open = false;
				}

				if ($next_c === "*") {
					$is_comment = true;
				}
			}

			// if it's a "{" then the block is every from the last $start to here
			if ($is_open && $has_len) {
				$len   = $i - $start;
				$block = substr($str, $start, $len);

				if ($block) {
					$blocks[] = [$block, $i];
				}
				$start    = $i;
			// If it's a "}" it's a closing block that starts at $start
			} elseif ($is_closed) {
				$len         = $i - $start + 1;
				$block       = substr($str, $start, $len);
				$is_function = preg_match("/^\{(if|foreach|literal)\b/", $block, $m);

				// If we're in a function, loop until we find the closing tag
				if ($is_function) {
					// Go character by character until we find a '}' and see if we find the closing tag
					for ($j = $i + 1; $j < strlen($str); $j++) {
						$closed = ($str[$j] === "}");

						// If we find a close tag we check to see if it's the final closed tag
						if ($closed) {
							$len = $j - $start + 1;
							$tmp = substr($str, $start, $len);

							// Open tag is whatever word is after the '{'
							$open_tag  = $m[1];
							// Build the closing tag so we can look for it later
							$close_tag = "{/$open_tag}";

							$open_count  = substr_count($tmp, '{' . $open_tag);
							$close_count = substr_count($tmp, $close_tag);

							//k([$open_tag, $close_tag, $open_count, $close_count, $tmp], KRUMO_EXPAND_ALL);

							// If this closing bracket is the closing tag we found the pair
							if ($open_count === $close_count && (str_ends_with($tmp, $close_tag))) {
								$block = $tmp;
								break;
							}
						}
					}
				}

				if ($block) {
					$blocks[]  = [$block, $i];
				}
				$start    += strlen($block);
				$i         = $start;
			}

			// If it's a comment we slurp all the chars until the first '*}' and make that the block
			if ($is_comment) {
				$end = $this->find_ending_tag(substr($str, $start), '{*', '*}');

				if ($end === false) {
					list($line, $col, $file) = $this->get_char_location($i, $this->tpl_file);
					return $this->error_out("Missing closing <code>*}</code> for comment in <code>$file</code> on line #$line", 48724);
				}

				$end += 2; // '*}' is 2 long so we add that

				$end_rel    = $end - $start;
				$start      += $end;
				$i          = $start;
			}
		}

		// If we're not at the end of the string, add the last block
		if ($start < $slen) {
			$block    = substr($str, $start);
			if ($block) {
				$blocks[] = [$block, $i];
			}
		}

		// If the *previous* block was an {if} or {foreach} we remove one leading \n
		// to maintain parity between input and output whitespace. ^ is the whitespace
		// we're removing.
		//
		// This allows templates like:
		//
		// {foreach $name as $x}
		// {$x}
		// {/foreach}^
		$prev_is_if = false;
		for ($i = 0; $i < count($blocks); $i++) {
			$str       = $blocks[$i][0] ?? "";
			$cur_is_if = str_starts_with($str, '{if') || str_starts_with($str, '{for');

			if ($prev_is_if) {
				$blocks[$i][0] = $this->ltrim_one($str, "\n");
			}

			$prev_is_if = $cur_is_if;
		}

		return $blocks;
	}

	// This is just a wrapper function because early versions of Sluz used parse() instead of fetch()
	public function parse($tpl_file = "") {
		$ret = $this->fetch($tpl_file);

		return $ret;
	}

	// Wrapper function to make us more compatible with Smarty
	public function display($tpl_file = "") {
		print $this->fetch($tpl_file);
	}

	// Specify a path to the .stpl file, or pass nothing to let sluz 'guess'
	// Guess is 'tpls/[scriptname_minus_dot_php].stpl
	public function fetch($tpl_file = "", $parent = null) {
		if (!$this->php_file) {
			$this->php_file     = $this->get_php_file();
			$this->php_file_dir = dirname($this->php_file);
        }

		// We use ABSOLUTE paths here because this may be called in the destructor which has a cwd() of '/'
		if (!$tpl_file) {
			$tpl_file = $this->guess_tpl_file($this->php_file);
		}

		$parent_tpl = $parent ?? $this->parent_tpl;
		if (!empty($parent_tpl)) {
			$this->assign("__CHILD_TPL", $tpl_file);
			$tpl_file = $parent_tpl;
		}

		$str    = $this->get_tpl_content($tpl_file);
		$blocks = $this->get_blocks($str);
		$html   = $this->process_blocks($blocks);

		$this->fetch_called = true;

		return $html;
	}

	public function parse_string($tpl_str) {
		$blocks = $this->get_blocks($tpl_str);
		$html   = $this->process_blocks($blocks);

		return $html;
	}

	// Guess the TPL filename based on the PHP file
	public function guess_tpl_file($php_file) {
		$base     = basename($php_file);
		$tpl_name = preg_replace('/\.php$/', '.stpl', $base);
		$ret      = "tpls/$tpl_name";

		return $ret;
	}

	// Find the calling parent PHP file (from the perspective of the sluz class)
	public function get_php_file() {
		$x    = debug_backtrace();
		$last = count($x) - 1;
		$ret  = $x[$last]['file'] ?? "";

		return $ret;
	}

	// Turn an array of blocks into output HTML
	private function process_blocks(array $blocks) {
		$start_time = microtime(1);
		$html       = '';

		foreach ($blocks as $x) {
			$block      = $x[0];
			$first_char = ($block[0] ?? "") === '{';

			// If the first char is a { it's something we need to process
			if ($first_char) {
				$char_pos  = $x[1];
				$html     .= $this->process_block($block, $char_pos);
			// It's a static text block so we just append it
			} elseif ($block) {
				$html .= $block;
			}
		}

		return $html;
	}

	// Load the template file into a string
	private function get_tpl_content($tpl_file) {
		$start_time = microtime(1);
		$tf         = $this->tpl_file = $tpl_file;

		// If we're in simple mode and we have a __halt_compiler() we can assume inline mode
		$inline_simple = $this->simple_mode && $this->get_inline_content($this->php_file);
		$is_inline     = ($tpl_file === SLUZ_INLINE) || $inline_simple;

		if ($this->php_file_dir) {
			$tf  = $this->php_file_dir . "/$tf";
		}

		if ($is_inline) {
			$str = $this->get_inline_content($this->php_file);
		} elseif ($tf && !is_readable($tf)) {
			return $this->error_out("Unable to load template file <code>$tf</code>",42280);
		} elseif ($tf) {
			$str = file_get_contents($tf);
		}

		if (empty($str)) { $str = ""; }

		return $str;
	}

	// Get the text after __halt_compiler()
	private function get_inline_content($file) {
		$str    = file_get_contents($file);
		$offset = stripos($str, '__halt_compiler();');

		if ($offset === false) {
			return null;
		}

		$ret = substr($str, $offset + 18);

		return $ret;
	}

	// Find the include TPL in the {include} string
	function extract_include_file($str) {
		// {include file='foo.stpl'}
		if (preg_match("/\s(file=)(['\"].+?['\"])/", $str, $m)) {
			$xstr = $this->convert_variables_in_string($m[2]);
			$ret  = $this->peval($xstr);
		// {include 'foo.stpl'} - unofficial
		} elseif (preg_match("/\s(['\"].+?['\"])/", $str, $m)) {
			$xstr = $this->convert_variables_in_string($m[1]);
			$ret  = $this->peval($xstr);
		} else {
			list($line, $col, $file) = $this->get_char_location($this->char_pos, $this->tpl_file);
			return $this->error_out("Unable to find a file in include block <code>$str</code> in <code>$file</code> on line #$line", 68493);
		}

		$this->inc_tpl_file = $ret;

		return $ret;
	}

	// Extract data from an array in the form of $foo.key.baz
	public function array_dive(string $needle, array $haystack) {
		// Do a simple hash lookup first before we dive deep (we may get lucky)
		$x = $haystack[$needle] ?? null;
		if ($x) {
			return $x;
		}

		// Split at the periods
		$parts = explode(".", $needle);

		// Loop through each level of the hash looking for elem
		$arr = $haystack;
		foreach ($parts as $elem) {
			$arr = $arr[$elem] ?? null;

			// If we don't find anything stop looking
			if ($arr === null) {
				break;
			}
		}

		// If we find a scalar it's the end of the line, anything else is just
		// another branch, so it doesn't cound as finding something
		if (is_scalar($arr) || is_array($arr)) {
			$ret = $arr;
		} else {
			$ret = null;
		}

		return $ret;
	}

	// Convert $cust.name.first -> $cust['name']['first'] and $num.0.3 -> $num[0][3]
	private function convert_variables_in_string($str) {
		// If there are no dollars signs it's not a variable string, nothing to do
		if (!str_contains($str, '$')) {
			return $str;
		}

		// Process flat arrays in the test like $cust.name or $array[3]
		$callback = array($this, 'dot_to_bracket_callback');
		$str      = preg_replace_callback('/(\$\w[\w\.]*)/', $callback, $str);

		return $str;
	}

	// Build an evalable string from a variable string
	private function dot_to_bracket_callback($m) {
		$str   = $m[1];
		$parts = explode(".", $str);

		$ret = array_shift($parts);
		$ret = "$" . $this->var_prefix . '_' . substr($ret,1);
		foreach ($parts as $x) {
			if (is_numeric($x)) {
				$ret .= "[" . $x . "]";
			} else {
				$ret .= "['" . $x . "']";
			}
		}

		return $ret;
	}

	// Spit out an error message
	public function error_out($msg, int $err_num) {
		$style = "
			.s_error {
				font-family  : sans;
				color        : #842029;
				padding      : 0.8em;
				border-radius: 4px;
				margin-bottom: 8px;
				background   : #f8d7da;
				border       : 1px solid #f5c2c7;
				max-width    : 70%;
				margin       : auto;
				min-width    : 370px;
			}

			.s_error_head {
				margin-top : 0;
				color      : white;
				text-shadow: 0px 0px 7px gray;
			}
			.s_error_num { margin-top: 1em; }
			.s_error_file {
				margin-top : 2em;
				padding-top: 0.5em;
				font-size  : .8em;
				border-top : 1px solid gray;
			}

			.s_error code {
				padding         : .2rem .4rem;
				font-size       : 1.1em;
				border-radius   : .2rem;
				background-color: #dad5d5;
				color           : #1a1a1a;
				border          : 1px solid #c2c2c2;
			}
		";

		if ($this->in_unit_test) {
			return "ERROR-$err_num";
		}

		$d    = debug_backtrace();
		$file = $d[0]['file'] ?? "";
		$line = $d[0]['line'] ?? 0;

		$title = "Sluz error #$err_num";

		$body  = "<div class=\"s_error\">\n";
		$body .= "<h1 class=\"s_error_head\">Sluz Fatal Error #$err_num</h1>";
		$body .= "<div class=\"s_error_desc\"><b>Description:</b> $msg</div>";

		if ($file && $line) {
			$body .= "<div class=\"s_error_file\">Source: <code>$file</code> #$line</div>";
		}

		$body .= "</div>\n";

		$out = "<!doctype html>
		<html lang=\"en\">
			<head>
				<meta charset=\"utf-8\">
				<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
				<title>$title</title>
				<style>$style</style>
			</head>
			<body>
				$body
			</body>
		</html>";

		print $out;

		exit;
	}

	// A bunch of little optimizations and shortcuts
	private function micro_optimize($input) {
		// Optimize raw integers
		if (is_numeric($input)) {
			return $input;
		}

		////////////////////////////////////////////

		// Optimize simple $vars
		$first_char = $last_char = null;
		if ($input && is_string($input)) {
			$first_char = $input[0];
			$last_char  = $input[-1];
		// It's not a number or a string?
		} else {
			return $input;
		}

		// If it starts with a '$' we might be able to cheat
		if ($first_char === '$') {
			// Remove the prefix so we can look it up raw
			$new = str_replace('$' . $this->var_prefix . '_', '', $input);
			$ret = $this->tpl_vars[$new] ?? null;

			return $ret;
		}

		// If it starts with a '!$' we might be able to cheat and invert
		if (str_starts_with($input, '!$')) {
			// Remove the prefix so we can look it up raw
			$new = str_replace('!$' . $this->var_prefix . '_', '', $input);
			$ret = $this->tpl_vars[$new] ?? null;

			if ($ret !== null) {
				return !$ret;
			}
		}

		////////////////////////////////////////////

		// Optimize a simple 'string'
		if ($first_char === "'" && $last_char === "'") {
			$tmp        = substr($input,1,strlen($input) - 2);
			$is_complex = str_contains($tmp, "'");

			if (!$is_complex) {
				return $tmp;
			}
		}

		////////////////////////////////////////////

		// Optimize a simple "string"
		if ($first_char === '"' && $last_char === '"') {
			$tmp        = substr($input,1,strlen($input) - 2);
			$is_complex = str_contains($tmp, '$') || str_contains($tmp, '"');

			if (!$is_complex) {
				return $tmp;
			}
		}

		return null;
	}

	// A smart wrapper around eval()
	private function peval($str, &$err = 0) {
		$x = $this->micro_optimize($str);
		if ($x) {
			return $x;
		}

		extract($this->tpl_vars, EXTR_PREFIX_ALL, $this->var_prefix);

		$ret = '';
		$cmd = '$ret = (' . $str. ");";

		try {
			@eval($cmd);
		} catch (ParseError $e) {
			// Ooops
			$err = -1;
		}

		//k([$str, $cmd, $ret, $err], KRUMO_EXPAND_ALL);

		return $ret;
	}

	// Turn on simple mode
	public function enable_simple_mode($php_file) {
		$this->php_file     = $php_file;
		$this->php_file_dir = dirname($php_file);
		$this->simple_mode  = true;
	}

	///////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	// parse a simple variable
	private function variable_block($str) {

		// If it has a '|' it's either a function call or 'default'
		if (preg_match("/(.+?)\|(.*)/", $str, $m)) {
			$key = $m[1];
			$mod = $m[2];

			$tmp        = $this->array_dive($key, $this->tpl_vars) ?? "";
			$is_nothing = ($tmp === null || $tmp === "");
			$is_default = str_contains($mod, "default:");

			// Empty with a default value
			if ($is_nothing && $is_default) {
				$p    = explode("default:", $str, 2);
				$dval = $p[1] ?? "";
				$ret  = $this->peval($dval);
			// Non-empty, but has a default value
			} elseif (!$is_nothing && $is_default) {
				$ret = $this->array_dive($key, $this->tpl_vars) ?? "";
			// User function
			} else {
				$pre   = $this->array_dive($key, $this->tpl_vars) ?? "";

				// Each modifier is separated by a | but we only split on
				// pipes that are NOT in a quoted string. Pattern provided
				// by ChatGPT.
				$pattern = '/\|(?![^"]*"(?:(?:[^"]*"){2})*[^"]*$)/';
				$parts   = preg_split($pattern, $mod);

				// Loop through each modifier (chaining)
				foreach ($parts as $mod) {
					$x         = preg_split("/:/", $mod);
					$func      = $x[0] ?? "";
					$param_str = $x[1] ?? "";
					$params    = [$pre];

					if ($param_str) {
						// Split a string by commas only when they are not inside quotation marks
						$new = preg_split('/,(?=(?:[^"]*"[^"]*")*[^"]*$)/', $param_str);
						$new = array_map([$this, 'peval'], $new);

						$params = array_merge($params, $new);
					}

					//k([$str, $parts, $func, $new, $params]);
					//printf("Calling: %s([%s])<br />\n", $func, join(", ", $params[0]));

					if (!is_callable($func)) {
						list($line, $col, $file) = $this->get_char_location($this->char_pos, $this->tpl_file);
						return $this->error_out("Unknown function call <code>$func</code> in <code>$file</code> on line #$line", 47204);
					}

					try {
						$pre = call_user_func_array($func, $params);
					} catch (Exception $e) {
						$msg = "Exception: " . $e->getMessage();
						$this->error_out($msg, 79134);
					} catch (TypeError $e) {
						$msg = "TypeError: " . $e->getMessage();
						$this->error_out($msg, 58200);
					}
				}

				$ret = $pre;
			}
		} else {
			$ret = $this->array_dive($str, $this->tpl_vars) ?? "";
		}

		// Array used as a scalar should silently convert to a string
		if (is_array($ret)) {
			return 'Array';
		}

		return $ret;
	}

	// Find the line/column based on char offset in a file
	private function get_char_location($pos, $tpl_file) {
		// If we're in an {include} the tpl is that temporarily
		if ($this->inc_tpl_file) {
			$tpl_file = $this->inc_tpl_file;
		}
		$str = $this->get_tpl_content($tpl_file);

		// Error catching...
		if ($pos < 0) {
			return [-1, -1, $tpl_file];
		}

		$line = 1;
		$col  = 0;
		for ($i = 0; $i < strlen($str); $i++) {
			$col++;
			$char = $str[$i];

			if ($char === "\n") {
				$line++;
				$col = 0;
			}

			if ($pos === $i) {
				$ret = [$line, $col, $tpl_file];
				return $ret;
			}
		}

		if ($pos === strlen($str)) {
			return [$line, $col, $tpl_file];
		}

		return [-1, -1, $tpl_file];
	}

	// Parse an if statement
	private function if_block($str) {
		// If it's a simple {if $name}Output{/if} we can save a lot of
		// time parsing detailed rules
		// i.e. there is no {else} or {elseif}
		$is_simple = (strpos($str, "{else", 7) === false);

		if ($is_simple) {
			preg_match("/{if (.+?)}(.+){\/if}/s", $str, $m);
			$cond     = $m[1] ?? "";
			$payload  = $m[2] ?? "";

			// This makes input -> output whitespace more correct
			$payload  = $this->ltrim_one($payload, "\n");

			$rules[0] = [$cond, $payload];
		} else {
			$toks  = $this->get_tokens($str);
			$rules = $this->get_if_rules_from_tokens($toks);
		}

		// Put the tpl_vars in the current scope so if works against them
		extract($this->tpl_vars, EXTR_PREFIX_ALL, $this->var_prefix);

		$ret = "";
		foreach ($rules as $x) {
			$test    = $x[0];
			$payload = $x[1];
			$testp   = $this->convert_variables_in_string($test);

			if ($this->peval($testp)) {
				$blocks  = $this->get_blocks($payload);
				$ret    .= $this->process_blocks($blocks);

				// One of the tests was true so we stop processing
				break;
			}
		}

		return $ret;
	}

	// Parse an include block
	private function include_block($str) {
		// Include blocks may modify tpl vars, so we save them here
		$save    = $this->tpl_vars;
		$inc_tpl = $this->extract_include_file($str);

		if ($this->php_file_dir) {
			$inc_tpl = $this->php_file_dir . "/$inc_tpl";
		}

		// Extra variables to include sub templates
		if (preg_match_all("/(\w+)=(['\"](.+?)['\"])/", $str, $m)) {
			for ($i = 0; $i < count($m[0]); $i++) {
				$key = $m[1][$i] ?? "";
				$val = $m[2][$i] ?? "";

				// We skip the file='header.stpl' option
				if ($key === 'file') { continue; }

				$val = $this->convert_variables_in_string($val);
				$val = $this->peval($val);

				$this->assign($key, $val);
			}
		}

		if (!is_file($inc_tpl) || !is_readable($inc_tpl)) {
			$this->inc_tpl_file = null; // Clear temp override so this error displays correctly
			list($line, $col, $file) = $this->get_char_location($this->char_pos, $this->tpl_file);
			return $this->error_out("Unable to load include template <code>$inc_tpl</code> in <code>$file</code> on line #$line", 18485);
		}

		$str    = file_get_contents($inc_tpl);
		$blocks = $this->get_blocks($str);
		$ret    = $this->process_blocks($blocks);

		// Restore the TPL vars to pre 'include' state
		$this->tpl_vars     = $save;
		$this->inc_tpl_file = null; // Clear temp override

		return $ret;
	}

	// Remove ONE \n from the beginning of a string
	function ltrim_one(string $str, $char) {
		if (isset($str[0]) && $str[0] === $char) {
			return substr($str, 1);
		}

		return $str;
	}

	// Parse a foreach block
	private function foreach_block($m) {
		$src     = $this->convert_variables_in_string($m[1]); // src array
		$okey    = $m[2]; // orig key
		$oval    = $m[4]; // orig val
		$payload = $m[5]; // code block to parse on iteration
		$payload = $this->ltrim_one($payload, "\n"); // Input -> Output \n parity
		$blocks  = $this->get_blocks($payload);

		$src = $this->peval($src);

		// If $src isn't an array we convert it to one so foreach doesn't barf
		if (isset($src) && !is_array($src)) {
			$src = [$src];
		// This prevents an E_WARNING on null (but doesn't output anything)
		} elseif (is_null($src)) {
			$src = [];
		}

		// Save the current values so we can restore them later
		$save = $this->tpl_vars;

		$ret  = '';
		$idx  = 0;
		$last = count($src) - 1;
		// Temp set a key/val so when we process this section it's correct
		foreach ($src as $key => $val) {
			// Set if we're on the FIRST iteration
			if ($idx === 0) {
				$this->tpl_vars['__FOREACH_FIRST'] = true;
			} else {
				$this->tpl_vars['__FOREACH_FIRST'] = false;
			}

			// Set if we're on the LAST iteration
			if ($idx === $last) {
				$this->tpl_vars['__FOREACH_LAST'] = true;
			} else {
				$this->tpl_vars['__FOREACH_LAST'] = false;
			}

			$this->tpl_vars['__FOREACH_INDEX'] = $idx;

			// This is a key/val pair: foreach $key => $val
			if ($oval) {
				$this->tpl_vars[$okey] = $key;
				$this->tpl_vars[$oval] = $val;
			// This is: foreach $array as $item
			} else {
				$this->tpl_vars[$okey] = $val;
			}

			$ret .= $this->process_blocks($blocks);

			$idx++;
		}

		// Restore the TPL vars to the version before the {foreach} started
		$this->tpl_vars = $save;

		return $ret;
	}

	// Parse a simple expression block
	private function expression_block($str, $m) {
		$ret = "";

		// Make sure the block has something parseble... at least a $ or "
		if (!preg_match("/[\"\d$(]/", $str)) {
			list($line, $col, $file) = $this->get_char_location($this->char_pos, $this->tpl_file);
			return $this->error_out("Unknown block type <code>$str</code> in <code>$file</code> on line #$line", 73467);
		}

		$err   = false;
		$blk   = $m[1] ?? "";
		$after = $this->convert_variables_in_string($blk);
		$ret   = $this->peval($after, $err);

		$valid_type = (is_string($ret) || is_numeric($ret));

		// The evaluated block has to return SOMETHING printable (not null/false/obj)
		// Even "" is fine
		if ($err || !$valid_type) {
			list($line, $col, $file) = $this->get_char_location($this->char_pos, $this->tpl_file);
			return $this->error_out("Unknown tag <code>$str</code> in <code>$file</code> on line #$line", 18933);
		}

		return $ret;
	}

	// Find the position of the closing tag in a string. This *IS* nesting aware
	function find_ending_tag($haystack, $open_tag, $close_tag) {
		// Do a quick check up to the FIRST closing tag to see if we find it
		$pos         = strpos($haystack, $close_tag);
		$substr      = substr($haystack,0, $pos);
		$open_count  = substr_count($substr, $open_tag);

		if ($open_count === 1) {
			return $pos;
		}

		// This is the FULL search, where we keep adding chunks of the string looking for
		// the close tag, and then checking if the open tags match the close tags

		// We skip ahead past the first match above because we know there isn't a match in
		// the first X characters
		$close_len = strlen($close_tag);
		$offset    = $pos + $close_len;

		// We only go five deep... this prevents endless loops
		// No one should need more than five levels of nested comments
		for ($h = 0; $h < 5; $h++) {
			$pos = strpos($haystack, $close_tag, $offset);

			if ($pos === false) {
				return false;
			}

			$substr = substr($haystack, 0, $pos + 2);

			// If we find the end delimiter and the open/closed tag count is the same
			$open_count  = substr_count($substr, $open_tag);
			$close_count = substr_count($substr, $close_tag);

			if ($open_count === $close_count) {
				return $pos;
			}

			$offset = $pos + $close_len;
		}

		return false;
	}

	// Break up a string into tokens: pieces of {} and the text between them
	function get_tokens($str) {
		$x = preg_split('/({[^}]+})/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
		$x = array_filter($x);
		$x = array_values($x);

		return $x;
	}

	// Is the string part of an if token
	function is_if_token($str) {
		if ($str === '{else}') {
			return true;
		}

		if ($str === '{/if}') {
			return true;
		}

		// Return the conditional for this
		if (preg_match("/({if|{elseif) (.+?)}/", $str, $m)) {
			$ret = trim($m[2] ?? "");
			return $ret;
		};

		return false;
	}

	// Take an array of tokens and build a list of if/else rules
	private function get_if_rules_from_tokens($toks) {
		$num    = count($toks);
		$nested = 0;

		// This builds an array of which tokens are pieces of the if
		$tmp = [];
		for ($i = 0; $i < $num; $i++) {
			$item = $toks[$i];

			if (str_starts_with($item, '{if')) { $nested++; }
			if ($item === '{/if}')             { $nested--; }

			// If we're in the middle of a nest, it's automatically NOT an if piece
			if ($nested !== 1) {
				$yes = false;
			} else {
				$yes = boolval($this->is_if_token($item));
			}

			// The last {/if} of a nested doesn't count
			if ($nested === 1 && $item === '{/if}') {
				$yes = false;
			}

			$tmp[$i] = $yes;
		}

		$tmp[$num - 1] = true;

		////////////////////////////////////////////////////////////////////////

		// Now that we know what pieces are the ifs we can pull those out
		// because they are the test conditions
		$conds = [];
		for ($i = 0; $i < $num; $i++) {
			$item = $tmp[$i];

			if ($item) {
				$test    = $this->is_if_token($toks[$i]);
				$is_last = ($i === ($num - 1));

				if (!$is_last) {
					$conds[] = $test;
				}
			}
		}

		// Last one is the final {/if} and it's always true
		$tmp[$num] = true;

		////////////////////////////////////////////////////////////////////////

		// Everything AFTER an {if} piece is the payload to that test condition
		$str      = '';
		$payloads = [];
		$first    = true;
		for ($i = 0; $i < $num; $i++) {
			$item = $tmp[$i];

			if (!$item) {
				$str .= $toks[$i];
			} else {
				if (!$first) {
					$payloads[] = $str;
				}

				$first = false;
				$str   = '';
			}
		}

		$cond_count = count($conds);
		$payl_count = count($payloads);

		if ($cond_count !== $payl_count) {
			$this->error_out("Error parsing {if} conditions in '$str'", 95320);
		}

		$ret = [];
		for ($i = 0; $i < count($conds); $i++) {
			$ret[] = [$conds[$i], $payloads[$i]];
		}

		return $ret;
	}

	// Get/Set parent tpl
	function parent_tpl($tpl) {
		if (isset($tpl)) {
			$this->parent_tpl = $tpl;

			return $this->parent_tpl;
		} else {
			return $this->parent_tpl;
		}
	}
}

// This function is *OUTSIDE* of the class so it can be called separately without
// instantiating the class
function sluz($one, $two = null) {
	static $s;

	if (!$s) {
		$s = new sluz();
		$d    = debug_backtrace();
		$last = array_shift($d);
		$file = $last['file'];

		$s->enable_simple_mode($file);
	}

	$s->assign($one, $two);

	return $s;
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
