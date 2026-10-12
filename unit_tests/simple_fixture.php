<?php

// Fixture for global sluz() simple mode tests (see tests.php). It is executed in a
// separate PHP process because the sluz() singleton is static and cannot be reset.
include __DIR__ . '/../sluz.class.php';

sluz('name', 'World');
$a = sluz(['count' => 3]);
$b = sluz('other', 'x');

print $a === $b ? "SAME\n" : "DIFF\n";

__halt_compiler();
Hello {$name}, count {$count}
