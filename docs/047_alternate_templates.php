<?php

///////////////////////////////////////////////////////////////////////////////
// If you want to use an alternate template specify it when you call fetch() //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis"); // Scalar syntax
$hour = date("H");

if ($hour > 17) {
	print $s->fetch("tpls/dark_mode.stpl");
} else {
	print $s->fetch("tpls/light_mode.stpl");
}
