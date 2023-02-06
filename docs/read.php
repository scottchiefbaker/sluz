<?php

include("../sluz.class.php");
$s = new sluz();

$doc      = $_GET['doc'] ?? "";
$doc_file = $doc . ".php";

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

$doc_files = get_doc_file_list();

$s->assign("doc_name", $doc_file);
$s->assign("php_contents", $phpc);
$s->assign("tpl_contents", $tplc);
$s->assign("doc_files", $doc_files);

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
