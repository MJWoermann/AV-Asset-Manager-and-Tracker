import './bootstrap';
import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;

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
        const q = this.query.trim().toLowerCase();
        if (!q) {
            return this.options;
        }

        return this.options.filter((o) => o.label.toLowerCase().includes(q));
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

Alpine.data('importMapper', (columns, initialSelections = {}) => ({
    selections: Object.fromEntries(columns.map((c) => [c, initialSelections[c] ?? ''])),
    duplicateWarning: '',

    init() {
        this.refreshWarning();
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
        this.refreshWarning();
    },

    refreshWarning() {
        const seen = {};
        for (const [col, value] of Object.entries(this.selections)) {
            if (!value || value === 'create_new_field') {
                continue;
            }
            if (seen[value]) {
                this.duplicateWarning =
                    'A target field is mapped more than once. Each field can only be used once.';
                return;
            }
            seen[value] = col;
        }
        this.duplicateWarning = '';
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

Alpine.data('barcodeScanner', (scanUrl) => ({
    scanning: false,
    code: '',
    quantity: 1,
    message: '',
    error: '',
    scanner: null,

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
                    this.code = decoded;
                    await this.submitCode();
                },
                () => {}
            );
        } catch (e) {
            this.error = 'Camera unavailable: ' + (e?.message || e);
            this.scanning = false;
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
    },

    async submitCode() {
        if (!this.code) return;
        this.message = '';
        this.error = '';
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const res = await fetch(scanUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ code: this.code, quantity: this.quantity }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            this.error = data.message || Object.values(data.errors || {}).flat().join(' ') || 'Scan failed';
            return;
        }
        this.message = data.message + (data.inventory_status === 'not_on_inventory' ? ' (not on inventory list)' : '');
        this.code = '';
    },
}));

Alpine.start();

import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });
