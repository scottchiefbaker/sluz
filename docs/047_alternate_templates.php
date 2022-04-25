<?php

///////////////////////////////////////////////////////////////////////////////
// If you want to use an alternate template specify it when you call parse() //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis"); // Scalar syntax
$hour = date("H");

if ($hour > 17) {
	print $s->parse("tpls/dark_mode.stpl");
} else {
	print $s->parse("tpls/light_mode.stpl");
}
