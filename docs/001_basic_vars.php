<?php

///////////////////////////////////////////////////////////////////////////////
// Basic syntax for assigning variables to the templating system. You can    //
// assign scalars, arrays, and hashes.                                       //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("first", "Jason"); // Scalar syntax
$s->assign("last" , "Doolis");

$s->assign("month", ["Jan", "Feb", "Mar"]); // Array syntax
$s->assign("data" , ["color" => "red"]);    // Hash syntax

print $s->fetch();
