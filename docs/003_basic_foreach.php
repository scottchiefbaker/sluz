<?php

include("../sluz.class.php");
$s = new sluz();

$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]);

print $s->parse();
