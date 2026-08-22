(function () {
	var KEY = 'sluz-theme';

	function getTheme() {
		try { return localStorage.getItem(KEY) || 'dark'; } catch (e) { return 'dark'; }
	}
	function setTheme(t) {
		try { localStorage.setItem(KEY, t); } catch (e) {}
		document.documentElement.setAttribute('data-bs-theme', t);
		updateIcons(t);
		applyFrameTheme(t);
	}

	function applyFrameTheme(theme) {
		var t = theme || document.documentElement.getAttribute('data-bs-theme') || getTheme();
		document.querySelectorAll('iframe.output-frame').forEach(function (frame) {
			var apply = function () {
				try {
					var doc = frame.contentDocument;
					if (!doc || !doc.documentElement) return;
					// ensure head exists
					if (!doc.head) return;
					var styleId = 'sluz-frame-theme';
					var el = doc.getElementById(styleId);
					if (t === 'dark') {
						if (!el) {
							el = doc.createElement('style');
							el.id = styleId;
							doc.head.appendChild(el);
						}
						// non-!important so inline styles (e.g. dark_mode.stpl body) win
						el.textContent = 'html{color-scheme:dark}body{background:#16181d;color:#e6edf3;margin:12px;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;line-height:1.5}a{color:#82aaff}h1,h2,h3,h4,h5,h6{color:#e6edf3}hr{border-color:#30363d}table{border-color:#30363d}th,td{border-color:#30363d}';
					} else {
						if (el) el.remove();
						// also remove color-scheme if we set it via style, fallback to light
					}
				} catch (e) {}
			};
			// if already loaded, apply now; otherwise wait for load
			try {
				var doc = frame.contentDocument;
				if (doc && doc.readyState === 'complete') {
					apply();
				}
			} catch (e) {}
			frame.addEventListener('load', apply);
			// also try shortly after (covers lazy loading timing)
			setTimeout(apply, 150);
		});
	}
	function updateIcons(t) {
		var isDark = t === 'dark';
		document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
			el.textContent = isDark ? '☀' : '☾';
			el.setAttribute('title', isDark ? 'Switch to light' : 'Switch to dark');
		});
		document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
			btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
			btn.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
		});
	}

	// Expose for inline early script consistency
	window.__sluzSetTheme = setTheme;
	window.__sluzGetTheme = getTheme;

	document.addEventListener('DOMContentLoaded', function () {
		updateIcons(getTheme());
		applyFrameTheme(getTheme());

		document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var cur = document.documentElement.getAttribute('data-bs-theme') || getTheme();
				setTheme(cur === 'dark' ? 'light' : 'dark');
			});
		});
	});
})();
