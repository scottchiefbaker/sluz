<?php

////////////////////////////////////////////////////////

class sluz {
	private $tpl_vars     = [];
	private $tpl_path     = null;
	public  $debug        = 0;
	public  $version      = '0.1';
	public  $in_unit_test = 0;
	private $var_prefix   = "sluz_pfx";

	function __construct() { }
	function __destruct()  { }

	function assign($key, $val) {
		$this->tpl_vars[$key] = $val;
	}

	function process_block(string $str) {
		$cur = error_reporting(); // Save current level so we can restore it
		error_reporting(E_ALL & ~E_NOTICE); // Disable E_NOTICE

		$ret = '';

		// Micro-optimization for "" input
		if (strlen($str) === 0) {
			return '';
		}

		// If it doesn't start with a '{' it's plain text so we just return it
		if (!preg_match('/^{/', $str, $m)) {
			$ret = $str;
		// Simple variable replacement
		} elseif (preg_match('/^\{\s*\$(\w[\w\|\.]+?)\s*\}$/', $str, $m)) {
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
			extract($this->tpl_vars, EXTR_PREFIX_ALL, $this->var_prefix);

			$test_var  = $this->convert_variables_in_string($m[1]);
			$payload   = $m[2];
			$parts     = explode("{else}", $payload);
			$true_val  = $parts[0] ?? "";
			$false_val = $parts[1] ?? "";

			$ok = $this->peval($test_var);

			// Figure out which we process
			if ($ok) {
				$blocks = $this->get_blocks($true_val);
			} else {
				$blocks = $this->get_blocks($false_val);
			}

			foreach ($blocks as $block) {
				$ret .= $this->process_block($block);
			}
		} elseif (preg_match('/\{foreach (\$\w[\w.]+) as \$(\w+)( => \$(\w+))?\}(.+)\{\/foreach\}/s', $str, $m)) {
			$src     = $this->convert_variables_in_string($m[1]); // src array
			$okey    = $m[2]; // orig key
			$oval    = $m[4]; // orig val
			$payload = $m[5]; // code block to parse on iteration

			$src = $this->peval($src);

			if (!is_array($src)) {
				return $this->error_out($m[1] . " is not an array", 85824);
			}

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

				$blocks = $this->get_blocks($payload);

				foreach ($blocks as $block) {
					$ret .= $this->process_block($block);
				}
			}
		// A {literal}Stuff here{/literal} block pair
		} elseif (preg_match('/^\{literal\}(.+)\{\/literal\}$/s', $str, $m)) {
			$ret = $m[1];
		// A {* COMMENT *} block
		} elseif (preg_match('/^{\*.*\*\}/s', $str, $m)) {
			$ret = '';
		// Catch all for other { $num + 3 } type of blocks
		} elseif (preg_match('/^\{(.+)}$/s', $str, $m)) {
			// Make sure the block has something parseble... at least a $ or "
			if (!preg_match("/[\"\d$]/", $str)) {
				return $this->error_out("Unknown block type '$str'", 73467);
			}

			$blk   = $m[1];
			$after = $this->convert_variables_in_string($blk);
			$ret   = $this->peval($after);

			if (!$ret) {
				$ret = $str;
				return $this->error_out("Unknown tag '$str'", 18933);
			}
		} else {
			$ret = "???";
		}

		error_reporting($cur); // Reset error reporting level

		return $ret;
	}

	function get_blocks($str) {
		$start  = 0;
		$blocks = [];

		for ($i = 0; $i < strlen($str); $i++) {
			$char = substr($str, $i, 1);

			$is_open   = $char === "{";
			$is_closed = $char === "}";
			$has_len   = $start != $i;

			if ($is_open && $has_len) {
				$len = $i - $start;
				$block = substr($str, $start, $len);

				$blocks[] = $block;
				$start    += $len;
			} elseif ($is_closed) {
				$len         = $i - $start + 1;
				$block       = substr($str, $start, $len);
				$is_function = preg_match("/^\{\w+/", $block);

				// If we're in a function, loop until we find the end end
				if ($is_function) {
					for ($j = $i + 1; $j < strlen($str); $j++) {
						$closed = (substr($str, $j, 1) === "}");
						if ($closed) {
							$len = $j - $start + 1;
							$tmp = substr($str, $start, $len);

							$of = preg_match_all("/\{(if|foreach|literal)/", $tmp);
							$cf = preg_match_all("/{\/\w+/", $tmp);

							if ($of === $cf) {
								$block = $tmp;
								break;
							}
						}
					}
				}

				$blocks[]  = $block;
				$start    += strlen($block);
				$i         = $start;
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
		$html   = '';
		foreach ($blocks as $block) {
			$html .= $this->process_block($block);
		}

		return $html;
	}

	// If there is not template specified we "guess" based on the PHP filename
	function get_tpl_file($tpl_file) {
		if (!$tpl_file) {
			$x         = debug_backtrace();
			$orig_file = basename($x[1]['file']);
			$tpl_file  = $this->guess_tpl_file($orig_file);
		}

		return $tpl_file;
	}

	function guess_tpl_file(string $php_file) {
		$php_file = preg_replace("/.php$/", '', $php_file);
		$dir      = $this->tpl_path ?? "tpls/";
		$tpl_file = $dir . $php_file . ".stpl";

		return $tpl_file;
	}


	function array_dive(string $needle, array $haystack) {
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
	function convert_variables_in_string($str) {
		$dot_to_bracket_callback = function($m) {
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
		};

		// Process flat arrays in the test like $cust.name or $array[3]
		$str = preg_replace_callback('/(\$\w[\w\.]+)/', $dot_to_bracket_callback, $str);

		return $str;
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

		if ($this->in_unit_test) {
			//print "Err: $msg\n#$err_num\n";

			return null;
		}

		$d    = debug_backtrace();
		$file = $d[1]['file'] ?? "";
		$line = $d[1]['line'] ?? 0;

		$out .= "<div class=\"s_error\">\n";
		$out .= "<h1 class=\"s_error_head\">Sluz Fatal Error</h1>";
		$out .= "<div class=\"s_error_desc\"><b>Description:</b> $msg</div>";
		$out .= "<div class=\"s_error_num\"><b>Number</b> #$err_num</div>";
		if ($file && $line) {
			$out .= "<div class=\"s_error_file\">Source: <code>$file</code> #$line</div>";
		}
		$out .= "</div>\n";

		print $out;

		exit;
	}

	function peval($str) {
		extract($this->tpl_vars, EXTR_PREFIX_ALL, $this->var_prefix);

		$ret      = '';
		$cmd      = '$ret = (' . $str. "); return true;";
		$parse_ok = false;

		try {
			$parse_ok = @eval($cmd);
		} catch (ParseError $e) {
			return null;
		}

		return $ret;
	}
}
