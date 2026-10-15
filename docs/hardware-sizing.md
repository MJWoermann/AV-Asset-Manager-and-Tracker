# Hardware sizing (server / VM)

Guidance for a dedicated LEMP host (Nginx, PHP-FPM, MariaDB, and the application) sized for about **10,000 assets** and **~20 concurrent users**. Install steps: [deploy-ubuntu-nginx.md](deploy-ubuntu-nginx.md).

**10,000 assets is not the hard limit.** Indexed scan lookups and paginated asset/list indexes work well at this size. Peak load comes from concurrent scanners, full-list admin operations (compare, export, import, PDF), attachment volume, and backup retention—not from asset row count alone.

## Assumptions

- Single dedicated host with MariaDB on the same machine (typical LAN deploy).
- Concurrent interactive users ≈ 20 (matches PHP-FPM pool tuning in the deploy guide: `pm.max_children = 40`).
- Workload: stocktake scanning, asset CRUD, list compare/export, occasional full Excel import, nightly backup with `queue:work` and `schedule:run`.
- Attachments may live on the host or on QNAP/SharePoint; backups often keep about seven copies when `BACKUP_DISK` is set.

## Recommended specifications

### Recommended (production LAN VM)

| Resource | Spec |
|----------|------|
| vCPU | 4 |
| RAM | 16 GB |
| System disk | 80–100 GB SSD (OS, app, logs, MariaDB) |
| Attachments | 100–250 GB local or NAS |
| Backup volume | ≈ 7 × (DB dump + attachment set) when keep=7 |
| Network | Gigabit LAN |

### Minimum viable (small team, light attachments)

| Resource | Spec |
|----------|------|
| vCPU | 2 |
| RAM | 8 GB |
| System SSD | 60 GB |
| Attachments | 50+ GB or offload to QNAP |

Acceptable if concurrent users stay well under ~20, event lists are split (not one 10k-item scan page), and heavy import/compare/backup run off-peak.

### Comfortable / growth headroom

| Resource | Spec |
|----------|------|
| vCPU | 8 |
| RAM | 32 GB |
| System SSD | 100+ GB |
| Attachments + backups | Sized to manuals × retention (often NAS) |

Use for frequent full-warehouse compares/exports, larger scanner spikes, or large local attachment libraries.

## Soft configuration targets

Pair hardware with the deploy guide’s PHP-FPM settings:

- PHP-FPM: `pm.max_children = 40` for the ~20-user baseline; ensure host RAM covers active workers, MariaDB, and ~2 GB OS slack.
- PHP `memory_limit`: **256M** baseline; **512M** if full-list compare/PDF or large imports are routine.
- MariaDB `innodb_buffer_pool_size`: about **2–4 GB** on a 16 GB host (leave room for FPM).
- Keep `queue:work` and `schedule:run` so backups and storage jobs do not rely only on interactive FPM workers.
- Prefer remote attachment/backup disk (QNAP) so the VM disk stays mostly OS and database.

Core database size for ~10k assets is typically well under 1–2 GB unless `audit_logs` grows very large over years. Disk capacity is driven by manuals (max 25 MB per file) and backup retention.

## Operational caveats

- Prefer multiple event lists (or smaller stocktake batches) so the scan UI does not render ~10k rows on every refresh.
- Run large Excel import, comparison PDF, and disk migration off-peak; they monopolize a worker and can spike RAM.
- Size backup disk from attachment library × retention, not from “10,000 assets.”
- Monitor `audit_logs` growth over years (immutable; can dominate DB size long-term).

## Summary

For a typical warehouse/LAN deploy supporting **~10,000 assets and ~20 concurrent users**: **4 vCPU, 16 GB RAM, ~100 GB SSD** system disk, plus **100–250 GB** (or NAS) for attachments and a backup volume sized for about seven copies of that dataset.
