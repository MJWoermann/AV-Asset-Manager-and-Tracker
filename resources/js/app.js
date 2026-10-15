import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const CAMERA_SAME_CODE_DEBOUNCE_MS = 800;

/** @type {Promise<typeof import('chart.js').Chart> | null} */
let chartModulePromise = null;
/** @type {Promise<typeof import('cytoscape').default> | null} */
let cytoscapeModulePromise = null;
/** @type {Promise<typeof import('qrcode').default> | null} */
let qrcodeModulePromise = null;
/** @type {Promise<typeof import('jsbarcode').default> | null} */
let jsBarcodeModulePromise = null;
/** @type {Promise<{ Html5Qrcode: typeof import('html5-qrcode').Html5Qrcode, Html5QrcodeSupportedFormats: typeof import('html5-qrcode').Html5QrcodeSupportedFormats }> | null} */
let html5QrcodeModulePromise = null;

async function loadChart() {
    if (!chartModulePromise) {
        chartModulePromise = (async () => {
            const {
                Chart,
                LineController,
                LineElement,
                PointElement,
                LinearScale,
                TimeScale,
                CategoryScale,
                Filler,
                Legend,
                Tooltip,
            } = await import('chart.js');
            const { default: annotationPlugin } = await import('chartjs-plugin-annotation');
            await import('chartjs-adapter-date-fns');

            Chart.register(
                LineController,
                LineElement,
                PointElement,
                LinearScale,
                TimeScale,
                CategoryScale,
                Filler,
                Legend,
                Tooltip,
                annotationPlugin
            );

            window.Chart = Chart;
            return Chart;
        })();
    }

    return chartModulePromise;
}

async function loadCytoscape() {
    if (!cytoscapeModulePromise) {
        cytoscapeModulePromise = import('cytoscape').then((mod) => {
            const cytoscape = mod.default;
            window.cytoscape = cytoscape;
            return cytoscape;
        });
    }

    return cytoscapeModulePromise;
}

async function loadQRCode() {
    if (!qrcodeModulePromise) {
        qrcodeModulePromise = import('qrcode').then((mod) => {
            const QRCode = mod.default;
            window.QRCode = QRCode;
            return QRCode;
        });
    }

    return qrcodeModulePromise;
}

async function loadJsBarcode() {
    if (!jsBarcodeModulePromise) {
        jsBarcodeModulePromise = import('jsbarcode').then((mod) => {
            const JsBarcode = mod.default;
            window.JsBarcode = JsBarcode;
            return JsBarcode;
        });
    }

    return jsBarcodeModulePromise;
}

async function loadHtml5Qrcode() {
    if (!html5QrcodeModulePromise) {
        html5QrcodeModulePromise = import('html5-qrcode').then((mod) => {
            window.Html5Qrcode = mod.Html5Qrcode;
            return {
                Html5Qrcode: mod.Html5Qrcode,
                Html5QrcodeSupportedFormats: mod.Html5QrcodeSupportedFormats,
            };
        });
    }

    return html5QrcodeModulePromise;
}

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

    emitChanged() {
        const input = this.$el.querySelector('input[type="hidden"]');
        this.$dispatch('searchable-select-changed', {
            value: this.value,
            name: input?.name || '',
        });
    },

    select(option) {
        this.value = option.value;
        this.closeList();
        this.emitChanged();
    },

    clear() {
        if (!this.nullable) {
            return;
        }
        this.value = '';
        this.closeList();
        this.emitChanged();
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

Alpine.data('interfaceTemplateForm', (initialTemplates = []) => ({
    templates: (initialTemplates || []).map((template, index) => ({
        key: template.id ? `id-${template.id}` : `new-${index}-${Math.random().toString(36).slice(2, 8)}`,
        id: template.id || null,
        name: template.name || '',
        notes: template.notes || '',
    })),

    addTemplate() {
        this.templates.push({
            key: `new-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            id: null,
            name: '',
            notes: '',
        });
    },

    removeTemplate(index) {
        this.templates.splice(index, 1);
    },
}));

Alpine.data('assetRuFields', (config = {}) => ({
    supportsByType: config.supportsByType || {},
    itemTypeId: config.initialTypeId === null || config.initialTypeId === undefined
        ? ''
        : String(config.initialTypeId),
    forceShow: Boolean(config.forceShow),

    get showRuFields() {
        return this.forceShow || Boolean(this.supportsByType[this.itemTypeId]);
    },
}));

Alpine.data('assetFormFields', (config = {}) => ({
    ruSupportsByType: config.ruSupportsByType || {},
    stockSupportsByType: config.stockSupportsByType || {},
    itemTypeId: config.initialTypeId === null || config.initialTypeId === undefined
        ? ''
        : String(config.initialTypeId),
    forceShowRu: Boolean(config.forceShowRu),
    forceShowStock: Boolean(config.forceShowStock),
    emailMacNotify: Boolean(config.emailMacNotify),
    macCustomFields: Array.isArray(config.macCustomFields) ? config.macCustomFields : [],
    macAddress: config.initialMac === null || config.initialMac === undefined
        ? ''
        : String(config.initialMac),
    customMacValues: {},

    get showRuFields() {
        return this.forceShowRu || Boolean(this.ruSupportsByType[this.itemTypeId]);
    },

    get showStockFields() {
        return this.forceShowStock || Boolean(this.stockSupportsByType[this.itemTypeId]);
    },

    get showMacNotify() {
        if (!this.emailMacNotify) {
            return false;
        }

        if (String(this.macAddress || '').trim() !== '') {
            return true;
        }

        const typeId = Number(this.itemTypeId);
        return this.macCustomFields.some((field) => {
            const onType = (field.item_type_ids || []).map(Number).includes(typeId);
            if (!onType) {
                return false;
            }
            const value = this.customMacValues[String(field.id)]
                ?? document.querySelector(`[name="custom_fields[${field.id}]"]`)?.value
                ?? '';
            return String(value).trim() !== '';
        });
    },
}));

Alpine.data('stockPanel', (config = {}) => ({
    chartPayload: config.chart || {},
    action: config.initialAction || 'count',
    selected: null,
    chart: null,

    get quantityHint() {
        if (this.action === 'count') {
            return 'Absolute on-hand quantity after counting';
        }
        if (this.action === 'restock') {
            return 'Units added from a restock / Momentous PO';
        }
        if (this.action === 'breakage') {
            return 'Units removed due to breakage';
        }
        return '';
    },

    init() {
        const points = this.chartPayload.points || [];
        if (config.openDetailId) {
            this.selected = points.find((point) => String(point.id) === String(config.openDetailId)) || null;
        }
        this.$nextTick(() => this.mountChart());
    },

    async mountChart() {
        const canvas = this.$refs.chart;
        if (!canvas) {
            return;
        }

        const Chart = await loadChart();
        const points = this.chartPayload.points || [];
        const restocks = this.chartPayload.restock_markers || [];
        const trend = this.chartPayload.trend?.projection || [];
        const annotations = {};

        if (this.chartPayload.min_line !== null && this.chartPayload.min_line !== undefined) {
            annotations.minLine = {
                type: 'line',
                yMin: this.chartPayload.min_line,
                yMax: this.chartPayload.min_line,
                borderColor: '#ef4444',
                borderWidth: 1,
                borderDash: [6, 4],
                label: {
                    display: true,
                    content: 'Min',
                    position: 'start',
                },
            };
        }

        if (this.chartPayload.max_line !== null && this.chartPayload.max_line !== undefined) {
            annotations.maxLine = {
                type: 'line',
                yMin: this.chartPayload.max_line,
                yMax: this.chartPayload.max_line,
                borderColor: '#00adb7',
                borderWidth: 1,
                borderDash: [6, 4],
                label: {
                    display: true,
                    content: 'Max',
                    position: 'start',
                },
            };
        }

        this.chart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                datasets: [
                    {
                        label: 'On hand',
                        data: points.map((point) => ({ x: point.x, y: point.y, meta: point })),
                        borderColor: '#111827',
                        backgroundColor: 'transparent',
                        stepped: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0,
                    },
                    {
                        label: 'Restock',
                        data: restocks.map((point) => ({ x: point.x, y: point.y, meta: point })),
                        borderColor: '#00adb7',
                        backgroundColor: '#00adb7',
                        showLine: false,
                        pointRadius: 7,
                        pointStyle: 'triangle',
                    },
                    {
                        label: 'Trend',
                        data: trend,
                        borderColor: '#9ca3af',
                        borderDash: [8, 4],
                        pointRadius: 0,
                        tension: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: true },
                scales: {
                    x: {
                        type: 'time',
                        time: { tooltipFormat: 'yyyy-MM-dd HH:mm' },
                        ticks: { maxRotation: 0 },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                    },
                },
                plugins: {
                    legend: { display: true },
                    annotation: { annotations },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const meta = context.raw?.meta;
                                if (!meta) {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                }
                                return `${meta.type_label}: ${meta.quantity_after}`;
                            },
                        },
                    },
                },
                onClick: (_event, elements) => {
                    if (!elements.length) {
                        return;
                    }
                    const element = elements[0];
                    const raw = this.chart.data.datasets[element.datasetIndex].data[element.index];
                    if (raw?.meta) {
                        this.selected = raw.meta;
                    }
                },
            },
        });
    },
}));

Alpine.data('kanbanQr', (url) => ({
    async init() {
        if (!this.$refs.qr || !url) {
            return;
        }

        try {
            const QRCode = await loadQRCode();
            await QRCode.toCanvas(this.$refs.qr, url, {
                width: 160,
                margin: 1,
                color: { dark: '#000000', light: '#ffffff' },
            });
        } catch (_) {}
    },
}));

const buildRuFaceRows = (capacity, placements, face) => {
    const occupied = {};
    const mirrored = {};

    (Array.isArray(placements) ? placements : []).forEach((placement) => {
        const onFace = placement.face === face;
        const mirrorOnRear = Boolean(placement.full_depth) && face === 'rear' && placement.face === 'front';
        if (!onFace && !mirrorOnRear) {
            return;
        }

        const start = Number(placement.start_ru);
        const height = Number(placement.ru_height);
        for (let ru = start; ru < start + height; ru += 1) {
            occupied[ru] = placement;
            if (mirrorOnRear) {
                mirrored[ru] = true;
            }
        }
    });

    const rows = [];
    for (let ru = capacity; ru >= 1; ru -= 1) {
        const placement = occupied[ru] || null;
        const isStart = placement && Number(placement.start_ru) + Number(placement.ru_height) - 1 === ru;
        rows.push({
            ru,
            placement,
            isStart,
            span: isStart ? Number(placement.ru_height) : 0,
            occupied: Boolean(placement),
            mirrored: Boolean(mirrored[ru]),
        });
    }

    return rows;
};

const ruPlacementLabel = (placement) => {
    if (!placement) {
        return '';
    }
    if (placement.kind === 'note') {
        return placement.note || 'Note';
    }
    return placement.child_name || 'Asset';
};

Alpine.data('ruRackPreview', (config = {}) => ({
    capacity: Number(config.capacity) || 0,
    face: 'front',
    placements: Array.isArray(config.placements) ? config.placements : [],

    get rows() {
        return buildRuFaceRows(this.capacity, this.placements, this.face);
    },

    placementLabel: ruPlacementLabel,
}));

Alpine.data('ruVisualiser', (config = {}) => ({
    capacity: Number(config.capacity) || 0,
    rearEnabled: Boolean(config.rearEnabled),
    face: config.initialFace || 'front',
    placements: Array.isArray(config.placements) ? config.placements : [],
    childOptions: Array.isArray(config.childOptions) ? config.childOptions : [],
    storeUrl: config.storeUrl || '',
    panel: null,
    editingId: null,
    confirmParent: Boolean(config.showParentConfirm),
    pendingParentName: config.pendingParentName || '',
    form: {
        face: config.old?.face || config.initialFace || 'front',
        start_ru: config.old?.start_ru || '',
        ru_height: config.old?.ru_height || '',
        full_depth: Boolean(config.old?.full_depth),
        kind: config.old?.kind || 'asset',
        child_asset_id: config.old?.child_asset_id || '',
        note: config.old?.note || '',
        confirm_parent_change: Boolean(config.old?.confirm_parent_change),
    },

    init() {
        if (!config.openPanel) {
            return;
        }

        this.panel = config.openPanel;
        this.editingId = config.editingId || null;
        this.face = this.form.face || this.face;

        const placement = this.editingPlacement();
        const childId = this.form.child_asset_id || placement?.child_asset_id;
        if (
            childId &&
            !this.childOptions.some((option) => String(option.value) === String(childId))
        ) {
            this.childOptions = [
                ...this.childOptions,
                {
                    value: String(childId),
                    label: placement?.child_name || String(childId),
                    parent_id: String(config.rackId || ''),
                    parent_name: config.pendingParentName || null,
                    ru_height: this.form.ru_height || placement?.ru_height || 1,
                },
            ];
        }
    },

    get rows() {
        return buildRuFaceRows(this.capacity, this.placements, this.face);
    },

    placementLabel: ruPlacementLabel,

    selectedChild() {
        return this.childOptions.find((option) => String(option.value) === String(this.form.child_asset_id));
    },

    onChildSelected(value) {
        this.form.child_asset_id = value;
        const child = this.selectedChild();
        if (child && (!this.form.ru_height || this.panel === 'add')) {
            this.form.ru_height = child.ru_height || 1;
        }
    },

    openAdd(ru = null) {
        this.panel = 'add';
        this.editingId = null;
        this.confirmParent = false;
        this.form = {
            face: this.face === 'rear' && !this.rearEnabled ? 'front' : this.face,
            start_ru: ru || '',
            ru_height: 1,
            full_depth: false,
            kind: 'asset',
            child_asset_id: '',
            note: '',
            confirm_parent_change: false,
        };
    },

    openAddForChild(childId, ruHeight = 1) {
        this.openAdd();
        this.form.kind = 'asset';
        this.form.child_asset_id = String(childId);
        this.form.ru_height = ruHeight || 1;
    },

    openEdit(placement) {
        this.panel = 'edit';
        this.editingId = placement.id;
        this.confirmParent = false;
        this.face = placement.full_depth ? 'front' : placement.face;
        this.form = {
            face: placement.full_depth ? 'front' : placement.face,
            start_ru: placement.start_ru,
            ru_height: placement.ru_height,
            full_depth: Boolean(placement.full_depth),
            kind: placement.kind,
            child_asset_id: placement.child_asset_id || '',
            note: placement.note || '',
            confirm_parent_change: false,
        };

        if (
            placement.child_asset_id &&
            !this.childOptions.some((option) => String(option.value) === String(placement.child_asset_id))
        ) {
            this.childOptions = [
                ...this.childOptions,
                {
                    value: String(placement.child_asset_id),
                    label: placement.child_name || String(placement.child_asset_id),
                    parent_id: String(config.rackId || ''),
                    parent_name: null,
                    ru_height: placement.ru_height,
                },
            ];
        }
    },

    closePanel() {
        this.panel = null;
        this.editingId = null;
        this.confirmParent = false;
    },

    editingPlacement() {
        return this.placements.find((placement) => placement.id === this.editingId) || null;
    },

    needsParentConfirm() {
        if (this.form.kind !== 'asset' || !this.form.child_asset_id) {
            return false;
        }
        const child = this.selectedChild();
        if (!child || !child.parent_id) {
            return false;
        }
        return String(child.parent_id) !== String(config.rackId || '');
    },

    prepareSubmit(event) {
        if (this.form.kind === 'asset' && this.form.full_depth) {
            this.form.face = 'front';
        }

        if (this.form.confirm_parent_change) {
            return true;
        }

        if (this.needsParentConfirm()) {
            event.preventDefault();
            const child = this.selectedChild();
            this.pendingParentName = child?.parent_name || 'another asset';
            this.confirmParent = true;
            return false;
        }

        return true;
    },

    confirmAndSubmit() {
        this.form.confirm_parent_change = true;
        this.confirmParent = false;
        this.$nextTick(() => {
            this.$refs.placementForm?.requestSubmit();
        });
    },
}));

Alpine.data('assetConnectionsForm', (config = {}) => ({
    peerAssetId: '',
    peerInterfaceId: '',
    peerInterfaces: [],
    peerLoading: false,
    connectionTypeId: '',
    allowsProtocolByType: config.allowsProtocolByType || {},
    peerInterfacesUrl: config.peerInterfacesUrl || '',

    get needsProtocol() {
        return Boolean(this.allowsProtocolByType[String(this.connectionTypeId)]);
    },

    async loadPeerInterfaces() {
        this.peerInterfaces = [];
        this.peerInterfaceId = '';

        if (!this.peerAssetId || !this.peerInterfacesUrl) {
            return;
        }

        this.peerLoading = true;
        const url = this.peerInterfacesUrl.replace('__PEER__', encodeURIComponent(this.peerAssetId));

        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json().catch(() => ({}));
            this.peerInterfaces = Array.isArray(data.interfaces) ? data.interfaces : [];
        } catch (_) {
            this.peerInterfaces = [];
        } finally {
            this.peerLoading = false;
        }
    },
}));

Alpine.data('connectionMap', (graph = { nodes: [], edges: [] }) => ({
    selection: null,
    cy: null,

    async init() {
        const elements = [...(graph.nodes || []), ...(graph.edges || [])];
        const cytoscape = await loadCytoscape();

        this.cy = cytoscape({
            container: this.$refs.cy,
            elements,
            style: [
                {
                    selector: 'node',
                    style: {
                        label: 'data(label)',
                        'background-color': '#00adb7',
                        color: '#111827',
                        'text-valign': 'bottom',
                        'text-halign': 'center',
                        'font-size': 10,
                        'text-margin-y': 6,
                        width: 28,
                        height: 28,
                    },
                },
                {
                    selector: 'edge',
                    style: {
                        width: 3,
                        'line-color': 'data(color)',
                        'target-arrow-color': 'data(color)',
                        'curve-style': 'bezier',
                        label: 'data(label)',
                        'font-size': 9,
                        color: '#4b5563',
                        'text-rotation': 'autorotate',
                    },
                },
            ],
            layout: {
                name: elements.length > 40 ? 'cose' : 'circle',
                animate: false,
                padding: 24,
            },
        });

        this.cy.on('tap', 'node', (event) => {
            const data = event.target.data();
            this.selection = {
                kind: 'node',
                ...data,
            };
        });

        this.cy.on('tap', 'edge', (event) => {
            const data = event.target.data();
            this.selection = {
                kind: 'edge',
                ...data,
            };
        });

        this.cy.on('tap', (event) => {
            if (event.target === this.cy) {
                this.selection = null;
            }
        });
    },
}));

Alpine.data('customFieldSetForm', (initialFields = []) => ({
    draggedIndex: null,
    fields: (initialFields || []).map((field, index) => ({
        key: field.id ? `id-${field.id}` : `new-${index}-${Math.random().toString(36).slice(2, 8)}`,
        id: field.id || null,
        name: field.name || '',
        type: field.type || 'text',
        options: field.options || '',
        is_required: !!field.is_required,
    })),

    isLayoutType(type) {
        return type === 'spacer' || type === 'divider';
    },

    newFieldKey() {
        return `new-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    },

    addField() {
        this.fields.push({
            key: this.newFieldKey(),
            id: null,
            name: '',
            type: 'text',
            options: '',
            is_required: false,
        });
    },

    addSpacer() {
        this.fields.push({
            key: this.newFieldKey(),
            id: null,
            name: 'Spacer',
            type: 'spacer',
            options: '',
            is_required: false,
        });
    },

    addDivider() {
        this.fields.push({
            key: this.newFieldKey(),
            id: null,
            name: 'Divider',
            type: 'divider',
            options: '',
            is_required: false,
        });
    },

    removeField(index) {
        this.fields.splice(index, 1);
    },

    moveField(index, direction) {
        const target = index + direction;
        if (target < 0 || target >= this.fields.length) {
            return;
        }

        const fields = [...this.fields];
        const [item] = fields.splice(index, 1);
        fields.splice(target, 0, item);
        this.fields = fields;
    },

    onTypeChange(field) {
        if (this.isLayoutType(field.type)) {
            field.is_required = false;
            field.options = '';
            if (!field.name || field.name.trim() === '') {
                field.name = field.type === 'spacer' ? 'Spacer' : 'Divider';
            }
        }
    },

    onDragStart(index, event) {
        this.draggedIndex = index;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(index));
    },

    onDragOver(event) {
        event.dataTransfer.dropEffect = 'move';
    },

    onDrop(index) {
        if (this.draggedIndex === null || this.draggedIndex === index) {
            this.draggedIndex = null;
            return;
        }

        const fields = [...this.fields];
        const [item] = fields.splice(this.draggedIndex, 1);
        fields.splice(index, 0, item);
        this.fields = fields;
        this.draggedIndex = null;
    },

    onDragEnd() {
        this.draggedIndex = null;
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

Alpine.data('bulkLocations', () => ({
    selected: {},
    panelOpen: false,
    deleteConfirmOpen: false,
    pendingDelete: false,
    pendingMerge: false,
    fields: {
        update_parent: false,
        clear_parent: false,
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
        return [...this.$el.querySelectorAll('[data-bulk-location-id]')].map((el) =>
            Number(el.getAttribute('data-bulk-location-id'))
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

    selectDuplicateNames(names) {
        const normalized = new Set(
            (names || []).map((name) => String(name).trim().toLowerCase())
        );
        const next = {};
        [...this.$el.querySelectorAll('[data-bulk-location-id]')].forEach((el) => {
            const name = (el.getAttribute('data-bulk-location-name') || '').trim().toLowerCase();
            if (normalized.has(name)) {
                next[Number(el.getAttribute('data-bulk-location-id'))] = true;
            }
        });
        this.selected = next;
        this.panelOpen = true;
    },

    clearSelection() {
        this.selected = {};
        this.panelOpen = false;
        this.deleteConfirmOpen = false;
        this.pendingDelete = false;
        this.pendingMerge = false;
    },

    openDeleteConfirm() {
        if (this.selectedCount === 0) {
            return;
        }
        this.pendingDelete = false;
        this.pendingMerge = false;
        this.deleteConfirmOpen = true;
    },

    cancelDelete() {
        this.deleteConfirmOpen = false;
        this.pendingDelete = false;
    },

    confirmDelete() {
        this.pendingDelete = true;
        this.pendingMerge = false;
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

    submitMerge() {
        if (this.selectedCount < 2) {
            return;
        }

        this.pendingMerge = true;
        this.pendingDelete = false;
        this.$nextTick(() => {
            this.$refs.bulkForm?.requestSubmit();
        });
    },

    prepareSubmit(event) {
        if (this.pendingDelete || this.pendingMerge) {
            return true;
        }

        if (this.selectedCount === 0) {
            event.preventDefault();
            return false;
        }

        return true;
    },
}));

Alpine.data('shelfTagDesigner', (initial = {}, presets = {}, fieldOptions = {}) => ({
    name: initial.name || '',
    paperPreset: initial.paper_preset || 'continuous_62',
    widthMm: Number(initial.width_mm) || 62,
    heightMm: Number(initial.height_mm) || 40,
    orientation: initial.orientation || 'portrait',
    elements: Array.isArray(initial.elements) ? initial.elements.map((el) => ({
        ...el,
        props: { ...(el.props || {}) },
    })) : [],
    logoUrl: initial.logo_url || null,
    presets,
    fieldOptions,
    selectedId: null,
    scale: 4,
    drag: null,
    resize: null,
    showGrid: true,
    snapToGrid: false,
    gridSizeMm: 5,

    get selected() {
        return this.elements.find((el) => el.id === this.selectedId) || null;
    },

    get sortedElements() {
        return [...this.elements].sort((a, b) => (a.z || 0) - (b.z || 0));
    },

    get elementsJson() {
        return JSON.stringify(this.elements);
    },

    get presetNotes() {
        return this.presets[this.paperPreset]?.notes || '';
    },

    get canvasStyle() {
        return {
            width: `${this.widthMm * this.scale}px`,
            height: `${this.heightMm * this.scale}px`,
        };
    },

    get gridOverlayStyle() {
        const stepPx = Math.max(0.5, Number(this.gridSizeMm) || 5) * this.scale;
        const centerX = (this.widthMm / 2) * this.scale;
        const centerY = (this.heightMm / 2) * this.scale;
        return {
            backgroundImage: [
                'linear-gradient(to right, rgba(0,0,0,0.14) 1px, transparent 1px)',
                'linear-gradient(to bottom, rgba(0,0,0,0.14) 1px, transparent 1px)',
            ].join(', '),
            backgroundSize: `${stepPx}px ${stepPx}px`,
            backgroundPosition: `${centerX}px ${centerY}px`,
        };
    },

    get centerCrosshairStyleX() {
        return { left: `${(this.widthMm / 2) * this.scale}px` };
    },

    get centerCrosshairStyleY() {
        return { top: `${(this.heightMm / 2) * this.scale}px` };
    },

    snapToCenterGrid(value, sizeMm) {
        const step = Math.max(0.5, Number(this.gridSizeMm) || 5);
        const center = sizeMm / 2;
        const snapped = center + Math.round((value - center) / step) * step;
        return Math.round(snapped * 1000) / 1000;
    },

    syncElementsJson() {
        if (this.$refs.elementsInput) {
            this.$refs.elementsInput.value = this.elementsJson;
        }
    },

    onPresetChange() {
        const preset = this.presets[this.paperPreset];
        if (!preset) {
            return;
        }
        this.widthMm = Number(preset.width_mm) || this.widthMm;
        if (!preset.continuous && preset.height_mm) {
            this.heightMm = Number(preset.height_mm);
        }
    },

    newId() {
        return `el-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
    },

    addElement(type) {
        const defaults = {
            text: { width: 40, height: 8, props: { content: 'Text', fontSize: 11, fontWeight: 'normal', align: 'left', color: '#000000' } },
            field: { width: 40, height: 8, props: { fieldKey: 'name', fontSize: 11, fontWeight: 'normal', align: 'left', color: '#000000' } },
            barcode: { width: 50, height: 16, props: { fieldKey: 'tp_barcode', symbology: 'code128', showText: true } },
            qr: { width: 20, height: 20, props: { fieldKey: 'tp_barcode', content: '', ecc: 'M' } },
            rect: { width: 20, height: 10, props: { stroke: '#000000', fill: 'transparent' } },
            line: { width: 40, height: 2, props: { stroke: '#000000' } },
            logo: { width: 20, height: 12, props: {} },
        };
        const base = defaults[type] || { width: 20, height: 10, props: {} };
        const el = {
            id: this.newId(),
            type,
            x: 2,
            y: 2,
            width: base.width,
            height: base.height,
            rotation: 0,
            z: this.elements.length + 1,
            props: { ...base.props },
        };
        this.elements.push(el);
        this.selectedId = el.id;
        this.$nextTick(() => {
            if (type === 'barcode') {
                const node = this.$el.querySelector(`[data-el-id="${el.id}"] svg`);
                if (node) this.renderBarcode(el, node);
            }
            if (type === 'qr') {
                const node = this.$el.querySelector(`[data-el-id="${el.id}"] canvas`);
                if (node) this.renderQr(el, node);
            }
        });
    },

    removeSelected() {
        if (!this.selectedId) {
            return;
        }
        this.elements = this.elements.filter((el) => el.id !== this.selectedId);
        this.selectedId = null;
    },

    nudgeZ(delta) {
        if (!this.selected) {
            return;
        }
        this.selected.z = Math.max(0, (this.selected.z || 0) + delta);
    },

    elementStyle(el) {
        return {
            left: `${el.x * this.scale}px`,
            top: `${el.y * this.scale}px`,
            width: `${el.width * this.scale}px`,
            height: `${el.height * this.scale}px`,
            zIndex: el.z || 0,
            transform: el.rotation ? `rotate(${el.rotation}deg)` : undefined,
        };
    },

    elementInnerStyle(el) {
        if (el.type !== 'text' && el.type !== 'field') {
            return {};
        }
        return {
            fontSize: `${el.props.fontSize || 11}px`,
            fontWeight: el.props.fontWeight || 'normal',
            textAlign: el.props.align || 'left',
            color: el.props.color || '#000000',
            lineHeight: '1.1',
        };
    },

    rectStyle(el) {
        return {
            border: `1px solid ${el.props.stroke || '#000000'}`,
            background: el.props.fill || 'transparent',
        };
    },

    lineStyle(el) {
        return {
            borderTop: `2px solid ${el.props.stroke || '#000000'}`,
            marginTop: `${Math.max(0, (el.height * this.scale) / 2 - 1)}px`,
        };
    },

    previewText(el) {
        if (el.type === 'text') {
            return el.props.content || 'Text';
        }
        const key = el.props.fieldKey || 'field';
        return `{${key}}`;
    },

    async renderBarcode(el, node) {
        if (!node) {
            return;
        }
        try {
            const JsBarcode = await loadJsBarcode();
            node.innerHTML = '';
            JsBarcode(node, el.props.fieldKey || 'SAMPLE', {
                format: el.props.symbology === 'code39' ? 'CODE39' : 'CODE128',
                displayValue: Boolean(el.props.showText),
                margin: 0,
                width: 1.5,
                height: Math.max(20, el.height * this.scale - (el.props.showText ? 14 : 0)),
                fontSize: 10,
            });
        } catch (_) {}
    },

    async renderQr(el, node) {
        if (!node) {
            return;
        }
        try {
            const QRCode = await loadQRCode();
            await QRCode.toCanvas(node, el.props.fieldKey || el.props.content || 'SAMPLE', {
                width: Math.max(16, Math.floor(Math.min(el.width, el.height) * this.scale)),
                margin: 0,
            });
        } catch (_) {}
    },

    startDrag(event, el) {
        this.selectedId = el.id;
        this.drag = {
            id: el.id,
            startX: event.clientX,
            startY: event.clientY,
            origX: el.x,
            origY: el.y,
        };
        el._pointerId = event.pointerId;
        event.currentTarget.setPointerCapture?.(event.pointerId);
        const onMove = (e) => this.onPointerMove(e);
        const onUp = (e) => {
            this.drag = null;
            this.resize = null;
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
    },

    startResize(event, el) {
        this.selectedId = el.id;
        this.resize = {
            id: el.id,
            startX: event.clientX,
            startY: event.clientY,
            origW: el.width,
            origH: el.height,
        };
        const onMove = (e) => this.onPointerMove(e);
        const onUp = () => {
            this.drag = null;
            this.resize = null;
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
    },

    onPointerMove(event) {
        if (this.drag) {
            const el = this.elements.find((item) => item.id === this.drag.id);
            if (!el) return;
            const dx = (event.clientX - this.drag.startX) / this.scale;
            const dy = (event.clientY - this.drag.startY) / this.scale;
            const rawX = Math.max(0, this.drag.origX + dx);
            const rawY = Math.max(0, this.drag.origY + dy);
            if (this.snapToGrid) {
                el.x = Math.max(0, this.snapToCenterGrid(rawX, this.widthMm));
                el.y = Math.max(0, this.snapToCenterGrid(rawY, this.heightMm));
            } else {
                el.x = Math.round(rawX * 10) / 10;
                el.y = Math.round(rawY * 10) / 10;
            }
        }
        if (this.resize) {
            const el = this.elements.find((item) => item.id === this.resize.id);
            if (!el) return;
            const dx = (event.clientX - this.resize.startX) / this.scale;
            const dy = (event.clientY - this.resize.startY) / this.scale;
            if (this.snapToGrid) {
                const snappedRight = this.snapToCenterGrid(el.x + this.resize.origW + dx, this.widthMm);
                const snappedBottom = this.snapToCenterGrid(el.y + this.resize.origH + dy, this.heightMm);
                el.width = Math.max(2, Math.round((snappedRight - el.x) * 1000) / 1000);
                el.height = Math.max(2, Math.round((snappedBottom - el.y) * 1000) / 1000);
            } else {
                el.width = Math.max(2, Math.round((this.resize.origW + dx) * 10) / 10);
                el.height = Math.max(2, Math.round((this.resize.origH + dy) * 10) / 10);
            }
        }
    },
}));

Alpine.data('shelfTagPrintSetup', (assets = [], preselectedIds = [], maxBatch = 200) => ({
    assets: Array.isArray(assets) ? assets : [],
    maxBatch: Number(maxBatch) || 200,
    filter: '',
    selected: Object.fromEntries(
        (Array.isArray(preselectedIds) ? preselectedIds : [])
            .map((id) => Number(id))
            .filter((id) => id > 0)
            .map((id) => [id, true])
    ),

    get selectedIds() {
        return Object.keys(this.selected)
            .filter((id) => this.selected[id])
            .map((id) => Number(id));
    },

    get selectedCount() {
        return this.selectedIds.length;
    },

    get overLimit() {
        return this.selectedCount > this.maxBatch;
    },

    get canSubmit() {
        return this.selectedCount > 0 && !this.overLimit;
    },

    get visibleAssets() {
        const term = String(this.filter || '')
            .trim()
            .toLowerCase();
        if (!term) {
            return this.assets;
        }

        return this.assets.filter((asset) => {
            const haystack = [
                asset.name,
                asset.tp_barcode,
                asset.fmi_ast,
                asset.item_type,
                asset.status,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return haystack.includes(term);
        });
    },

    get allVisibleSelected() {
        const visible = this.visibleAssets;
        return visible.length > 0 && visible.every((asset) => !!this.selected[asset.id]);
    },

    setSelected(id, checked) {
        this.selected = { ...this.selected, [Number(id)]: !!checked };
    },

    toggleVisible(checked) {
        const next = { ...this.selected };
        this.visibleAssets.forEach((asset) => {
            next[asset.id] = !!checked;
        });
        this.selected = next;
    },

    selectVisible() {
        this.toggleVisible(true);
    },

    clearVisible() {
        this.toggleVisible(false);
    },

    clearSelection() {
        this.selected = {};
    },

    prepareSubmit(event) {
        if (!this.canSubmit) {
            event.preventDefault();
        }
    },
}));

Alpine.data('shelfTagPrintPreview', (labels = [], widthMm = 62, heightMm = 40, logoUrl = null) => ({
    labels,
    widthMm,
    heightMm,
    logoUrl,

    async renderAll() {
        await this.$nextTick();

        const barcodeNodes = [...this.$el.querySelectorAll('.shelf-tag-barcode')];
        const qrNodes = [...this.$el.querySelectorAll('.shelf-tag-qr')];

        if (barcodeNodes.length) {
            const JsBarcode = await loadJsBarcode();
            barcodeNodes.forEach((node) => {
                const value = node.getAttribute('data-value') || '';
                if (!value) {
                    return;
                }
                try {
                    JsBarcode(node, value, {
                        format: node.getAttribute('data-symbology') === 'code39' ? 'CODE39' : 'CODE128',
                        displayValue: node.getAttribute('data-show-text') === '1',
                        margin: 0,
                        width: 1.4,
                        height: 40,
                        fontSize: 10,
                    });
                } catch (_) {}
            });
        }

        if (qrNodes.length) {
            const QRCode = await loadQRCode();
            await Promise.all(
                qrNodes.map(async (node) => {
                    const value = node.getAttribute('data-value') || '';
                    if (!value) {
                        return;
                    }
                    try {
                        const size = Math.max(32, Math.min(node.clientWidth || 64, node.clientHeight || 64));
                        await QRCode.toCanvas(node, value, { width: size, margin: 0 });
                    } catch (_) {}
                })
            );
        }
    },
}));

Alpine.data('barcodeScanner', (scanUrl, eventListId = null, initialEventItems = [], noteUrlTemplate = '', initialUnknownScans = []) => ({
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
    noteUrlTemplate,
    serverUnknowns: [],
    lastCameraCode: '',
    lastCameraAt: 0,

    init() {
        this.eventItems = (initialEventItems || []).map((entry, index) => this.normalizeEventItem(entry, index));
        this.serverUnknowns = (initialUnknownScans || []).map((scan, index) =>
            this.normalizeEntry(
                {
                    id: `unknown-${scan.id}`,
                    unknown: true,
                    inventory_status: 'unknown_asset',
                    unknown_scan: scan,
                    time: scan.time || new Date().toLocaleTimeString(),
                },
                index
            )
        );
        const stored = this.loadRecent();
        const unknownIds = new Set(this.serverUnknowns.map((entry) => entry.unknown_scan?.id).filter(Boolean));
        const storedWithoutDupUnknowns = stored.filter(
            (entry) => !entry.unknown || !unknownIds.has(entry.unknown_scan?.id)
        );
        this.recent = [...this.serverUnknowns, ...storedWithoutDupUnknowns].slice(0, 50);
        this.persistRecent();
        // #region agent log
        fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'67bc37'},body:JSON.stringify({sessionId:'67bc37',location:'app.js:barcodeScanner.init',message:'scan recent init',data:{eventListId:this.eventListId,storedLen:stored.length,storedAfterDedup:storedWithoutDupUnknowns.length,serverUnknownsLen:this.serverUnknowns.length,recentLen:this.recent.length,storageKey:this.storageKey(),recentUnknownCount:this.recent.filter((e)=>e.unknown).length,recentKnownCount:this.recent.filter((e)=>!e.unknown).length},timestamp:Date.now(),hypothesisId:'A,C,D'})}).catch(()=>{});
        // #endregion
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
        // #region agent log
        const beforeLen = this.recent.length;
        const beforeKnown = this.recent.filter((e) => !e.unknown).length;
        const beforeUnknown = this.recent.filter((e) => e.unknown).length;
        // #endregion
        this.recent = [...this.serverUnknowns];
        this.persistRecent();
        // #region agent log
        fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'67bc37'},body:JSON.stringify({sessionId:'67bc37',location:'app.js:barcodeScanner.resetRecent',message:'resetRecent called',data:{beforeLen,beforeKnown,beforeUnknown,afterLen:this.recent.length,serverUnknownsLen:this.serverUnknowns.length,eventListId:this.eventListId,storageKey:this.storageKey()},timestamp:Date.now(),hypothesisId:'A,B,E'})}).catch(()=>{});
        // #endregion
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
        const unknownScan = entry.unknown_scan || null;
        return {
            key: String(entry.id ?? `${asset.id || unknownScan?.id || 'asset'}-${index}-${Date.now()}`),
            id: entry.id ?? null,
            quantity: entry.quantity ?? unknownScan?.quantity ?? 1,
            is_child_expand: Boolean(entry.is_child_expand),
            duplicate: Boolean(entry.duplicate),
            unknown: Boolean(entry.unknown),
            inventory_status: entry.inventory_status || 'unknown',
            warning: entry.warning || null,
            asset,
            unknown_scan: unknownScan,
            noteDraft: unknownScan?.note || '',
            editingNote: false,
            savingNote: false,
            time: entry.time ?? new Date().toLocaleTimeString(),
        };
    },

    noteUrl(id) {
        return String(this.noteUrlTemplate || '').replace('__ID__', String(id));
    },

    toggleNoteEditor(entry) {
        entry.editingNote = !entry.editingNote;
        if (entry.editingNote) {
            entry.noteDraft = entry.unknown_scan?.note || '';
        }
    },

    async saveNote(entry) {
        if (!entry.unknown_scan?.id) {
            return;
        }

        entry.savingNote = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        try {
            const res = await fetch(this.noteUrl(entry.unknown_scan.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-HTTP-Method-Override': 'PATCH',
                },
                body: JSON.stringify({ note: entry.noteDraft || null }),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                this.error =
                    data.message ||
                    Object.values(data.errors || {})
                        .flat()
                        .join(' ') ||
                    'Could not save note';
                return;
            }

            entry.unknown_scan = data.unknown_scan || {
                ...entry.unknown_scan,
                note: entry.noteDraft || null,
            };
            entry.editingNote = false;
            this.message = data.message || 'Note saved.';
            this.error = '';
            this.persistRecent();
        } catch (e) {
            this.error = 'Could not save note: ' + (e?.message || e);
        } finally {
            entry.savingNote = false;
        }
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

        try {
            const { Html5Qrcode, Html5QrcodeSupportedFormats } = await loadHtml5Qrcode();
            this.scanner = new Html5Qrcode('qr-reader', {
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.QR_CODE,
                ],
                verbose: false,
            });
            await this.scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 15,
                    disableFlip: true,
                    qrbox: (viewfinderWidth, viewfinderHeight) => ({
                        width: Math.floor(viewfinderWidth * 0.9),
                        height: Math.floor(viewfinderHeight * 0.35),
                    }),
                    videoConstraints: {
                        facingMode: 'environment',
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                    },
                },
                async (decoded) => {
                    const now = Date.now();
                    if (decoded === this.lastCameraCode && now - this.lastCameraAt < CAMERA_SAME_CODE_DEBOUNCE_MS) {
                        return;
                    }
                    this.lastCameraCode = decoded;
                    this.lastCameraAt = now;
                    this.code = decoded;
                    await this.submitCode();
                },
                () => {}
            );

            try {
                await this.scanner.applyVideoConstraints({
                    focusMode: 'continuous',
                    advanced: [{ zoom: 1.5 }],
                });
            } catch (_) {}
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

            if (data.unknown && data.unknown_scan) {
                entries.push(
                    this.normalizeEntry(
                        {
                            id: `unknown-${data.unknown_scan.id}`,
                            quantity,
                            unknown: true,
                            inventory_status: data.inventory_status || 'unknown_asset',
                            unknown_scan: data.unknown_scan,
                            time: stamped,
                        },
                        0
                    )
                );
            } else if (Array.isArray(data.items) && data.items.length > 0) {
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

            if (!data.duplicate && !data.unknown && Array.isArray(data.items) && data.items.length > 0) {
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
