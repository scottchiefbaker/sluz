<?php

////////////////////////////////////////////////////////

include("krumo/class.krumo.php");

class sluz {
	private $tpl_vars = [];
	public  $debug    = 1;

	function __construct() { }
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
		if (preg_match('/\{\$(\w+)\}/', $str, $m)) {
			$key = $m[1];
			$ret = $this->tpl_vars[$key] ?? "";
		// If statement
		} elseif (preg_match('/\{if (.+?)\}(.+)\{\/if\}/s', $str, $m)) {
			// Put the tpl_vars in the current scope so if works against them
			extract($this->tpl_vars);

			//k($m);

			$cmd = '$ok = ' . $m[1] . ";";
			eval($cmd);

			if ($ok) {
				$ret = $m[2];
			} else {
				$ret = '';
			}
		} elseif (preg_match('/\{foreach \$(\w+) as \$(\w+)( => \$(\w+))?\}(.+)\{\/foreach\}/s', $str, $m)) {
			$src   = $m[1];
			$okey  = $m[2];
			$oval  = $m[4];
			$block = $m[5];

			extract($this->tpl_vars);

			$src = $this->tpl_vars[$src];

			foreach ($src as $val) {
				// Temp set a key so when we process this section it's correct
				$this->tpls_vars[$okey] = $val;

				$ret .= $this->process_block($block);
			}
		} else {
			$ret = $str;
		}

		if ($this->debug) { k("$str = $ret"); }

		error_reporting($cur); // Reset error reporting level

		return $ret;
	}

	function parse($file = "") {
		$str = file_get_contents($file);

		if ($this->debug) { print $str . "<hr>"; }

		$out   = '';
		$oc    = 0; // Open Count
		$start = 0;

		for ($i = 0; $i < strlen($str); $i++) {
			$char = substr($str, $i, 1);

			$is_open   = $char === "{";
			$is_closed = $char === "}";

			if ($is_open) {
				$oc++;
				$start = $i;
				$next  = substr($str,$i + 1, 7);

				// We found an open { now check if it's a command
				if (preg_match("/^(if|foreach)/", $next, $m)) {
					$cmd = $m[1];

					if ($this->debug) { k("Found \"$cmd\" at $i ($oc)"); }
					$oc++;

					// We keep looking until we find the closing } for this
					for ($j = $i + 1; $j < strlen($str); $j++) {
						$closed = (substr($str, $j, 1) === "}");
						$opened = (substr($str, $j, 1) === "{");

						if ($closed) { $oc--; }
						//if ($opened) { $oc++; }

						//print("$j = $oc<br />");

						if ($closed && $oc == 0) {
							$end = $j;

							$out .= $this->process_block(substr($str, $start, $end - $start + 1));

							$i = $j;

							// We found the closing } so we stop processing the command
							break;
						}
					}
				} else {
					// We keep looking until we find the closing } for this
					for ($j = $i; $j < strlen($str); $j++) {
						$closed = (substr($str, $j, 1) === "}");

						if ($closed) { $oc--; }
						//if ($opened) { $oc++; }

						if ($closed && $oc == 0) {
							$end = $j;

							$section = substr($str, $start, $end - $start + 1);
							$tmp     = $this->process_block($section);

							//kd($out, $section, $tmp, $start);
							$out .= $tmp;

							$i = $j;

							// We found the closing } so we stop processing the command
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

