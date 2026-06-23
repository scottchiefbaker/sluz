<?php

///////////////////////////////////////////////////////////////////////////////
// Auto-escape mode automatically HTML-encodes all {$var} output.            //
// Use |raw to opt out for trusted content.                                  //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->setEscapeHtml(true);

$s->assign("name"        , "<b>Scott</b>");
$s->assign("user_input"  , "<script>alert('XSS')</script>");
$s->assign("trusted_html", "<em>Safe HTML</em>");

print $s->fetch("tpls/085_autoescape.stpl");
