<?php

////////////////////////////////////////////////////////

class sluz {
	private $tpl_vars = [];
	public  $debug    = 0;

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

		// Simple variable replacement
		if (preg_match('/^\{\$(\w.+?)\}$/', $str, $m)) {
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

			$cmd = '$ok = ' . $m[1] . ";";
			eval($cmd);

			if ($ok) {
				$ret = $this->process_block($true_val);
			} else {
				$ret = $this->process_block($false_val);
			}
		} elseif (preg_match('/\{foreach \$(\w+) as \$(\w+)( => \$(\w+))?\}(.+)\{\/foreach\}/s', $str, $m)) {
			$src   = $m[1]; // src array
			$okey  = $m[2]; // orig key
			$oval  = $m[4]; // orig val
			$orig_t = $m[5]; // code block to parse on iteration

			extract($this->tpl_vars);

			$src = $this->tpl_vars[$src];

			foreach ($src as $val) {
				// Temp set a key so when we process this section it's correct
				$this->tpl_vars[$okey] = $val;
				$blocks = $this->get_blocks($orig_t);

				foreach ($blocks as $block) {
					$ret .= $this->process_block($block);
				}
			}
		} else {
			$ret = $str;
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

							$of = preg_match_all("/\{(if|foreach)/", $tmp);
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

	function parse($file = "") {
		$str = file_get_contents($file);

		if ($this->debug) { print nl2br(htmlentities($str)) . "<hr>"; }

		$blocks = $this->get_blocks($str);
		//kd($blocks);
		$html = '';
		foreach ($blocks as $block) {
			$html .= $this->process_block($block);
		}

		return $html;
	}

	function array_dive(string $needle, array $haystack) {
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
		if (is_scalar($arr)) {
			$ret = $arr;
		} else {
			$ret = null;
		}

		return $ret;
	}

}

////////////////////////////////////////////////////////

