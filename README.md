# Football player database

A small, local authenticated PHP/MySQL teaching application for creating, editing, listing, deleting and CSV-exporting players. Source originates in audited projects30/127; the owner expressly confirmed ownership and public redistribution rights. No permissive license was invented.

The staging rewrite replaces interpolated SQL with prepared parameters, escapes every database value rendered in HTML, replaces deletion-by-GET with authenticated POST+CSRF, neutralizes CSV spreadsheet formulas and moves DB credentials to environment configuration. Sign-in uses an explicitly configured password hash, regenerated sessions, a 30-minute idle timeout, strict/HTTP-only cookies and hash-version invalidation on password rotation. Every read/export and mutation requires authentication; writes/login/logout require CSRF. Error responses omit database details. CSP restricts content and form actions to this origin.

See SETUP.md for supported runtime requirements, isolated setup and test commands. No private database, dump rows, original credentials, binary runtime or historical Git data are published. Older archive variants are preserved locally for audit provenance but excluded from publication.

## Validation

24 pure PHP helper assertions pass. Disposable HTTP/database checks pass for authentication, session regeneration, CSRF failures, safe apostrophe/HTML input, ID injection rejection, CRUD, legacy GET-delete rejection, CSV formulas, logout and unauthenticated export. Source syntax was checked with PHP8.5.11. Fixtures used fresh synthetic DB data and loopback-only temporary services, which were stopped afterward.

## Limits

This is a single-admin local educational application, not a production multitenant service. Login throttling is per session and does not withstand cookie-reset brute force; add reverse-proxy/account-aware rate limiting before shared hosting. HTTPS/proxy integration, multiuser RBAC, concurrent transactions and modern database-runtime deployment have not been verified. Installed legacy XAMPP runtimes are unsupported; use current PHP/MySQL/MariaDB. The local design was simplified during security remediation; no public site was deployed.
