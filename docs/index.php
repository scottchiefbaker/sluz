<?php

include("../sluz.class.php");
$s = new sluz();

$doc      = $_GET['doc'] ?? "";
$doc_file = basename($doc . ".php");

// If we just request index.php we get this
if (!isset($_GET['doc'])) {
	$doc_file = "001_basic_vars.php";
}

if (!is_readable($doc_file)) {
	$s->error_out("Unable to find documentation '$doc'", 10321);
}

$str  = file_get_contents($doc_file);
$phpc = highlight_string($str, true);

$tpl_file = $s->guess_tpl_file($doc_file);
if (is_readable($tpl_file)) {
	$tplc = htmlentities(file_get_contents($tpl_file));
} else {
	$tplc = '';
}

// Build the object for the left nav structure
$doc_files = get_doc_file_list();
$doc_nav   = [];
foreach ($doc_files as $id) {
	$doc_nav[] = [
		'id'    => $id,
		'label' => pretty_doc_label($id)
	];
}

$doc_current = preg_replace('/\.php$/', '', $doc_file);

$s->assign("doc_name", $doc_file);
$s->assign("doc_current", $doc_current);
$s->assign("php_contents", $phpc);
$s->assign("tpl_contents", $tplc);
$s->assign("doc_files", $doc_nav);
$s->assign('sluz_version', $s->version);

print $s->fetch("tpls/read.stpl");

/////////////////////////////////////////////////////////////////

function get_doc_file_list() {
	$files = glob("???_*.php");

	foreach ($files as &$x) {
		$x = preg_replace("/\.php/", '', $x);
	}

	sort($files);

	return $files;
}

function pretty_doc_label($id) {
	$label = preg_replace('/^\d+_/', '', $id);
	$label = str_replace('_', ' ', $label);
	return ucwords($label);
}
