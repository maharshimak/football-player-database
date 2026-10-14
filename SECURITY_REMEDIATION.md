# Verified remediation

- SQL injection: create/update/delete/id reads use prepare and bind_param, with positive integer IDs and validated player fields.
- Stored XSS: HTML escapes all values, including quotes; tested malicious HTML is inert escaped text.
- CSRF and unintended GET writes: every write/login/logout requires POST plus constant-time session token equality. Legacy GET deletion no longer mutates.
- Authentication: required for listing, edits, deletes, dashboard and CSV export; environment-configured password_hash only, no default credential; session regeneration at login and expiry/hash rotation checks.
- CSV formulas: leading =,+,-,@ and leading control characters are prefixed with an apostrophe before CSV serialization.
- Privacy: no original DB/dump content or raw DB error details; named environment variables only. No stock/external assets downloaded.
- Runtime: source verified on official checksum-verified PHP8.5.11, legacy isolated MariaDB10.4 fixture only. Supported modern DB required for actual deployment. PHP unsupported branches: https://www.php.net/eol.php ; supported releases: https://www.php.net/supported-versions.php ; MariaDB policy: https://mariadb.org/about/#maintenance-policy .

Remaining shared-hosting work: account-aware durable throttling, HTTPS/reverse-proxy review, supported DB fixture, operational monitoring and backup/restore policy. No deployment or original modification occurred.
