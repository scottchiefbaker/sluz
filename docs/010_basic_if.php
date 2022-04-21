<?php

include("../sluz.class.php");
$s = new sluz();

$s->assign("weekday", date("D"));
$s->assign("kittens", [2,3,4,5,6,7]);
$s->assign("verbose", true);

print $s->parse();
