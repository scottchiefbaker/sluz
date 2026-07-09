<?php
	$json = $_POST['json'] ?? "";
	$tpl  = $_POST['tpl']  ?? "";

	require("../../sluz.class.php");
	$s = new sluz();

	if ($json && $tpl) {
		$obj  = json_decode($json, true);
		$type = 'json';

		if ($obj === null && json_last_error() !== JSON_ERROR_NONE) {
			$obj = yaml_parse($json);

			if (is_array($obj)) {
				$type = 'yaml';
			} else {
				$type = false;
			}
		}

		$s->assign($obj);

		$parsed = $s->parse_string($tpl);
		$ret    = [
			'type'   => $type,
			'parsed' => $parsed,
			'raw'    => $obj,
		];

		print json_encode($ret);
		exit;
	}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Sluz sandbox</title>

		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<script type="text/javascript" src="../js/jquery.min.js"></script>
		<script>
			var json_sample = `---
name: Jason Doolis
color: Red
orders:
- ID: "1"
  Total: $99
  Items: "3"
- ID: "2"
  Total: $299
  Items: "6"
- ID: "3"
  Total: $50
  Items: "10"`;
			var sluz_tpl = '<h2>Orders</h2>\n\n{foreach $orders as $x}\n<div>{$x.ID}) Total: {$x.Total} in items: {$x.Items}</div>\n{/foreach}\n\n<p class=\"mt-3\">Customer: {$name}</p>';

			$(document).ready(function() {
				init();
				process();
			});

			// Generic debounce function
			function debounce(fn, delay) {
				let timer = null;
				return function(...args) {
					clearTimeout(timer);
					timer = setTimeout(() => fn.apply(this, args), delay);
				};
			}

			function init() {
				$("#sluz_input, #json_input").on("keyup", debounce(function() {
					process();
				}, 200));

				$("#process").on("click", function() {
					process();
				});

				$("#use_defaults").on("click", function(e) {
					e.preventDefault();
					$("#sluz_input").val(sluz_tpl);
					$("#json_input").val(json_sample);

					process();
				});
			}

			function process() {
				var tpl  = $("#sluz_input").val();
				var json = $("#json_input").val();

				var bad_color = 'rgb(46, 24, 28)';

				try {
					var data = { 'json': json, 'tpl': tpl, };
					var out_text = $.ajax({
						dataType: "json",
						url     : "index.php",
						method  : "post",
						data    : data,
						success : function(e) {
							var out_text = e.parsed;
							var ok       = (e.type !== false);

							if (!ok) {
								console.log('Unknown input');
								$("#json_input").css('background', bad_color);
							} else {
								$("#json_input").css('background', 'inherit');
							}

							$("#sluz_text").val(out_text);
							$("#html_output").html(out_text);
							$("#sluz_input").css('background', 'inherit');
						},
						error : function(e) {
							if (tpl) {
								console.log('bad sluz');
								$("#sluz_input").css('background', bad_color);
							}
						}
					});

				} catch { }
			}
		</script>

		<link rel="stylesheet" type="text/css" media="screen" href="../css/bootstrap.min.css" />

		<style>
		</style>
	</head>

<body class="" data-bs-theme="dark">
	<div class="container-fluid">
		<div class="row">
			<h2 class="col-10 bg-dark-subtle text-light p-2 ps-3">Sluz v<?PHP print $s->version ?> sandbox</h2>
			<h2 class="col-2 text-end bg-dark-subtle text-light p-2 pe-3"><a href="#" title="Use sample data" id="use_defaults"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-brilliance" viewBox="0 0 16 16">
  <path d="M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16M1 8a7 7 0 0 0 7 7 3.5 3.5 0 1 0 0-7 3.5 3.5 0 1 1 0-7 7 7 0 0 0-7 7"/>
</svg></a></h2>
		</div>
	</div>

	<div class="container-fluid row">
		<div class="col">
			<div class="p-2">
				<div class="mb-3">
					JSON/YAML Input:
					<textarea id="json_input" class="w-100" rows="9" placeholder="JSON/YAML input"></textarea>
				</div>

				<div class="mb-3">
					Sluz template:
					<textarea id="sluz_input" class="w-100" rows="9" placeholder="Sluz TPL input"></textarea>
				</div>

				<div class="mb-3">
					Text Output:
					<textarea id="sluz_text" class="font-monospace w-100" rows="9" placeholder="Text output"></textarea>
				</div>

				<button id="process" class="btn btn-primary">Process</button>
			</div>
		</div>

		<div class="col">
			<div class="p-2">
				<h3 class="alert alert-dark">HTML Output:</h3>
				<div id="html_output"></div>
			</div>
		</div>
	</div>
</body>
</html>
