import './bootstrap';
import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;

function searchTokens(term) {
    return String(term || '')
        .trim()
        .split(/\s+/u)
        .filter(Boolean);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function highlightSearchTerm(text, term) {
    const escaped = escapeHtml(text);
    const tokens = searchTokens(term);

    if (!tokens.length) {
        return escaped;
    }

    const pattern = new RegExp(
        `(${tokens.map((token) => token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`,
        'giu'
    );

    return escaped.replace(
        pattern,
        '<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">$1</mark>'
    );
}

function matchScore(label, term) {
    const value = String(label ?? '').toLowerCase();
    const full = String(term ?? '').trim().toLowerCase();
    const tokens = searchTokens(term).map((token) => token.toLowerCase());

    if (!full) {
        return 0;
    }

    if (!tokens.every((token) => value.includes(token))) {
        return Number.POSITIVE_INFINITY;
    }

    if (value === full) {
        return 0;
    }

    if (value.startsWith(full)) {
        return 1;
    }

    if (value.includes(full)) {
        return 2;
    }

    return 3;
}

Alpine.data('searchableSelect', (options, selected = '', config = {}) => ({
    open: false,
    query: '',
    value: selected === null || selected === undefined ? '' : String(selected),
    options: (options || []).map((o) => ({
        value: String(o.value),
        label: String(o.label ?? ''),
    })),
    placeholder: config.placeholder ?? '—',
    nullable: Boolean(config.nullable),
    highlight: -1,

    get selectedLabel() {
        const opt = this.options.find((o) => o.value === this.value);
        return opt ? opt.label : this.placeholder;
    },

    get filtered() {
        const q = this.query.trim();
        if (!q) {
            return this.options;
        }

        return this.options
            .map((option) => ({ option, score: matchScore(option.label, q) }))
            .filter((entry) => entry.score !== Number.POSITIVE_INFINITY)
            .sort((a, b) => a.score - b.score || a.option.label.localeCompare(b.option.label))
            .map((entry) => entry.option);
    },

    highlightedLabel(label) {
        return highlightSearchTerm(label, this.query);
    },

    openList() {
        this.open = true;
        this.highlight = -1;
        this.$nextTick(() => this.$refs.search?.focus());
    },

    closeList() {
        this.open = false;
        this.query = '';
        this.highlight = -1;
    },

    select(option) {
        this.value = option.value;
        this.closeList();
    },

    clear() {
        if (!this.nullable) {
            return;
        }
        this.value = '';
        this.closeList();
    },

    onKeydown(event) {
        if (!this.open) {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.openList();
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            this.closeList();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.highlight = Math.min(this.highlight + 1, this.filtered.length - 1);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.highlight = Math.max(this.highlight - 1, 0);
            return;
        }

        if (event.key === 'Enter' && this.highlight >= 0 && this.filtered[this.highlight]) {
            event.preventDefault();
            this.select(this.filtered[this.highlight]);
        }
    },
}));

Alpine.data('comparisonSearch', (sections = []) => ({
    query: '',
    sections,

    tokens() {
        return searchTokens(this.query);
    },

    rowMatches(haystack) {
        const tokens = this.tokens();
        if (!tokens.length) {
            return true;
        }

        const value = String(haystack ?? '').toLowerCase();

        return tokens.every((token) => value.includes(token.toLowerCase()));
    },

    visibleCount(section) {
        return (section.rows || []).filter((row) => this.rowMatches(row.haystack)).length;
    },

    highlight(text) {
        return highlightSearchTerm(text, this.query);
    },
}));

Alpine.data('importMapper', (columns, initialSelections = {}, requiredFields = []) => ({
    selections: Object.fromEntries(columns.map((c) => [c, initialSelections[c] ?? ''])),
    requiredFields: requiredFields,
    missingRequirements: [],
    duplicateWarning: '',

    init() {
        this.refreshValidation();
    },

    get canSubmit() {
        return this.missingRequirements.length === 0 && !this.duplicateWarning;
    },

    isTaken(field, currentColumn) {
        if (!field || field === 'create_new_field') {
            return false;
        }

        return Object.entries(this.selections).some(
            ([col, value]) => col !== currentColumn && value === field
        );
    },

    onChange(column) {
        const value = this.selections[column];
        if (value && value !== 'create_new_field' && this.isTaken(value, column)) {
            this.selections[column] = '';
        }
        this.refreshValidation();
    },

    refreshValidation() {
        const mapped = new Set(
            Object.values(this.selections).filter((value) => value && value !== 'create_new_field')
        );

        this.missingRequirements = this.requiredFields
            .filter((field) => !mapped.has(field.key))
            .map((field) => field.label);

        const seen = {};
        this.duplicateWarning = '';
        for (const [col, value] of Object.entries(this.selections)) {
            if (!value || value === 'create_new_field') {
                continue;
            }
            if (seen[value]) {
                this.duplicateWarning =
                    'A target field is mapped more than once. Each field can only be used once.';
                break;
            }
            seen[value] = col;
        }
    },

    onSubmit(event) {
        this.refreshValidation();
        if (this.canSubmit) {
            return true;
        }

        event.preventDefault();
        return false;
    },
}));

Alpine.data('customFieldSetForm', (initialFields = []) => ({
    fields: (initialFields || []).map((field, index) => ({
        key: field.id ? `id-${field.id}` : `new-${index}-${Math.random().toString(36).slice(2, 8)}`,
        id: field.id || null,
        name: field.name || '',
        type: field.type || 'text',
        options: field.options || '',
        is_required: !!field.is_required,
    })),

    addField() {
        this.fields.push({
            key: `new-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            id: null,
            name: '',
            type: 'text',
            options: '',
            is_required: false,
        });
    },

    removeField(index) {
        this.fields.splice(index, 1);
    },
}));

Alpine.data('bulkAssets', () => ({
    selected: {},
    panelOpen: false,
    deleteConfirmOpen: false,
    pendingDelete: false,
    fields: {
        update_status: false,
        update_location: false,
        clear_location: false,
        update_parent: false,
        clear_parent: false,
        update_test_tag_expiry: false,
        clear_test_tag_expiry: false,
        update_warranty_expiry: false,
        clear_warranty_expiry: false,
        update_notes: false,
        clear_notes: false,
    },

    get selectedIds() {
        return Object.keys(this.selected)
            .filter((id) => this.selected[id])
            .map((id) => Number(id));
    },

    get selectedCount() {
        return this.selectedIds.length;
    },

    get allPageSelected() {
        const boxes = this.pageCheckboxes();
        return boxes.length > 0 && boxes.every((id) => !!this.selected[id]);
    },

    pageCheckboxes() {
        return [...this.$el.querySelectorAll('[data-bulk-asset-id]')].map((el) =>
            Number(el.getAttribute('data-bulk-asset-id'))
        );
    },

    setSelected(id, checked) {
        this.selected = { ...this.selected, [id]: !!checked };
    },

    toggleAll(checked) {
        const next = { ...this.selected };
        this.pageCheckboxes().forEach((id) => {
            next[id] = !!checked;
        });
        this.selected = next;
    },

    clearSelection() {
        this.selected = {};
        this.panelOpen = false;
        this.deleteConfirmOpen = false;
        this.pendingDelete = false;
    },

    openDeleteConfirm() {
        if (this.selectedCount === 0) {
            return;
        }
        this.pendingDelete = false;
        this.deleteConfirmOpen = true;
    },

    cancelDelete() {
        this.deleteConfirmOpen = false;
        this.pendingDelete = false;
    },

    confirmDelete() {
        this.pendingDelete = true;
        this.deleteConfirmOpen = false;
        this.$nextTick(() => {
            const form = this.$refs.bulkForm;
            if (!form) {
                return;
            }

            let confirmInput = form.querySelector('input[name="confirm_delete"]');
            if (!confirmInput) {
                confirmInput = document.createElement('input');
                confirmInput.type = 'hidden';
                confirmInput.name = 'confirm_delete';
                form.appendChild(confirmInput);
            }
            confirmInput.value = '1';

            form.requestSubmit();
        });
    },

    prepareSubmit(event) {
        if (this.pendingDelete) {
            return true;
        }

        if (this.selectedCount === 0) {
            event.preventDefault();
            return false;
        }

        return true;
    },
}));

Alpine.data('barcodeScanner', (scanUrl, eventListId = null, initialEventItems = []) => ({
    scanning: false,
    code: '',
    quantity: 1,
    message: '',
    error: '',
    warning: '',
    scanner: null,
    busy: false,
    queue: [],
    recent: [],
    eventItems: [],
    eventListId: eventListId,
    lastCameraCode: '',
    lastCameraAt: 0,

    init() {
        this.eventItems = (initialEventItems || []).map((entry, index) => this.normalizeEventItem(entry, index));
        this.recent = this.loadRecent();
        this.$nextTick(() => this.focusInput());
    },

    storageKey() {
        return `av-scan-recent-${this.eventListId ?? 'none'}`;
    },

    loadRecent() {
        try {
            const raw = sessionStorage.getItem(this.storageKey());
            if (!raw) {
                return [];
            }
            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                return [];
            }
            return parsed.map((entry, index) => this.normalizeEntry(entry, index));
        } catch (_) {
            return [];
        }
    },

    persistRecent() {
        try {
            sessionStorage.setItem(this.storageKey(), JSON.stringify(this.recent));
        } catch (_) {}
    },

    resetRecent() {
        this.recent = [];
        try {
            sessionStorage.removeItem(this.storageKey());
        } catch (_) {}
    },

    focusInput() {
        const input = this.$refs.codeInput;
        if (!input || this.scanning) {
            return;
        }
        input.focus({ preventScroll: true });
        input.select();
    },

    normalizeEntry(entry, index = 0) {
        const asset = entry.asset || {};
        return {
            key: String(entry.id ?? `${asset.id || 'asset'}-${index}-${Date.now()}`),
            id: entry.id ?? null,
            quantity: entry.quantity ?? 1,
            is_child_expand: Boolean(entry.is_child_expand),
            duplicate: Boolean(entry.duplicate),
            inventory_status: entry.inventory_status || 'unknown',
            warning: entry.warning || null,
            asset,
            time: entry.time ?? new Date().toLocaleTimeString(),
        };
    },

    normalizeEventItem(entry, index = 0) {
        const asset = entry.asset || {};
        return {
            id: entry.id ?? `event-${asset.id || index}-${Date.now()}`,
            quantity: entry.quantity ?? 1,
            is_child_expand: Boolean(entry.is_child_expand),
            asset,
        };
    },

    async startCamera() {
        this.error = '';
        this.scanning = true;
        await this.$nextTick();
        this.scanner = new Html5Qrcode('qr-reader');
        try {
            await this.scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                async (decoded) => {
                    const now = Date.now();
                    if (decoded === this.lastCameraCode && now - this.lastCameraAt < 2000) {
                        return;
                    }
                    this.lastCameraCode = decoded;
                    this.lastCameraAt = now;
                    this.code = decoded;
                    await this.submitCode();
                },
                () => {}
            );
        } catch (e) {
            this.error = 'Camera unavailable: ' + (e?.message || e);
            this.scanning = false;
            this.$nextTick(() => this.focusInput());
        }
    },

    async stopCamera() {
        if (this.scanner) {
            try {
                await this.scanner.stop();
                await this.scanner.clear();
            } catch (_) {}
            this.scanner = null;
        }
        this.scanning = false;
        this.$nextTick(() => this.focusInput());
    },

    async submitCode() {
        const code = String(this.code || '').trim();
        if (!code) {
            return;
        }

        const quantity = Number(this.quantity) > 0 ? Number(this.quantity) : 1;
        this.code = '';
        this.message = '';
        this.error = '';
        this.queue.push({ code, quantity });
        this.$nextTick(() => this.focusInput());
        await this.processQueue();
    },

    async processQueue() {
        if (this.busy) {
            return;
        }

        this.busy = true;

        while (this.queue.length > 0) {
            const job = this.queue.shift();
            await this.sendScan(job.code, job.quantity);
            this.$nextTick(() => this.focusInput());
        }

        this.busy = false;
        this.$nextTick(() => this.focusInput());
    },

    async sendScan(code, quantity) {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        try {
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ code, quantity }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                this.error =
                    data.message ||
                    Object.values(data.errors || {})
                        .flat()
                        .join(' ') ||
                    'Scan failed';
                this.warning = '';
                return;
            }

            this.message = data.message || '';
            this.warning = data.warning || '';
            this.error = '';

            const stamped = new Date().toLocaleTimeString();
            const entries = [];

            if (Array.isArray(data.items) && data.items.length > 0) {
                data.items.forEach((item, index) => {
                    entries.push(
                        this.normalizeEntry(
                            {
                                ...item,
                                duplicate: false,
                                inventory_status: index === 0 ? data.inventory_status : 'unknown',
                                warning: index === 0 ? data.warning : null,
                                time: stamped,
                            },
                            index
                        )
                    );
                });
            } else if (data.asset) {
                entries.push(
                    this.normalizeEntry(
                        {
                            id: `dup-${data.asset.id}-${Date.now()}`,
                            quantity,
                            is_child_expand: false,
                            duplicate: Boolean(data.duplicate),
                            inventory_status: data.inventory_status,
                            warning: data.warning,
                            asset: data.asset,
                            time: stamped,
                        },
                        0
                    )
                );
            }

            if (entries.length) {
                this.recent = [...entries, ...this.recent].slice(0, 50);
                this.persistRecent();
            }

            if (!data.duplicate && Array.isArray(data.items) && data.items.length > 0) {
                const added = data.items.map((item, index) => this.normalizeEventItem(item, index));
                this.eventItems = [...added, ...this.eventItems];
            }
        } catch (e) {
            this.error = 'Scan failed: ' + (e?.message || e);
            this.warning = '';
        }
    },
}));

Alpine.start();

import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });
