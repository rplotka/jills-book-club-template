# AGENTS.md: how code in this repo is written

This is Jill's Book Club, a PHP 8.3 + MariaDB site for a first-year web course. Keep changes small
and in the style of the code that is already here, so a first-year student can read them.

## Stack
- Plain PHP pages, no framework and no Composer. Each page starts with
  `require 'includes/init.inc.php';` and uses `includes/head.inc.php` and
  `includes/foot.inc.php` for the layout.
- MariaDB through `mysqli` (the `$db` object from `init.inc.php`). The schema is in `setup.sql`.
- Front end: HTML, `resources/jill.css`, vanilla JavaScript, and jQuery 4.0.0 (already in
  `resources/`). Do not add other libraries or CDNs.

## Rules
- **Every query that uses input is a prepared statement** (`prepare`, `bind_param`, `execute`).
  `$db->query()` is only for fixed SQL.
- **Escape every value you print** with `h()` from `includes/functions.inc.php`.
- **After a change, redirect.** Forms that change data use POST, then `redirect()`, then GET.
  Deletes are POST forms, never links.
- **Validate on the server** even when the browser also checks.
- **Never commit secrets.** Database settings live in `includes/config.inc.php`, which is
  gitignored. If a change needs a new setting, add it to `includes/config.example.inc.php` with a
  placeholder value.
- **Schema changes go in `setup.sql`** and are also written out as the SQL to run on an existing
  database, in the pull request description.

## Checking your work
- Run `php -l` on every PHP file you change.
- In the pull request description, say what you changed, which files, and how to test it in a
  browser.
