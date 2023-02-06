<?php

include('sluz.class.php');

////////////////////////////////////////////////////////
$s = new sluz();

// Specify a template file directly
//print $s->fetch("tpls/index.stpl");

// Sluz will default to "tpls/[filename_minus_dot_php].stpl"
$s->assign('sluz_version', $s->version);
print $s->fetch("tpls/index.stpl");

////////////////////////////////////////////////////////
