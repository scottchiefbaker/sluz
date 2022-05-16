<?php

///////////////////////////////////////////////////////////////////////////////
// Templates can call external functions. Any function that's callable in    //
// normal scope can be used: global PHP functions, or functions you have     //
// written. Functions will be called with the variable being the first       //
// and only parameter. You may need to write wrapper functions to make this  //
// work in your environment.                                                 //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name" , "Jason Doolis"); // Scalar syntax
$s->assign("numbers" , [4,1,9,7,2]); // Array syntax

print $s->fetch();

///////////////////////////////////////////////////////////////////////////////

function join_comma(array $arr) {
	return join(", ", $arr);
}

function initials($str) {
	$ret   = '';
	foreach (preg_split('/ /', $str) as $x) { $ret .= substr($x, 0, 1); }

	return $ret;
}
