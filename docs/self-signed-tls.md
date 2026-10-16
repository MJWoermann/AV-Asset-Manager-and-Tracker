# Self-signed TLS for LAN / mobile scanning

Mobile browsers treat the camera (`getUserMedia`), barcode scanning, and PWA install as **secure-context** features. On phones and tablets those APIs work on **`https://…`** (or `http://localhost` / `http://127.0.0.1`, which is useless for a LAN server). Plain `http://YOUR_SERVER_IP` over WiFi will typically fail with camera permission / “secure connection required” errors even when the Scan page loads.

For a self-hosted host **without** a public DNS name, enable HTTPS with a **private CA + server certificate** (recommended) or a leaf-only self-signed cert, then **install and trust that CA on every phone and tablet** that will scan.

Prefer, when available:

1. Corporate internal CA / MDM-pushed cert (best for fleets)
2. Public hostname + Let’s Encrypt / Certbot
3. This guide (private CA or self-signed on the LAN)

---

## What you will end up with

| Piece | Purpose |
|-------|---------|
| Private CA (or self-signed leaf) | Signs (or is) the server certificate |
| Server cert + key on the LEMP host | Nginx terminates TLS on port `443` |
| `APP_URL=https://…` and `SESSION_SECURE_COOKIE=true` | Laravel cookies and generated URLs match HTTPS |
| CA installed + trusted on each mobile device | Safari / Chrome stop warning and allow camera / PWA |

Use a **stable hostname** (e.g. `av-assets.corp.local` or `av-assets.lan`) in the certificate and in `APP_URL`. Raw IPs work technically but are awkward for SAN renewal and device trust UX. Put the same name in DNS, split-horizon DNS, or `/etc/hosts` / phone DNS so staff resolve one URL.

---

## 1. Generate a private CA and server certificate (recommended)

Run on the Ubuntu LEMP host (or a secure admin machine, then copy the files). Replace `av-assets.corp.local` with your hostname.

```bash
sudo mkdir -p /etc/ssl/av-asset-manager
sudo chmod 750 /etc/ssl/av-asset-manager
cd /etc/ssl/av-asset-manager

# --- Private CA (10 years) ---
sudo openssl genrsa -out ca.key 4096
sudo openssl req -x509 -new -nodes -key ca.key -sha256 -days 3650 \
  -out ca.crt \
  -subj "/CN=AV Asset Manager LAN CA"

# --- Server key + CSR ---
sudo openssl genrsa -out av-assets.key 2048

cat <<'EOF' | sudo tee av-assets.ext
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names

[alt_names]
DNS.1 = av-assets.corp.local
# Optional extras — uncomment / adjust:
# DNS.2 = av-assets.lan
# IP.1 = 192.168.10.50
EOF

sudo openssl req -new -key av-assets.key -out av-assets.csr \
  -subj "/CN=av-assets.corp.local"

sudo openssl x509 -req -in av-assets.csr -CA ca.crt -CAkey ca.key \
  -CAcreateserial -out av-assets.crt -days 825 -sha256 \
  -extfile av-assets.ext

sudo chmod 640 av-assets.key ca.key
sudo chown root:www-data av-assets.key
```

Serve only the **leaf** to browsers (`av-assets.crt`). Distribute **`ca.crt`** to staff devices for trust (never distribute `ca.key` or `av-assets.key`).

**Leaf-only shortcut (not preferred):** a single `openssl req -x509` cert without a separate CA works for a quick test, but phones must trust that exact leaf; rotating it means re-installing on every device. A private CA is the durable approach.

### Optional: mkcert

On a workstation with [mkcert](https://github.com/FiloSottile/mkcert), `mkcert -install` then `mkcert av-assets.corp.local` produces a locally trusted CA + cert. Copy the cert/key to the server under `/etc/ssl/av-asset-manager/` and distribute mkcert’s root CA the same way as `ca.crt` below. mkcert is convenient for admins; fleet phones still need the CA installed manually or via MDM.

---

## 2. Nginx HTTPS site

Create or replace `/etc/nginx/sites-available/av-asset-manager` so HTTP redirects to HTTPS and TLS uses the files from §1. You can start from the repo file `nginx-ssl.example`:

```bash
sudo cp /var/www/av-asset-manager/nginx-ssl.example /etc/nginx/sites-available/av-asset-manager
# edit server_name and ssl_certificate / ssl_certificate_key paths
```

Example contents:

```nginx
# Redirect HTTP → HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name av-assets.corp.local;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name av-assets.corp.local;
    root /var/www/av-asset-manager/public;

    ssl_certificate     /etc/ssl/av-asset-manager/av-assets.crt;
    ssl_certificate_key /etc/ssl/av-asset-manager/av-assets.key;
    # Optional: present CA chain for clients that want it
    # ssl_trusted_certificate /etc/ssl/av-asset-manager/ca.crt;

    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    # HSTS: enable only after every client uses HTTPS and the cert is trusted.
    # add_header Strict-Transport-Security "max-age=31536000" always;

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
sudo ln -sf /etc/nginx/sites-available/av-asset-manager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Open HTTPS in the firewall (adjust source CIDRs as in [security-hardening.md](security-hardening.md)):

```bash
sudo ufw allow 443/tcp
# Prefer source-restricted rules in production, e.g.:
# sudo ufw allow from STAFF_WIFI_SUBNET_CIDR to any port 443 proto tcp
sudo ufw reload
```

---

## 3. Laravel / app configuration

In `/var/www/av-asset-manager/.env`:

```env
APP_URL=https://av-assets.corp.local
SESSION_SECURE_COOKIE=true
```

Then:

```bash
cd /var/www/av-asset-manager
php artisan config:cache
# or: sudo bash scripts/update.sh
```

If you use SSO, update Google / Entra redirect URIs to the `https://` callbacks — see [sso-setup.md](sso-setup.md).

Staff must open the app at **exactly** that URL (same host and scheme as `APP_URL`). Mixing `http://`, IP, and hostname breaks sessions, PWA, and OAuth.

---

## 4. Trust the CA on mobile devices (required)

Until the device trusts your CA, iOS/Android will show certificate warnings and **camera scanning will not work reliably**. Install `ca.crt` on each scanner phone/tablet (or push via MDM).

Copy `ca.crt` off the server for staff (USB, secure file share, or a short-lived internal download). Do **not** email `ca.key`.

### iPhone / iPad (Safari)

1. Transfer `ca.crt` to the device (AirDrop, Files, or open a link to the file in Safari).
2. Open the profile prompt → **Allow** → Settings → **Profile Downloaded** → **Install** (enter passcode).
3. Settings → **General → About → Certificate Trust Settings**.
4. Enable **Full Trust** for **AV Asset Manager LAN CA** (or whatever CN you used).
5. Open `https://av-assets.corp.local` in Safari — the lock should show without a persistent warning.
6. Grant camera permission when the Scan page asks; use **Add to Home Screen** if you want the PWA shell.

Without step 3–4 (Full Trust), Safari may still treat the site as untrusted even after the profile is installed.

### Android (Chrome)

Steps vary slightly by OEM and Android version:

1. Transfer `ca.crt` to the phone.
2. Settings → **Security** (or **Encryption & credentials**) → **Install a certificate** → **CA certificate** → install `ca.crt`.
3. Confirm Chrome opens `https://av-assets.corp.local` without a warning interstitial you have to bypass every time.
4. Open Scan and allow camera access.

Notes:

- Prefer installing as a **CA certificate**, not a “VPN and app user credential” only, so Chrome trusts the site.
- Some managed devices block user CAs — use MDM / corporate CA instead.
- After installing, fully close Chrome (or clear the site’s security interstitial) and reopen the HTTPS URL.

### Desktop browsers (admin machines)

- **Windows:** double-click `ca.crt` → Install Certificate → **Local Machine** → **Trusted Root Certification Authorities**.
- **macOS:** open `ca.crt` in Keychain Access → set trust to **Always Trust** for SSL.
- **Linux (Ubuntu desktop):** copy to `/usr/local/share/ca-certificates/av-asset-manager-ca.crt` and run `sudo update-ca-certificates`.

---

## 5. Verification checklist

On a phone that has the CA trusted:

- [ ] `https://av-assets.corp.local` loads with no certificate warning
- [ ] Sign-in works; session cookie is set (stay logged in across pages)
- [ ] **Scan → Start camera** prompts for camera and shows a live preview
- [ ] PWA / “Add to Home Screen” (optional) opens the same HTTPS origin
- [ ] From a laptop: `curl -vI --cacert /path/to/ca.crt https://av-assets.corp.local/up` returns success

Server-side smoke test without trusting system-wide:

```bash
curl -vI --cacert /etc/ssl/av-asset-manager/ca.crt https://av-assets.corp.local/up
sudo nginx -t
```

---

## 6. Renewal and rotation

| Material | Typical lifetime in this guide | Action when expired |
|----------|--------------------------------|---------------------|
| `ca.crt` / `ca.key` | ~10 years | Re-issue CA, redistribute to **all** devices, re-sign server cert |
| `av-assets.crt` | ~825 days (~27 months) | Re-sign with existing CA (§1 CSR + `openssl x509 -req`), reload Nginx; devices that trust the CA need **no** change |

```bash
# Re-issue leaf only (CA unchanged)
cd /etc/ssl/av-asset-manager
sudo openssl x509 -req -in av-assets.csr -CA ca.crt -CAkey ca.key \
  -CAcreateserial -out av-assets.crt -days 825 -sha256 \
  -extfile av-assets.ext
sudo nginx -t && sudo systemctl reload nginx
```

Calendar a reminder before the leaf expires. Browsers fail hard on expired certs — scanning will stop until you renew.

---

## 7. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Camera button does nothing / “secure context” / permission denied on HTTP | Site not HTTPS | Complete §§1–3; open `https://` URL only |
| Continual cert warning on iOS | Profile installed but Full Trust off | Certificate Trust Settings → enable Full Trust |
| Works on laptop, not on phone | Phone DNS / hosts differ; wrong host in SAN | Ensure phone resolves the same hostname; SAN includes that DNS/IP |
| Logged out immediately / cookies missing | `SESSION_SECURE_COOKIE` or mixed HTTP/HTTPS | `SESSION_SECURE_COOKIE=true`, `APP_URL=https://…`, `config:cache`; no HTTP bookmarks |
| `NET::ERR_CERT_AUTHORITY_INVALID` on Android | CA not installed or wrong file | Install **`ca.crt`**, not the leaf alone |
| Nginx `SSL_CTX_use_certificate` errors | Wrong paths / permissions | Paths match §2; key readable by Nginx (`root:www-data`, mode `640`) |
| SSO redirect mismatch after TLS | IdP still has `http://` callbacks | Update Google/Entra redirect URIs to `https://` |

HTTP-only LAN access remains a documented security risk and **does not meet the mobile scanning requirement**. See [security-hardening.md](security-hardening.md) for broader TLS and WiFi hardening.

---

## Related docs

- [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) — full LEMP install; enable TLS as part of go-live
- [security-hardening.md](security-hardening.md) — corporate WiFi firewall, `APP_URL`, HSTS
- [sso-setup.md](sso-setup.md) — HTTPS callback URLs after cutting over
