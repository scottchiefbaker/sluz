<?php

///////////////////////////////////////////////////////////////////////////////
// Templates can call external functions. Any function that is callable in   //
// normal scope can be used: global PHP functions, or user functions.        //
// Functions will be called with the template variable being the first       //
// parameter, and other params after. You may need to write wrapper          //
// functions to make this work in your environment.                          //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name" , "Jason Doolis"); // Scalar syntax
$s->assign("numbers" , [4,1,9,7,2]); // Array syntax

print $s->fetch("tpls/046_external_functions.stpl");

///////////////////////////////////////////////////////////////////////////////

function join_comma(array $arr, string $separator = ", ") {
	return join($separator, $arr);
}

function initials($str) {
	$ret = '';

	foreach (preg_split('/ /', $str) as $x) { $ret .= substr($x, 0, 1); }

	return $ret;
}
