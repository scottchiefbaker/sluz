<?php

///////////////////////////////////////////////////////////////////////////////
// Simple mode allows you use a function oriented syntax instead of the      //
// object oriented traditional method. This decreases the number of function //
// calls needed to run sluz. Less typing = better?                           //
///////////////////////////////////////////////////////////////////////////////

require('../sluz.class.php');

// sluz() is an alias for $s->assign($key, $value);
$s = sluz("weather", "Sure is sunny today");
$s = sluz("temperature", 75);

// Note: fetch() is automatically called in simple mode as part of the destructor.
// Template file used is the PHP filename with the .php changed to .stpl in
// the tpls/ directory.
//
// If you need an alternate template you have to call fetch() manually like:
// print $s->fetch("tpls/special.stpl");
