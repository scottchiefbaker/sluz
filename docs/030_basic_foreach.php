<?php

///////////////////////////////////////////////////////////////////////////////
// Foreach works on either arrays or hashes. You can use regular PHP foreach //
// syntax with foreach ($foo as $bar) or foreach ($foo as $key => $val).     //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]); // Array
$s->assign("numbers", ["one" => "uno", "two" => "dos", "three" => "tres"]); // Hash

print $s->fetch("tpls/030_basic_foreach.stpl");
