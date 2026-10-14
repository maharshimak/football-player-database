# Setup

Use a supported PHP release (PHP >=8.3, mysqli/mysqlnd required) and supported MySQL/MariaDB. Current verification used checksum-verified official PHP 8.5.11. The installed XAMPP PHP8.0/MariaDB10.4 versions are end-of-life and must not be used for deployment.

Create a new utf8mb4 database; import only schema.sql. Create a database user limited to SELECT, INSERT, UPDATE, DELETE on that database. Supply DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD via process environment; `.env.example` is documentation and is not automatically loaded. Never commit actual credentials.

Set APP_ADMIN_USERNAME and APP_ADMIN_PASSWORD_HASH. Generate the hash using PHP password_hash from a securely supplied local password; no default admin is created. Leave no password on a command line, in source, or in reports. Missing/invalid configuration fails closed. Password-hash rotation invalidates old sessions. Default allowed hosts are loopback; localhost development uses HTTP-only SameSite=Strict cookies. HTTPS sets Secure automatically.

Start a deliberate local-only PHP server with `php -S 127.0.0.1:8080 -t .`; this built-in development server is not for public deployment. Visit login.php. Authenticated users may create, edit, list, delete and export synthetic players.

## Tests

`php -n tests/helpers.php` runs 24 pure helper assertions, without a database.

For Windows-only disposable integration verification, set FOOTBALL_PHP to a trusted PHP executable, FOOTBALL_MYSQL_BIN to a trusted MariaDB bin folder that supplies mysql_install_db.exe/mysqld.exe/mysql.exe, and FOOTBALL_FIXTURE_ROOT to a dedicated empty test workspace. Then run `python tests/integration_fixture.py`. The runner initializes a new database directory, synthetic accounts, schema and random fixture-only passwords; launches hidden loopback-only temporary processes on free ports; checks authenticated CRUD and security rejection paths; and stops its own processes. It does not connect to any original service/datadir and does not install a Windows service. Generated fixture directories are preserved for inspection rather than deleting user files.

The integration harness was verified on PHP8.5.11 plus a fresh database created with legacy MariaDB10.4.32; that legacy DB was used only for isolated compatibility testing. Re-run on a supported DB runtime before deployment. Do not reuse existing production services/credentials for tests.
