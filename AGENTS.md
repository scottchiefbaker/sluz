# sluz – Agent Guidance

## Project

Single-file PHP templating engine with Smarty-like syntax. Zero dependencies, PHP ≥8.0.

- Class: `sluz` (lowercase) in `sluz.class.php`
- Composer autoload: `"files": ["sluz.class.php"]` — no PSR-4 namespace
- Template extension: `.stpl`
- Version string: `$sluz->version` (current `0.9.6`)

## Commands

```sh
php unit_tests/tests.php           # full suite
php unit_tests/tests.php "Foreach" # filter by test name substring
php unit_tests/tests.php --simple  # quiet mode (pass/fail counts only)
```

## Benchmarking

Evaluate any changes to `sluz.class.php` for performance regressions with the detail benchmark:

```sh
php unit_tests/detail-benchmark.php            # full suite (15000 iters)
php unit_tests/detail-benchmark.php -f foreach # filter by name/description
php unit_tests/detail-benchmark.php -n 50000   # alternate iteration flag
```

Runs a warmup then times each template scenario (variables, modifiers, foreach, if, mixed, etc.) and prints per-scenario millis/iter-per-second plus a TOTAL. Compare TOTAL (and any scenario you touched) before and after your change to catch regressions.

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
$s->set_delimiters('[', ']');                           // custom delimiters (default { / })
$s->setEscapeHtml(true);                                // auto-escape all {$var} output
$s->debug = 1;                                          // verbose debug output
$s->in_unit_test = true;                                // suppress error output during testing
// Global helper functions usable in templates:
//   {$var|escape}  — htmlspecialchars (default), or "url", "js"
//   {$var|raw}     — opt out of auto-escaping
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
| 65491 | Unknown escape type in `escape` |

## Gotchas

- Must be `error_reporting(E_ALL)` compliant — no `E_NOTICE` allowed.
- `foreach` loop vars do NOT persist after the loop block (tested explicitly).
- `$__FOREACH_FIRST`/`$__FOREACH_LAST`/`$__FOREACH_INDEX` are reserved variable names inside `{foreach}`.
- `.gitattributes` controls Packagist archive — see the file for excluded paths.
- `.gitignore` excludes scratch files `x.php`, `tpls/x.stpl`, `tpls/child.stpl`, `tpls/parent.stpl`.
