<?php

///////////////////////////////////////////////////////////////////////////////
// Sluz has if/then logic as well. If an if statement references a variable  //
// that is not assigned it will be treated as if it were false.              //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("weekday", date("D"));
$s->assign("kittens", [2,3,4,5,6,7]);
$s->assign("verbose", true);

print $s->fetch();
