{{-- #region agent log --}}
<script>
    (() => {
        const send = (message, hypothesisId, data) => {
            fetch('http://127.0.0.1:7405/ingest/c488235e-e5fa-4788-ba67-4d24eab5b812', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '5e9f10' },
                body: JSON.stringify({
                    sessionId: '5e9f10',
                    runId: 'pre-fix',
                    hypothesisId,
                    location: 'debug-print-log.blade.php',
                    message,
                    data,
                    timestamp: Date.now(),
                }),
            }).catch(() => {});
        };

        const snapshot = (message, hypothesisId) => {
            const sheets = [...document.querySelectorAll('.label-sheet')].map((sheet) => ({
                width: sheet.style.width,
                height: sheet.style.height,
                orientation: sheet.dataset.orientation || null,
            }));
            const barcodes = [...document.querySelectorAll('.shelf-tag-barcode')];
            send(message, hypothesisId, {
                href: location.pathname,
                sheetCount: sheets.length,
                sheets,
                barcodeCount: barcodes.length,
                barcodesDrawn: barcodes.filter((node) => node.childNodes.length > 0).length,
            });
        };

        document.addEventListener('DOMContentLoaded', () => snapshot('label sheet mounted', 'B,C,D'));
        window.addEventListener('beforeprint', () => {
            snapshot('browser print invoked', 'A,D');
            const first = document.querySelector('.label-sheet');
            const main = document.querySelector('main');
            const root = document.body.firstElementChild;
            send('first label print offset', 'F', {
                firstOffsetTop: first ? first.offsetTop : null,
                firstRectTop: first ? Math.round(first.getBoundingClientRect().top) : null,
                mainPaddingTop: main ? getComputedStyle(main).paddingTop : null,
                rootMinHeight: root ? getComputedStyle(root).minHeight : null,
            });
        });
    })();
</script>
{{-- #endregion --}}
