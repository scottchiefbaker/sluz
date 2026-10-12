<?php

// Fixture: block processing inside the inline section (see tests.php).
__halt_compiler();
{foreach $y as $v}[{$v}]{/foreach}{if $debug}ON{/if}{$name|escape:"bogus"}