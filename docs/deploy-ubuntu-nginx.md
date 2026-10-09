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
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

First-time install needs no extra environment variables for per-user asset table columns or the asset **History** tab. Column choices are stored in `users.preferences` after each user saves them in the UI; History reads the existing append-only `audit_logs` table.

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

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw enable
```

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

## Security checklist

- [ ] `APP_DEBUG=false`
- [ ] Strong DB password; MariaDB on localhost only
- [ ] Dedicated DB user (not root)
- [ ] `storage` and `bootstrap/cache` writable by `www-data` only as needed
- [ ] Web root is `public/` only
- [ ] Uploads limited to 25MB in Nginx and PHP (`upload_max_filesize`, `post_max_size`)
- [ ] Admin password rotated after first login
- [ ] Audit logs treated as append-only
- [ ] Updates always run `php artisan migrate --force` before serving traffic
