<?php

include("../sluz.class.php");
$s = new sluz();

$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]); // Array
$s->assign("numbers", ["one" => "uno", "two" => "dos", "three" => "tres"]); // Hash

print $s->parse();
