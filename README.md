# 📰 Sluz PHP templating system

Sluz is a lightweight PHP templating engine. It lets you keep your
logic in `.php` files and your presentation in `.stpl` template files.
The template syntax is similar to [Smarty](https://www.smarty.net/),
so Smarty users will feel at home.

Sluz is a single PHP source file with no dependencies, which keeps it
small and easy to drop into any project.

## 📐 Requirements

Sluz requires PHP 8.0+, and is a zero-dependency library. **Only** the
`sluz.class.php` file is needed for the library to function.

## 🤵 Composer

If you are a composer user you can install Sluz with this command:

```text
composer require sluz/sluz
```

## 📦 Getting started

File: `script.php`
```php
<?php
include('/path/to/sluz/sluz.class.php');
$s = new sluz();

$s->assign("name", "Jason");
$s->assign("version", "0.3");

print $s->fetch("tpls/script.stpl");
```

File: `tpls/script.stpl`
```
<h1>Hello {$name}</h1>

<div>Welcome to MyScript version: {$version}</div>
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
| `{$var\|escape}`                     | `{$name\|escape}`                                | `&lt;script&gt;`    |
| `{$expr}`                            | `{$number + 3}`                                  | `18`                |
| `{if}…{elseif}…{else}…{/if}`         | `{if $x}yes{else}no{/if}`                        | `yes`               |
| `{foreach $array as $v}`             | `{foreach $items as $x}{$x}{/foreach}`           | `onetwothree`       |
| `{foreach $array as $k => $v}`       | `{foreach $data as $idx => $z}{$idx}{/foreach}`  | `012`               |
| `$__FOREACH_FIRST/LAST/INDEX`        | `{if $__FOREACH_FIRST}…{/if}`                    | —                   |
| `{include file='...'}`               | `{include file='header.stpl'}`                   | —                   |
| `{literal}…{/literal}`               | `{literal}function foo() { {/literal}`           | `function foo() { ` |
| `{* comment *}`                      | `{* hidden *}`                                   | *(empty)*           |

## 🏷️ Alternate Delimiters

By default Sluz uses `{` and `}` as delimiters. If your content
frequently contains braces (e.g. CSS, JSON, JavaScript), you can
switch to a different single-character pair:

```php
$s->set_delimiters('[', ']');
```

With the above, all template tags use `[` and `]`:

```
[$name]
[if $admin]Hello admin[/if]
[foreach $items as $item][/foreach]
[* this is a comment *]
[literal]{$name} is not parsed[/literal]
```

**Constraints:** Both delimiters must be single characters and must
not be the same character.

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

### 🛡️ Auto-escape

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
Expression blocks like `{$x + 3}` are not auto-escaped.

### 📜 Trusted templates

Sluz treats **template files as trusted code**. Only let people who are
allowed to run PHP on the server write or edit `.stpl` files.

This is because Sluz evaluates template expressions with `eval()` and
lets modifiers call any PHP function that is callable. For example:

```
{getcwd()}                    {* runs PHP code *}
{$x|shell_exec}               {* any callable function can be used as a modifier *}
```

Sluz does not sandbox or whitelist functions. Do not load templates from
user uploads, databases, or other untrusted sources. Template content is
also not escaped, so a template can output anything it contains.

Treat template files like PHP source files: keep them under version
control, deploy them with your code, and restrict write access to them.

### 🔒 Safe mode

`setSafeMode(true)` closes both code-execution channels described above.
When enabled, no template content can execute PHP code:

- **Nothing is ever `eval()`'d.** Anything that would have been evaluated —
  expression blocks (`{$x + 3}`, `{"a" . "b"}`) and function calls
  (`{getcwd()}`) — becomes the literal string `safe mode enabled`.
- **Only built-in modifiers may run.** `default`, `escape`, and `raw` —
  all defined by the library itself, all pure — keep working, so safe
  mode templates can still escape output and supply fallbacks. Every
  other modifier (`{$var|strtoupper}`, `{$cmd|shell_exec}`) returns
  `safe mode enabled`, and a chain that mixes built-ins with anything
  else is blocked as a whole.
- **Complex `{if}` conditions fail closed.** Simple conditions
  (`{if $x}`, `{if $x == 5}`) are evaluated without `eval()` and keep
  working. Conditions the fast path can't handle (`&&`, arithmetic,
  function calls) skip their branch; `{elseif}`/`{else}` branches still
  render.
- **`{include}` is contained.** Include paths must resolve *inside* an
  include root — the calling script's directory (simple mode) or the
  current working directory.

You can pin the include root yourself, in either mode:

```php
$s->setTemplateRoot('/var/www/app/templates'); // realpath enforced always
```

With a root set it does two things, even with safe mode **off**: relative
include names resolve against it (like Smarty's template directory), and
nothing outside it can be read. Calling `setTemplateRoot()` is opt-in, so
templates behave exactly as before until you do.

**What safe mode does not do:**

- Variables still render their assigned values, so escaping still matters.
  `|escape` works in safe mode, but `setEscapeHtml(true)` is still the
  stronger choice — it escapes every `{$var}` by default instead of
  relying on each call site to opt in.
- `{include file="$dynamic"}` fails with error 18485 because the
  placeholder string is used as the filename.

Safe mode trades functionality for hard guarantees: no template content
executes PHP, and includes cannot read outside your root. It does not by
itself make untrusted templates safe to render.

## 🥽 Testing

Sluz has an extensive test suite that is used to verify compatibility
across PHP versions. As of this writing Sluz passes all unit tests on
all supported PHP versions (8.2+).

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
