# Sining Banwa Event Management System

A responsive starting point for the **Web-Based Event Management System for Sining Banwa with SMS Notification**, an arts organization in Albay, Philippines. This project is a thesis-defense prototype, not a live ticketing or payment service.

## Phase 1 foundation and Phase 2 scope

The Phase 1 foundation provides a public homepage, customer registration, login/logout, password recovery, and role-specific dashboards for customers, administrators, staff, and venue personnel. Registration is limited to the customer role; privileged accounts are provisioned separately.

Phase 2 adds event creation and management for administrators and staff, optional private poster uploads, and public listings/details for published events. The Phase 3 seat designer adds event-specific sections, rows, draggable seats, availability, and pricing. Phase 4 presents those published events on the public homepage and adds profile management for signed-in users. Reservations, payments, tickets, SMS/email notifications, attendance, reports, expenses, and evaluations remain for later phases.

## Technology

- PHP 8 (plain PHP), PDO, and MySQL/MariaDB
- HTML5, CSS3, vanilla JavaScript, and Bootstrap 5
- XAMPP Apache and MySQL for local development
- Composer project metadata; no external PHP packages are needed yet

Bootstrap 5 is loaded from jsDelivr, so its framework stylesheet and responsive navigation need an internet connection. The custom stylesheet remains local.

## Requirements

- XAMPP with PHP 8, Apache, MySQL/MariaDB, and the `pdo_mysql`, `mbstring`, `fileinfo`, and `session` extensions enabled
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

The `users` migration creates the account table only; it does not create sample accounts.


### Enable password recovery

After creating the `users` table, run `database/migrations/002_create_password_reset_tokens.sql` in the same database. Copy `.env.example` to `.env` if you have not already, then set `APP_URL` to the full URL of this app (for example `http://localhost/Sining%20Banwa/public`) and set `MAIL_FROM_EMAIL` to a valid sender address. The recovery form sends a one-hour, single-use link using PHP `mail()`; the token itself is not stored in the database.

PHP mail delivery is not configured by default in XAMPP. Configure the mail transport in XAMPP PHP/sendmail settings with your email provider, then restart Apache and test delivery. Do not put mail-provider passwords in source files or share them in chat. On a production site, use HTTPS and configure `APP_URL` with the real HTTPS address. If mail cannot be sent, the application log records the failure without revealing whether an account exists.

### Add the Phase 2 event table

After applying migrations `001_create_users.sql` and `002_create_password_reset_tokens.sql` in order, run `database/migrations/003_create_events.sql` in the `sining_banwa` database. The app does not run migrations automatically. Event posters are optional and are saved under the private `uploads/events/` directory; Apache serves them only through the application endpoint, which checks event publication status or the signed-in manager role.

### Add the Phase 3 seating tables

After running migrations `001_create_users.sql`, `002_create_password_reset_tokens.sql`, and `003_create_events.sql` in order, run `database/migrations/004_create_event_seating.sql` in phpMyAdmin. The migration adds event sections, rows, and seats, including visual coordinates, availability, section default prices, and optional per-seat price overrides. It does not change existing event or account records.

To design a layout, sign in as an admin or staff member, open **Manage events**, edit an existing event, and choose **Design seats**. Add a section, set its default PHP price, add rows and seats, drag seats to arrange the map, select seats to add an optional price override or block/unblock them, then click **Save seat layout**. Published event details show the layout and seat prices; booking and seat locking will be added in later phases.

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
3. After configuring PHP mail delivery, request a reset link for an active test account, set a new password, and verify the link cannot be reused.
4. Provision an admin, staff, and/or venue account with the command-line tool and sign in to each to see the corresponding dashboard.
5. Submit `GET /logout.php` directly and confirm it does not log out (logout requires a CSRF-protected POST); then use the **Log out** button and confirm the session ends.
6. Confirm that customer registration has no role selector and that dashboard access requires sign-in.

### Test Phase 2 event management

1. Apply migration `003_create_events.sql` after the account and password-reset migrations.
2. Sign in as an admin or staff member and open **Manage events**. Create a draft, edit it, then publish it.
3. Open **Events** in a signed-out or customer session. Confirm the published event appears, while drafts, completed events, and cancelled events do not.
4. Test the detail page and an optional valid JPG, PNG, or WebP poster (maximum 5 MiB). Confirm a customer cannot view a draft poster and cannot open the event-management page.
5. Try an invalid image or an image larger than 5 MiB and confirm it is rejected. Change a published event to cancelled and confirm its record remains in the management list but disappears from public pages.
### Test Phase 3 seat layout and poster preview

1. Apply `database/migrations/004_create_event_seating.sql` after migration `003_create_events.sql`.
2. On an existing event, open **Design seats**, add a priced section, rows, and seats, drag seats, set one seat override, block a seat, save, and reload. Confirm all positions and prices persist and are visible on the published event detail.
3. On an event edit page with a poster, choose **View current poster**. Confirm the dialog has both a close icon and a Close button, and also closes with Escape.

### Test Phase 4 public website and customer accounts

No new database migration is required. The profile page uses the existing `users` table.

1. Open the homepage while signed out. Confirm published events appear there and on **Events**, and that draft events do not.
2. Create or sign in to a customer account, open **Profile**, change the name or phone number, and save. Confirm the dashboard shows the new details.
3. Change the password with the wrong current password and confirm it is rejected. Then change it with the correct current password, log out, and sign in with the new password.
4. Confirm the profile form does not let you edit the email address or role.

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
