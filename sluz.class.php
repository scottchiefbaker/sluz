<?php

////////////////////////////////////////////////////////

class sluz {
	private $tpl_vars = [];
	public  $debug    = 0;

	function __construct() {
		// Load Krumo if debug is on
		if (!function_exists('krumo')) {
			include("krumo/class.krumo.php");
		} else {
			function k() { }; // Do nothing stub
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
		if (preg_match('/^\{\$(\w.+?)\}/', $str, $m)) {
			$key = $m[1];
			if (preg_match("/(.+?)\|(.+)/", $key, $m)) {
				$key = $m[1];
				$mod = $m[2];

				$ret = call_user_func($mod, $this->tpl_vars[$key] ?? "");
			} else {
				$ret = $this->tpl_vars[$key] ?? "";
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
			$block = $m[5]; // code block to parse on iteration

			extract($this->tpl_vars);

			$src = $this->tpl_vars[$src];

			foreach ($src as $val) {
				// Temp set a key so when we process this section it's correct
				$this->tpl_vars[$okey] = $val;

				$ret .= $this->process_block($block);
			}
		} else {
			$ret = $str;
		}

		if ($this->debug) { k("Output: $ret"); }

		error_reporting($cur); // Reset error reporting level

		return $ret;
	}

	function parse($file = "") {
		$str = file_get_contents($file);

		if ($this->debug) { print nl2br(htmlentities($str)) . "<hr>"; }

		$out   = '';
		$start = 0;

		for ($i = 0; $i < strlen($str); $i++) {
			$char = substr($str, $i, 1);

			$is_open   = $char === "{";
			$is_closed = $char === "}";

			if ($is_open) {
				$start = $i;
				$next  = substr($str,$i + 1, 7);

				// We found an open { now check if it's a command
				if (preg_match("/^(if|foreach)/", $next, $m)) {
					$cmd = $m[1];

					if ($this->debug) { k("Found \"$cmd\" at $i"); }
					$of = 0; // Open function count

					// We keep looking until we find the closing } for this
					for ($j = $i + 1; $j < strlen($str); $j++) {
						$closed = (substr($str, $j, 1) === "}");
						if ($closed) {
							$tmp = substr($str, $start, $j - $start + 1);

							if ($this->debug > 1) { k("Found close bracket. Block is #$start -> #$j = '$tmp'"); }

							$of = preg_match_all("/\{(if|foreach)/", $tmp, $m);
							$cf = preg_match_all("/{\/\w+/", $tmp, $m);

							if ($of === $cf) {
								$out .= $this->process_block($tmp);
								$i = $j;
								break;
							}
						}
					}

					if ($j === strlen($str)) {
						die("Didn't find closing delimter '}' started at $start");
					}
				// Not a command just a normal variable block
				} else {
					// We keep looking until we find the closing } for this
					for ($j = $i; $j < strlen($str); $j++) {
						$closed = (substr($str, $j, 1) === "}");

						if ($closed) {
							$end     = $j;
							$section = substr($str, $start, $end - $start + 1);
							$tmp     = $this->process_block($section);

							$out .= $tmp;

							$i = $j;

							// We found the closing } so we stop processing the for loop
							break;
						}
					}

				}
			} else {
				$out .= $char;
			}
		}

		return $out;
	}
}

////////////////////////////////////////////////////////

