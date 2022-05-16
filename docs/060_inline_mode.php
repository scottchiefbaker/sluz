<?php

///////////////////////////////////////////////////////////////////////////////
// Instead of loading an .stpl file we use a template that's inline with     //
// your PHP logic. This uses the __halt_compiler() pragma to allow us to     //
// insert non-PHP code in our PHP file.                                      //
///////////////////////////////////////////////////////////////////////////////

require('../sluz.class.php');

$s = new sluz;
$s->assign("name", "Jason Doolis");
$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]);

print $s->fetch("INLINE");

////////////////////////////////////////////////////

__halt_compiler();

<h1>Hello {$name}</h1>

{foreach $turtles as $t}
<div>{$t}</div>
{/foreach}
