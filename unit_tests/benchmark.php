<?php

$runtime_secs = 3;
$start        = microtime(1);

$loops = 0;
while (microtime(1) - $start < $runtime_secs) {
	$lstart = microtime(1);

	require_once(__DIR__ . "/../sluz.class.php");
	$s = new sluz();

	///////////////////////////////////////////////////////////////////////////////

	$vars = [
		'turtles'      => ["Michelangeo" => "orange", "Donatello" => "purple", "Leonardo" => "blue", "Raphael" => "red"],
		'sluz_version' => $s->version,
		'lorem'        => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.",
		'hour'         => $hour = date("H"),
		'fruits'       => ['apple', 'banana', 'pear', 'grape', 'strawberry', 'kiwi'],
		'name'         => 'Jason Doolis',
	];

	///////////////////////////////////////////////////////////////////////////////

	$s->assign($vars);

	$total = sprintf("%.2f", (microtime(1) - $lstart) * 1000);
	$s->assign('millis', $total);

	$s->tpl_path = __DIR__ . "/tpls/";
	$output = $s->fetch();
	$loops++;
}

$total = microtime(1) - $start;
$runs_per_second = intval($loops / $total);
print "$runs_per_second renders per second\n";

///////////////////////////////////////////////////////////////////////////////

function initials($str) {
    $ret   = '';
    foreach (preg_split('/ /', $str) as $x) { $ret .= substr($x, 0, 1); }

    return $ret;
}
