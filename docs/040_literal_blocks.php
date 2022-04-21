<?php

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis");

print $s->parse();
