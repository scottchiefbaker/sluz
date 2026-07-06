# Release Process

## Overview

Releases are tagged from `main` with annotated tags (`v{major}.{minor}.{patch}`).
Packagist auto-discovers new tags — no manual publish step needed.

## Steps

### 1. Bump the version

Edit `sluz.class.php`:

```php
public $version = '0.9.7';   # bump to the new version
```

Use [SemVer](https://semver.org/) conventions while pre-1.0:
- Patch bump (0.9.4 → 0.9.5) for bug fixes / minor changes.
- Minor bump (0.9.x → 0.10.0) for new features.
- Development versions use an odd patch number (e.g. 0.9.5-dev); release
  versions use an even patch number (e.g. 0.9.6).

### 2. Run the test suite

```sh
php unit_tests/tests.php
```

All tests must pass. The library must be `error_reporting(E_ALL)` compliant
(no `E_NOTICE` warnings).

### 3. Commit and tag the version bump

```sh
git add -p                        # review hunk-by-hunk
git commit -m "Bump version to v0.9.7"
git tag -a v0.9.7 -m "Release v0.9.7"
```

### 4. Push

```sh
git push origin main
git push origin v0.9.7
```

This publishes the release on GitHub. Packagist picks up the new tag
automatically via its webhook.

### 5. Verify

- <https://github.com/scottchiefbaker/sluz/releases> — release page
- <https://packagist.org/packages/sluz/sluz> — latest version badge
