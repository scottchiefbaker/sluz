<?php

///////////////////////////////////////////////////////////////////////////////
// Foreach works just like native PHP, on either arrays or hashes.           //
//                                                                           //
// Array syntax: {foreach $foo as $bar}                                      //
// Hash syntax : {foreach $foo as $key => $val}                              //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]); // Array
$s->assign("numbers", ["one" => "uno", "two" => "dos", "three" => "tres"]); // Hash

print $s->fetch("tpls/030_basic_foreach.stpl");
