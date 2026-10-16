<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <?php if(! empty($retryAfter)): ?>
        <meta http-equiv="refresh" content="<?php echo e((int) $retryAfter); ?>">
    <?php endif; ?>
    <title>Server maintenance — <?php echo e(config('app.name', 'AV Asset Manager')); ?></title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        :root {
            --brand: #00adb7;
            --black: #000000;
            --white: #ffffff;
            --charcoal: #545759;
            --silver: #f0f0f0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--silver);
            color: var(--black);
        }

        main {
            width: 100%;
            max-width: 28rem;
            text-align: center;
        }

        .mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            margin-bottom: 1.25rem;
            border-radius: 0.75rem;
            background: var(--brand);
            color: var(--white);
        }

        .mark svg {
            width: 1.75rem;
            height: 1.75rem;
        }

        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-weight: 650;
            letter-spacing: -0.02em;
        }

        p {
            margin: 0;
            color: var(--charcoal);
            line-height: 1.5;
        }

        .hint {
            margin-top: 1.25rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <main>
        <div class="mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <h1>Server maintenance</h1>
        <p>
            <?php echo e(config('app.name', 'AV Asset Manager')); ?> is temporarily unavailable while an update is applied.
            Please try again in a few minutes.
        </p>
        <?php if(! empty($retryAfter)): ?>
            <p class="hint">This page will refresh automatically.</p>
        <?php endif; ?>
    </main>
</body>
</html>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/errors/503.blade.php ENDPATH**/ ?>