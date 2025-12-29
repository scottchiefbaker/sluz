<?php

///////////////////////////////////////////////////////////////////////////////
// Simple mode allows you to use a function oriented syntax instead of the   //
// object oriented syntax. This decreases the number of function calls       //
// needed to run sluz. Less typing = better?                                 //
///////////////////////////////////////////////////////////////////////////////

require('../sluz.class.php');

// sluz() is an alias for $s->assign($key, $value) and returns a sluz object
$s = sluz("weather", "Sure is sunny today");
$s = sluz("temperature", 75);

// Note: fetch() is called automatically in simple mode as part of the destructor.
// The template file used is the PHP filename with the `.php` changed to `.stpl`
// in the `tpls/` directory. Example: `myscript.php` => `/tpls/myscript.stpl`
//
// If you need an alternate template you will need to call fetch() manually:
// print $s->fetch("tpls/special.stpl");
