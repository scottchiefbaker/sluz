<?php

///////////////////////////////////////////////////////////////////////////////
// Comments are wrapped in {* and *}. Anything between them is removed from  //
// the output and is never evaluated, so variables and nested braces inside  //
// a comment are ignored. Comments can span multiple lines and can be nested.//
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis");

print $s->fetch("tpls/090_comments.stpl");
