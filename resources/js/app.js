import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const CAMERA_SAME_CODE_DEBOUNCE_MS = 800;
/** Idle camera auto-pause while stocktake scanning (no successful decode). */
const CAMERA_IDLE_PAUSE_MS = 60_000;
/** Max gap between keystrokes for keyboard-wedge barcode scanners. */
const WEDGE_MAX_INTER_KEY_MS = 50;
/** Minimum buffered characters before a terminator counts as a wedge scan. */
const WEDGE_MIN_BUFFER_LENGTH = 3;
/** Device-local preferred scan capture mode (hardware vs camera). */
const SCAN_INPUT_MODE_STORAGE_KEY = 'av-scan-input-mode';

/** @type {AudioContext | null} */
let scanAudioContext = null;

function getScanAudioContext() {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) {
        return null;
    }
    if (!scanAudioContext) {
        scanAudioContext = new AudioCtx();
    }
    return scanAudioContext;
}

/**
 * Play a short generated tone for scan feedback.
 * @param {'match' | 'fail'} kind
 */
/**
 * Short haptic pulse for scan feedback (cheaper than audio when both are optional).
 * @param {'match' | 'fail'} kind
 * @returns {boolean} whether vibration was attempted successfully
 */
function playScanVibrate(kind) {
    try {
        if (typeof navigator === 'undefined' || typeof navigator.vibrate !== 'function') {
            return false;
        }
        const pattern = kind === 'match' ? [40, 30, 40] : [80, 40, 80];
        return Boolean(navigator.vibrate(pattern));
    } catch (_) {
        return false;
    }
}

function playScanNoise(kind) {
    try {
        const ctx = getScanAudioContext();
        if (!ctx) {
            return;
        }

        const start = () => {
            const now = ctx.currentTime;
            const schedule = (frequency, offset, duration, type = 'sine') => {
                const oscillator = ctx.createOscillator();
                const gain = ctx.createGain();
                oscillator.type = type;
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, now + offset);
                gain.gain.exponentialRampToValueAtTime(0.18, now + offset + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + duration);
                oscillator.connect(gain);
                gain.connect(ctx.destination);
                oscillator.start(now + offset);
                oscillator.stop(now + offset + duration + 0.02);
            };

            if (kind === 'match') {
                schedule(880, 0, 0.09);
                schedule(1175, 0.1, 0.12);
            } else {
                schedule(240, 0, 0.16, 'square');
                schedule(160, 0.14, 0.22, 'square');
            }
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(start).catch(() => {});
            return;
        }

        start();
    } catch (_) {
        // Ignore audio failures (autoplay policy, unsupported browsers).
    }
}

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

/**
 * Scale a JsBarcode SVG to fill its box without cropping (object-fit: contain).
 */
function fitBarcodeSvgToBox(node) {
    if (!node) {
        return;
    }
    const attrW = parseFloat(node.getAttribute('width'));
    const attrH = parseFloat(node.getAttribute('height'));
    if (!node.getAttribute('viewBox') && Number.isFinite(attrW) && Number.isFinite(attrH) && attrW > 0 && attrH > 0) {
        node.setAttribute('viewBox', `0 0 ${attrW} ${attrH}`);
    }
    node.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    node.setAttribute('width', '100%');
    node.setAttribute('height', '100%');
    node.style.width = '100%';
    node.style.height = '100%';
    node.style.maxWidth = '100%';
    node.style.maxHeight = '100%';
    node.style.display = 'block';
}

/**
 * Render a 1D barcode into an SVG, sized to fill boxW×boxH without clipping.
 */
async function renderBarcodeSvgFit(node, value, { symbology = 'code128', showText = false, boxW = 120, boxH = 40 } = {}) {
    if (!node) {
        return;
    }
    const JsBarcode = await loadJsBarcode();
    const format = symbology === 'code39' ? 'CODE39' : 'CODE128';
    const text = value || 'SAMPLE';
    const widthPx = Math.max(16, Number(boxW) || 120);
    const heightPx = Math.max(16, Number(boxH) || 40);
    const show = Boolean(showText);
    const textSpace = show ? Math.min(16, Math.max(10, heightPx * 0.28)) : 0;
    const barHeight = Math.max(8, heightPx - textSpace);
    const quiet = 4;

    node.innerHTML = '';
    JsBarcode(node, text, {
        format,
        displayValue: show,
        margin: quiet,
        width: 1,
        height: barHeight,
        fontSize: Math.max(8, Math.min(12, textSpace || 10)),
        textMargin: show ? 2 : 0,
    });

    let naturalW = 0;
    try {
        naturalW = node.getBBox().width;
    } catch (_) {
        naturalW = parseFloat(node.getAttribute('width')) || widthPx;
    }
    const moduleWidth = Math.max(0.5, Math.min(4, (widthPx - quiet * 2) / Math.max(1, naturalW - quiet * 2)));

    node.innerHTML = '';
    JsBarcode(node, text, {
        format,
        displayValue: show,
        margin: quiet,
        width: moduleWidth,
        height: barHeight,
        fontSize: Math.max(8, Math.min(12, textSpace || 10)),
        textMargin: show ? 2 : 0,
    });

    fitBarcodeSvgToBox(node);
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


/** @type {{ stopCamera: () => Promise<void> } | null} */
let activeFieldBarcodeScanner = null;

/**
 * Whether the signed-in user opted into battery-saving camera constraints (profile preference).
 *
 * @returns {boolean}
 */
function wantsCameraBatteryOptimisation() {
    const meta = document.querySelector('meta[name="av-camera-battery-optimisation"]');
    const value = meta?.getAttribute('content') || '0';

    return value === '1' || value === 'true';
}

/**
 * Browsers only expose getUserMedia in a secure context (HTTPS, or http://localhost / 127.0.0.1).
 * Plain http://LAN-IP is not secure, so navigator.mediaDevices is usually missing.
 *
 * @returns {boolean}
 */
function cameraBlockedByInsecureOrigin() {
    return !window.isSecureContext || !navigator.mediaDevices;
}

/**
 * Same path/query on localhost — works for camera when the app is served on this PC.
 *
 * @returns {string|null}
 */
function cameraLocalhostUrl() {
    if (!cameraBlockedByInsecureOrigin()) {
        return null;
    }

    try {
        const url = new URL(window.location.href);
        if (url.hostname === 'localhost' || url.hostname === '127.0.0.1' || url.hostname === '[::1]') {
            return null;
        }
        url.protocol = 'http:';
        url.hostname = 'localhost';

        return url.toString();
    } catch (_) {
        return null;
    }
}

/**
 * Prefer configured APP_URL (https), else https:// on the current host.
 *
 * @returns {string|null}
 */
function cameraHttpsUrl() {
    if (!cameraBlockedByInsecureOrigin()) {
        return null;
    }

    try {
        const preferred = document.querySelector('meta[name="av-preferred-origin"]')?.getAttribute('content') || '';
        if (preferred.startsWith('https://')) {
            const target = new URL(preferred);
            target.pathname = window.location.pathname;
            target.search = window.location.search;
            target.hash = window.location.hash;

            return target.toString();
        }

        if (window.location.protocol === 'http:') {
            const url = new URL(window.location.href);
            url.protocol = 'https:';

            return url.toString();
        }
    } catch (_) {}

    return null;
}

/**
 * @param {unknown} [error]
 * @returns {string}
 */
function formatCameraStartError(error) {
    const raw = String(error?.message ?? error ?? '');
    const looksUnsupported =
        raw.includes('Camera streaming not supported') ||
        raw.toLowerCase().includes('secure context') ||
        raw.includes('navigator.mediaDevices not supported');

    if (cameraBlockedByInsecureOrigin() || looksUnsupported) {
        const host = window.location.host || 'this address';
        const localhostUrl = cameraLocalhostUrl();
        const httpsUrl = cameraHttpsUrl();
        const steps = [];

        if (localhostUrl) {
            steps.push(`On this PC, open ${localhostUrl}`);
        }
        if (httpsUrl) {
            steps.push(`If HTTPS is enabled, open ${httpsUrl}`);
        }
        steps.push(
            'For phones and other PCs on the LAN, ask an admin to enable HTTPS (private CA / self-signed TLS) and use the https:// address'
        );

        return (
            `Camera blocked: ${host} is not a secure context over plain HTTP, so the browser hides the camera. ` +
            steps.join(' · ') +
            '.'
        );
    }

    return 'Camera unavailable: ' + raw;
}

/**
 * Start an html5-qrcode camera session with the same formats/constraints as stocktake scanning.
 *
 * Default: 1080p ideal @ 15fps with continuous AF and mild zoom for fine barcodes.
 * Opt-in battery mode (profile): 720p capped @ 10fps, no AF/zoom extras.
 *
 * @param {string} elementId
 * @param {(decoded: string) => void | Promise<void>} onDecoded
 * @returns {Promise<import('html5-qrcode').Html5Qrcode>}
 */
async function startBarcodeCamera(elementId, onDecoded) {
    const { Html5Qrcode, Html5QrcodeSupportedFormats } = await loadHtml5Qrcode();
    const formatsToSupport = [
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.CODE_39,
        Html5QrcodeSupportedFormats.QR_CODE,
    ];
    const scanner = new Html5Qrcode(elementId, {
        formatsToSupport,
        verbose: false,
    });
    const compactCamera = window.matchMedia('(max-width: 639px)').matches;
    const batteryOptimised = wantsCameraBatteryOptimisation();
    const fps = batteryOptimised ? 10 : 15;
    const requestedVideoConstraints = batteryOptimised
        ? {
              facingMode: 'environment',
              width: { ideal: 1280, max: 1280 },
              height: { ideal: 720, max: 720 },
          }
        : {
              facingMode: 'environment',
              width: { ideal: 1920 },
              height: { ideal: 1080 },
          };
    await scanner.start(
        { facingMode: 'environment' },
        {
            fps,
            disableFlip: true,
            aspectRatio: compactCamera ? 2.2 : 1.777778,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const box = {
                    width: Math.floor(viewfinderWidth * 0.9),
                    height: Math.floor(viewfinderHeight * (compactCamera ? 0.45 : 0.35)),
                };
                return box;
            },
            videoConstraints: requestedVideoConstraints,
        },
        (decoded) => {
            void onDecoded(decoded);
        },
        () => {}
    );

    if (!batteryOptimised) {
        try {
            await scanner.applyVideoConstraints({
                focusMode: 'continuous',
                advanced: [{ zoom: 1.5 }],
            });
        } catch (_) {
            // Zoom / focus constraints are best-effort; ignore unsupported devices.
        }
    }

    return scanner;
}

/**
 * @param {import('html5-qrcode').Html5Qrcode | null} scanner
 */
async function stopBarcodeCamera(scanner) {
    if (!scanner) {
        return;
    }

    try {
        await scanner.stop();
        await scanner.clear();
    } catch (_) {}
}

/**
 * @returns {{ buffer: string, lastKeyAt: number }}
 */
function createWedgeBufferState() {
    return { buffer: '', lastKeyAt: 0 };
}

/**
 * Process a document-level keydown for keyboard-wedge scanners (burst + Enter/Tab).
 *
 * @param {{ buffer: string, lastKeyAt: number }} state
 * @param {KeyboardEvent} event
 * @param {{
 *   maxInterKeyMs?: number,
 *   minLength?: number,
 *   ignoreSlowTyping?: boolean,
 * }} [options]
 * @returns {{ scanned: string | null, consume: boolean }}
 */
function processWedgeKeydown(state, event, options = {}) {
    if (event.isComposing || event.repeat) {
        return { scanned: null, consume: false };
    }

    const maxInterKeyMs = options.maxInterKeyMs ?? WEDGE_MAX_INTER_KEY_MS;
    const minLength = options.minLength ?? WEDGE_MIN_BUFFER_LENGTH;
    const now = performance.now();
    const gap = state.lastKeyAt > 0 ? now - state.lastKeyAt : 0;
    const continuingBurst = state.lastKeyAt > 0 && gap <= maxInterKeyMs;

    const isTerminator =
        event.key === 'Enter' || event.key === 'Tab' || event.code === 'NumpadEnter';

    if (isTerminator) {
        const code = state.buffer;
        const terminatorInBurst = state.lastKeyAt > 0 && gap <= maxInterKeyMs;
        state.buffer = '';
        state.lastKeyAt = 0;

        if (code.length >= minLength && terminatorInBurst) {
            return { scanned: code, consume: true };
        }

        return { scanned: null, consume: false };
    }

    if (event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) {
        if (!continuingBurst) {
            state.buffer = '';
            state.lastKeyAt = 0;
        }

        return { scanned: null, consume: false };
    }

    if (!continuingBurst) {
        state.buffer = '';
    }

    // Focus drifted to quantity/notes/etc.: seed the buffer on the first key without
    // stealing it; only preventDefault once a fast burst is confirmed (2+ chars).
    if (options.ignoreSlowTyping && !continuingBurst && state.buffer.length === 0) {
        state.buffer = event.key;
        state.lastKeyAt = now;

        return { scanned: null, consume: false };
    }

    state.buffer += event.key;
    state.lastKeyAt = now;

    const consume = Boolean(options.ignoreSlowTyping && state.buffer.length >= 2);

    return { scanned: null, consume };
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
    disabled: Boolean(config.disabled),
    autoSubmit: Boolean(config.autoSubmit),
    compact: Boolean(config.compact),
    createOption: config.createOption
        ? {
              value: String(config.createOption.value),
              label: String(config.createOption.label ?? ''),
          }
        : null,
    highlight: -1,
    /** Stable root — Alpine `$el` is contextual and becomes the clicked option during `@click`. */
    rootEl: null,
    fieldName: '',
    panelStyle: {},
    _reposition: null,

    init() {
        this.rootEl = this.$el;
        const input = this.rootEl?.querySelector('input[type="hidden"]');
        this.fieldName = input?.name || '';
        this._reposition = () => {
            if (this.open) {
                this.updatePanelPosition();
            }
        };
    },

    get selectedLabel() {
        if (this.createOption && this.value === this.createOption.value) {
            return this.createOption.label;
        }
        const opt = this.options.find((o) => o.value === this.value);
        return opt ? opt.label : this.placeholder;
    },

    get filtered() {
        const q = this.query.trim();
        let list = this.options;

        if (q) {
            list = this.options
                .map((option) => ({ option, score: matchScore(option.label, q) }))
                .filter((entry) => entry.score !== Number.POSITIVE_INFINITY)
                .sort((a, b) => a.score - b.score || a.option.label.localeCompare(b.option.label))
                .map((entry) => entry.option);
        }

        if (this.createOption) {
            return [...list, this.createOption];
        }

        return list;
    },

    highlightedLabel(label) {
        return highlightSearchTerm(label, this.query);
    },

    updatePanelPosition() {
        const trigger = this.$refs.trigger;
        if (!trigger) {
            return;
        }

        const rect = trigger.getBoundingClientRect();
        const minWidth = this.compact ? 11 * 16 : rect.width;
        const width = Math.max(rect.width, minWidth);
        const estimatedHeight = 280;
        const gap = 4;
        const spaceBelow = window.innerHeight - rect.bottom - gap;
        const openUp = spaceBelow < estimatedHeight && rect.top > spaceBelow;
        let top = openUp
            ? Math.max(8, rect.top - estimatedHeight - gap)
            : rect.bottom + gap;
        let left = rect.left;
        const maxLeft = window.innerWidth - width - 8;
        left = Math.min(Math.max(8, left), Math.max(8, maxLeft));

        this.panelStyle = {
            position: 'fixed',
            top: `${top}px`,
            left: `${left}px`,
            width: `${width}px`,
            zIndex: '60',
        };
    },

    bindPanelListeners() {
        window.addEventListener('resize', this._reposition);
        window.addEventListener('scroll', this._reposition, true);
    },

    unbindPanelListeners() {
        window.removeEventListener('resize', this._reposition);
        window.removeEventListener('scroll', this._reposition, true);
    },

    openList() {
        if (this.disabled) {
            return;
        }
        this.open = true;
        this.highlight = -1;
        this.bindPanelListeners();
        this.$nextTick(() => {
            this.updatePanelPosition();
            this.$refs.search?.focus();
        });
    },

    closeList() {
        this.open = false;
        this.query = '';
        this.highlight = -1;
        this.unbindPanelListeners();
    },

    associatedForm() {
        const root = this.rootEl || this.$el;
        const input = root?.querySelector('input[type="hidden"]');

        return input?.form ?? null;
    },

    maybeAutoSubmit() {
        if (!this.autoSubmit) {
            return;
        }

        const form = this.associatedForm();
        if (!form) {
            return;
        }

        // Allow Alpine to flush the hidden input value before submit.
        queueMicrotask(() => form.requestSubmit());
    },

    emitChanged() {
        const root = this.rootEl || this.$el;
        const input = root?.querySelector('input[type="hidden"]');
        const name = this.fieldName || input?.name || '';
        // Dispatch from the stable root — do not use contextual this.$dispatch during option clicks.
        root.dispatchEvent(new CustomEvent('searchable-select-changed', {
            detail: { value: this.value, name },
            bubbles: true,
            composed: true,
        }));
        this.maybeAutoSubmit();
    },

    select(option) {
        this.value = option.value;
        this.closeList();
        this.emitChanged();
    },

    setOptions(next) {
        this.options = (next || []).map((o) => ({
            value: String(o.value),
            label: String(o.label ?? ''),
        }));
    },

    setValue(next, { emit = false } = {}) {
        this.value = next === null || next === undefined ? '' : String(next);
        if (emit) {
            this.emitChanged();
        }
    },

    setDisabled(next) {
        this.disabled = Boolean(next);
        if (this.disabled) {
            this.closeList();
        }
    },

    clear() {
        if (!this.nullable || this.disabled) {
            return;
        }
        this.value = '';
        this.closeList();
        this.emitChanged();
    },

    onKeydown(event) {
        if (this.disabled) {
            return;
        }

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

    destroy() {
        this.unbindPanelListeners();
    },
}));

Alpine.data('searchableMultiSelect', (options, selected = [], config = {}) => ({
    open: false,
    query: '',
    values: (Array.isArray(selected) ? selected : []).map((value) => String(value)),
    options: (options || []).map((o) => ({
        value: String(o.value),
        label: String(o.label ?? ''),
    })),
    placeholder: config.placeholder ?? 'Search and add…',
    highlight: -1,

    get selectedOptions() {
        return this.values
            .map((value) => this.options.find((option) => option.value === value))
            .filter(Boolean);
    },

    get availableOptions() {
        return this.options.filter((option) => !this.values.includes(option.value));
    },

    get filtered() {
        const q = this.query.trim();
        const list = this.availableOptions;

        if (!q) {
            return list;
        }

        return list
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

    add(option) {
        if (!option || this.values.includes(option.value)) {
            return;
        }
        this.values = [...this.values, option.value];
        this.query = '';
        this.highlight = -1;
        this.$nextTick(() => this.$refs.search?.focus());
    },

    remove(value) {
        this.values = this.values.filter((entry) => entry !== String(value));
    },

    onKeydown(event) {
        if (!this.open && (event.key === 'ArrowDown' || event.key === 'Enter')) {
            event.preventDefault();
            this.openList();
            return;
        }

        if (!this.open) {
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
            this.add(this.filtered[this.highlight]);
        }
    },
}));

Alpine.data('catalogueManufacturerModelFields', (config = {}) => ({
    manufacturerId: config.manufacturerId === null || config.manufacturerId === undefined
        ? ''
        : String(config.manufacturerId),
    modelId: config.modelId === null || config.modelId === undefined
        ? ''
        : String(config.modelId),
    modelsByManufacturer: config.modelsByManufacturer || {},

    init() {
        this.$nextTick(() => this.syncModelSelect({ emit: false }));
    },

    onSearchableChanged(detail) {
        if (!detail?.name) {
            return;
        }

        if (detail.name === 'manufacturer_id') {
            this.manufacturerId = detail.value ?? '';
            if (this.manufacturerId === '__new__') {
                this.modelId = '__new__';
            }
            this.syncModelSelect({ emit: true });
            return;
        }

        if (detail.name === 'manufacturer_model_id') {
            this.modelId = detail.value ?? '';
        }
    },

    modelData() {
        // x-ref on a nested x-data root is registered on the child, not this parent —
        // resolve the model searchableSelect via its field id instead.
        const input = this.$el?.querySelector('#manufacturer_model_id');
        const root = input?.closest('[x-data]');
        if (!root || root === this.$el) {
            return null;
        }

        return Alpine.$data(root);
    },

    syncModelSelect({ emit = false } = {}) {
        const model = this.modelData();
        if (!model) {
            return;
        }

        if (!this.manufacturerId) {
            model.setOptions([]);
            model.setDisabled(true);
            model.setValue('', { emit });
            this.modelId = '';
            return;
        }

        if (this.manufacturerId === '__new__') {
            model.setOptions([]);
            model.setDisabled(false);
            model.setValue('__new__', { emit });
            this.modelId = '__new__';
            return;
        }

        const options = this.modelsByManufacturer[this.manufacturerId] || [];
        model.setOptions(options);
        model.setDisabled(false);

        const ids = options.map((row) => String(row.value));
        if (this.modelId === '__new__' || ids.includes(this.modelId)) {
            model.setValue(this.modelId, { emit });
            return;
        }

        this.modelId = '';
        model.setValue('', { emit });
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

Alpine.data('importMapper', (columns, initialSelections = {}, requiredFields = [], fieldOptions = []) => ({
    selections: Object.fromEntries(columns.map((c) => [c, initialSelections[c] ?? ''])),
    requiredFields: requiredFields,
    fieldOptions: (fieldOptions || []).map((o) => ({
        value: String(o.value ?? ''),
        label: String(o.label ?? ''),
    })),
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

Alpine.data('importMappingSelect', (column) => ({
    open: false,
    query: '',
    highlight: -1,
    column: String(column),

    mapper() {
        const root = this.$el.closest('[data-import-mapper]');

        return root ? Alpine.$data(root) : null;
    },

    get value() {
        const mapper = this.mapper();

        return mapper?.selections?.[this.column] ?? '';
    },

    get selectedLabel() {
        const opt = this.allOptions.find((o) => o.value === this.value);

        return opt ? opt.label : 'Ignore';
    },

    get allOptions() {
        const mapper = this.mapper();
        const fieldOptions = (mapper?.fieldOptions ?? []).map((o) => ({
            value: String(o.value ?? ''),
            label: String(o.label ?? ''),
        }));

        return [
            { value: '', label: 'Ignore' },
            ...fieldOptions,
            {
                value: 'create_new_field',
                label: `Create new field (Imported-Field:${this.column})`,
            },
        ];
    },

    get filtered() {
        const q = this.query.trim();
        const options = this.allOptions;

        if (!q) {
            return options;
        }

        return options
            .map((option) => ({ option, score: matchScore(option.label, q) }))
            .filter((entry) => entry.score !== Number.POSITIVE_INFINITY)
            .sort((a, b) => a.score - b.score || a.option.label.localeCompare(b.option.label))
            .map((entry) => entry.option);
    },

    isOptionTaken(field) {
        const mapper = this.mapper();

        return mapper ? mapper.isTaken(field, this.column) : false;
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
        if (this.isOptionTaken(option.value)) {
            return;
        }

        const mapper = this.mapper();
        if (!mapper) {
            return;
        }

        mapper.selections[this.column] = option.value;
        mapper.onChange(this.column);
        this.closeList();
    },

    clear() {
        const mapper = this.mapper();
        if (!mapper) {
            return;
        }

        mapper.selections[this.column] = '';
        mapper.onChange(this.column);
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

Alpine.data('interfaceTemplateForm', (initialTemplates = []) => ({
    templates: (initialTemplates || []).map((template, index) => ({
        key: template.id ? `id-${template.id}` : `new-${index}-${Math.random().toString(36).slice(2, 8)}`,
        id: template.id || null,
        name: template.name || '',
        notes: template.notes || '',
        connection_type_id: template.connection_type_id === null || template.connection_type_id === undefined
            ? ''
            : String(template.connection_type_id),
    })),

    addTemplate() {
        this.templates.push({
            key: `new-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            id: null,
            name: '',
            notes: '',
            connection_type_id: '',
        });
    },

    removeTemplate(index) {
        this.templates.splice(index, 1);
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

/**
 * Camera barcode fill for a single form/search input (asset identifiers, browse search).
 *
 * @param {string} fieldId Input element id to populate
 * @param {{ submitOnScan?: boolean }} [options]
 */
Alpine.data('fieldBarcodeScanner', (fieldId, options = {}) => ({
    fieldId: String(fieldId || ''),
    readerId: `field-qr-${String(fieldId || 'input')}`,
    submitOnScan: Boolean(options?.submitOnScan),
    scanning: false,
    scanner: null,
    error: '',
    lastCameraCode: '',
    lastCameraAt: 0,

    async toggleCamera() {
        if (this.scanning) {
            await this.stopCamera();
            return;
        }
        await this.startCamera();
    },

    async startCamera() {
        this.error = '';

        if (cameraBlockedByInsecureOrigin()) {
            this.error = formatCameraStartError();
            this.scanning = false;

            return;
        }

        if (activeFieldBarcodeScanner && activeFieldBarcodeScanner !== this) {
            await activeFieldBarcodeScanner.stopCamera();
        }

        this.scanning = true;
        activeFieldBarcodeScanner = this;
        await this.$nextTick();

        try {
            this.scanner = await startBarcodeCamera(this.readerId, async (decoded) => {
                const now = Date.now();
                if (decoded === this.lastCameraCode && now - this.lastCameraAt < CAMERA_SAME_CODE_DEBOUNCE_MS) {
                    return;
                }
                this.lastCameraCode = decoded;
                this.lastCameraAt = now;
                this.applyDecoded(decoded);
                await this.stopCamera();
            });
        } catch (e) {
            this.error = formatCameraStartError(e);
            this.scanning = false;
            if (activeFieldBarcodeScanner === this) {
                activeFieldBarcodeScanner = null;
            }
        }
    },

    async stopCamera() {
        await stopBarcodeCamera(this.scanner);
        this.scanner = null;
        this.scanning = false;
        if (activeFieldBarcodeScanner === this) {
            activeFieldBarcodeScanner = null;
        }
    },

    applyDecoded(decoded) {
        const value = String(decoded || '').trim();
        if (!value || !this.fieldId) {
            return;
        }

        const input = document.getElementById(this.fieldId);
        if (!input) {
            return;
        }

        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        this.$dispatch('field-barcode-scanned', { field: this.fieldId, value });

        if (this.submitOnScan && input.form) {
            input.form.requestSubmit();
        }
    },

    async destroy() {
        await this.stopCamera();
    },
}));

/**
 * Shared inventory movement chart + action panel (assets stock / spare parts).
 *
 * @param {{
 *   chart?: object,
 *   initialAction?: string,
 *   openDetailId?: string|number|null,
 *   actionHints?: Record<string, string>,
 *   showAnnotationLabels?: boolean,
 *   showLegend?: boolean,
 * }} [config]
 */
function createInventoryChartPanel(config = {}) {
    const actionHints = config.actionHints && typeof config.actionHints === 'object'
        ? config.actionHints
        : {};
    const showAnnotationLabels = config.showAnnotationLabels !== false;
    const showLegend = config.showLegend !== false;

    return {
        chartPayload: config.chart || {},
        action: config.initialAction || 'count',
        selected: null,
        chart: null,

        get quantityHint() {
            return actionHints[this.action] || '';
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
                    ...(showAnnotationLabels
                        ? {
                            label: {
                                display: true,
                                content: 'Min',
                                position: 'start',
                            },
                        }
                        : {}),
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
                    ...(showAnnotationLabels
                        ? {
                            label: {
                                display: true,
                                content: 'Max',
                                position: 'start',
                            },
                        }
                        : {}),
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
                        legend: { display: showLegend },
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
    };
}

const ASSET_STOCK_ACTION_HINTS = {
    count: 'Absolute on-hand quantity after counting',
    restock: 'Units added from a restock / Momentous PO',
    breakage: 'Units removed due to breakage',
};

Alpine.data('inventoryChartPanel', createInventoryChartPanel);

/** Asset stock tab — chart + quantity hints for count/restock/breakage. */
Alpine.data('stockPanel', (config = {}) => createInventoryChartPanel({
    showAnnotationLabels: true,
    showLegend: true,
    ...config,
    actionHints: config.actionHints || ASSET_STOCK_ACTION_HINTS,
}));

/** Spare-part stock panel — same chart; no asset-stock quantity hints by default. */
Alpine.data('sparePartPanel', (config = {}) => createInventoryChartPanel({
    showAnnotationLabels: true,
    showLegend: true,
    ...config,
    actionHints: config.actionHints || {},
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
    localInterfaceId: config.old?.local_interface_id || '',
    peerAssetId: config.old?.peer_asset_id || '',
    peerInterfaceId: config.old?.peer_interface_id || '',
    peerInterfaces: [],
    peerLoading: false,
    createPeerInterface: Boolean(config.old?.create_peer_interface),
    newPeerInterfaceName: config.old?.new_peer_interface_name || '',
    allowsProtocolByType: config.allowsProtocolByType || {},
    allowsBusByType: config.allowsBusByType || {},
    busesByType: config.busesByType || {},
    peerInterfacesUrl: config.peerInterfacesUrl || '',
    localInterfaces: Array.isArray(config.localInterfaces) ? config.localInterfaces : [],
    peerAssets: Array.isArray(config.peerAssets) ? config.peerAssets : [],
    peerOpen: false,
    peerQuery: '',
    peerHighlight: -1,
    busLocalInterfaceId: config.old?.bus_local_interface_id || '',
    connectionBusId: config.old?.connection_bus_id || '',
    createNewBus: Boolean(config.old?.create_new_bus),
    newBusName: config.old?.new_bus_name || '',

    init() {
        if (this.peerAssetId) {
            this.loadPeerInterfaces({ preserveSelection: true });
        }
    },

    get selectedLocalTypeId() {
        const local = this.localInterfaces.find((iface) => String(iface.id) === String(this.localInterfaceId));
        return local?.connection_type_id ? String(local.connection_type_id) : '';
    },

    get selectedBusLocalTypeId() {
        const local = this.localInterfaces.find((iface) => String(iface.id) === String(this.busLocalInterfaceId));
        return local?.connection_type_id ? String(local.connection_type_id) : '';
    },

    get needsProtocol() {
        return Boolean(this.allowsProtocolByType[this.selectedLocalTypeId]);
    },

    get availableBuses() {
        const typeId = this.selectedBusLocalTypeId;
        if (!typeId) {
            return [];
        }

        const list = this.busesByType[typeId];
        return Array.isArray(list) ? list : [];
    },

    onBusLocalInterfaceChange() {
        this.connectionBusId = '';
        this.newBusName = '';
        this.createNewBus = this.availableBuses.length === 0;
    },

    onCreateNewBusToggle() {
        if (this.createNewBus) {
            this.connectionBusId = '';
        } else {
            this.newBusName = '';
        }
    },

    get peerSelectedLabel() {
        const opt = this.peerAssets.find((o) => String(o.value) === String(this.peerAssetId));
        return opt ? opt.label : 'Select…';
    },

    sortedPeerAssets() {
        const typeId = this.selectedLocalTypeId;

        return [...this.peerAssets]
            .map((option) => {
                const typeIds = Array.isArray(option.connection_type_ids)
                    ? option.connection_type_ids.map(String)
                    : [];
                const matches = typeId !== '' && typeIds.includes(typeId);

                return {
                    value: String(option.value),
                    label: String(option.label ?? ''),
                    connection_type_ids: typeIds,
                    matches_type: matches,
                };
            })
            .sort((a, b) => {
                if (a.matches_type !== b.matches_type) {
                    return a.matches_type ? -1 : 1;
                }

                return a.label.localeCompare(b.label);
            });
    },

    get filteredPeerAssets() {
        const options = this.sortedPeerAssets();
        const q = this.peerQuery.trim();

        if (!q) {
            return options;
        }

        return options
            .map((option) => ({ option, score: matchScore(option.label, q) }))
            .filter((entry) => entry.score !== Number.POSITIVE_INFINITY)
            .sort((a, b) => {
                if (a.option.matches_type !== b.option.matches_type) {
                    return a.option.matches_type ? -1 : 1;
                }

                return a.score - b.score || a.option.label.localeCompare(b.option.label);
            })
            .map((entry) => entry.option);
    },

    highlightedPeerLabel(label) {
        return highlightSearchTerm(label, this.peerQuery);
    },

    openPeerList() {
        this.peerOpen = true;
        this.peerHighlight = -1;
        this.$nextTick(() => this.$refs.peerSearch?.focus());
    },

    closePeerList() {
        this.peerOpen = false;
        this.peerQuery = '';
        this.peerHighlight = -1;
    },

    selectPeerAsset(option) {
        this.peerAssetId = option.value;
        this.closePeerList();
        this.createPeerInterface = false;
        this.newPeerInterfaceName = '';
        this.loadPeerInterfaces();
    },

    onPeerKeydown(event) {
        if (!this.peerOpen) {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.openPeerList();
            }
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            this.closePeerList();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.peerHighlight = Math.min(this.peerHighlight + 1, this.filteredPeerAssets.length - 1);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.peerHighlight = Math.max(this.peerHighlight - 1, 0);
            return;
        }

        if (event.key === 'Enter' && this.peerHighlight >= 0 && this.filteredPeerAssets[this.peerHighlight]) {
            event.preventDefault();
            this.selectPeerAsset(this.filteredPeerAssets[this.peerHighlight]);
        }
    },

    onLocalInterfaceChange() {
        if (this.peerAssetId) {
            this.loadPeerInterfaces();
        }
    },

    onCreatePeerToggle() {
        if (this.createPeerInterface) {
            this.peerInterfaceId = '';
        }
    },

    async loadPeerInterfaces({ preserveSelection = false } = {}) {
        const previous = preserveSelection ? this.peerInterfaceId : '';
        this.peerInterfaces = [];
        this.peerInterfaceId = '';

        if (!this.peerAssetId || !this.peerInterfacesUrl) {
            return;
        }

        this.peerLoading = true;
        let url = this.peerInterfacesUrl.replace('__PEER__', encodeURIComponent(this.peerAssetId));
        if (this.selectedLocalTypeId) {
            url += (url.includes('?') ? '&' : '?') + 'connection_type_id=' + encodeURIComponent(this.selectedLocalTypeId);
        }

        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json().catch(() => ({}));
            this.peerInterfaces = Array.isArray(data.interfaces) ? data.interfaces : [];

            const usable = this.peerInterfaces.filter(
                (iface) => iface.matches_type || !iface.connection_type_id
            );
            if (!this.createPeerInterface && usable.length === 0 && this.selectedLocalTypeId) {
                this.createPeerInterface = true;
            }

            if (previous && this.peerInterfaces.some((iface) => String(iface.id) === String(previous))) {
                this.peerInterfaceId = String(previous);
            }
        } catch (_) {
            this.peerInterfaces = [];
        } finally {
            this.peerLoading = false;
        }
    },
}));

const CONNECTION_MAP_DISPLAY_KEY = 'av_connection_map_display';
const CONNECTION_TYPE_ICON_PATHS = {
    network: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5.5 4.5h13v15h-13z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.25 8.25h7.5v5.5c0 1.1-.9 2-2 2h-3.5c-1.1 0-2-.9-2-2v-5.5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.4 8.5v2.5M11.15 8.5v2.5M12.85 8.5v2.5M14.6 8.5v2.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.5 15.75h3"/>',
    wireless: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.5 9.75a10.5 10.5 0 0115 0M7.25 12.75a6.5 6.5 0 019.5 0M10 15.75a2.75 2.75 0 014 0M12 18.25h.01"/>',
    hdmi: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 8.5h16v7H4zM8 15.5v2M16 15.5v2M7 11.5h2M11 11.5h2M15 11.5h2"/>',
    sdi: '<circle cx="12" cy="12" r="7.25" stroke-width="1.75"/><circle cx="12" cy="12" r="2.5" stroke-width="1.75"/>',
    dante: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4.5 12h3l2-4 3 8 2-4h5"/>',
    custom: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.5v3M12 16.5v3M4.5 12h3M16.5 12h3M7 7l2.1 2.1M14.9 14.9 17 17M17 7l-2.1 2.1M9.1 14.9 7 17"/>',
    analog: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 14c2-4 4-4 6 0s4 4 6 0 4-4 6 0"/>',
    xlr: '<circle cx="12" cy="12" r="8" stroke-width="1.75"/><circle cx="12" cy="8.25" r="1.35" stroke-width="1.5"/><circle cx="8.85" cy="13.75" r="1.35" stroke-width="1.5"/><circle cx="15.15" cy="13.75" r="1.35" stroke-width="1.5"/>',
    dmx: '<circle cx="12" cy="12" r="8" stroke-width="1.75"/><circle cx="12" cy="7.5" r="1.15" stroke-width="1.5"/><circle cx="8.2" cy="10.4" r="1.15" stroke-width="1.5"/><circle cx="15.8" cy="10.4" r="1.15" stroke-width="1.5"/><circle cx="9.4" cy="15.4" r="1.15" stroke-width="1.5"/><circle cx="14.6" cy="15.4" r="1.15" stroke-width="1.5"/>',
    power: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3.5v8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7.35 6.9a6.5 6.5 0 109.3 0"/>',
    usb: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 21V10.5M12 10.5 7.5 7M12 10.5l4.5-3.5M12 10.5V5"/><circle cx="7.5" cy="6.25" r="1.6" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.4 4.15h3.4v3.4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.6 3.4 12 2l1.4 1.4"/>',
    generic: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 12h8M12 8v8M5 5h14v14H5z"/>',
};

function connectionTypeIconDataUri(iconKey, stroke = '#ffffff') {
    const paths = CONNECTION_TYPE_ICON_PATHS[iconKey] || CONNECTION_TYPE_ICON_PATHS.generic;
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="${stroke}" viewBox="0 0 24 24">${paths}</svg>`;

    return `data:image/svg+xml;utf8,${encodeURIComponent(svg)}`;
}

function defaultConnectionMapDisplay() {
    return {
        mapName: true,
        mapItemType: false,
        mapLocation: false,
        mapRoomRack: false,
        mapSerialNumber: false,
        mapTpBarcode: false,
        mapTypeName: false,
        showEdgeLabels: true,
        showTypeIcons: true,
        popupItemType: true,
        popupLocation: true,
        popupTypeName: true,
        popupProtocol: true,
        popupConnectionLabel: true,
        popupInterfaces: true,
        popupNotes: true,
        popupLink: true,
    };
}

function loadConnectionMapDisplay() {
    const defaults = defaultConnectionMapDisplay();
    try {
        const raw = localStorage.getItem(CONNECTION_MAP_DISPLAY_KEY);
        if (!raw) {
            return defaults;
        }
        return { ...defaults, ...JSON.parse(raw) };
    } catch (_) {
        return defaults;
    }
}

Alpine.data('connectionMap', (graph = { nodes: [], edges: [], focus_asset_id: null }) => ({
    selection: null,
    cy: null,
    displayOpen: false,
    display: loadConnectionMapDisplay(),
    _onResize: null,

    layoutOptions(elements) {
        const focusId = graph.focus_asset_id ? Number(graph.focus_asset_id) : null;
        const rootSelector = focusId ? `#asset-${focusId}` : null;
        const hasFocusRoot = rootSelector
            && (graph.nodes || []).some((node) => (
                Number(node?.data?.asset_id) === focusId && node?.data?.kind === 'asset'
            ));

        if (hasFocusRoot) {
            return {
                name: 'breadthfirst',
                directed: false,
                roots: rootSelector,
                padding: 48,
                spacingFactor: 1.85,
                animate: false,
                avoidOverlap: true,
                nodeDimensionsIncludeLabels: true,
                grid: false,
            };
        }

        // Prefer cose over circle so multi-edge graphs fan out instead of stacking
        // chords across a ring. nestingFactor keeps compound children from drifting.
        return {
            name: 'cose',
            animate: false,
            padding: 48,
            nestingFactor: 0.08,
            nodeRepulsion: (node) => (node.isParent() ? 14000 : 7000),
            idealEdgeLength: () => 150,
            edgeElasticity: () => 90,
            gravity: 0.35,
            numIter: 1600,
            initialTemp: 220,
            coolingFactor: 0.95,
            minTemp: 1.0,
            componentSpacing: 120,
            nodeDimensionsIncludeLabels: true,
            randomize: elements.length > 40,
        };
    },

    /**
     * Pack type-port children tightly inside compound assets and face each port
     * toward its connected peers so edges leave in different directions.
     */
    packCompoundGroups() {
        if (!this.cy) {
            return;
        }

        this.cy.batch(() => {
            this.cy.nodes(':parent').forEach((parent) => {
                const children = parent.children();
                if (children.empty()) {
                    return;
                }

                const parentPos = { ...parent.position() };
                const items = children.toArray().map((child) => {
                    let dx = 0;
                    let dy = 0;
                    let links = 0;

                    child.connectedEdges().forEach((edge) => {
                        const other = edge.otherEnd(child);
                        if (other.same(parent) || other.parent().same(parent)) {
                            return;
                        }

                        const op = other.position();
                        dx += op.x - parentPos.x;
                        dy += op.y - parentPos.y;
                        links += 1;
                    });

                    return {
                        child,
                        angle: links > 0 ? Math.atan2(dy, dx) : null,
                    };
                });

                const linked = items
                    .filter((item) => item.angle !== null)
                    .sort((a, b) => a.angle - b.angle);
                const ordered = [
                    ...linked,
                    ...items.filter((item) => item.angle === null),
                ];

                const n = ordered.length;
                const portSize = 24;
                const gap = 6;

                let sx = 0;
                let sy = 0;
                linked.forEach((item) => {
                    sx += Math.cos(item.angle);
                    sy += Math.sin(item.angle);
                });
                const meanAngle = linked.length > 0 ? Math.atan2(sy, sx) : -Math.PI / 2;

                // If peers sit in one cone, pack ports on a facing arc; otherwise a full ring.
                let sweep = 2 * Math.PI;
                if (linked.length >= 2) {
                    const deltas = linked.map((item) => {
                        let delta = item.angle - meanAngle;
                        while (delta > Math.PI) {
                            delta -= 2 * Math.PI;
                        }
                        while (delta < -Math.PI) {
                            delta += 2 * Math.PI;
                        }
                        return delta;
                    });
                    const span = Math.max(...deltas) - Math.min(...deltas);
                    if (span <= Math.PI) {
                        const minSweep = (n - 1) * (Math.PI / Math.max(n, 2));
                        sweep = Math.max(minSweep, Math.min(Math.PI * 0.9, span + Math.PI / 6));
                    }
                }

                const step = n <= 1 ? 0 : sweep / n;
                const radius = n <= 1
                    ? 0
                    : (portSize + gap) / (2 * Math.sin(Math.max(step, 0.01) / 2));
                const startAngle = meanAngle - sweep / 2 + step / 2;

                ordered.forEach((item, index) => {
                    const angle = n === 1 ? meanAngle : startAngle + index * step;
                    item.child.position({
                        x: parentPos.x + radius * Math.cos(angle),
                        y: parentPos.y + radius * Math.sin(angle),
                    });
                });
            });
        });
    },

    /**
     * Nudge nodes that share nearly-collinear multi-hop edges so bezier strokes
     * do not sit on top of each other.
     */
    untangleEdgeOverlays() {
        if (!this.cy || this.cy.edges().length < 2) {
            return;
        }

        const edges = this.cy.edges().toArray();
        const shifts = new Map();

        const segmentKey = (a, b) => {
            const ids = [a.id(), b.id()].sort();
            return `${ids[0]}|${ids[1]}`;
        };

        // Group edges that share the same pair of top-level anchors (parallel / stacked).
        const byPair = new Map();
        edges.forEach((edge) => {
            let source = edge.source();
            let target = edge.target();
            if (source.isChild()) {
                source = source.parent();
            }
            if (target.isChild()) {
                target = target.parent();
            }
            if (source.same(target)) {
                return;
            }
            const key = segmentKey(source, target);
            if (!byPair.has(key)) {
                byPair.set(key, []);
            }
            byPair.get(key).push({ edge, source, target });
        });

        byPair.forEach((group) => {
            if (group.length < 2) {
                return;
            }

            group.forEach((item, index) => {
                const offsetIndex = index - (group.length - 1) / 2;
                if (offsetIndex === 0) {
                    return;
                }

                const sp = item.source.position();
                const tp = item.target.position();
                const dx = tp.x - sp.x;
                const dy = tp.y - sp.y;
                const len = Math.hypot(dx, dy) || 1;
                const nx = -dy / len;
                const ny = dx / len;
                const amount = offsetIndex * 18;

                [item.source, item.target].forEach((node) => {
                    if (node.isParent() || node.data('kind') === 'type-port') {
                        return;
                    }
                    const current = shifts.get(node.id()) || { x: 0, y: 0 };
                    shifts.set(node.id(), {
                        x: current.x + nx * amount * 0.35,
                        y: current.y + ny * amount * 0.35,
                    });
                });
            });
        });

        if (shifts.size === 0) {
            return;
        }

        this.cy.batch(() => {
            shifts.forEach((delta, id) => {
                const node = this.cy.$id(id);
                if (!node.nonempty()) {
                    return;
                }
                const pos = node.position();
                node.position({
                    x: pos.x + delta.x,
                    y: pos.y + delta.y,
                });
            });
        });
    },

    runGraphLayout() {
        if (!this.cy) {
            return;
        }

        const elements = this.cy.elements();
        const layout = this.cy.layout(this.layoutOptions(elements));
        layout.on('layoutstop', () => {
            this.packCompoundGroups();
            this.untangleEdgeOverlays();
            this.packCompoundGroups();

            const focusId = graph.focus_asset_id ? Number(graph.focus_asset_id) : null;
            const focusNode = focusId ? this.cy.$id(`asset-${focusId}`) : null;
            if (focusNode && focusNode.nonempty()) {
                this.cy.center(focusNode);
            }
            this.cy.fit(undefined, 48);
        });
        layout.run();
    },

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
                        label: 'data(map_label)',
                        'background-color': 'data(color)',
                        color: '#111827',
                        'text-valign': 'bottom',
                        'text-halign': 'center',
                        'font-size': 10,
                        'text-margin-y': 6,
                        'text-wrap': 'wrap',
                        'text-max-width': 90,
                        width: 28,
                        height: 28,
                        'background-fit': 'contain',
                        'background-clip': 'node',
                        'background-width': '100%',
                        'background-height': '100%',
                        'background-position-x': '50%',
                        'background-position-y': '50%',
                    },
                },
                {
                    selector: 'node:parent',
                    style: {
                        label: 'data(map_label)',
                        'background-color': '#e5e7eb',
                        'background-opacity': 0.55,
                        'border-width': 1.5,
                        'border-color': '#6b7280',
                        'text-valign': 'top',
                        'text-halign': 'center',
                        'text-margin-y': -6,
                        padding: 6,
                        shape: 'roundrectangle',
                        color: '#111827',
                        'font-size': 11,
                        'font-weight': '600',
                        'background-image': 'none',
                    },
                },
                {
                    selector: 'node.type-port',
                    style: {
                        'background-color': 'data(color)',
                        width: 24,
                        height: 24,
                        'font-size': 8,
                        'text-max-width': 56,
                    },
                },
                {
                    selector: 'node.bus',
                    style: {
                        shape: 'roundrectangle',
                        width: 40,
                        height: 28,
                        'font-size': 9,
                        'font-weight': '600',
                        'text-max-width': 100,
                        'border-width': 2,
                        'border-color': '#111827',
                        'border-style': 'dashed',
                    },
                },
                {
                    selector: 'node.focus',
                    style: {
                        'border-width': 3,
                        'border-color': '#111827',
                        width: 36,
                        height: 36,
                        'font-size': 11,
                        'font-weight': 'bold',
                    },
                },
                {
                    selector: 'node:parent.focus',
                    style: {
                        'border-width': 3,
                        'border-color': '#111827',
                        'background-color': '#d1d5db',
                    },
                },
                {
                    selector: 'node.selected',
                    style: {
                        width: 42,
                        height: 42,
                        'font-size': 11,
                        'font-weight': 'bold',
                        'border-width': 3,
                        'border-color': '#111827',
                        'text-margin-y': 8,
                    },
                },
                {
                    selector: 'node:parent.selected',
                    style: {
                        'border-width': 3,
                        'border-color': '#007a82',
                        'background-color': '#cfd8dc',
                        'background-opacity': 0.7,
                    },
                },
                {
                    selector: 'node.type-port.selected',
                    style: {
                        width: 30,
                        height: 30,
                        'border-width': 3,
                        'border-color': '#111827',
                    },
                },
                {
                    selector: 'edge',
                    style: {
                        width: 3,
                        'line-color': 'data(color)',
                        'target-arrow-color': 'data(color)',
                        'curve-style': 'bezier',
                        'control-point-step-size': 40,
                        'control-point-weight': 0.5,
                        'edge-distances': 'node-position',
                        label: 'data(edge_label)',
                        'font-size': 9,
                        color: '#4b5563',
                        'text-rotation': 'autorotate',
                        'text-background-color': '#ffffff',
                        'text-background-opacity': 0.75,
                        'text-background-padding': 2,
                    },
                },
            ],
            layout: { name: 'null' },
        });

        this.applyDisplayOptions();
        this.runGraphLayout();

        this.cy.on('tap', 'node', (event) => {
            const data = event.target.data();
            this.selection = {
                ...data,
                kind: 'node',
                node_kind: data.kind || 'asset',
            };
        });

        this.cy.on('tap', 'edge', (event) => {
            const data = event.target.data();
            this.selection = {
                ...data,
                kind: 'edge',
            };
        });

        this.cy.on('tap', (event) => {
            if (event.target === this.cy) {
                this.selection = null;
            }
        });

        this.$watch('selection', () => this.syncSelectedNode());
        this.$watch('display', () => {
            this.persistDisplayOptions();
            this.applyDisplayOptions();
        }, { deep: true });

        this._onResize = () => {
            if (!this.cy) {
                return;
            }
            this.cy.resize();
        };
        window.addEventListener('resize', this._onResize);
        requestAnimationFrame(() => {
            this._onResize();
        });
    },

    mapLabelForNode(data) {
        if (data.kind === 'type-port') {
            if (this.display.mapTypeName && data.type_name) {
                return data.type_name;
            }
            return '';
        }

        if (data.kind === 'bus') {
            const parts = [];
            if (data.name) {
                parts.push(data.name);
            }
            if (this.display.mapTypeName && data.type_name) {
                parts.push(data.type_name);
            }
            return parts.join('\n');
        }

        const parts = [];
        if (this.display.mapName && data.name) {
            parts.push(data.name);
        }
        if (this.display.mapItemType && data.item_type) {
            parts.push(data.item_type);
        }
        if (this.display.mapLocation && data.location) {
            parts.push(data.location);
        }
        if (this.display.mapRoomRack && data.room_rack) {
            parts.push(data.room_rack);
        }
        if (this.display.mapSerialNumber && data.serial_number) {
            parts.push(data.serial_number);
        }
        if (this.display.mapTpBarcode && data.tp_barcode) {
            parts.push(data.tp_barcode);
        }
        if (this.display.mapTypeName && data.type_name && !data.compound) {
            parts.push(data.type_name);
        }

        return parts.join('\n');
    },

    applyDisplayOptions() {
        if (!this.cy) {
            return;
        }

        this.cy.batch(() => {
            this.cy.nodes().forEach((node) => {
                const data = node.data();
                node.data('map_label', this.mapLabelForNode(data));

                const canShowIcon = this.display.showTypeIcons
                    && data.kind !== undefined
                    && (data.kind === 'type-port' || data.kind === 'bus' || (data.kind === 'asset' && !data.compound));

                if (canShowIcon && data.icon) {
                    node.data('icon_url', connectionTypeIconDataUri(data.icon));
                    node.style('background-image', node.data('icon_url'));
                } else {
                    node.style('background-image', 'none');
                }
            });

            this.cy.edges().forEach((edge) => {
                edge.data(
                    'edge_label',
                    this.display.showEdgeLabels ? (edge.data('label') || '') : '',
                );
            });
        });
    },

    persistDisplayOptions() {
        try {
            localStorage.setItem(CONNECTION_MAP_DISPLAY_KEY, JSON.stringify(this.display));
        } catch (_) {
            // Ignore quota / private mode failures.
        }
    },

    syncSelectedNode() {
        if (!this.cy) {
            return;
        }

        this.cy.nodes().removeClass('selected');

        if (this.selection?.kind !== 'node' || !this.selection.id) {
            return;
        }

        const node = this.cy.$id(String(this.selection.id));
        if (!node.nonempty()) {
            return;
        }

        node.addClass('selected');

        const parentId = node.data('parent');
        if (parentId) {
            const parent = this.cy.$id(String(parentId));
            if (parent.nonempty()) {
                parent.addClass('selected');
            }
        }
    },

    destroy() {
        if (this._onResize) {
            window.removeEventListener('resize', this._onResize);
            this._onResize = null;
        }
        if (this.cy) {
            this.cy.destroy();
            this.cy = null;
        }
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

Alpine.data('dashboardModules', (initialModules = []) => ({
    open: false,
    modules: (initialModules || []).map((module) => ({
        id: module.id,
        label: module.label,
        enabled: Boolean(module.enabled),
    })),

    moveUp(index) {
        if (index <= 0) {
            return;
        }

        const modules = [...this.modules];
        const previous = modules[index - 1];
        modules[index - 1] = modules[index];
        modules[index] = previous;
        this.modules = modules;
    },

    moveDown(index) {
        if (index >= this.modules.length - 1) {
            return;
        }

        const modules = [...this.modules];
        const next = modules[index + 1];
        modules[index + 1] = modules[index];
        modules[index] = next;
        this.modules = modules;
    },
}));

/**
 * Shared bulk checkbox selection + optional delete/merge/tree collapse.
 *
 * @param {{
 *   idAttribute?: string,
 *   nameAttribute?: string|null,
 *   fields?: Record<string, boolean>,
 *   enableMerge?: boolean,
 *   enableTreeCollapse?: boolean,
 *   collapsibleAttribute?: string,
 *   collapsibleIdAttribute?: string,
 *   hideRacksAttribute?: string,
 *   hideRacksIdAttribute?: string,
 * }} [config]
 */
function createBulkSelection(config = {}) {
    const idAttribute = config.idAttribute || 'data-bulk-id';
    const nameAttribute = config.nameAttribute || null;
    const labelAttribute = config.labelAttribute || 'data-bulk-asset-label';
    const enableMerge = Boolean(config.enableMerge);
    const enableTreeCollapse = Boolean(config.enableTreeCollapse);
    const collapsibleAttribute = config.collapsibleAttribute || 'data-location-collapsible';
    const collapsibleIdAttribute = config.collapsibleIdAttribute || 'data-location-id';
    const hideRacksAttribute = config.hideRacksAttribute || 'data-location-has-racks';
    const hideRacksIdAttribute = config.hideRacksIdAttribute || 'data-location-id';
    const initialFields = config.fields && typeof config.fields === 'object'
        ? { ...config.fields }
        : {};

    return {
        selected: {},
        panelOpen: false,
        deleteConfirmOpen: false,
        mergeConfirmOpen: false,
        keepAssetId: null,
        pendingDelete: false,
        pendingMerge: false,
        collapsed: {},
        fields: initialFields,
        enableMerge,
        enableTreeCollapse,

        isOpen(id) {
            return !this.collapsed[id];
        },

        toggle(id) {
            if (!this.enableTreeCollapse) {
                return;
            }
            this.collapsed = { ...this.collapsed, [id]: !this.collapsed[id] };
        },

        expandAll() {
            this.collapsed = {};
        },

        collapseAll() {
            if (!this.enableTreeCollapse) {
                return;
            }
            const next = {};
            this.collapsibleIds().forEach((id) => {
                next[id] = true;
            });
            this.collapsed = next;
        },

        hideRacks() {
            if (!this.enableTreeCollapse) {
                return;
            }
            const next = { ...this.collapsed };
            this.$root.querySelectorAll(`[${hideRacksAttribute}="1"]`).forEach((el) => {
                next[Number(el.getAttribute(hideRacksIdAttribute))] = true;
            });
            this.collapsed = next;
        },

        collapsibleIds() {
            return [...this.$root.querySelectorAll(`[${collapsibleAttribute}="1"]`)].map((el) =>
                Number(el.getAttribute(collapsibleIdAttribute))
            );
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
            // Use $root: $el is the event target when called from child @change handlers.
            return [...this.$root.querySelectorAll(`[${idAttribute}]`)].map((el) =>
                Number(el.getAttribute(idAttribute))
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
            if (!nameAttribute) {
                return;
            }
            const normalized = new Set(
                (names || []).map((name) => String(name).trim().toLowerCase())
            );
            const next = {};
            [...this.$root.querySelectorAll(`[${idAttribute}]`)].forEach((el) => {
                const name = (el.getAttribute(nameAttribute) || '').trim().toLowerCase();
                if (normalized.has(name)) {
                    next[Number(el.getAttribute(idAttribute))] = true;
                }
            });
            this.selected = next;
            this.panelOpen = true;
        },

        clearSelection() {
            this.selected = {};
            this.panelOpen = false;
            this.deleteConfirmOpen = false;
            this.mergeConfirmOpen = false;
            this.keepAssetId = null;
            this.pendingDelete = false;
            this.pendingMerge = false;
        },

        selectedMergeOptions() {
            return this.selectedIds.map((id) => {
                const el = this.$root.querySelector(`[${idAttribute}="${id}"]`);
                const label = el?.getAttribute(labelAttribute) || `Asset #${id}`;

                return { id, label };
            });
        },

        openMergeConfirm() {
            if (this.selectedCount < 2) {
                return;
            }

            this.pendingDelete = false;
            this.pendingMerge = false;
            this.keepAssetId = this.selectedIds[0] ?? null;
            this.mergeConfirmOpen = true;
        },

        cancelMerge() {
            this.mergeConfirmOpen = false;
            this.pendingMerge = false;
            this.keepAssetId = null;
        },

        confirmMerge() {
            if (this.selectedCount < 2 || !this.keepAssetId) {
                return;
            }

            this.pendingMerge = true;
            this.pendingDelete = false;
            this.mergeConfirmOpen = false;

            this.$nextTick(() => {
                const form = this.$refs.bulkForm;
                if (!form) {
                    return;
                }

                let keepInput = form.querySelector('input[name="keep_asset_id"]');
                if (!keepInput) {
                    keepInput = document.createElement('input');
                    keepInput.type = 'hidden';
                    keepInput.name = 'keep_asset_id';
                    form.appendChild(keepInput);
                }
                keepInput.value = String(this.keepAssetId);

                let confirmInput = form.querySelector('input[name="confirm_merge"]');
                if (!confirmInput) {
                    confirmInput = document.createElement('input');
                    confirmInput.type = 'hidden';
                    confirmInput.name = 'confirm_merge';
                    form.appendChild(confirmInput);
                }
                confirmInput.value = '1';

                form.requestSubmit();
            });
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
            if (!this.enableMerge || this.selectedCount < 2) {
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
    };
}

const BULK_ASSET_FIELDS = {
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
};

const BULK_LOCATION_FIELDS = {
    update_parent: false,
    clear_parent: false,
};

Alpine.data('bulkSelection', createBulkSelection);

/** Assets index / list show — flat page selection + asset field toggles. */
Alpine.data('bulkAssets', (config = {}) => createBulkSelection({
    idAttribute: 'data-bulk-asset-id',
    labelAttribute: 'data-bulk-asset-label',
    fields: BULK_ASSET_FIELDS,
    enableMerge: true,
    enableTreeCollapse: false,
    ...config,
}));

/** Locations tree — selection + merge duplicates + collapse/hide racks. */
Alpine.data('bulkLocations', (config = {}) => createBulkSelection({
    idAttribute: 'data-bulk-location-id',
    nameAttribute: 'data-bulk-location-name',
    fields: BULK_LOCATION_FIELDS,
    enableMerge: true,
    enableTreeCollapse: true,
    ...config,
}));

Alpine.data('manufacturerCatalogue', () => ({
    collapsed: {},

    isOpen(id) {
        return !this.collapsed[id];
    },

    toggle(id) {
        this.collapsed = { ...this.collapsed, [id]: !this.collapsed[id] };
    },

    expandAll() {
        this.collapsed = {};
    },

    collapseAll() {
        const next = {};
        this.collapsibleIds().forEach((id) => {
            next[id] = true;
        });
        this.collapsed = next;
    },

    collapsibleIds() {
        return [...this.$root.querySelectorAll('[data-manufacturer-collapsible="1"]')].map((el) =>
            Number(el.getAttribute('data-manufacturer-id'))
        );
    },
}));

Alpine.data('shelfTagDesigner', (initial = {}, presets = {}, fieldOptions = {}) => ({
    name: initial.name || '',
    paperPreset: initial.paper_preset || 'continuous_62',
    widthMm: Number(initial.width_mm) || 62,
    heightMm: Number(initial.height_mm) || 40,
    orientation: initial.orientation || 'portrait',
    lengthMode: initial.length_mode === 'auto' ? 'auto' : 'fixed',
    elements: Array.isArray(initial.elements) ? initial.elements.map((el) => ({
        ...el,
        props: { ...(el.props || {}) },
    })) : [],
    logoUrl: initial.logo_url || null,
    presets,
    fieldOptions,
    selectedIds: [],
    scale: 4,
    viewZoom: 1,
    panX: 0,
    panY: 0,
    drag: null,
    resize: null,
    pan: null,
    marquee: null,
    spaceDown: false,
    alignTarget: 'group',
    showGrid: initial.editor_settings?.show_grid ?? true,
    snapToGrid: initial.editor_settings?.snap_to_grid ?? false,
    gridSizeMm: Number(initial.editor_settings?.grid_size_mm) || 5,
    autoPaddingMm: 2,
    continuousMinMm: 29,
    continuousMaxMm: 300,
    viewReady: false,

    init() {
        const allowed = [2.5, 5, 10];
        if (!allowed.includes(Number(this.gridSizeMm))) {
            this.gridSizeMm = 5;
        }
        this.$watch('orientation', () => {
            this.$nextTick(() => {
                this.centerView();
            });
        });
        this.$nextTick(() => {
            this.centerView();
            this.viewReady = true;
            if (this.lengthMode === 'auto' && this.isContinuous) {
                this.syncAutoHeight();
            }
        });
    },

    get selected() {
        if (this.selectedIds.length !== 1) {
            return null;
        }
        return this.elements.find((el) => el.id === this.selectedIds[0]) || null;
    },

    get selectedElements() {
        const set = new Set(this.selectedIds);
        return this.elements.filter((el) => set.has(el.id));
    },

    get selectedCount() {
        return this.selectedIds.length;
    },

    get canAlignRelative() {
        return this.selectedCount >= 2;
    },

    get canAlignPage() {
        return this.selectedCount >= 1;
    },

    get canAlign() {
        return this.alignTarget === 'page' ? this.canAlignPage : this.canAlignRelative;
    },

    get sortedElements() {
        return [...this.elements].sort((a, b) => (a.z || 0) - (b.z || 0));
    },

    get elementsJson() {
        return JSON.stringify(this.elements);
    },

    get editorSettingsJson() {
        return JSON.stringify({
            show_grid: Boolean(this.showGrid),
            snap_to_grid: Boolean(this.snapToGrid),
            grid_size_mm: Number(this.gridSizeMm) || 5,
        });
    },

    get presetNotes() {
        return this.presets[this.paperPreset]?.notes || '';
    },

    get isContinuous() {
        return Boolean(this.presets[this.paperPreset]?.continuous);
    },

    get zoomPercent() {
        return Math.round(this.viewZoom * 100);
    },

    get effectiveScale() {
        return this.scale * this.viewZoom;
    },

    get isLandscape() {
        return this.orientation === 'landscape';
    },

    /** Visual canvas width in mm (length along feed when landscape). */
    get displayWidthMm() {
        return this.isLandscape ? this.heightMm : this.widthMm;
    },

    /** Visual canvas height in mm (tape width when landscape). */
    get displayHeightMm() {
        return this.isLandscape ? this.widthMm : this.heightMm;
    },

    get canvasStyle() {
        const cssW = `${this.displayWidthMm * this.scale}px`;
        const cssH = `${this.displayHeightMm * this.scale}px`;

        return {
            width: cssW,
            height: cssH,
        };
    },

    /**
     * Label content plane matches the visible canvas. Landscape only swaps aspect —
     * elements keep upright positions (no CSS rotate of the whole plane).
     */
    get labelContentStyle() {
        const s = this.scale;
        const style = {
            width: `${this.displayWidthMm * s}px`,
            height: `${this.displayHeightMm * s}px`,
            position: 'absolute',
            top: '0',
            left: '0',
        };

        return style;
    },

    get viewportTransformStyle() {
        return {
            transform: `translate(${this.panX}px, ${this.panY}px) scale(${this.viewZoom})`,
            transformOrigin: '0 0',
        };
    },

    get viewportCursor() {
        if (this.pan || this.spaceDown) {
            return this.pan ? 'grabbing' : 'grab';
        }
        return 'default';
    },

    get gridOverlayStyle() {
        const stepPx = Math.max(0.5, Number(this.gridSizeMm) || 5) * this.scale;
        const centerX = (this.displayWidthMm / 2) * this.scale;
        const centerY = (this.displayHeightMm / 2) * this.scale;
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
        return { left: `${(this.displayWidthMm / 2) * this.scale}px` };
    },

    get centerCrosshairStyleY() {
        return { top: `${(this.displayHeightMm / 2) * this.scale}px` };
    },

    get marqueeStyle() {
        if (!this.marquee) {
            return { display: 'none' };
        }
        const x = Math.min(this.marquee.x0, this.marquee.x1);
        const y = Math.min(this.marquee.y0, this.marquee.y1);
        const w = Math.abs(this.marquee.x1 - this.marquee.x0);
        const h = Math.abs(this.marquee.y1 - this.marquee.y0);
        return {
            left: `${x * this.scale}px`,
            top: `${y * this.scale}px`,
            width: `${w * this.scale}px`,
            height: `${h * this.scale}px`,
        };
    },

    isSelected(el) {
        return this.selectedIds.includes(el.id);
    },

    snapToCenterGrid(value, sizeMm) {
        const step = Math.max(0.5, Number(this.gridSizeMm) || 5);
        const center = sizeMm / 2;
        const snapped = center + Math.round((value - center) / step) * step;
        return Math.round(snapped * 1000) / 1000;
    },

    roundMm(value) {
        return Math.round(value * 10) / 10;
    },

    clampContinuousHeight(heightMm) {
        return this.roundMm(Math.min(this.continuousMaxMm, Math.max(this.continuousMinMm, heightMm)));
    },

    contentBottomMm() {
        if (!this.elements.length) {
            return this.continuousMinMm;
        }
        return Math.max(...this.elements.map((el) => Number(el.y) + Number(el.height)));
    },

    contentRightMm() {
        if (!this.elements.length) {
            return this.continuousMinMm;
        }
        return Math.max(...this.elements.map((el) => Number(el.x) + Number(el.width)));
    },

    /** Continuous feed length: X in landscape (visual width), Y in portrait (visual height). */
    contentFeedExtentMm() {
        return this.isLandscape ? this.contentRightMm() : this.contentBottomMm();
    },

    syncAutoHeight() {
        if (!this.isContinuous || this.lengthMode !== 'auto') {
            return;
        }
        const needed = this.elements.length
            ? this.contentFeedExtentMm() + this.autoPaddingMm
            : this.continuousMinMm;
        this.heightMm = this.clampContinuousHeight(needed);
    },

    maybeExtendAlongFeed(extentMm) {
        if (!this.isContinuous) {
            return;
        }
        if (this.lengthMode === 'auto') {
            this.syncAutoHeight();
            return;
        }
        if (extentMm > this.heightMm) {
            this.heightMm = this.clampContinuousHeight(extentMm);
        }
    },

    afterGeometryChange(els = null) {
        const list = els && els.length ? els : this.elements;
        const bottom = list.length
            ? Math.max(...list.map((el) => Number(el.y) + Number(el.height)))
            : 0;
        const right = list.length
            ? Math.max(...list.map((el) => Number(el.x) + Number(el.width)))
            : 0;
        const extent = this.isLandscape ? right : bottom;
        this.maybeExtendAlongFeed(extent);
        if (this.lengthMode === 'auto') {
            this.syncAutoHeight();
        }
    },

    applyCoord(value, axisSize) {
        if (this.snapToGrid) {
            return Math.max(0, this.snapToCenterGrid(value, axisSize));
        }
        return Math.max(0, this.roundMm(value));
    },

    syncFormFields() {
        if (this.$refs.elementsInput) {
            this.$refs.elementsInput.value = this.elementsJson;
        }
        if (this.$refs.editorSettingsInput) {
            this.$refs.editorSettingsInput.value = this.editorSettingsJson;
        }
    },

    syncElementsJson() {
        this.syncFormFields();
    },

    centerView() {
        const vp = this.$refs.viewport;
        if (!vp) {
            return;
        }
        const cw = this.displayWidthMm * this.scale * this.viewZoom;
        const ch = this.displayHeightMm * this.scale * this.viewZoom;
        this.panX = (vp.clientWidth - cw) / 2;
        this.panY = Math.max(16, (vp.clientHeight - ch) / 2);
    },

    setZoom(nextZoom, focusX = null, focusY = null) {
        const vp = this.$refs.viewport;
        const oldZoom = this.viewZoom;
        const zoom = Math.min(4, Math.max(0.25, nextZoom));
        if (!vp || focusX === null || focusY === null) {
            this.viewZoom = zoom;
            return;
        }
        const worldX = (focusX - this.panX) / oldZoom;
        const worldY = (focusY - this.panY) / oldZoom;
        this.viewZoom = zoom;
        this.panX = focusX - worldX * zoom;
        this.panY = focusY - worldY * zoom;
    },

    zoomBy(factor) {
        const vp = this.$refs.viewport;
        if (!vp) {
            this.setZoom(this.viewZoom * factor);
            return;
        }
        this.setZoom(this.viewZoom * factor, vp.clientWidth / 2, vp.clientHeight / 2);
    },

    zoomIn() {
        this.zoomBy(1.15);
    },

    zoomOut() {
        this.zoomBy(1 / 1.15);
    },

    resetZoom() {
        this.viewZoom = 1;
        this.$nextTick(() => this.centerView());
    },

    onViewportWheel(event) {
        if (!event.ctrlKey && !event.metaKey) {
            return;
        }
        event.preventDefault();
        const vp = this.$refs.viewport;
        if (!vp) {
            return;
        }
        const rect = vp.getBoundingClientRect();
        const factor = event.deltaY > 0 ? 1 / 1.1 : 1.1;
        this.setZoom(this.viewZoom * factor, event.clientX - rect.left, event.clientY - rect.top);
    },

    onKeyDown(event) {
        if (event.code === 'Space' && !event.repeat && !this.isTypingTarget(event.target)) {
            this.spaceDown = true;
            event.preventDefault();
        }
    },

    onKeyUp(event) {
        if (event.code === 'Space') {
            this.spaceDown = false;
        }
    },

    isTypingTarget(target) {
        if (!target || !target.tagName) {
            return false;
        }
        const tag = target.tagName.toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable;
    },

    onPresetChange() {
        const preset = this.presets[this.paperPreset];
        if (!preset) {
            return;
        }
        this.widthMm = Number(preset.width_mm) || this.widthMm;
        if (!preset.continuous) {
            this.lengthMode = 'fixed';
            if (preset.height_mm) {
                this.heightMm = Number(preset.height_mm);
            }
        } else if (this.lengthMode === 'auto') {
            this.syncAutoHeight();
        }
        this.$nextTick(() => this.centerView());
    },

    onLengthModeChange() {
        if (!this.isContinuous) {
            this.lengthMode = 'fixed';
            return;
        }
        if (this.lengthMode === 'auto') {
            this.syncAutoHeight();
        }
    },

    onHeightManualEdit() {
        if (!this.isContinuous) {
            return;
        }
        this.lengthMode = 'fixed';
        this.heightMm = this.clampContinuousHeight(Number(this.heightMm) || this.continuousMinMm);
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
        this.selectedIds = [el.id];
        this.afterGeometryChange([el]);
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
        if (!this.selectedIds.length) {
            return;
        }
        const remove = new Set(this.selectedIds);
        this.elements = this.elements.filter((el) => !remove.has(el.id));
        this.selectedIds = [];
        this.afterGeometryChange();
    },

    nudgeZ(delta) {
        if (!this.selected) {
            return;
        }
        this.selected.z = Math.max(0, (this.selected.z || 0) + delta);
    },

    clearSelection() {
        this.selectedIds = [];
    },

    toggleSelect(id) {
        if (this.selectedIds.includes(id)) {
            this.selectedIds = this.selectedIds.filter((item) => item !== id);
        } else {
            this.selectedIds = [...this.selectedIds, id];
        }
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
            await renderBarcodeSvgFit(node, el.props.fieldKey || 'SAMPLE', {
                symbology: el.props.symbology || 'code128',
                showText: Boolean(el.props.showText),
                boxW: Math.max(16, el.width * this.scale),
                boxH: Math.max(16, el.height * this.scale),
            });
        } catch (_) {}
    },

    async renderQr(el, node) {
        if (!node) {
            return;
        }
        try {
            const QRCode = await loadQRCode();
            const size = Math.max(16, Math.floor(Math.min(el.width, el.height) * this.scale));
            node.style.width = `${size}px`;
            node.style.height = `${size}px`;
            node.style.maxWidth = '100%';
            node.style.maxHeight = '100%';
            await QRCode.toCanvas(node, el.props.fieldKey || el.props.content || 'SAMPLE', {
                width: size,
                margin: 0,
            });
        } catch (_) {}
    },

    canvasPointFromEvent(event) {
        const canvas = this.$refs.canvas;
        if (!canvas) {
            return { x: 0, y: 0 };
        }
        const rect = canvas.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) {
            return { x: 0, y: 0 };
        }
        return {
            x: ((event.clientX - rect.left) / rect.width) * this.displayWidthMm,
            y: ((event.clientY - rect.top) / rect.height) * this.displayHeightMm,
        };
    },

    bindPointerSession(onMove, onUp) {
        const move = (e) => onMove(e);
        const up = (e) => {
            onUp(e);
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
        };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);
    },

    onViewportPointerDown(event) {
        if (event.button === 1 || this.spaceDown || (event.button === 0 && event.target === this.$refs.viewport)) {
            event.preventDefault();
            this.startPan(event);
        }
    },

    startPan(event) {
        this.pan = {
            startX: event.clientX,
            startY: event.clientY,
            origX: this.panX,
            origY: this.panY,
        };
        this.bindPointerSession(
            (e) => {
                if (!this.pan) return;
                this.panX = this.pan.origX + (e.clientX - this.pan.startX);
                this.panY = this.pan.origY + (e.clientY - this.pan.startY);
            },
            () => {
                this.pan = null;
            }
        );
    },

    onCanvasPointerDown(event) {
        if (this.spaceDown || event.button === 1) {
            event.preventDefault();
            this.startPan(event);
            return;
        }
        if (event.button !== 0) {
            return;
        }
        const point = this.canvasPointFromEvent(event);
        this.marquee = { x0: point.x, y0: point.y, x1: point.x, y1: point.y, additive: event.shiftKey };
        if (!event.shiftKey) {
            this.clearSelection();
        }
        this.bindPointerSession(
            (e) => {
                if (!this.marquee) return;
                const p = this.canvasPointFromEvent(e);
                this.marquee.x1 = p.x;
                this.marquee.y1 = p.y;
            },
            () => {
                this.finishMarquee();
            }
        );
    },

    finishMarquee() {
        if (!this.marquee) {
            return;
        }
        const x0 = Math.min(this.marquee.x0, this.marquee.x1);
        const y0 = Math.min(this.marquee.y0, this.marquee.y1);
        const x1 = Math.max(this.marquee.x0, this.marquee.x1);
        const y1 = Math.max(this.marquee.y0, this.marquee.y1);
        const tiny = Math.abs(x1 - x0) < 0.4 && Math.abs(y1 - y0) < 0.4;
        if (!tiny) {
            const hit = this.elements
                .filter((el) => {
                    const right = el.x + el.width;
                    const bottom = el.y + el.height;
                    return el.x < x1 && right > x0 && el.y < y1 && bottom > y0;
                })
                .map((el) => el.id);
            this.selectedIds = this.marquee.additive
                ? [...new Set([...this.selectedIds, ...hit])]
                : hit;
        }
        this.marquee = null;
    },

    startDrag(event, el) {
        if (this.spaceDown || event.button === 1) {
            event.preventDefault();
            this.startPan(event);
            return;
        }
        if (event.shiftKey) {
            this.toggleSelect(el.id);
            return;
        }
        if (!this.selectedIds.includes(el.id)) {
            this.selectedIds = [el.id];
        }
        const ids = [...this.selectedIds];
        const orig = {};
        ids.forEach((id) => {
            const item = this.elements.find((entry) => entry.id === id);
            if (item) {
                orig[id] = { x: item.x, y: item.y };
            }
        });
        this.drag = {
            ids,
            startX: event.clientX,
            startY: event.clientY,
            orig,
        };
        event.currentTarget.setPointerCapture?.(event.pointerId);
        this.bindPointerSession(
            (e) => this.onPointerMove(e),
            () => {
                if (this.drag) {
                    const moved = this.elements.filter((item) => this.drag.ids.includes(item.id));
                    this.afterGeometryChange(moved);
                }
                this.drag = null;
                this.resize = null;
            }
        );
    },

    startResize(event, el) {
        if (event.shiftKey || this.spaceDown) {
            return;
        }
        this.selectedIds = [el.id];
        this.resize = {
            id: el.id,
            startX: event.clientX,
            startY: event.clientY,
            origW: el.width,
            origH: el.height,
        };
        this.bindPointerSession(
            (e) => this.onPointerMove(e),
            () => {
                if (this.resize) {
                    const item = this.elements.find((entry) => entry.id === this.resize.id);
                    if (item) {
                        this.afterGeometryChange([item]);
                    }
                }
                this.drag = null;
                this.resize = null;
            }
        );
    },

    onPointerMove(event) {
        if (this.drag) {
            const dx = (event.clientX - this.drag.startX) / this.effectiveScale;
            const dy = (event.clientY - this.drag.startY) / this.effectiveScale;
            this.drag.ids.forEach((id) => {
                const el = this.elements.find((item) => item.id === id);
                const orig = this.drag.orig[id];
                if (!el || !orig) return;
                el.x = this.applyCoord(orig.x + dx, this.displayWidthMm);
                el.y = this.applyCoord(Math.max(0, orig.y + dy), this.displayHeightMm);
            });
            const moved = this.elements.filter((item) => this.drag.ids.includes(item.id));
            this.afterGeometryChange(moved);
        }
        if (this.resize) {
            const el = this.elements.find((item) => item.id === this.resize.id);
            if (!el) return;
            const dx = (event.clientX - this.resize.startX) / this.effectiveScale;
            const dy = (event.clientY - this.resize.startY) / this.effectiveScale;
            if (this.snapToGrid) {
                const snappedRight = this.snapToCenterGrid(el.x + this.resize.origW + dx, this.displayWidthMm);
                const snappedBottom = this.snapToCenterGrid(el.y + this.resize.origH + dy, this.displayHeightMm);
                el.width = Math.max(2, Math.round((snappedRight - el.x) * 1000) / 1000);
                el.height = Math.max(2, Math.round((snappedBottom - el.y) * 1000) / 1000);
            } else {
                el.width = Math.max(2, this.roundMm(this.resize.origW + dx));
                el.height = Math.max(2, this.roundMm(this.resize.origH + dy));
            }
            this.afterGeometryChange([el]);
        }
    },

    selectionBounds(els) {
        const left = Math.min(...els.map((el) => el.x));
        const top = Math.min(...els.map((el) => el.y));
        const right = Math.max(...els.map((el) => el.x + el.width));
        const bottom = Math.max(...els.map((el) => el.y + el.height));
        return { left, top, right, bottom, width: right - left, height: bottom - top };
    },

    biggestElement(els) {
        return [...els].sort((a, b) => (b.width * b.height) - (a.width * a.height))[0] || null;
    },

    alignReference() {
        const els = this.selectedElements;
        if (!els.length) {
            return null;
        }
        if (this.alignTarget === 'page') {
            return {
                left: 0,
                top: 0,
                right: this.displayWidthMm,
                bottom: this.displayHeightMm,
                width: this.displayWidthMm,
                height: this.displayHeightMm,
            };
        }
        if (this.alignTarget === 'biggest') {
            const big = this.biggestElement(els);
            if (!big) {
                return null;
            }
            return {
                left: big.x,
                top: big.y,
                right: big.x + big.width,
                bottom: big.y + big.height,
                width: big.width,
                height: big.height,
                anchorId: big.id,
            };
        }
        return this.selectionBounds(els);
    },

    align(edge) {
        if (!this.canAlign) {
            return;
        }
        const ref = this.alignReference();
        if (!ref) {
            return;
        }
        const els = this.selectedElements;
        els.forEach((el) => {
            if (this.alignTarget === 'biggest' && ref.anchorId === el.id) {
                return;
            }
            if (edge === 'left') {
                el.x = this.applyCoord(ref.left, this.displayWidthMm);
            } else if (edge === 'center') {
                el.x = this.applyCoord(ref.left + ref.width / 2 - el.width / 2, this.displayWidthMm);
            } else if (edge === 'right') {
                el.x = this.applyCoord(ref.right - el.width, this.displayWidthMm);
            } else if (edge === 'top') {
                el.y = this.applyCoord(ref.top, this.displayHeightMm);
            } else if (edge === 'middle') {
                el.y = this.applyCoord(ref.top + ref.height / 2 - el.height / 2, this.displayHeightMm);
            } else if (edge === 'bottom') {
                el.y = this.applyCoord(ref.bottom - el.height, this.displayHeightMm);
            }
        });
        this.afterGeometryChange(els);
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
        const tokens = searchTokens(this.filter).map((token) => token.toLowerCase());
        if (!tokens.length) {
            return this.assets;
        }

        return this.assets.filter((asset) => {
            const haystack = [
                asset.name,
                asset.tp_barcode,
                asset.fmi_ast,
                asset.rig_tag,
                asset.serial_number,
                asset.mac_address,
                asset.item_type,
                asset.status,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return tokens.every((token) => haystack.includes(token));
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
                    const parent = node.parentElement;
                    const boxW = Math.max(16, parent?.clientWidth || node.clientWidth || 120);
                    const boxH = Math.max(16, parent?.clientHeight || node.clientHeight || 40);
                    renderBarcodeSvgFit(node, value, {
                        symbology: node.getAttribute('data-symbology') || 'code128',
                        showText: node.getAttribute('data-show-text') === '1',
                        boxW,
                        boxH,
                    }).catch(() => {});
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

Alpine.data('barcodeScanner', (scanUrl, eventListId = null, initialEventItems = [], noteUrlTemplate = '', initialUnknownScans = [], clearRecent = false, scanNoiseEnabled = false, scanVibrateEnabled = false) => ({
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
    scanNoiseEnabled: Boolean(scanNoiseEnabled),
    scanVibrateEnabled: Boolean(scanVibrateEnabled),
    /** @type {'auto' | 'hardware' | 'camera'} */
    inputMode: 'auto',
    hardwareDetected: false,
    wedgeState: createWedgeBufferState(),
    _idleTimer: null,
    _onVisibilityChange: null,
    _onWedgeKeydown: null,

    get cameraNeedsSecureOrigin() {
        return cameraBlockedByInsecureOrigin();
    },

    get localhostCameraUrl() {
        return cameraLocalhostUrl();
    },

    get httpsCameraUrl() {
        return cameraHttpsUrl();
    },

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

        if (clearRecent) {
            this.clearRecentList({ markCleared: true, wipeAllKeys: true });
        } else if (this.isRecentCleared()) {
            // Reset previously dismissed server-seeded history; keep only client-stored rows.
            const stored = this.loadRecent();
            this.recent = stored.slice(0, 50);
            this.persistRecent();
        } else {
            const stored = this.loadRecent();
            const unknownIds = new Set(this.serverUnknowns.map((entry) => entry.unknown_scan?.id).filter(Boolean));
            const storedWithoutDupUnknowns = stored.filter(
                (entry) => !entry.unknown || !unknownIds.has(entry.unknown_scan?.id)
            );
            this.recent = [...this.serverUnknowns, ...storedWithoutDupUnknowns].slice(0, 50);
            this.persistRecent();
        }

        this.inputMode = this.readInputMode();
        this.hardwareDetected = this.inputMode === 'hardware';

        this._onVisibilityChange = () => {
            if (document.hidden && this.scanning) {
                void this.pauseCamera('Camera paused while the app was in the background. Tap Use camera to resume.');
            }
        };
        document.addEventListener('visibilitychange', this._onVisibilityChange);
        window.addEventListener('pagehide', this._onVisibilityChange);

        this._onWedgeKeydown = (event) => this.handleWedgeKeydown(event);
        window.addEventListener('keydown', this._onWedgeKeydown, true);

        this.$nextTick(() => this.focusInput());
    },

    async destroy() {
        this.clearIdleTimer();
        if (this._onVisibilityChange) {
            document.removeEventListener('visibilitychange', this._onVisibilityChange);
            window.removeEventListener('pagehide', this._onVisibilityChange);
            this._onVisibilityChange = null;
        }
        if (this._onWedgeKeydown) {
            window.removeEventListener('keydown', this._onWedgeKeydown, true);
            this._onWedgeKeydown = null;
        }
        await this.stopCamera({ silent: true });
    },

    readInputMode() {
        try {
            const stored = localStorage.getItem(SCAN_INPUT_MODE_STORAGE_KEY);
            if (stored === 'hardware' || stored === 'camera' || stored === 'auto') {
                return stored;
            }
        } catch (_) {}

        return 'auto';
    },

    persistInputMode(mode) {
        if (mode !== 'hardware' && mode !== 'camera' && mode !== 'auto') {
            return;
        }
        this.inputMode = mode;
        try {
            localStorage.setItem(SCAN_INPUT_MODE_STORAGE_KEY, mode);
        } catch (_) {}
    },

    get prefersHardware() {
        return this.inputMode === 'auto' || this.inputMode === 'hardware';
    },

    get showHardwareSwitch() {
        return this.scanning || this.inputMode === 'camera';
    },

    get inputModeStatus() {
        if (this.scanning) {
            return 'Camera active';
        }
        if (this.inputMode === 'camera') {
            return 'Camera mode — tap Use camera when ready';
        }

        return 'Hardware scanner ready';
    },

    isSlowTypingTarget(el) {
        if (!el || el === document.body || el === document.documentElement) {
            return false;
        }
        if (this.$refs.codeInput && el === this.$refs.codeInput) {
            return false;
        }

        const tag = el.tagName;
        if (tag === 'TEXTAREA' || tag === 'SELECT') {
            return true;
        }
        if (tag === 'INPUT') {
            const type = String(el.type || 'text').toLowerCase();
            return type !== 'button' && type !== 'submit' && type !== 'checkbox' && type !== 'radio' && type !== 'hidden';
        }

        return Boolean(el.isContentEditable);
    },

    handleWedgeKeydown(event) {
        const active = document.activeElement;
        const ignoreSlowTyping = this.isSlowTypingTarget(active);
        const result = processWedgeKeydown(this.wedgeState, event, { ignoreSlowTyping });

        if (result.scanned) {
            event.preventDefault();
            event.stopPropagation();
            void this.onWedgeScan(result.scanned);
            return;
        }

        if (result.consume) {
            event.preventDefault();
        }
    },

    async onWedgeScan(code) {
        this.hardwareDetected = true;
        if (this.inputMode !== 'hardware') {
            this.persistInputMode('hardware');
        }
        if (this.scanning) {
            await this.stopCamera({ silent: true });
        }
        this.code = code;
        await this.submitCode();
    },

    async useCamera() {
        this.persistInputMode('camera');
        await this.startCamera();
    },

    async useHardware() {
        await this.stopCamera({ silent: true });
        this.persistInputMode('hardware');
        this.message = '';
        this.$nextTick(() => this.focusInput());
    },

    clearIdleTimer() {
        if (this._idleTimer) {
            clearTimeout(this._idleTimer);
            this._idleTimer = null;
        }
    },

    bumpIdleTimer() {
        this.clearIdleTimer();
        if (!this.scanning) {
            return;
        }
        this._idleTimer = setTimeout(() => {
            void this.pauseCamera('Camera paused after idle. Tap Use camera to resume.');
        }, CAMERA_IDLE_PAUSE_MS);
    },

    async pauseCamera(message) {
        if (!this.scanning && !this.scanner) {
            return;
        }
        await this.stopCamera({ silent: true });
        if (message) {
            this.message = message;
        }
    },

    storageKey() {
        return `av-scan-recent-${this.eventListId ?? 'none'}`;
    },

    clearedKey() {
        return `av-scan-recent-cleared-${this.eventListId ?? 'none'}`;
    },

    isRecentCleared() {
        try {
            return sessionStorage.getItem(this.clearedKey()) === '1';
        } catch (_) {
            return false;
        }
    },

    markRecentCleared() {
        try {
            sessionStorage.setItem(this.clearedKey(), '1');
        } catch (_) {}
    },

    clearRecentClearedFlag() {
        try {
            sessionStorage.removeItem(this.clearedKey());
        } catch (_) {}
    },

    wipeScanRecentStorage({ allKeys = false } = {}) {
        try {
            if (allKeys) {
                const keys = [];
                for (let i = 0; i < sessionStorage.length; i += 1) {
                    const key = sessionStorage.key(i);
                    if (key && (key.startsWith('av-scan-recent-') || key.startsWith('av-scan-recent-cleared-'))) {
                        keys.push(key);
                    }
                }
                keys.forEach((key) => sessionStorage.removeItem(key));
                return;
            }
            sessionStorage.removeItem(this.storageKey());
        } catch (_) {}
    },

    clearRecentList({ markCleared = true, wipeAllKeys = false } = {}) {
        this.recent = [];
        this.serverUnknowns = [];
        this.wipeScanRecentStorage({ allKeys: wipeAllKeys });
        this.persistRecent();
        if (markCleared) {
            this.markRecentCleared();
        }
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
        this.clearRecentList({ markCleared: true, wipeAllKeys: true });
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
        const asset = { ...(entry.asset || {}) };
        if (asset.id && !asset.url) {
            asset.url = `/assets/${asset.id}`;
        }
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
        const asset = { ...(entry.asset || {}) };
        if (asset.id && !asset.url) {
            asset.url = `/assets/${asset.id}`;
        }
        return {
            id: entry.id ?? `event-${asset.id || index}-${Date.now()}`,
            quantity: entry.quantity ?? 1,
            is_child_expand: Boolean(entry.is_child_expand),
            asset,
        };
    },

    async startCamera() {
        this.error = '';
        this.message = '';

        if (cameraBlockedByInsecureOrigin()) {
            this.error = formatCameraStartError();
            this.scanning = false;
            this.clearIdleTimer();
            this.$nextTick(() => this.focusInput());

            return;
        }

        this.scanning = true;
        await this.$nextTick();

        try {
            this.scanner = await startBarcodeCamera('qr-reader', async (decoded) => {
                // Rest decode CPU while a scan POST is in flight.
                if (this.busy) {
                    return;
                }
                const now = Date.now();
                if (decoded === this.lastCameraCode && now - this.lastCameraAt < CAMERA_SAME_CODE_DEBOUNCE_MS) {
                    return;
                }
                this.lastCameraCode = decoded;
                this.lastCameraAt = now;
                this.bumpIdleTimer();
                this.code = decoded;
                await this.submitCode();
            });
            this.bumpIdleTimer();
        } catch (e) {
            this.error = formatCameraStartError(e);
            this.scanning = false;
            this.clearIdleTimer();
            this.$nextTick(() => this.focusInput());
        }
    },

    async stopCamera({ silent = false } = {}) {
        this.clearIdleTimer();
        await stopBarcodeCamera(this.scanner);
        this.scanner = null;
        this.scanning = false;
        if (!silent) {
            this.message = '';
        }
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

    playScanFeedback(kind) {
        let vibrated = false;
        if (this.scanVibrateEnabled) {
            vibrated = playScanVibrate(kind);
        }
        // Prefer haptics when both prefs are on; fall back to tone if vibrate is unavailable.
        if (this.scanNoiseEnabled && !(this.scanVibrateEnabled && vibrated)) {
            playScanNoise(kind);
        }
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
                this.playScanFeedback('fail');
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
                this.playScanFeedback('fail');
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
                this.playScanFeedback('match');
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
                this.playScanFeedback('match');
            }

            if (entries.length) {
                this.recent = [...entries, ...this.recent].slice(0, 50);
                this.persistRecent();
            }

            if (
                this.eventListId &&
                !data.duplicate &&
                !data.unknown &&
                Array.isArray(data.items) &&
                data.items.length > 0
            ) {
                const added = data.items.map((item, index) => this.normalizeEventItem(item, index));
                this.eventItems = [...added, ...this.eventItems];
            }
        } catch (e) {
            this.error = 'Scan failed: ' + (e?.message || e);
            this.warning = '';
            this.playScanFeedback('fail');
        }
    },
}));

const SCROLL_RESTORE_KEY = 'av.scrollRestore';

function currentScrollKey() {
    return `${window.location.pathname}${window.location.search}`;
}

function saveScrollPosition() {
    try {
        sessionStorage.setItem(
            SCROLL_RESTORE_KEY,
            JSON.stringify({
                key: currentScrollKey(),
                x: window.scrollX,
                y: window.scrollY,
            })
        );
    } catch {
        // Ignore quota / private-mode failures.
    }
}

function restoreScrollPosition() {
    let data;

    try {
        const raw = sessionStorage.getItem(SCROLL_RESTORE_KEY);
        if (!raw) {
            return;
        }

        data = JSON.parse(raw);
        sessionStorage.removeItem(SCROLL_RESTORE_KEY);
    } catch {
        return;
    }

    if (!data || data.key !== currentScrollKey()) {
        return;
    }

    const x = Number(data.x) || 0;
    const y = Number(data.y) || 0;

    if (x === 0 && y === 0) {
        return;
    }

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    const apply = () => window.scrollTo(x, y);

    apply();
    requestAnimationFrame(apply);

    if (document.readyState !== 'complete') {
        window.addEventListener('load', apply, { once: true });
    }
}

document.addEventListener(
    'submit',
    (event) => {
        if (event.target instanceof HTMLFormElement) {
            saveScrollPosition();
        }
    },
    true
);

restoreScrollPosition();

function defaultRiggingInputs(calculator) {
    switch (calculator) {
        case 'two_point_hang':
            return { W: 100, L: 10, x: 4 };
        case 'lever':
            return { class: 'I', Fr: 100, dr: 1, de: 2 };
        case 'bridle_two_leg':
            return { W: 200, span: 4, drop: 2, offset: 2 };
        case 'bridle_multi_leg':
            return { W: 200, legs: 4, angle_from_vertical_deg: 30 };
        case 'sling_angle_factor':
            return { W: 100, angle_from_vertical_deg: 30, wll: 500 };
        case 'dead_hang':
            return { W: 100, wll: 500 };
        case 'beam_point_load':
            return { L: 10, loads: [{ W: 100, x: 4 }] };
        case 'udl_multi_point':
            return {
                point_count: 4,
                distribution_mode: 'continuous_beam',
                hoist_self_weight_kg: 0,
                weight_lines: [{ label: 'Truss', weight_kg: 200, quantity: 1, item_type_id: '' }],
                custom_percents: [25, 25, 25, 25],
            };
        case 'projector_lens':
            return {
                mode: 'width_from_ratio_distance',
                aspect: '16:9',
                custom_aspect: 1.777,
                throw_ratio: 1.5,
                throw_distance: 6,
                image_width: null,
                zoom_min: null,
                zoom_max: null,
            };
        case 'unit_convert':
            return { value: 100, from: 'kg', to: 'lb' };
        default:
            return {};
    }
}

function buildRiggingDiagram(calculator, inputs, results) {
    const stroke = '#00adb7';
    const ink = '#111111';
    if (!results) {
        return `<svg viewBox="0 0 320 120" xmlns="http://www.w3.org/2000/svg"><text x="16" y="64" fill="${ink}" font-size="12">Enter inputs and update</text></svg>`;
    }

    if (calculator === 'two_point_hang') {
        const L = Number(inputs.L) || 10;
        const x = Number(inputs.x) || 0;
        const px = 40 + (240 * x) / L;
        return `<svg viewBox="0 0 320 140" xmlns="http://www.w3.org/2000/svg">
            <line x1="40" y1="40" x2="280" y2="40" stroke="${ink}" stroke-width="3"/>
            <circle cx="40" cy="40" r="5" fill="${stroke}"/><text x="28" y="28" font-size="11">A</text>
            <circle cx="280" cy="40" r="5" fill="${stroke}"/><text x="268" y="28" font-size="11">B</text>
            <line x1="${px}" y1="40" x2="${px}" y2="100" stroke="${stroke}" stroke-width="2"/>
            <rect x="${px - 12}" y="100" width="24" height="16" fill="${stroke}"/>
            <text x="40" y="130" font-size="11">RA ${results.RA}</text>
            <text x="220" y="130" font-size="11">RB ${results.RB}</text>
        </svg>`;
    }

    if (calculator === 'lever') {
        const cls = inputs.class || 'I';
        const labels = cls === 'I' ? ['E', 'F', 'L'] : cls === 'II' ? ['E', 'L', 'F'] : ['L', 'E', 'F'];
        return `<svg viewBox="0 0 320 120" xmlns="http://www.w3.org/2000/svg">
            <line x1="40" y1="50" x2="280" y2="50" stroke="${ink}" stroke-width="3"/>
            <polygon points="160,50 150,70 170,70" fill="${stroke}"/>
            <text x="50" y="40" font-size="12">${labels[0]}</text>
            <text x="150" y="40" font-size="12">${labels[1]}</text>
            <text x="250" y="40" font-size="12">${labels[2]}</text>
            <text x="40" y="100" font-size="11">Fe ${results.Fe_kg} kg · Fulcrum ${results.fulcrum_kg} kg</text>
        </svg>`;
    }

    if (calculator === 'bridle_two_leg') {
        return `<svg viewBox="0 0 320 140" xmlns="http://www.w3.org/2000/svg">
            <circle cx="40" cy="30" r="5" fill="${stroke}"/><circle cx="280" cy="30" r="5" fill="${stroke}"/>
            <line x1="40" y1="30" x2="160" y2="110" stroke="${ink}" stroke-width="2"/>
            <line x1="280" y1="30" x2="160" y2="110" stroke="${ink}" stroke-width="2"/>
            <rect x="148" y="110" width="24" height="16" fill="${stroke}"/>
            <text x="20" y="130" font-size="11">T1 ${results.T1}</text>
            <text x="220" y="130" font-size="11">T2 ${results.T2}</text>
        </svg>`;
    }

    if (calculator === 'udl_multi_point' && Array.isArray(results.points)) {
        const pts = results.points;
        const max = Math.max(...pts.map((p) => Number(p.total_kg) || 0), 1);
        const bars = pts.map((p, i) => {
            const x = 30 + i * (260 / Math.max(pts.length, 1));
            const h = (80 * (Number(p.total_kg) || 0)) / max;
            return `<rect x="${x}" y="${100 - h}" width="16" height="${h}" fill="${stroke}"/><text x="${x}" y="114" font-size="9">${i + 1}</text>`;
        }).join('');
        return `<svg viewBox="0 0 320 130" xmlns="http://www.w3.org/2000/svg"><line x1="20" y1="100" x2="300" y2="100" stroke="${ink}"/>${bars}</svg>`;
    }

    if (calculator === 'projector_lens') {
        const w = Math.min(120, 40 + (Number(results.image_width) || 1) * 20);
        return `<svg viewBox="0 0 320 120" xmlns="http://www.w3.org/2000/svg">
            <rect x="20" y="50" width="28" height="20" fill="${stroke}"/>
            <line x1="48" y1="60" x2="220" y2="60" stroke="${ink}" stroke-width="2"/>
            <rect x="220" y="${60 - w / 4}" width="8" height="${w / 2}" fill="${ink}"/>
            <text x="60" y="40" font-size="11">TR ${results.throw_ratio} · ${results.throw_distance} m → ${results.image_width} m</text>
        </svg>`;
    }

    if (calculator === 'beam_point_load') {
        return `<svg viewBox="0 0 320 120" xmlns="http://www.w3.org/2000/svg">
            <line x1="40" y1="50" x2="280" y2="50" stroke="${ink}" stroke-width="3"/>
            <text x="40" y="90" font-size="11">RA ${results.RA}</text>
            <text x="220" y="90" font-size="11">RB ${results.RB}</text>
        </svg>`;
    }

    const summary = Object.entries(results).slice(0, 4).map(([k, v]) => `${k}: ${typeof v === 'object' ? '…' : v}`).join(' · ');
    return `<svg viewBox="0 0 320 100" xmlns="http://www.w3.org/2000/svg"><text x="16" y="55" font-size="12" fill="${ink}">${summary}</text></svg>`;
}

Alpine.data('riggingCalculator', (config = {}) => ({
    calculator: config.calculator,
    computeUrl: config.computeUrl,
    saveUrl: config.saveUrl,
    csrf: config.csrf,
    events: config.events || [],
    itemTypes: config.itemTypes || [],
    inputs: defaultRiggingInputs(config.calculator),
    results: null,
    error: '',
    title: '',
    rigEventId: '',
    diagramSvg: buildRiggingDiagram(config.calculator, defaultRiggingInputs(config.calculator), null),
    init() {
        this.title = this.calculator.replace(/_/g, ' ');
        this.recompute();
    },
    get resultEntries() {
        if (!this.results) {
            return [];
        }
        return Object.entries(this.results).filter(([, v]) => typeof v !== 'object' || v === null);
    },
    formatValue(value) {
        if (typeof value === 'boolean') {
            return value ? 'yes' : 'no';
        }
        return value;
    },
    async recompute() {
        this.error = '';
        try {
            const response = await fetch(this.computeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ inputs: this.payloadInputs() }),
            });
            const json = await response.json();
            if (!response.ok) {
                this.results = null;
                this.error = json.message || 'Could not compute.';
                this.diagramSvg = buildRiggingDiagram(this.calculator, this.inputs, null);
                return;
            }
            this.results = json.results;
            this.diagramSvg = buildRiggingDiagram(this.calculator, this.inputs, this.results);
        } catch (e) {
            this.error = 'Network error computing results.';
        }
    },
    payloadInputs() {
        const raw = JSON.parse(JSON.stringify(this.inputs));
        if (this.calculator === 'udl_multi_point') {
            raw.weight_lines = (raw.weight_lines || []).map((line) => ({
                ...line,
                item_type_id: line.item_type_id === '' || line.item_type_id === null ? null : Number(line.item_type_id),
            }));
        }
        if (this.calculator === 'sling_angle_factor' && (raw.wll === '' || raw.wll === null || Number.isNaN(raw.wll))) {
            delete raw.wll;
        }
        return raw;
    },
    prepareSave(event) {
        if (!this.results) {
            event.preventDefault();
            this.error = 'Compute a valid result before saving.';
        }
    },
    addBeamLoad() {
        this.inputs.loads.push({ W: 50, x: 1 });
        this.recompute();
    },
    removeBeamLoad(idx) {
        this.inputs.loads.splice(idx, 1);
        this.recompute();
    },
    addWeightLine() {
        this.inputs.weight_lines.push({ label: '', weight_kg: null, quantity: 1, item_type_id: '' });
    },
    removeWeightLine(idx) {
        this.inputs.weight_lines.splice(idx, 1);
        this.recompute();
    },
    applyItemType(idx) {
        const line = this.inputs.weight_lines[idx];
        const type = this.itemTypes.find((t) => String(t.id) === String(line.item_type_id));
        if (type) {
            line.label = type.name;
            if (type.rigging_weight_kg != null) {
                line.weight_kg = Number(type.rigging_weight_kg);
            }
        }
        this.recompute();
    },
    onPointCountChange() {
        const n = Math.max(1, Math.min(32, Number(this.inputs.point_count) || 1));
        this.inputs.point_count = n;
        const equal = 100 / n;
        this.inputs.custom_percents = Array.from({ length: n }, () => equal);
        this.recompute();
    },
}));

Alpine.data('rigEventMotorPatches', (initial = []) => ({
    rows: (initial || []).map((row) => ({
        motor_label: row.motor_label || '',
        patch_to: row.patch_to || '',
        notes: row.notes || '',
        sort_order: row.sort_order || 0,
    })),
    addRow() {
        this.rows.push({ motor_label: '', patch_to: '', notes: '', sort_order: this.rows.length });
    },
    removeRow(idx) {
        this.rows.splice(idx, 1);
    },
}));

Alpine.data('rigEventSignOff', () => ({
    drawing: false,
    init() {
        const canvas = this.$refs.pad;
        if (!canvas) {
            return;
        }
        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#111';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        this._ctx = ctx;
    },
    pointerPos(event) {
        const canvas = this.$refs.pad;
        const rect = canvas.getBoundingClientRect();
        return {
            x: ((event.clientX - rect.left) / rect.width) * canvas.width,
            y: ((event.clientY - rect.top) / rect.height) * canvas.height,
        };
    },
    startStroke(event) {
        this.drawing = true;
        const p = this.pointerPos(event);
        this._ctx.beginPath();
        this._ctx.moveTo(p.x, p.y);
        event.target.setPointerCapture?.(event.pointerId);
    },
    drawStroke(event) {
        if (!this.drawing) {
            return;
        }
        const p = this.pointerPos(event);
        this._ctx.lineTo(p.x, p.y);
        this._ctx.stroke();
    },
    endStroke() {
        this.drawing = false;
    },
    clearPad() {
        const canvas = this.$refs.pad;
        this._ctx.clearRect(0, 0, canvas.width, canvas.height);
    },
    beforeSubmit() {
        if (this.$refs.signatureData && this.$refs.pad) {
            this.$refs.signatureData.value = this.$refs.pad.toDataURL('image/png');
        }
    },
}));

Alpine.start();

import { registerSW } from 'virtual:pwa-register';

registerSW({ immediate: true });
