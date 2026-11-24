# e-Bunge

A PHP 8+ and SQLite implementation of the e-Bunge Digital County Assembly Motion and Proceedings Platform.

## Run locally

1. Ensure PHP 8+ is installed with the SQLite PDO extension enabled.
2. From this folder run: `php -S localhost:8000`
3. Open `http://localhost:8000/landing.php`.

The SQLite database is created automatically in `database/ebunge.db` on first request.

## Account rules

Registration enforces the requested role-specific password prefixes: `Mca` for Member of County Assembly, `Han` for Hansard Office, and `spkr` for Office of the Speaker. Speaker accounts are limited to two. All forms use CSRF tokens and passwords are stored with PHP's password hashing.

The workflow pages implement the requested paths: MCA submissions, Hansard review/preparation, Speaker approvals, public petitions, attendance approval, one-time MCA voting, committee approval, reports and an activity tracker.
