<?php

///////////////////////////////////////////////////////////////////////////////
// You can include external templates in your .stpl files. This allows you   //
// to have a common header, footer, or framework. Included files have all    //
// the same properties as normal templates. Templates in {include} tags      //
// need to be pathed *relative* to the calling PHP script.                   //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("first", "Jason"); // Scalar syntax
$s->assign("last" , "Doolis");

print $s->fetch("tpls/045_include.stpl");
