<?php

///////////////////////////////////////////////////////////////////////////////
// {foreach} blocks apply special variables on each loop interation. They    //
// are: $__FOREACH_FIRST, $__FOREACH_LAST, $__FOREACH_INDEX. First/Last will //
// be true if it is either first of last interation, and the index will be   //
// set the loop iteration number (starting at zero).                         //
///////////////////////////////////////////////////////////////////////////////

include("../sluz.class.php");
$s = new sluz();

$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]); // Array

print $s->fetch("tpls/031_advanced_foreach.stpl");
