<?php

/////////////////////////////////////////////////////////////////////////////
// set_delimiters() changes the opening/closing tags Sluz looks for. The   //
// default is { and }. Switching them is useful when the output is full of //
// curly brackets, such as JSON. Both delimiters must be single characters //
// and must be different.                                                  //
/////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->set_delimiters('<', '>');

$s->assign("name", "Jason Doolis");
$s->assign("age", 42);
$s->assign("tags", ["php", "templates", "sluz"]);

print $s->fetch("tpls/075_set_delimiters.stpl");
