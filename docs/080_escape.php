<?php

///////////////////////////////////////////////////////////////////////////////
// The escape modifier safely encodes output to prevent XSS.                 //
// Supported types: 'html' (default), 'url', and 'js'.                      //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "<script>alert('XSS')</script>");
$s->assign("url_path", "hello world");
$s->assign("js_val", "It's a \"test\"");

print $s->fetch("tpls/080_escape.stpl");
