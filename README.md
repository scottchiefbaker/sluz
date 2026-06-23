# 📰 Sluz PHP templating system

Sluz is a minimalistic PHP templating engine with an emphasis on
syntax similar to [Smarty](https://www.smarty.net/). This allows
you to separate your logic (`.php`) and your presentation (`.stpl`)
files.

The goal of Sluz is to be a **small**, single PHP source file, that
emulates simple Smarty-like syntax.

## 📦 Getting started

File: `script.php`
```php
include('/path/to/sluz/sluz.class.php');
$s = new sluz();

$s->assign("name", "Jason");
$s->assign("version", "0.3");

print $s->fetch("tpls/script.stpl");
```

File: `tpls/script.stpl`
```
<h1>Hello {$name}</h1>

<div>Welcome to Sluz version: {$version}</div>
```

## 🧩 Template syntax

| Syntax                               | Example                                          | Output              |
|--------------------------------------|--------------------------------------------------|---------------------|
| `{$var}`                             | `{$name}`                                        | `Jason`             |
| `{$hash.key}` / `{$array.0}`         | `{$cust.first}`                                  | `Scott`             |
| `{$var\|modifier}`                   | `{$animal\|strtoupper}`                          | `KITTEN`            |
| `{$var\|mod:params}`                 | `{$greet\|substr:0,3}`                           | `Hel`               |
| `{$var\|mod1\|mod2}`                 | `{$word\|strtolower\|ucfirst}`                   | `Crazy`             |
| `{$var\|default:"val"}`              | `{$null\|default:"?"}`                           | `?`                 |
| `{$expr}`                            | `{$number + 3}`                                  | `18`                |
| `{if}…{elseif}…{else}…{/if}`         | `{if $x}yes{else}no{/if}`                        | `yes`               |
| `{foreach $a as $v}`                 | `{foreach $items as $x}{$x}{/foreach}`           | `onetwothree`       |
| `{foreach $a as $k => $v}`           | `{foreach $m as $idx => $z}{$idx}{/foreach}`     | `012`               |
| `$__FOREACH_FIRST/LAST/INDEX`        | `{if $__FOREACH_FIRST}…{/if}`                    | —                   |
| `{include file='...'}`               | `{include file='header.stpl'}`                   | —                   |
| `{literal}…{/literal}`               | `{literal}function foo() { {/literal}`           | `function foo() { ` |
| `{* comment *}`                      | `{* hidden *}`                                   | *(empty)*           |
| `{function()}`                       | `{count($array)}`                                | `3`                 |

## 🔒 Security

Template variables hold untrusted data (form input, database rows, URL
parameters) by default. The `{$var}` construct emits the value verbatim,
so a template that renders user data without escaping is vulnerable to
cross-site scripting (XSS).

Always use the `escape` modifier on untrusted output:

```
{$user_input|escape}          {* HTML-encode (default) *}
{$redirect_url|escape:"url"}  {* URL-encode *}
{$inline_js|escape:"js"}      {* JavaScript-string-encode *}
```

By default `escape` uses PHP's `htmlspecialchars()` with `ENT_QUOTES |
ENT_SUBSTITUTE` and UTF-8 encoding, which converts `<`, `>`, `"`, and
`'` to their HTML entity equivalents.

### Auto-escape

For stricter safety you can enable automatic HTML escaping of all
variable output. When enabled, every `{$var}` is escaped unless the
template explicitly opts out with `|raw`.

```php
$s->setEscapeHtml(true);
```

```
{$user_input}                {* auto-escaped *}
{$user_input|escape}         {* not double-escaped *}
{$user_input|escape:"url"}   {* URL-escaped, not overridden *}
{$trusted_html|raw}          {* opt-out: output verbatim *}
```

**Note:** Auto-escaping only applies to `{$var}` variable blocks.
Expression blocks like `{$x + 3}` and function calls like
`{count($array)}` are not auto-escaped.

## 🤵 Composer

If you are a composer user you can install Sluz with this command:

```text
composer require sluz/sluz
```

## 📐 Requirements

Sluz requires PHP 8.0+, and is a zero-dependency library. **Only** the
`sluz.class.php` file is needed for the library to function.

## 🥽 Testing

Sluz has an extensive test suite that is used to verify compatibility
across PHP versions. As of this writing Sluz passes all unit tests on
PHP versions: 8.0, 8.1, 8.2, 8.3, 8.4, and 8.5.

To run the tests issue this command at the CLI:

```
php unit_tests/tests.php
```

**Note:** Care was taken to ensure that no `E_NOTICE` warnings are emitted
and that Sluz is `error_reporting(E_ALL)` compliant.

## 📖 Documentation

There is extensive documentation in the `docs/` with real world examples of the syntax.

## 🧬 See Also

* [Template::Sluz](https://github.com/scottchiefbaker/perl-Template-Sluz) / Perl
* [Template-Sluz](https://github.com/scottchiefbaker/js-Template-Sluz) / JavaScript

## 🔤 Naming

Sluz is pronounced "slooz". The name comes from the "S" in Smarty
and "luz" which is Spanish for light. Sluz is a lite, Smarty-like
templating system.
