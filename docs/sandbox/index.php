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
		<title>Sluz v<?php print $s->version ?> sandbox</title>

		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Sluz sandbox — try templates live with JSON/YAML input">
		<link rel="icon" href='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><text y="14" font-size="14">◈</text></svg>'>
		<script>try{var k='sluz-theme';var t=localStorage.getItem(k)||'dark';document.documentElement.setAttribute('data-bs-theme',t);}catch(e){document.documentElement.setAttribute('data-bs-theme','dark');}</script>

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
								$("#json_input").addClass('is-invalid');
							} else {
								$("#json_input").removeClass('is-invalid');
							}

							$("#sluz_text").val(out_text);
							$("#html_output").html(out_text);
							$("#sluz_input").removeClass('is-invalid');
						},
						error : function(e) {
							if (tpl) {
								console.log('bad sluz');
								$("#sluz_input").addClass('is-invalid');
							}
						}
					});

				} catch { }
			}
		</script>

		<link rel="stylesheet" type="text/css" media="screen" href="../css/bootstrap.min.css" />

		<style>
			:root { --sluz-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
			body { -webkit-font-smoothing: antialiased; }
			textarea.form-control { font-family: var(--sluz-mono); font-size: .875rem; line-height: 1.5; }
			textarea.form-control.is-invalid { border-color: var(--bs-danger); box-shadow: 0 0 0 .25rem rgba(var(--bs-danger-rgb), .25); }
			.card { box-shadow: 0 1px 2px rgba(0,0,0,.06); }
			#html_output { min-height: 6rem; }
			.output-card .card-body { border-radius: 0 0 var(--bs-card-border-radius) var(--bs-card-border-radius); }
			[data-bs-theme="dark"] #html_output { color: var(--bs-body-color); }
			[data-bs-theme="dark"] #html_output a { color: #82aaff; }
		</style>
	</head>

<body>
	<nav class="navbar sticky-top bg-body-tertiary border-bottom shadow-sm">
		<div class="container-fluid d-flex align-items-center justify-content-between">
			<div class="d-flex align-items-center gap-3">
				<a href="../" class="btn btn-outline-secondary btn-sm" title="Back to docs">← Docs</a>
				<span class="navbar-brand mb-0 h1">Sluz <span class="badge text-bg-secondary align-middle fw-normal" style="font-size:.55em; letter-spacing:.04em;">v<?php print $s->version ?></span> <span class="text-body-secondary fw-normal" style="font-size:.65em;">sandbox</span></span>
			</div>
			<div class="d-flex align-items-center gap-2">
				<button data-theme-toggle class="btn btn-outline-secondary btn-sm" type="button" aria-label="Toggle theme" title="Toggle theme" style="width:3em;"><span data-theme-icon aria-hidden="true">☾</span></button>
				<a href="#" id="use_defaults" class="btn btn-outline-secondary btn-sm" title="Load sample data">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-brilliance" viewBox="0 0 16 16" aria-hidden="true">
						<path d="M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16M1 8a7 7 0 0 0 7 7 3.5 3.5 0 1 0 0-7 3.5 3.5 0 1 1 0-7 7 7 0 0 0-7 7"/>
					</svg>
					<span class="d-none d-sm-inline ms-1">Sample</span>
				</a>
				<a href="https://github.com/scottchiefbaker/sluz" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm d-none d-sm-inline">GitHub</a>
			</div>
		</div>
	</nav>

	<div class="container-fluid mt-3 mb-4">
		<div class="row g-4">
			<div class="col-lg-6">
				<div class="p-2">
					<div class="mb-3">
						<label for="json_input" class="form-label fw-semibold small text-body-secondary text-uppercase" style="letter-spacing:.04em;">JSON / YAML Input</label>
						<textarea id="json_input" class="form-control w-100" rows="9" placeholder="JSON or YAML input"></textarea>
					</div>

					<div class="mb-3">
						<label for="sluz_input" class="form-label fw-semibold small text-body-secondary text-uppercase" style="letter-spacing:.04em;">Sluz template</label>
						<textarea id="sluz_input" class="form-control w-100" rows="9" placeholder="Sluz template input"></textarea>
					</div>

					<div class="mb-3">
						<label for="sluz_text" class="form-label fw-semibold small text-body-secondary text-uppercase" style="letter-spacing:.04em;">Text output</label>
						<textarea id="sluz_text" class="form-control font-monospace w-100" rows="9" placeholder="Text output" readonly></textarea>
					</div>

					<button id="process" class="btn btn-primary">Process</button>
				</div>
			</div>

			<div class="col-lg-6">
				<div class="p-2">
					<div class="card output-card">
						<div class="card-header fw-semibold d-flex align-items-center gap-2">
							<span class="badge text-bg-warning">OUT</span> HTML Output
							<span class="text-body-secondary fw-normal small ms-auto">live preview</span>
						</div>
						<div class="card-body">
							<div id="html_output"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<footer class="text-center text-body-secondary small mt-5 pt-3 border-top">
			Sluz v<?php print $s->version ?> — <a href="https://github.com/scottchiefbaker/sluz" class="link-secondary" target="_blank" rel="noopener">GitHub</a> · <a href="../" class="link-secondary">Docs</a>
		</footer>
	</div>
	<script src="../js/theme.js"></script>
</body>
</html>
