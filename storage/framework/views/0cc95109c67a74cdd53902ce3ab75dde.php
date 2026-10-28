<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="<?php echo e(auth()->user()?->theme === 'dark' ? 'dark' : ''); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <meta name="theme-color" content="#00adb7">
        <meta name="av-camera-battery-optimisation" content="<?php echo e(auth()->user()?->wantsCameraBatteryOptimisation() ? '1' : '0'); ?>">
        <?php
            $preferredOrigin = rtrim((string) config('app.url'), '/');
        ?>
        <?php if(str_starts_with($preferredOrigin, 'https://')): ?>
            <meta name="av-preferred-origin" content="<?php echo e($preferredOrigin); ?>">
        <?php endif; ?>
        <link rel="manifest" href="<?php echo e(asset('build/manifest.webmanifest')); ?>">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <title><?php echo e($title ?? config('app.name', 'AV Asset Manager')); ?></title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

        <?php if(session('status')): ?>
            <meta name="av-flash-status" content="<?php echo e(session('status')); ?>">
        <?php endif; ?>
        <?php if(session('warning')): ?>
            <meta name="av-flash-warning" content="<?php echo e(session('warning')); ?>">
        <?php endif; ?>
        <?php if(isset($errors) && $errors->any()): ?>
            <meta name="av-history-invalid" content="1">
        <?php endif; ?>
        <script>
            (function () {
                var ROUNDTRIP_KEY = 'av.historyRoundtrip';
                var COLLAPSE_KEY = 'av.historyCollapse';
                var DEPTH_KEY = 'av.navDepth';
                var FLASH_KEY = 'av.historyFlash';

                function storageGet(key) {
                    try {
                        return sessionStorage.getItem(key);
                    } catch (e) {
                        return null;
                    }
                }

                function storageSet(key, value) {
                    try {
                        sessionStorage.setItem(key, value);
                    } catch (e) {
                        // Ignore quota / private-mode failures.
                    }
                }

                function storageRemove(key) {
                    try {
                        sessionStorage.removeItem(key);
                    } catch (e) {
                        // Ignore quota / private-mode failures.
                    }
                }

                function sameOriginPath(value) {
                    try {
                        var url = new URL(value, window.location.origin);
                        if (url.origin !== window.location.origin) {
                            return null;
                        }

                        return url.pathname + url.search;
                    } catch (e) {
                        return null;
                    }
                }

                function stripCacheBust() {
                    var url = new URL(window.location.href);
                    if (!url.searchParams.has('_hc')) {
                        return;
                    }

                    url.searchParams.delete('_hc');
                    var state = history.state && typeof history.state === 'object' ? history.state : {};
                    history.replaceState(state, '', url.pathname + url.search + url.hash);
                }

                function replaceCollapsed(replaceUrl) {
                    var target = new URL(replaceUrl, window.location.origin);
                    if (target.origin !== window.location.origin) {
                        return;
                    }

                    document.documentElement.style.visibility = 'hidden';
                    target.searchParams.set('_hc', String(Date.now()));
                    window.location.replace(target.href);
                }

                function consumeCollapse() {
                    if (window.__avHistoryCollapseStarted) {
                        return true;
                    }

                    var raw = storageGet(COLLAPSE_KEY);
                    if (!raw) {
                        return false;
                    }

                    storageRemove(COLLAPSE_KEY);
                    var pending;
                    try {
                        pending = JSON.parse(raw);
                    } catch (e) {
                        return false;
                    }

                    if (!pending || !sameOriginPath(pending.replaceUrl || '')) {
                        return false;
                    }

                    window.__avHistoryCollapseStarted = true;
                    replaceCollapsed(pending.replaceUrl);

                    return true;
                }

                function metaContent(name) {
                    var node = document.querySelector('meta[name="' + name + '"]');
                    return node ? node.content : '';
                }

                function stashFlash() {
                    var status = metaContent('av-flash-status');
                    var warning = metaContent('av-flash-warning');
                    if (!status && !warning) {
                        return;
                    }

                    storageSet(FLASH_KEY, JSON.stringify({ status: status, warning: warning }));
                }

                stripCacheBust();
                if (consumeCollapse()) {
                    return;
                }

                var state = history.state && typeof history.state === 'object' ? history.state : {};
                var depth;
                if (typeof state.avDepth === 'number') {
                    depth = state.avDepth;
                } else {
                    var prev = Number(storageGet(DEPTH_KEY) || 0);
                    depth = (Number.isFinite(prev) ? prev : 0) + 1;
                    var nextState = {};
                    Object.keys(state).forEach(function (key) {
                        nextState[key] = state[key];
                    });
                    nextState.avDepth = depth;
                    history.replaceState(nextState, '', window.location.pathname + window.location.search + window.location.hash);
                }
                storageSet(DEPTH_KEY, String(depth));

                var roundRaw = storageGet(ROUNDTRIP_KEY);
                if (!roundRaw) {
                    return;
                }

                var trip;
                try {
                    trip = JSON.parse(roundRaw);
                } catch (e) {
                    storageRemove(ROUNDTRIP_KEY);
                    return;
                }

                if (!trip || typeof trip.fromDepth !== 'number' || !trip.fromPath || !trip.formPath || !trip.fromHref) {
                    storageRemove(ROUNDTRIP_KEY);
                    return;
                }

                var herePath = window.location.pathname;
                var invalid = !!document.querySelector('meta[name="av-history-invalid"]');
                var anchor = typeof trip.anchorDepth === 'number' ? trip.anchorDepth : null;

                if (herePath === trip.formPath && depth === trip.fromDepth + 1 && anchor === null) {
                    return;
                }

                if (invalid && herePath === trip.formPath) {
                    trip.anchorDepth = depth;
                    storageSet(ROUNDTRIP_KEY, JSON.stringify(trip));
                    return;
                }

                if (depth <= trip.fromDepth && herePath === trip.fromPath) {
                    storageRemove(ROUNDTRIP_KEY);
                    return;
                }

                var expected = anchor === null ? trip.fromDepth + 2 : anchor + 1;
                if (depth !== expected) {
                    if (depth > expected) {
                        storageRemove(ROUNDTRIP_KEY);
                    }
                    return;
                }

                var saved = metaContent('av-flash-status') !== '' || metaContent('av-flash-warning') !== '';
                if (herePath !== trip.fromPath && !saved) {
                    storageRemove(ROUNDTRIP_KEY);
                    return;
                }

                var steps = herePath === trip.fromPath ? depth - trip.fromDepth : depth - trip.fromDepth - 1;
                var replaceUrl = herePath === trip.fromPath
                    ? trip.fromHref
                    : window.location.pathname + window.location.search;

                if (steps < 1 || steps > 10 || !sameOriginPath(replaceUrl)) {
                    storageRemove(ROUNDTRIP_KEY);
                    return;
                }

                stashFlash();
                storageRemove(ROUNDTRIP_KEY);
                storageSet(COLLAPSE_KEY, JSON.stringify({ replaceUrl: replaceUrl }));
                window.__avHistoryCollapseStarted = true;
                document.documentElement.style.visibility = 'hidden';

                var left = false;
                window.addEventListener('pagehide', function () {
                    left = true;
                }, { once: true });
                history.go(-steps);
                window.setTimeout(function () {
                    if (left) {
                        return;
                    }

                    storageRemove(COLLAPSE_KEY);
                    storageRemove(FLASH_KEY);
                    window.__avHistoryCollapseStarted = false;
                    document.documentElement.style.visibility = '';
                }, 800);
            })();
        </script>
    </head>
    <body class="font-sans antialiased bg-brand-silver text-brand-black dark:bg-black dark:text-white">
        <div class="min-h-screen">
            <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <?php if(isset($header)): ?>
                <header class="bg-white dark:bg-brand-slate border-b border-brand-silver dark:border-brand-charcoal">
                    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                        <?php echo e($header); ?>

                    </div>
                </header>
            <?php endif; ?>

            <?php if(session('status')): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" data-flash-banner="status">
                    <div class="rounded border border-brand bg-white dark:bg-brand-slate px-4 py-3 text-sm text-brand-teal dark:text-brand-teal-bright">
                        <?php echo e(session('status')); ?>

                    </div>
                </div>
            <?php endif; ?>
            <?php if(session('warning')): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" data-flash-banner="warning">
                    <div class="rounded border border-brand-gold bg-white dark:bg-brand-slate px-4 py-3 text-sm text-brand-charcoal dark:text-brand-gold">
                        <?php echo e(session('warning')); ?>

                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($component)) { $__componentOriginala48bcfe49d6ae182f80bf7ab2545aa4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala48bcfe49d6ae182f80bf7ab2545aa4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.no-department-notice','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('no-department-notice'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala48bcfe49d6ae182f80bf7ab2545aa4f)): ?>
<?php $attributes = $__attributesOriginala48bcfe49d6ae182f80bf7ab2545aa4f; ?>
<?php unset($__attributesOriginala48bcfe49d6ae182f80bf7ab2545aa4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala48bcfe49d6ae182f80bf7ab2545aa4f)): ?>
<?php $component = $__componentOriginala48bcfe49d6ae182f80bf7ab2545aa4f; ?>
<?php unset($__componentOriginala48bcfe49d6ae182f80bf7ab2545aa4f); ?>
<?php endif; ?>

            <main class="py-6">
                <?php echo e($slot); ?>

            </main>
        </div>

        <?php if($gitVersion = \App\Support\GitVersion::short()): ?>
            <div class="fixed bottom-2 left-2 z-10 pointer-events-none select-none text-xs font-mono text-brand-charcoal dark:text-brand-grey-blue" title="Deployed commit">
                <?php echo e($gitVersion); ?>

            </div>
        <?php endif; ?>
    </body>
</html>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/layouts/app.blade.php ENDPATH**/ ?>