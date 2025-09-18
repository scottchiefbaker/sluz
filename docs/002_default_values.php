<?php

///////////////////////////////////////////////////////////////////////////////
// A variable that is: null, empty string, or not assign() can be given      //
// a default value in the template.                                          //
//                                                                           //
// Note: The default value must be a scalar (not a variable)                 //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis");
//$s->assign("greeting", "Hola"); // If we don't send this, the default it used

print $s->fetch("tpls/002_default_values.stpl");
