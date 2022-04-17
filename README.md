# Sluz PHP template system

Sluz is a minimalistic PHP templating engine with an emphasis on
syntax similar to [Smarty](https://www.smarty.net/). This allows
you to separate your logic (`.php`) and your presentation (`.stpl`)
files.

The goal of Sluz is to be a **small** single PHP source file.

## Getting started

File: `script.php`
```php
include('/path/to/sluz/sluz.class.php');
$s = new sluz();

$s->assign("name", "Jason");
$s->assign("version", "0.2");

print $s->parse();
```

File: `tpls/script.stpl`
```
<h1>Hello {$name}</h1>

<div>Welcome to Sluz version: {$version}</div>
```

## Requirements

Sluz has no external library requirements. Only the `sluz.class.php` is 
needed for the library to function.

## Testing

Sluz has an extensive test suite that is used to verify compatibility
across PHP versions. As of this writing Sluz runs on PHP versions: 7.3, 7.4,
8.0, and 8.1.

To run the tests issue this command at the CLI:

```
php unit_tests/tests.php
```

## Documentation

There is extensive documentation in the `docs/` with real world examples of the syntax. 

## Naming

Sluz is pronounced "sloos". The name comes from the "S" in Smarty
and "luz" which is Spanish for light. Sluz is a lite, Smarty-like
templating system.
