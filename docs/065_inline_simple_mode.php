<?php

///////////////////////////////////////////////////////////////////////////////
// Combine both inline mode, and simple mode to minimize the lines of code   //
// required to instatiate and call Sluz. This also combines your logic and   //
// presentation in to one file. Great for simple projects.                   //
///////////////////////////////////////////////////////////////////////////////

require('../sluz.class.php');

// Use hash mode to send multiple items at once
$data = ['name' => 'Jason', 'age' => 13, 'animal' => 'kitten'];
$s    = sluz($data);

////////////////////////////////////////////////////

__halt_compiler();

<h1>Hello {$name}</h1>

<p>I am {$age} years old and I really like my {$animal}</p>
