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
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="rounded border border-brand bg-white dark:bg-brand-slate px-4 py-3 text-sm text-brand-teal dark:text-brand-teal-bright">
                        <?php echo e(session('status')); ?>

                    </div>
                </div>
            <?php endif; ?>
            <?php if(session('warning')): ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
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