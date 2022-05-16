<?php

///////////////////////////////////////////////////////////////////////////////
// You may need a literal '{' or '}' in your HTML. These characters are      //
// common in both CSS and Javascript. To prevent Sluz from interpretting     //
// them as code block just use {literal}.                                    //
// Note: Brackets with whitespace on both sides do NOT require {literal}     //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("name", "Jason Doolis");

print $s->fetch();
