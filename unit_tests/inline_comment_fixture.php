<?php

// Fixture: marker text inside a comment and a string is ignored; only the real marker
// starts the inline section (see tests.php).
// note: __halt_compiler(); in a comment
$note = "__halt_compiler();";
__halt_compiler();
REAL-SECTION