import './bootstrap';
import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;

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
