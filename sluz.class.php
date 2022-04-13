<?php

////////////////////////////////////////////////////////

class sluz {
	private $tpl_vars = [];
	private $tpl_path = null;
	public  $debug    = 0;
	public  $version  = '0.1';

	function __construct() {
		// Load Krumo if debug is on
		if (!function_exists('k')) {
			require_once("krumo/class.krumo.php");
		}
	}

	function __destruct()  { }

	function assign($key, $val) {
		$this->tpl_vars[$key] = $val;
	}

	function process_block($str) {
		$cur = error_reporting(); // Save current level so we can restore it
		error_reporting(E_ALL & ~E_NOTICE); // Disable E_NOTICE

		$ret = '';
		if ($this->debug) { k("Input: " . $str); }

		// If it doesn't start with a '{' it's plain text so we just return it
		if (preg_match('/^(?!\{)/', $str, $m)) {
			$ret = $str;
		// Simple variable replacement
		} elseif (preg_match('/^\{\s*\$(\w.+?)\s*\}$/', $str, $m)) {
			$key = $m[1];
			if (preg_match("/(.+?)\|(.+)/", $key, $m)) {
				$key = $m[1];
				$mod = $m[2];

				$pre = $this->array_dive($key, $this->tpl_vars) ?? "";
				$ret = call_user_func($mod, $pre);
			} else {
				$ret = $this->array_dive($key, $this->tpl_vars) ?? "";
			}
		// If statement
		} elseif (preg_match('/\{if (.+?)\}(.+)\{\/if\}/s', $str, $m)) {
			// Put the tpl_vars in the current scope so if works against them
			extract($this->tpl_vars);

			$test_var  = $m[1];
			$payload   = $m[2];
			$p         = explode("{else}", $payload);
			$true_val  = $p[0] ?? "";
			$false_val = $p[1] ?? "";

			$callback = function($m) {
				$key = $m[1] ?? "";
				$ret = $this->array_dive($m[1], $this->tpl_vars);
				//k(["Looking up: $key = $ret", $this->tpl_vars]);

				$ret = var_export($ret, true);
				return $ret;
			};

			// Process flat arrays in the test like $cust.name or $array[3]
			$test_var = preg_replace_callback('/\b\$([\w\.]+?)\b/', $callback, $test_var);

			$cmd      = '$ok = (' . $test_var . "); return true;";
			$parse_ok = false;

			try {
				$parse_ok = @eval($cmd);
			} catch (ParseError $e) {
				$this->error_out("Error parsing block '$test_var'", 91917);
			}

			//k([$test_var, $ok]);

			if (!$parse_ok) {
				$this->error_out("Error parsing block '$test_var'", 91923);
			}

			if ($ok) {
				$ret = $this->process_block($true_val);
			} else {
				$ret = $this->process_block($false_val);
			}

			//k([$ok, $cmd]);
		} elseif (preg_match('/\{foreach \$(\w+) as \$(\w+)( => \$(\w+))?\}(.+)\{\/foreach\}/s', $str, $m)) {
			$src    = $m[1]; // src array
			$okey   = $m[2]; // orig key
			$oval   = $m[4]; // orig val
			$orig_t = $m[5]; // code block to parse on iteration

			extract($this->tpl_vars);

			$src = $this->tpl_vars[$src];

			// Temp set a key/val so when we process this section it's correct
			foreach ($src as $key => $val) {
				// This is a key/val pair: foreach $key => $val
				if ($oval) {
					$this->tpl_vars[$okey] = $key;
					$this->tpl_vars[$oval] = $val;
				// This is: foreach $array as $item
				} else {
					$this->tpl_vars[$okey] = $val;
				}

				$blocks = $this->get_blocks($orig_t);

				foreach ($blocks as $block) {
					$ret .= $this->process_block($block);
				}
			}
		} elseif (preg_match('/\{literal\}(.+)\{\/literal\}/s', $str, $m)) {
			$ret = $m[1];
		} else {
			$ret = $str;
			//$this->error_out("Unknown tag '$str'", 18933);
		}

		if ($this->debug) { k("Output: $ret"); }

		error_reporting($cur); // Reset error reporting level

		return $ret;
	}

	function get_blocks($str) {
		$start  = 0;
		$blocks = [];

		//k("INPUT: $str");

		for ($i = 0; $i < strlen($str); $i++) {
			$char = substr($str, $i, 1);

			$is_open   = $char === "{";
			$is_closed = $char === "}";
			$has_len   = $start != $i;

			if ($is_open && $has_len) {
				$len = $i - $start;
				$block = substr($str, $start, $len);
				//k("AddingOpen:$block $start $len");

				$blocks[] = $block;
				$start    += $len;
			} elseif ($is_closed) {
				$len         = $i - $start + 1;
				$block       = substr($str, $start, $len);
				$is_function = preg_match("/^\{\w+/", $block);

				//k([$block, $is_function]);

				// If we're in a function, loop until we find the end end
				if ($is_function) {
					for ($j = $i + 1; $j < strlen($str); $j++) {
						$closed = (substr($str, $j, 1) === "}");
						if ($closed) {
							$len = $j - $start + 1;
							$tmp = substr($str, $start, $len);

							$of = preg_match_all("/\{(if|foreach|literal)/", $tmp);
							$cf = preg_match_all("/{\/\w+/", $tmp);

							//k([$tmp, $of, $cf], KRUMO_EXPAND_ALL);

							if ($of === $cf) {
								$block = $tmp;
								break;
							}
						}
					}
				}

				//k("AddingClose: $block $start $len");

				$blocks[]  = $block;
				$start    += strlen($block);
				$i         = $start;

				//K([$block, $of, $cf], KRUMO_EXPAND_ALL);
			}
		}

		// If we're not at the end of the string, add the last block
		if ($start != strlen($str)) {
			$blocks[] = substr($str, $start);
		}

		return $blocks;
	}

	// Specify a path to the .stpl file, or pass nothing to let sluz 'guess'
	// Guess is 'tpls/[scriptname_minus_dot_php].stpl
	function parse($tpl_file = "") {
		$tpl_file = $this->get_tpl_file($tpl_file);

		if (!is_readable($tpl_file)) {
			$this->error_out("Unable to load template file <code>$tpl_file</code>",42280);
		}

		$str = file_get_contents($tpl_file);

		if ($this->debug) { print nl2br(htmlentities($str)) . "<hr>"; }

		$blocks = $this->get_blocks($str);
		//kd($blocks);
		$html = '';
		foreach ($blocks as $block) {
			$html .= $this->process_block($block);
		}

		return $html;
	}

	function get_tpl_file($tpl_file) {
		if (!$tpl_file) {
			$dir       = $this->tpl_path ?? "tpls/";
			$x         = debug_backtrace();
			$orig_file = basename($x[1]['file']);
			$tpl_file  = $dir . preg_replace("/.php$/", '', $orig_file) . ".stpl";

			//k([$dir, $x, $orig_file, $tpl_file]);
		}

		return $tpl_file;
	}

	function array_dive(string $needle, array $haystack) {
		// Allow normal $foo['bar'] or $foo[3] syntax also
		$after  = preg_split("/(\['?|'?\])/", $needle, 0, PREG_SPLIT_NO_EMPTY);
		$needle = join(".", $after);

		// Split at the periods
		$parts = explode(".", $needle);

		// Loop through each level of the hash looking for elem
		$arr = $haystack;
		foreach ($parts as $elem) {
			//print "Diving for $elem<br />";
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

	function error_out($msg, int $err_num) {
		$out = "<style>
			.s_error {
				font-family: sans;
				border: 1px solid;
				padding: 6px;
				border-radius: 4px;
			}

			.s_error_head { margin-top: 0; }
			.s_error_num { margin-top: 1em; }
			.s_error_file {
				margin-top: 1em;
				padding-top: 0.5em;
				font-size: .8em;
				border-top: 1px solid;
			}

			.s_error code {
				padding: .2rem .4rem;
				font-size: 1.1em;
				color: #fff;
				background-color: #212529;
				border-radius: .2rem;
			}
		</style>";

		$d    = debug_backtrace();
		$file = $d[1]['file'];
		$line = $d[1]['line'];

		$out .= "<div class=\"s_error\">\n";
		$out .= "<h1 class=\"s_error_head\">Sluz Fatal Error</h1>";
		$out .= "<div class=\"s_error_desc\"><b>Description:</b> $msg</div>";
		$out .= "<div class=\"s_error_num\"><b>Number</b> #$err_num</div>";
		$out .= "<div class=\"s_error_file\">Source: <code>$file</code> #$line</div>";
		$out .= "</div>\n";

		print $out;

		exit;
	}

}

////////////////////////////////////////////////////////

