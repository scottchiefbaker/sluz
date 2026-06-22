#!/usr/bin/env php
<?php

require_once(__DIR__ . "/../sluz.class.php");

$ITERATIONS = 15000;
$filter     = '';

$opts       = getopt('f:n:', ['filter:', 'iterations:']);
$filter     = $opts['f'] ?? $opts['filter'] ?? '';
$ITERATIONS = (int)($opts['n'] ?? $opts['iterations'] ?? $ITERATIONS);

if (!empty($argv[1]) && is_numeric($argv[1])) {
    $ITERATIONS = (int)$argv[1];
}

################################################################################
################################################################################

$sluz = new sluz();
$sluz->in_unit_test = true;

$vars      = get_tpl_vars();
$templates = get_templates();

$sluz->assign($vars);

// Print header
$line = str_repeat('-', 61);
printf("%-30s %8s %10s %10s\n", "Benchmark", "Iters", "Millis", "Iter /s");
print "$line\n";

$total_time = 0;
$results    = [];

foreach ($templates as $name => $t) {
    $tpl  = $t['tpl'];
    $desc = $t['desc'];

    if ($filter && !preg_match("/$filter/i", $name) && !preg_match("/$filter/i", $desc)) {
        continue;
    }

    // Warmup
    for ($i = 0; $i < 10; $i++) {
        $sluz->parse_string($tpl);
    }

    $start = millis();
    for ($i = 0; $i < $ITERATIONS; $i++) {
        $sluz->parse_string($tpl);
    }
    $elapsed = millis() - $start;
    $total_time += $elapsed;

    $per_sec = $elapsed > 0 ? ($ITERATIONS * 1000) / $elapsed : 0;

    printf("%-30s %8d %10d %10.1f\n", $desc, $ITERATIONS, $elapsed, $per_sec);
    $results[$name] = ['elapsed' => $elapsed, 'per_sec' => $per_sec];
}

print "$line\n";
printf("%-30s %8s %10d\n", "TOTAL", "", $total_time);

################################################################################

function get_tpl_vars() {
    return [
        'name'     => "Scott Baker",
        'age'      => 42,
        'email'    => 'scott@perturb.org',
        'city'     => "Portland",
        'state'    => "OR",
        'active'   => 1,
        'verified' => 0,
        'items'    => ['apple', 'banana', 'cherry', 'date', 'elderberry', 'fig', 'grape'],
        'users'    => [
            ['name' => "Alice", 'age' => 30, 'role' => "admin"],
            ['name' => "Bob",   'age' => 25, 'role' => "user"],
            ['name' => "Carol", 'age' => 35, 'role' => "mod"],
        ],
        'config' => [
            'theme'    => "dark",
            'lang'     => "en",
            'per_page' => 25,
        ],
        'empty_list' => [],
        'undef_var'  => null,
        'big_list'   => range(1, 100),
    ];
}

function get_templates() {
    return [
        'variables_simple' => [
            'desc' => "Simple variable output",
            'tpl'  => 'Hello {$name}, you are {$age} years old.',
        ],
        'variables_dotted' => [
            'desc' => "Dotted path variables",
            'tpl'  => 'Theme: {$config.theme}, Lang: {$config.lang}, Per page: {$config.per_page}',
        ],
        'modifiers' => [
            'desc' => "Variable modifiers",
            'tpl'  => '{$name|uc} {$name|lc} {$name|ucfirst} {$name|substr:0,5}',
        ],
        'modifiers_chained' => [
            'desc' => "Chained modifiers",
            'tpl'  => '{$name|lc|ucfirst} {$name|uc|substr:0,5}',
        ],
        'modifiers_default' => [
            'desc' => "Default modifier",
            'tpl'  => '{$undef_var|default:"N/A"} {$name|default:"Unknown"}',
        ],
        'if_simple' => [
            'desc' => "Simple if/else",
            'tpl'  => '{if $active}ACTIVE{else}INACTIVE{/if}',
        ],
        'if_nested' => [
            'desc' => "Nested if blocks",
            'tpl'  => '{if $active}{if $verified}VERIFIED{else}UNVERIFIED{/if}{else}DISABLED{/if}',
        ],
        'if_elseif' => [
            'desc' => "If/elseif/else chains",
            'tpl'  => '{if $age > 50}SENIOR{elseif $age > 30}ADULT{elseif $age > 18}YOUNG{else}MINOR{/if}',
        ],
        'if_negated' => [
            'desc' => "Negated conditions",
            'tpl'  => '{if !$verified}NOT VERIFIED{/if}{if !$undef_var}IS UNDEF{/if}',
        ],
        'foreach_array' => [
            'desc' => "Foreach over array",
            'tpl'  => '{foreach $items as $item}[{$item}]{/foreach}',
        ],
        'foreach_array_with_index' => [
            'desc' => "Foreach with index/first/last",
            'tpl'  => '{foreach $items as $item}{$__FOREACH_INDEX}:{$item}{if $__FOREACH_LAST}!{/if} {/foreach}',
        ],
        'foreach_hash' => [
            'desc' => "Foreach over hash",
            'tpl'  => '{foreach $config as $k => $v}{$k}={$v} {/foreach}',
        ],
        'foreach_nested' => [
            'desc' => "Nested foreach",
            'tpl'  => '{foreach $users as $u}{foreach $items as $i}{if $i == "banana"}{$u.name}:{$i} {/if}{/foreach}{/foreach}',
        ],
        'foreach_empty' => [
            'desc' => "Foreach over empty list",
            'tpl'  => 'BEFORE{foreach $empty_list as $item}{$item}{/foreach}AFTER',
        ],
        'comments' => [
            'desc' => "Comments (should be stripped)",
            'tpl'  => '{* this is a comment *}Hello {$name}!',
        ],
        'literal' => [
            'desc' => "Literal blocks",
            'tpl'  => '{literal}function foo() { return {$x}; }{/literal}',
        ],
        'expression' => [
            'desc' => "Expression/function blocks",
            'tpl'  => 'Count: {$items|count} Joined: {$items|join:"-"}',
        ],
        'mixed' => [
            'desc' => "Mixed template features",
            'tpl'  => '<div class="user-list">' . "\n"
                    . '{* Display each user *}' . "\n"
                    . '{foreach $users as $u}' . "\n"
                    . '  <div class="user {if $u.role eq "admin"}admin{else}regular{/if}">' . "\n"
                    . '    <span class="name">{$u.name|ucfirst}</span>' . "\n"
                    . '    <span class="age">({$u.age})</span>' . "\n"
                    . '    {if $u.age > 28}' . "\n"
                    . '      <span class="senior">Senior</span>' . "\n"
                    . '    {/if}' . "\n"
                    . '  </div>' . "\n"
                    . '{/foreach}' . "\n"
                    . '</div>',
        ],
        'foreach_large' => [
            'desc' => "Large foreach (100 items)",
            'tpl'  => '{foreach $big_list as $i}{$i} {/foreach}',
        ],
    ];
}

function millis() {
    return (int)(microtime(true) * 1000);
}
