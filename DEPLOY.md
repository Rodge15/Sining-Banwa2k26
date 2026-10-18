# Deploy on Vercel with Supabase

Local XAMPP and MySQL stay the default. These steps create a separate Supabase database and a Vercel site. They do not change the tables in your local `sining_banwa` database.

Do not run `database/supabase/schema.sql` in phpMyAdmin. Do not put passwords, the Supabase service-role key, or the Resend API key in source files or in Git.

## 1. Push this code

Commit and push the deployment files to GitHub, including `vercel.json`, `Dockerfile.vercel`, `Caddyfile`, and `database/supabase/schema.sql`. Vercel builds from that repository. Leave `.env` untracked.

## 2. Create the Supabase database

1. Sign in at [supabase.com](https://supabase.com) and create a new project. Save the database password somewhere private. You cannot recover it from the dashboard later.
2. Wait until the project status is healthy.
3. Open **SQL Editor**, choose **New query**, and paste the full contents of [database/supabase/schema.sql](database/supabase/schema.sql).
4. Run it once. It creates empty `users`, `password_reset_tokens`, `events`, `event_sections`, `event_rows`, and `event_seats` tables. It does not copy your local rows.
5. Open **Table Editor** and confirm those six tables exist. They should have no rows yet.

The script turns on row-level security and removes public Data API access. The PHP app connects as the database owner, so it can still read and write. Do not add the anon key or service-role key to browser code.

## 3. Create the private poster bucket

1. In Supabase, open **Storage**.
2. Create a bucket named exactly `event-posters`.
3. Leave it **private**. Do not add a public read policy.

The site serves posters through `event_poster.php` after checking whether the event is published. The bucket itself stays private.

## 4. Copy the database connection settings

In Supabase, open **Project Settings**, then **Database**, then the **Connection string** section. Choose the **Session pooler**, not the transaction pooler.

Use the URI fields like this:

| App setting | Where it comes from |
| --- | --- |
| `DB_HOST` | Host after `@`, such as `aws-0-ap-southeast-1.pooler.supabase.com` |
| `DB_PORT` | `5432` |
| `DB_NAME` | `postgres` |
| `DB_USER` | `postgres.` followed by your project reference |
| `DB_PASS` | The database password from step 2 |

Do not use port `6543`. This app uses prepared statements, and the transaction pooler on that port can break them.

Also copy:

- **Project URL**, such as `https://your-project-ref.supabase.co`, for `SUPABASE_URL`.
- **service_role** secret from **Project Settings > API** for `SUPABASE_SERVICE_ROLE_KEY`. This is a server secret. Do not use the anon key.

## 5. Create the Resend email key

Password reset cannot use PHP `mail()` on Vercel. Local XAMPP still uses `mail()` unless you set a Resend key there too.

1. Create an account at [resend.com](https://resend.com).
2. Add and verify a domain you control. Resend shows the DNS records to add at your domain host.
3. Create an API key and store it privately.
4. Choose a sender on that verified domain, such as `no-reply@your-domain.com`.

Resend's shared onboarding sender only delivers to the email address on the Resend account. It is not enough for real password-reset links. Login still works without email; only the forgot-password message needs this step.

## 6. Create the Vercel project

1. Sign in at [vercel.com](https://vercel.com).
2. Choose **Add New > Project** and import the GitHub repository.
3. Leave the framework preset as **Other**. Do not set an install or output command.
4. Before deploying, add these environment variables. Use the values from the steps above, not the examples.

| Name | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_BASE_URL` | leave empty |
| `APP_URL` | `https://your-project.vercel.app` after the first deploy, then redeploy |
| `MAIL_FROM_EMAIL` | verified sender from Resend |
| `MAIL_FROM_NAME` | `Sining Banwa` |
| `DB_DRIVER` | `pgsql` |
| `DB_HOST` | session-pooler host |
| `DB_PORT` | `5432` |
| `DB_NAME` | `postgres` |
| `DB_USER` | `postgres.your-project-ref` |
| `DB_PASS` | Supabase database password |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `cookie` |
| `APP_SECRET` | a random string of at least 32 characters |
| `STORAGE_DRIVER` | `supabase` |
| `SUPABASE_URL` | `https://your-project-ref.supabase.co` |
| `SUPABASE_SERVICE_ROLE_KEY` | service-role secret |
| `SUPABASE_STORAGE_BUCKET` | `event-posters` |
| `RESEND_API_KEY` | Resend API key |

Generate `APP_SECRET` locally without saving it in the project:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Copy the printed value into Vercel only.

5. Deploy. The first build uses `Dockerfile.vercel` and can take several minutes.
6. Copy the `https://` URL Vercel gives you into `APP_URL`, with no trailing slash, and redeploy so password-reset links use that address.

If Vercel reports that the container runtime is unavailable on the selected plan, stop there and check that project's plan limits. This app needs that container runtime because it is a multi-page PHP site, not a Node app.

## 7. Create admin, staff, and venue accounts

Do this from the project folder in a new PowerShell window. It talks to Supabase for that window only. It does not rewrite your saved `.env`, and it does not change the local MySQL database.

First confirm PHP can speak Postgres:

```powershell
php -m | Select-String pdo_pgsql
```

If `pdo_pgsql` is missing, open `C:\xampp\php\php.ini`, remove the semicolon before `extension=pdo_pgsql` and `extension=pgsql`, save, and run the check again. This does not require restarting Apache.

Then set only the database variables for this window and run the existing account tool. Replace the host, user, and password with your Supabase session-pooler values. Do not put the password in the `php` command.

```powershell
$env:DB_DRIVER = 'pgsql'
$env:DB_HOST = 'aws-0-ap-southeast-1.pooler.supabase.com'
$env:DB_PORT = '5432'
$env:DB_NAME = 'postgres'
$env:DB_USER = 'postgres.your-project-ref'
$env:DB_PASS = 'paste-the-database-password-here'
$env:DB_SSLMODE = 'require'
php tools\create_user.php admin admin@example.com "Demo Administrator"
```

The tool asks for the account password without showing it. Repeat the `php` line with `staff` or `venue` if you need those roles. Customers should register on the website.

Close the PowerShell window when you are finished so the password is not kept in that session. Do not copy those `$env:` lines into `.env`.

## 8. Check the live site

- `https://your-project.vercel.app/` shows the homepage.
- `https://your-project.vercel.app/database-test.php` returns a not-found page. That test is disabled on Vercel.
- Register a customer, sign out, and sign in again.
- Sign in as admin, create an event, upload a poster, publish it, and confirm the poster opens from the public event page.
- Use forgot-password only after Resend's domain is verified. The link must start with your `APP_URL`.

## What this does not move

Existing local accounts, events, seats, and poster files stay in XAMPP. The Supabase project starts empty. Uploading a poster locally still saves it under `uploads/events/`. Uploading one on Vercel saves it in the private Supabase bucket.

To keep developing locally, leave `DB_DRIVER=mysql` in `.env` and do not set `SESSION_DRIVER=cookie` there.
