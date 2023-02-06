<?php

///////////////////////////////////////////////////////////////////////////////
// A variable that is either null, empty string, or not assigned() at all    //
// can be given a default value in templates                                 //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis");
//$s->assign("greeting", "Hola"); // If we don't send this, the default it used

print $s->fetch("tpls/002_default_values.stpl");
