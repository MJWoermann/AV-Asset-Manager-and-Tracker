# Troubleshooting

Symptom → checks → fix. Deeper setup lives in the linked guides; this page is the searchable index.

## Auth / login

| Symptom | Checks | Fix |
|---------|--------|-----|
| Cannot sign in with password | Caps lock; account exists; role assigned | Admin creates/resets user under Manage users. Password must meet complexity rules. |
| SSO button does nothing / errors | Provider enabled in Admin → SSO; redirect URI matches; client secrets in `.env` only | See [sso-setup.md](sso-setup.md). SSO signs in **existing** users only — admin must create the account first. |
| 403 after login | Role permissions; developer feature on the role or, for admins, on Profile | The role is missing the permission for that page. Admin hubs need `admin:read`, `admin:write`, or `admin:settings`. |
| Locked out / too many attempts | Throttle on login/reset | Wait, then retry; admin can reset password. |

## Scan / camera

| Symptom | Checks | Fix |
|---------|--------|-----|
| Camera won’t start on phone/tablet | URL is **HTTPS**; cert trusted on the device | Browser camera APIs require a secure context. On LAN, use a private CA or self-signed cert: [self-signed-tls.md](self-signed-tls.md). |
| Camera fails on PC via `http://LAN-IP` but works on `localhost` | Address bar host | Same secure-context rule: `http://localhost` / `http://127.0.0.1` is allowed; plain `http://192.168.x.x` is not. Use the Scan page “Open via localhost” / “Open over HTTPS” links, or enable HTTPS ([self-signed-tls.md](self-signed-tls.md)). Do not rely on browser “insecure origins treated as secure” flags in production. |
| Camera works on desktop but not phone | Mixed content; HTTP bookmark | Open the HTTPS origin; reinstall the PWA shortcut if needed. |
| Barcode not found | Identifier fields (`fmi_ast`, `tp_barcode`, `rig_tag`, `device_sn`, etc.) | Check Unknown scans report; create or link the asset. |

## Uploads / import

| Symptom | Checks | Fix |
|---------|--------|-----|
| Upload rejected | File type and size | Allow-list: xlsx/csv/jpg/png/pdf/docx; max 25MB. |
| Import mapping looks wrong | Previous remembered mappings; header spelling | Override suggestions on the map step; successful mappings are remembered per user. |
| Old item type / manufacturer name fails | Entity was renamed or merged | Delete/merge records an alias so old names still resolve — see operator notes in [features-and-usage.md](features-and-usage.md). |

## Storage / backup

| Symptom | Checks | Fix |
|---------|--------|-----|
| Admin → Storage test or backup: `Unable to create a directory at /mnt/qnap/av-assets/backups` (job log **Failed**) | `findmnt -T /mnt/qnap/av-assets` shows `cifs` and `uid=0`. `stat -c '%a %U %G'` on that path shows `755 root root`. `sudo -u www-data touch /mnt/qnap/av-assets/.write-test` returns `Permission denied`. | The SMB/CIFS mount is owned by root on the client. `chown`, `chmod`, and `mount -o remount` do not grant `www-data` write access. Unmount and mount again with `uid`/`gid` of `www-data` (usually 33), `forceuid,forcegid`, `file_mode=0664`, and `dir_mode=0775`, saved in `/etc/fstab` with a root-only credentials file. Steps: [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) § SMB / CIFS. |
| **Backup now** stays **Queued** for a long time. The NAS only has `backups/_write-test.txt`. | From the app directory, `pgrep -af "artisan queue:work"` prints nothing. `systemctl status av-asset-manager-queue` reports not found or inactive. | **Backup now** only inserts a database job. The location test writes its file in the web request and does not prove a worker is running. Install and start the worker below. A `queue:work` started in an SSH session stops when you disconnect. |
| Job count command says `Could not open input file: artisan` | The shell prompt is `~$`, not the app directory. | `cd /var/www/av-asset-manager`, then run artisan again. |
| Tinker prints `Writing to directory /var/www/.config/psysh is not allowed` and no job count | `www-data` cannot write `/var/www/.config`. | Count jobs with a writable home: `sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan tinker --execute 'echo DB::table("jobs")->count(), PHP_EOL;'`. A number above zero is a backup still waiting. For a restore check, run tinker as the deploy user instead: `php artisan tinker --execute="echo json_encode(['users'=>DB::table('users')->count(),'max_created'=>DB::table('assets')->max('created_at')]);"`. |
| `app:restore` prints `Restore complete` but assets created after the backup are still in the app | The command was an older build that sent the whole dump through one database call, or it never printed `Restore complete`. | Deploy the current app. Restore replaces each table. It does not merge. `max(created_at)` on `assets` must be at or before the dump time. See [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) § Backups and restore. |
| `mysql restore failed` … `ERROR 1044` … `LOCK TABLES` | The client started, then MariaDB rejected `LOCK TABLES` for the application database user. The named table may already be empty. | Deploy the current app and run the restore again as `www-data`. Current restores strip `LOCK TABLES` / `UNLOCK TABLES`. Re-running loads that table from the backup. |
| `app:restore` prints `Unable to create a directory at …/storage/app/private/attachments/…` | The shell user is not `www-data`. `ls -ld storage/app/private` shows `www-data`. | The SQL portion has already been applied. Re-run `sudo -u www-data php artisan app:restore <backup-path> --disk=qnap --force`. Success includes `attachments restored=N`. |

Install the worker once on an existing host. Stop any `queue:work` still running in an SSH session first, or two workers will run. Full unit notes: [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md#7-scheduler-and-queue-worker).

```bash
sudo tee /etc/systemd/system/av-asset-manager-queue.service >/dev/null <<'EOF'
[Unit]
Description=AV Asset Manager queue worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
WorkingDirectory=/var/www/av-asset-manager
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --timeout=3600

[Install]
WantedBy=multi-user.target
EOF
sudo systemctl daemon-reload
sudo systemctl enable --now av-asset-manager-queue
systemctl status av-asset-manager-queue --no-pager
pgrep -af "artisan queue:work"
```

If `/usr/bin/php` does not exist, set `ExecStart` to the path from `command -v php`. After the service is up, refresh Admin → Storage. The row should leave **Queued**. `scripts/update.sh` restarts this unit on later deploys when the unit file is installed.

The nightly backup does not use this worker. It needs the `www-data` cron entry in the next section. With `BACKUP_DISK` set, `schedule:run` runs `app:backup` at 02:15 UTC.

## Mail / queue / cron

There is no separate email worker. Inbound polling (`email:poll-inbound`) and daily digests (`email:send-digests`) run from cron via `schedule:run`, then exit. Outgoing mail is sent in that same process. `av-asset-manager-queue.service` is for **Backup now** and attachment migration, not for polling the mailbox.

| Symptom | Checks | Fix |
|---------|--------|-----|
| Test email fails | Admin → Email settings; `.env` secrets; outbound network | [email-setup.md](email-setup.md). |
| Digests never arrive, inbound replies are not logged, or the nightly backup never appears | `sudo crontab -u www-data -l` prints `no crontab for www-data`. Also check `sudo crontab -l` so a root entry is not missed. | Install the scheduler below. `pgrep` will usually show nothing, because `schedule:run` is not a resident process. |
| `sudo -u www-data php artisan schedule:list` has no `email:poll-inbound` | Email integration is off, or credentials for that driver are missing | Enable integration under Admin → Email. The poll task is registered only when integration is enabled. Digests use the same cron entry. |
| Notifications were expected from `queue:work` | Mail is not a queue worker in this app | Fix cron. Use the queue service only for storage jobs that stay **Queued**. |
| A queued storage job stays **Queued** or **Running** with a live worker | `sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan tinker --execute 'echo DB::table("jobs")->count(), PHP_EOL;'` and `failed_jobs` | Restart the worker after a deploy (`sudo systemctl restart av-asset-manager-queue`). Inspect `failed_jobs`, fix the error, then retry. `DB_QUEUE_RETRY_AFTER` must stay above the worker timeout (3900 and `--timeout=3600`). |

Install the scheduler for `www-data` when that user’s crontab is empty:

```bash
echo '* * * * * cd /var/www/av-asset-manager && php artisan schedule:run >> /dev/null 2>&1' | sudo crontab -u www-data -
sudo crontab -u www-data -l
cd /var/www/av-asset-manager
sudo -u www-data php artisan schedule:list
```

`crontab -l` should print that one line. `schedule:list` should include `email:poll-inbound` (when email integration is on), `email:send-digests`, and `app:backup` (when `BACKUP_DISK` is set). Do not put `queue:work` in cron. A new process every minute overlaps the systemd worker.

## Assets / UI

| Symptom | Checks | Fix |
|---------|--------|-----|
| Columns / dashboard modules reset | Signed-in user; preferences saved | Layout is per-user (`users.preferences`). Save Columns / Save modules again. |
| Page jumps after Save | Same URL after submit | Scroll restore only applies when pathname+query match; landing on a new show page jumps to top by design. |
| “Unable to locate file in Vite manifest” | Frontend not built | Run `npm run build` (or `npm run dev` locally). |

## Common env misconfigs

| Symptom | Checks | Fix |
|---------|--------|-----|
| Blank page / 500 after deploy | `APP_KEY`; `APP_DEBUG=false` in prod; storage permissions | See deploy guide § permissions and `.env`. |
| Assets 404 (CSS/JS) | `public/build` present; Nginx root points at `public` | Rebuild assets; fix web root. |
| Database connection refused | `DB_*` host/socket; MariaDB listening on localhost | Match `.env` to the install in [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md). |

## Related guides

- [features-and-usage.md](features-and-usage.md) — day-to-day operator workflows
- [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md) — production install and updates
- [security-hardening.md](security-hardening.md) — hardening checklist
- [ui-interaction-guidelines.md](ui-interaction-guidelines.md) — UI / habits playbook for developers and agents
