# Sining Banwa Event Management System

A responsive starting point for the **Web-Based Event Management System for Sining Banwa with SMS Notification**, an arts organization in Albay, Philippines. This project is a thesis-defense prototype, not a live ticketing or payment service.

## Phase 1 scope

This project currently provides a public homepage, customer registration, login/logout, and role-specific account dashboards for customers, administrators, staff, and venue personnel. Registration is limited to the customer role; privileged accounts must be provisioned separately.

Other features (events, seating, reservations, payments, tickets, SMS/email, attendance, reports, expenses, and evaluations) are not implemented in this phase. Authentication requires a `users` table, so this phase includes only the minimal account migration at `database/migrations/001_create_users.sql`. Phase 2 will add the remaining schema.

## Technology

- PHP 8 (plain PHP), PDO, and MySQL/MariaDB
- HTML5, CSS3, vanilla JavaScript, and Bootstrap 5
- XAMPP Apache and MySQL for local development
- Composer project metadata; no external PHP packages are needed yet

Bootstrap 5 is loaded from jsDelivr, so its framework stylesheet and responsive navigation need an internet connection. The custom stylesheet remains local.

## Requirements

- XAMPP with PHP 8, Apache, MySQL/MariaDB, and the `pdo_mysql`, `mbstring`, and `session` extensions enabled
- Composer (optional for this phase; there are no third-party PHP packages)
- A modern web browser

## Folder structure

```text
app/                    PHP bootstrap, auth helpers, and shared views (not public)
config/                 Database connection configuration (not public)
database/               Manual database creation SQL and migrations (not public)
public/                 Web entry point, auth pages, CSS, and JavaScript
storage/logs/            Private application diagnostics
tools/                  Command-line account provisioning utility
uploads/                Private storage area for future uploaded files
.env                    Local credentials; create this file and never commit it
.env.example            Safe local configuration template
```

## 1. Start XAMPP

1. Open the XAMPP Control Panel.
2. Click **Start** beside **Apache** and **MySQL**.
3. Confirm both services show as running.

The default MySQL port is `3306`. If MySQL will not start, see [Troubleshooting](#troubleshooting).

## 2. Create the database in phpMyAdmin

The application does not create databases automatically.

1. Open `http://localhost/phpmyadmin`.
2. To use the provided SQL, open the **SQL** tab and run the contents of `database/create_database.sql`. Alternatively, choose **Import** and select that file, or click **New**, enter `sining_banwa`, and select `utf8mb4_unicode_ci`.
3. Confirm that `sining_banwa` appears in the database list.

The database creation file contains only a non-destructive `CREATE DATABASE IF NOT EXISTS` statement.

### Create the accounts table

After the database exists:

1. Select `sining_banwa` in phpMyAdmin.
2. Open the **SQL** tab.
3. Paste and run the contents of `database/migrations/001_create_users.sql`.
4. Confirm that the `users` table appears.

This is the only table in this phase. No event, reservation, or other application tables or sample accounts are created.

## 3. Configure local settings

1. In the project folder, copy `.env.example` to a new file named `.env`.
2. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` to match your local MySQL setup. Keep `DB_NAME=sining_banwa`.
3. XAMPP often uses `DB_USER=root` and an empty `DB_PASS` locally. An empty root password is for local development only; do not use it on a public server.
4. Leave `APP_BASE_URL` empty to let the app detect `/public` from its script URL. If automatic detection is not suitable, set it to the URL path ending in `/public`, for example `/Sining%20Banwa/public`.
5. Never share or commit `.env`.

The database connector uses PDO exceptions, native prepared statements, and `utf8mb4`. The database connection test displays only a generic failure message; diagnostic details are written to `storage/logs/app.log` when the folder is writable.

## 4. Provision administrator, staff, or venue accounts

Public registration creates customer accounts only. To create a privileged account after the database and `.env` are ready, run the tool from the project folder in PowerShell:

```powershell
php tools\create_user.php admin admin@example.com "Demo Administrator"
php tools\create_user.php staff staff@example.com "Demo Staff"
php tools\create_user.php venue venue@example.com "Demo Venue User"
```

Run only the command(s) you need. The tool asks for the password without displaying it and stores a PHP password hash. Do not put a password in the command, source code, or a shared file. You may use `C:\xampp\php\php.exe` instead of `php` if PHP is not on your PATH. Provision trusted staff accounts only.

The supported role values are `admin`, `staff`, and `venue`. Customers should use the registration form.

## 5. Composer (optional)

There are no third-party PHP dependencies in this phase. If Composer is installed, run:

```powershell
composer install
```

This checks the project's PHP platform requirement and does not install future-phase libraries.

## Version control

The `.gitignore` file excludes `.env`, local logs, and Composer's `vendor/` directory. If Git is installed, initialize the project from its root:

```powershell
git init
git status
```

Before making any commit, confirm `.env` is not listed as a file to add.

## 6. Open the site and test the database

The intended local web root is the `public/` folder. With this project in XAMPP's `htdocs`, open:

```text
http://localhost/Sining%20Banwa/public/
```

Test the PDO connection at:

```text
http://localhost/Sining%20Banwa/public/database-test.php
```

“Database connection successful” confirms PDO connected to the configured database; it does not confirm that the `users` migration was run. A failure message means MySQL could not be reached with the current settings; see the troubleshooting section. This diagnostic page is for local setup and must be removed or disabled before public deployment.

To test authentication:

1. Open **Create a customer account**, register a test customer, then sign in.
2. Confirm that the customer dashboard opens; submit an incorrect password and confirm it is rejected.
3. Provision an admin, staff, and/or venue account with the command-line tool and sign in to each to see the corresponding dashboard.
4. Submit `GET /logout.php` directly and confirm it does not log out (logout requires a CSRF-protected POST); then use the **Log out** button and confirm the session ends.
5. Confirm that customer registration has no role selector and that dashboard access requires sign-in.

## Web access protection

Only `public/` is intended to be served. The project-root `.htaccess` denies web access by default, while `public/.htaccess` permits the public application pages. Separate rules deny access to `app/`, `config/`, `database/`, `storage/`, and `uploads/`; directory listings are disabled.

For these rules to work, Apache must allow `.htaccess` overrides for the project directory. In XAMPP's `apache\conf\httpd.conf`, check the `<Directory "C:/xampp/htdocs">` block and ensure it includes `AllowOverride All` (or permits `AuthConfig` and `Options` overrides) and `Require all granted`. Confirm `mod_authz_core` is enabled, then restart Apache. Do not deploy if private folders or `.env` can be opened in a browser. In production, configure the web server's document root directly to `public/` instead of relying only on `.htaccess`.

## Troubleshooting

### MySQL will not start / port 3306 is busy

Another MySQL service may already use port `3306`. In XAMPP, inspect **Netstat** or the MySQL error log, stop the conflicting local service if appropriate, or configure XAMPP MySQL to use a free port. Set the matching number in `.env` as `DB_PORT`. Restart MySQL and retry the database test.

### `Unknown database`

Confirm MySQL is running, that `sining_banwa` appears in phpMyAdmin, and that `.env` says `DB_NAME=sining_banwa`. Create the database manually using the instructions above.

### `Access denied`

Check `DB_USER`, `DB_PASS`, `DB_HOST`, and `DB_PORT` in `.env`. XAMPP's local `root` account commonly has an empty password, but this may differ on your computer. Do not change database access controls on a public server to accommodate local defaults.

### Apache or `.htaccess` rules appear not to work

Check `AllowOverride` for the XAMPP `htdocs` directory and confirm Apache is configured to read `.htaccess` files. Check the Apache error log after a restart. Keep `.env` and private application folders outside public access until the protections work. The preferred deployment setup is a document root pointed at `public/`.

### The homepage loads but auth fails

Confirm that you created the `users` table by running `database/migrations/001_create_users.sql`, then inspect `storage/logs/app.log` for local diagnostics. Do not copy the log into a public folder or share it if it contains system details.

## Security and deployment notes

- Passwords are stored using `password_hash()` and checked with `password_verify()`.
- Customer registration always assigns the customer role on the server; role authorization is checked on each protected request.
- Forms use CSRF tokens; session cookies are HTTP-only and use `SameSite=Lax`, and session IDs are regenerated after login.
- `.env` and application diagnostics must remain private. Configure HTTPS before deployment so session cookies are secure.
- This prototype has not undergone a full security review and should not be treated as production-secure.
- The database connection test is temporary diagnostics and must be removed or disabled before deployment.
