<?php

include("../sluz.class.php");
$s = new sluz();

$s->assign("weekday", date("D"));

print $s->parse();
