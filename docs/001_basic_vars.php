<?php

include("../sluz.class.php");
$s = new sluz();

$s->assign("first", "Jason");
$s->assign("last", "Doolis");

print $s->parse();
