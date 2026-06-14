# sluz – Agent Guidance

## Project

Single-file (1075 LOC) PHP templating engine with Smarty-like syntax. Zero dependencies, PHP ≥8.0.

- Class: `sluz` (lowercase) in `sluz.class.php`
- Composer autoload: `"files": ["sluz.class.php"]` — no PSR-4 namespace
- Template extension: `.stpl`
- Version string: `$sluz->version` (current `0.9.3`)

## Commands

```sh
php unit_tests/tests.php           # full suite
php unit_tests/tests.php "Foreach" # filter by test name substring
php unit_tests/tests.php --simple  # quiet mode (pass/fail counts only)
```

## Architecture

- `sluz.class.php` — the entire library. Parse `{...}` blocks, variables `{$var}`, dot-notation array access (`{$user.name.first}` → `$user['name']['first']`), modifiers (`|strtoupper`, `|substr:0,3`, `|default:"fallback"`), modifier chaining (`{$var|default:"none"|strtoupper}`), `{if}`/`{elseif}`/`{else}`, `{foreach}` (with `$__FOREACH_FIRST`, `$__FOREACH_LAST`, `$__FOREACH_INDEX`), `{include}`, `{literal}`, `{* comments *}`, expression blocks `{$x + 3}`.
- `unit_tests/tests.php` — inline test definitions calling `sluz_test(input, expected, label)`. No test framework. Custom helper functions (`truncate`, `join_comma`, etc.) defined at bottom of file.
- `docs/` — runnable PHP examples in subdirectories. Each includes `sluz.class.php` and fetches a paired `.stpl`.
- `tpls/` — template files. Default lookup path: `tpls/[script_filename_minus_php].stpl`.

## Key API

```php
$s = new sluz();
$s->assign('name', 'value');                            // single var
$s->assign(['k1' => 'v1', ...]);                        // bulk assign
print $s->fetch('tpls/file.stpl');                      // normal fetch
print $s->fetch('tpls/child.stpl', 'tpls/parent.stpl'); // parent/child
print $s->parse_string('Hello {$name}');                // parse string, not file
$s->display('tpls/file.stpl');                          // fetch + print
$s->parse('tpls/file.stpl');                            // alias for fetch()
$s->parent_tpl('tpls/parent.stpl');                     // get/set parent template
$s->debug = 1;                                          // verbose debug output
$s->in_unit_test = true;                                // suppress error output during testing
```

## Modes

- **Simple mode**: global function `sluz(key, val)` returns singleton. Auto-prints in destructor. Template auto-resolved as `tpls/[script].stpl`.
- **Inline mode**: `$s->fetch(SLUZ_INLINE)` reads template from `__halt_compiler();` section of the same PHP file.

## Error codes

| Code  | Meaning                        |
|-------|--------------------------------|
| 18485 | Unable to load include template |
| 18933 | Bad function/expression         |
| 42280 | Unable to load template file    |
| 45821 | Unclosed tag                    |
| 47204 | Unknown function call in modifier |
| 58200 | TypeError in modifier call      |
| 68493 | Missing file in include block   |
| 73467 | Bare word / unknown block       |
| 79134 | Exception in modifier call      |

## Gotchas

- Must be `error_reporting(E_ALL)` compliant — no `E_NOTICE` allowed.
- `foreach` loop vars do NOT persist after the loop block (tested explicitly).
- `$__FOREACH_FIRST`/`$__FOREACH_LAST`/`$__FOREACH_INDEX` are reserved variable names inside `{foreach}`.
- `docs/tpls/` templates are paired with `docs/*.php` scripts; they live alongside runnable examples.
- `.gitattributes` controls Packagist archive — `/docs`, `/unit_tests`, `/tpls`, `/x.php`, `/index.php` excluded from distribution.
- `.gitignore` excludes scratch files `x.php`, `tpls/x.stpl`, `tpls/child.stpl`, `tpls/parent.stpl`.
- `.user.ini` is machine-local (developer tooling); not part of the library.
