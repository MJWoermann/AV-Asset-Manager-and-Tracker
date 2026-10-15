# Deploy AV Asset Manager on Ubuntu 26.04 LTS (LEMP)

This guide installs Nginx, PHP 8.5-FPM, MariaDB, and the application on a dedicated host for LAN users.

## 1. System packages

```bash
sudo apt update
sudo apt install -y nginx mariadb-server composer git unzip curl \
  php8.5-fpm php8.5-cli php8.5-mysql php8.5-xml php8.5-mbstring \
  php8.5-curl php8.5-zip php8.5-gd php8.5-bcmath php8.5-intl

# Node 20 LTS (for building frontend assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. MariaDB (dedicated database)

```bash
sudo mysql -e "CREATE DATABASE av_assets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'av_app'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"
sudo mysql -e "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES ON av_assets.* TO 'av_app'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

Bind MariaDB to localhost only (`bind-address = 127.0.0.1` in `/etc/mysql/mariadb.conf.d/50-server.cnf`).

**Immutable audit (optional hardening):** after first migrate, revoke UPDATE/DELETE on `audit_logs` for `av_app` and grant only INSERT/SELECT:

```sql
REVOKE UPDATE, DELETE ON av_assets.audit_logs FROM 'av_app'@'localhost';
GRANT SELECT, INSERT ON av_assets.audit_logs TO 'av_app'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Application code

```bash
sudo mkdir -p /var/www/av-asset-manager
sudo chown $USER:www-data /var/www/av-asset-manager
cd /var/www/av-asset-manager
git clone <YOUR_REPO_URL> .
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="AV Asset Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_SERVER_IP_OR_HOSTNAME

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=av_assets
DB_USERNAME=av_app
DB_PASSWORD=STRONG_PASSWORD_HERE

SESSION_SECURE_COOKIE=false
FILESYSTEM_DISK=local
```

```bash
npm ci
npm run build
# Runs migrations (incl. users.preferences), seeds, and creates the first admin
php artisan app:install --email=admin@your.org --password='ChooseAStrongPassword'
# If the prior command fails use php artisan db:wipe --force to celar the data and try again
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

First-time install needs no extra environment variables for per-user asset table columns or the asset **History** tab. Column choices are stored in `users.preferences` after each user saves them in the UI; History reads the existing append-only `audit_logs` table.

### Optional: Google and Microsoft Entra SSO

OAuth client IDs and secrets belong in `.env` only (not the database). Provider toggles and allowed email domains are configured in the app under **Admin → SSO**.

Set `APP_URL` to your public site URL, add `GOOGLE_*` and/or `AZURE_*` variables (see `.env.example`), register matching callback URLs in Google Cloud and Entra, then follow the full checklist:

**[docs/sso-setup.md](sso-setup.md)** — IdP setup, `.env` mapping, admin whitelist, link-only users, and troubleshooting.

SSO signs in **existing** users only; password login remains available.

### Optional: Email integration (Gmail / Outlook Graph / SMTP)

Outbound notifications and inbound helpdesk reply polling are gated by the per-user developer feature `email_integration`. Secrets stay in `.env`; driver toggles and mailbox settings live under **Admin → Email**.

Full host-specific setup (Gmail app passwords, Entra Graph `Mail.Send`/`Mail.Read`, manual SMTP/IMAP): **[docs/email-setup.md](email-setup.md)**.

Ensure cron runs `php artisan schedule:run` every minute so `email:poll-inbound` can honour the configured poll interval. Use `queue:work` if you queue mail delivery.

## 4. Nginx site

Create `/etc/nginx/sites-available/av-asset-manager`:
```bash
cd /var/www/av-asset-manager/
sudo cp nginx.example /etc/nginx/sites-available/av-asset-manager
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name YOUR_SERVER_IP_OR_HOSTNAME;
    root /var/www/av-asset-manager/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/av-asset-manager /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl enable nginx php8.5-fpm mariadb
```

Optional TLS: use Certbot (`sudo apt install certbot python3-certbot-nginx`) if the host has a public DNS name.

## 5. PHP-FPM tuning (20 concurrent users)

Recommended host CPU/RAM/disk for ~10k assets and this concurrency target: [hardware-sizing.md](hardware-sizing.md).

In `/etc/php/8.5/fpm/pool.d/www.conf` consider:

```ini
pm = dynamic
pm.max_children = 40
pm.start_servers = 8
pm.min_spare_servers = 4
pm.max_spare_servers = 16
```

```bash
sudo systemctl restart php8.5-fpm
```

## 6. Firewall (LAN only example)

Minimal open ports for a first boot:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw enable
```

For production on corporate WiFi, prefer **source-restricted** rules (staff subnets for HTTP/HTTPS, admin/jump only for SSH) and keep MariaDB off the WiFi path. Limit host outbound internet to SSO (Google/Microsoft Entra OAuth) and email (SMTP and/or Microsoft Graph) when IT policy allows. Full patterns, `APP_URL`/split-DNS notes, and guest QR / Sanctum reachability: **[security-hardening.md — Corporate LAN / staff WiFi deployment](security-hardening.md#corporate-lan--staff-wifi-deployment)**.

## 7. Updates

Always run migrations after pulling code. Skipping `migrate` will break asset list pages once the app expects `users.preferences`.

One-shot update (recommended):

```bash
sudo bash /var/www/av-asset-manager/scripts/update.sh
```

That script pulls the latest code, installs PHP/JS deps, builds assets, runs `migrate --force`, rebuilds config/route/view caches, fixes `storage` / `bootstrap/cache` / SQLite permissions, and reloads PHP-FPM. Override defaults if needed:

```bash
sudo WEB_USER=www-data PHP_FPM_SERVICE=php8.5-fpm bash /var/www/av-asset-manager/scripts/update.sh
```

Manual equivalent:

```bash
cd /var/www/av-asset-manager
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data /var/www/av-asset-manager/database
sudo chmod 775 /var/www/av-asset-manager/database
sudo chmod 664 /var/www/av-asset-manager/database/database.sqlite
sudo systemctl reload php8.5-fpm
```

If you are experiencing errors with `php artisan`, reset ownership so your deploy user can write, then re-run the update script (it restores `www-data` on writable paths):

```bash
cd /var/www/av-asset-manager
sudo chown -R $USER:$USER .
chmod -R 755 .
sudo bash scripts/update.sh
```

### Schema notes (additive)

| Migration concern | Effect on existing installs |
|-------------------|-----------------------------|
| `users.preferences` (JSON, nullable) | Added by migrate. Null means default asset table columns. No data backfill. |
| Asset **History** tab | Uses existing `audit_logs` morph records. No new tables or seeders. |
| Frontend (Columns picker, History tab) | Served after `npm run build`. Clear view cache as above if Blade looks stale. |

Optional: after migrate, re-apply audit hardening from §2 if you recreate DB grants.

Do **not** grant `UPDATE`/`DELETE` on `audit_logs` to the app DB user.

## 8. Remote attachment storage (QNAP / SharePoint)

Attachments use Laravel disks. Set `ATTACHMENTS_DISK` to `local` (default), `qnap`, or `sharepoint`. Optional `BACKUP_DISK` (same allow-list) enables nightly `php artisan app:backup` via the scheduler and Admin → Storage.

Downloads always go through the authenticated app proxy (`/files/{id}/download`); do not expose raw NAS or Graph URLs to browsers.

### QNAP

Pick one mode with `QNAP_DRIVER`:

| Mode | When to use | Key env |
|------|-------------|---------|
| `mount` (default) | NFS/SMB mount on the LEMP host | `QNAP_MOUNT_PATH=/mnt/qnap/av-assets` |
| `sftp` | No OS mount; app writes over SFTP | `QNAP_SFTP_HOST`, user/password or key, `QNAP_SFTP_ROOT` |
| `s3` | QuObjects / S3-compatible | `QNAP_S3_ENDPOINT`, key/secret, bucket; path-style usually `true` |

Mount example (NFS):

```bash
sudo mkdir -p /mnt/qnap/av-assets
# add fstab entry for your NAS share, then:
sudo mount -a
sudo chown -R www-data:www-data /mnt/qnap/av-assets
sudo chmod 775 /mnt/qnap/av-assets
```

Set `QNAP_THROW=true` in production so storage failures surface clearly. After cutting over live uploads:

```bash
# Preview
php artisan attachments:migrate-disk local qnap --dry-run
# Copy + update files.disk (optionally --delete-source)
php artisan attachments:migrate-disk local qnap
```

Then set `ATTACHMENTS_DISK=qnap` and `php artisan config:cache`.

### SharePoint (Microsoft Graph, app-only)

1. Entra app registration → note tenant ID and client ID; create a client secret.
2. API permissions (application): `Files.ReadWrite.All` and/or `Sites.ReadWrite.All`, then **Grant admin consent**.
3. Resolve the document library drive ID (Graph Explorer: site → drives).
4. Env:

```bash
GRAPH_CLIENT_ID=...
GRAPH_CLIENT_SECRET=...
GRAPH_TENANT_ID=...
SHAREPOINT_DRIVE_ID=...
SHAREPOINT_PREFIX=AV-Asset-Manager/attachments
ATTACHMENTS_DISK=sharepoint
# or BACKUP_DISK=sharepoint for backups only
```

Optional `SHAREPOINT_ROOT_ITEM_ID` mounts a single folder as the disk root. Interactive user sign-in via Entra/Google is configured separately — see [sso-setup.md](sso-setup.md). Storage/backup here uses app-only client credentials only.

### Backups and restore

```bash
# Manual / scheduled when BACKUP_DISK is set
php artisan app:backup --disk=qnap --keep=7

# Restore SQL + attachment blobs onto ATTACHMENTS_DISK (destructive)
php artisan app:restore backups/2026-10-14_021500 --disk=qnap --force
```

Backup archives exclude `.env` and Graph/QNAP secrets. SQL dumps include user password hashes — treat restore media as sensitive. Ensure `queue:work` and `schedule:run` (cron) are running for queued Admin → Storage actions, the nightly backup, and optional email inbound polling (`email:poll-inbound` — see [email-setup.md](email-setup.md)).

## Security checklist

Full evaluation, threat model, prioritized application hardening, and **corporate LAN / staff WiFi networking**: **[docs/security-hardening.md](security-hardening.md)**.

- [ ] `APP_DEBUG=false`
- [ ] `ALLOW_REGISTRATION=false` (default; create users via Admin → Users or `app:install`)
- [ ] Optional: `STOCK_GUEST_ENABLED=false` if guest Kanban QR should be off
- [ ] Confirm every user has an app role (`admin`, `inventory_manager`, or `scanner`)
- [ ] Strong DB password; MariaDB on localhost only
- [ ] Dedicated DB user (not root)
- [ ] Firewall: HTTP(S) from staff WiFi/LAN subnets only; SSH from admin/jump only; no MariaDB on WiFi
- [ ] `APP_URL` matches the hostname staff resolve on corporate WiFi (PWA, API, SSO callbacks, guest QR)
- [ ] Outbound from the host limited to SSO + email where policy allows (see security-hardening corporate LAN section)
- [ ] `storage` and `bootstrap/cache` writable by `www-data` only as needed
- [ ] Web root is `public/` only
- [ ] Uploads limited to 25MB in Nginx and PHP (`upload_max_filesize`, `post_max_size`)
- [ ] Admin password rotated after first login
- [ ] Audit logs treated as append-only
- [ ] Updates always run `php artisan migrate --force` before serving traffic
- [ ] QNAP mount owned by `www-data` (or SFTP/S3 credentials scoped to the attachment path)
- [ ] SharePoint Graph app has admin consent; secrets only in `.env` / secret store
- [ ] `BACKUP_DISK` private to ops; restore media handled as confidential
