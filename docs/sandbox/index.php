<?php
	$json = $_POST['json'] ?? "";
	$tpl  = $_POST['tpl']  ?? "";

	if ($json && $tpl) {
		require("../../sluz.class.php");

		$s   = new sluz();
		$obj = json_decode($json, true);
		$s->assign($obj);

		$parsed = $s->parse_string($tpl);
		$ret    = ['parsed' => $parsed];

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
			var json_sample = '{"true":true,"nums":[1,2,3,4,5],"name":"Jason Doolis","customer":{"name":"Thomas","age":39,"color":"purple"},"fruits":[["Apple","Banana","Cherry"],["Pear","Orange","Grape"],["Lime","Lemon","Mango"]],"orders":[{"ID":"1","Total":"$99","Items":"3"},{"ID":"2","Total":"$299","Items":"6"},{"ID":"3","Total":"$50","Items":"10"},{"ID":"4","Total":"$75","Items":"27"},{"ID":"5","Total":"$200","Items":"4"}]}';
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
				var data = {};

				var bad_color = 'rgb(46, 24, 28)';

				try {
					if (!json) { return; }

					data = JSON.parse(json);

					$("#json_input").css('background', 'inherit');
				} catch {
					console.log('bad json');
					$("#json_input").css('background', bad_color);
				}

				try {
					var data = { 'json': json, 'tpl': tpl, };
					var out_text = $.ajax({
						dataType: "json",
						url     : "index.php",
						method  : "post",
						data    : data,
						success : function(e) {
							var out_text = e.parsed;

							$("#sluz_text").val(out_text);
							$("#html_output").html(out_text);
							$("#sluz_input").css('background', 'inherit');
						},
						error : function(e) {
							console.log('bad sluz');
							$("#sluz_input").css('background', bad_color);
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
	<div class="container-xxl">
		<div class="row">
			<h2 class="col-10 bg-dark-subtle text-light p-2 ps-3">Sluz sandbox</h2>
			<h2 class="col-2 text-end bg-dark-subtle text-light p-2 pe-3"><a href="#" title="Use sample data" id="use_defaults">#</a></h2>
		</div>
	</div>

	<div class="container-fluid row">
		<div class="col">
			<div class="p-2">
				<div class="mb-3">
					JSON Input:
					<textarea id="json_input" class="w-100" rows="9" placeholder="JSON input"></textarea>
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
