<?php

///////////////////////////////////////////////////////////////////////////////
// Instead of loading a template file we can use a template that is inline   //
// with your PHP code. We use the __halt_compiler() pragma to allow us to    //
// insert template content into PHP files.                                   //
///////////////////////////////////////////////////////////////////////////////

require('../sluz.class.php');

$s = new sluz;
$s->assign("name", "Jason Doolis");
$s->assign("turtles", ["Michelangeo", "Donatello", "Leonardo", "Raphael"]);

print $s->fetch(SLUZ_INLINE);

////////////////////////////////////////////////////

__halt_compiler();

<h1>Hello {$name}</h1>

{foreach $turtles as $t}
<div>{$t}</div>
{/foreach}
