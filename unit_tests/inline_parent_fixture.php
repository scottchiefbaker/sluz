<?php

// Fixture: an inline parent template that includes a file child (see tests.php).
__halt_compiler();
<h1>inline-header</h1>{include file="$__CHILD_TPL"}<h1>inline-footer</h1>