# Run Lockedin locally (Windows + XAMPP)

This guide starts the current Lockedin project on a friend's Windows computer. It uses XAMPP, Apache, PHP, and MySQL/MariaDB. There is no Composer, Node.js, or `npm install` step.

## What they need

1. [XAMPP for Windows](https://www.apachefriends.org/) with Apache, MySQL, PHP, and phpMyAdmin.
2. A copy of this project folder, named exactly `Lockedin`.
3. A browser.

The installed PHP must have the `PDO` and `pdo_mysql` extensions enabled. They are normally included with XAMPP.

## 1. Put the project in XAMPP's web folder

1. Install XAMPP if it is not already installed.
2. Copy the full project folder to `C:\xampp\htdocs\Lockedin`.
3. Confirm that this file exists:

   ```text
   C:\xampp\htdocs\Lockedin\public\index.php
   ```

Do not copy only the `public` folder: PHP also needs the adjacent `app` and `config` folders.

## 2. Start Apache and MySQL

1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.
4. Check that both entries turn green and show a running status.

If either service will not start, its usual ports may already be in use. Stop the conflicting service or change the port in XAMPP, then use the matching port in the URL/configuration below.

## 3. Create and import the database

1. In the XAMPP Control Panel, click **Admin** on the MySQL row. This opens phpMyAdmin.
2. Choose the **Import** tab.
3. Select this project file:

   ```text
   C:\xampp\htdocs\Lockedin\database_clean.sql
   ```

4. Leave the format as **SQL** and click **Import** / **Go**.
5. Verify that a database named `workspace_db` now appears in the left sidebar.

Use `database_clean.sql`, not `database.sql`: the latter currently includes Markdown code-fence lines and phpMyAdmin will reject it as SQL.

The import creates the tables, but it intentionally does not add sample rooms or an administrator account. A new account created through the website is a student account.

## 4. Set the database password in the app

Open `C:\xampp\htdocs\Lockedin\config\database.php` in a text editor. Set `$dbPass` to the password for that computer's MySQL `root` account.

```php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'workspace_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: 'YOUR_MYSQL_ROOT_PASSWORD';
```

For a default XAMPP installation, MySQL's `root` password is often blank. In that case use:

```php
$dbPass = '';
```

If MySQL uses a port other than `3306`, change `$dbPort` as well. Keep these settings local; do not commit a real password to Git.

## 5. Open the app

Visit this exact URL in a browser:

```text
http://localhost/Lockedin/public/
```

You should reach the Lockedin landing page. Choose **Create account** to make a student account, then sign in.

The project detects this subfolder URL automatically. The public web root is `public/`; do not browse directly to `app/` or `config/`.

## Optional: use a cleaner local URL

You can configure an Apache VirtualHost whose `DocumentRoot` is:

```text
C:/xampp/htdocs/Lockedin/public
```

For example, map a local hostname such as `lockedin.local` to that VirtualHost, restart Apache, then use `http://lockedin.local/`. This is optional; the `localhost/Lockedin/public/` URL above is the simplest setup.

## Quick checks

- Apache and MySQL are running in XAMPP.
- `workspace_db` appears in phpMyAdmin and contains a `users` table.
- `config/database.php` has the correct MySQL password and port.
- `http://localhost/Lockedin/public/` opens without a 503 error.
- You can register a student account and log in.

## Troubleshooting

| What you see | What to check |
| --- | --- |
| `The application is temporarily unavailable` / HTTP 503 | MySQL is running; `workspace_db` was imported; the username, password, host, and port in `config/database.php` are correct; PHP has `pdo_mysql` enabled. |
| `404 Not Found` | Confirm the project is at `C:\xampp\htdocs\Lockedin` and use `/Lockedin/public/` in the URL. |
| phpMyAdmin reports an SQL syntax error at the start of the file | Import `database_clean.sql`, not `database.sql`. |
| Apache or MySQL does not start | Read the XAMPP Control Panel logs; another program may already be using ports 80, 443, or 3306. |
| Styles or images are missing | Start from the landing-page URL above rather than opening a PHP file from the filesystem. |

## Copyable prompt for an AI helper

Paste the following into an AI chat if you get stuck. Replace bracketed details, and do not paste database passwords, private keys, or personal data.

```text
I am setting up the Lockedin PHP/MySQL project locally on Windows with XAMPP. The project is located at:
C:\xampp\htdocs\Lockedin

The intended setup is:
- Start Apache and MySQL in XAMPP.
- Import C:\xampp\htdocs\Lockedin\database_clean.sql into phpMyAdmin.
- The database name should be workspace_db.
- Open http://localhost/Lockedin/public/.
- Configure the local MySQL credentials only in config\database.php (I will not share the password).

I am stuck at this step: [describe the step].
The exact error message is: [paste the full error].
My XAMPP Apache/MySQL status is: [running/not running and any port shown].
My PHP version is: [version, if known].

Please diagnose this one step at a time. First explain the likely cause, then give the smallest safe fix. Do not ask me to delete databases, reinstall XAMPP, expose app/config outside the project, or change unrelated project files unless you explain why it is necessary. Do not request passwords or secrets.
```
