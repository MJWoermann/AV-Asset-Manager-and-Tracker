<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex justify-between"><h2 class="font-semibold text-xl">Users</h2>
            <a href="<?php echo e(route('admin.users.create')); ?>" class="px-3 py-2 bg-brand text-white text-sm">Add user</a></div>
     <?php $__env->endSlot(); ?>
    <div class="max-w-7xl mx-auto px-4">
        <table class="min-w-full text-sm bg-white dark:bg-brand-slate border">
            <thead class="bg-brand-silver dark:bg-brand-charcoal text-left"><tr>
                <th class="px-3 py-2">Name</th><th class="px-3 py-2">Email</th><th class="px-3 py-2">Role</th><th class="px-3 py-2">Departments</th><th class="px-3 py-2"></th>
            </tr></thead>
            <tbody>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-t border-brand-silver dark:border-brand-charcoal">
                        <td class="px-3 py-2"><?php echo e($user->name); ?></td>
                        <td class="px-3 py-2"><?php echo e($user->email); ?></td>
                        <td class="px-3 py-2"><?php echo e($user->roles->pluck('name')->map(fn (string $name): string => \App\Support\AccessPermissions::roleLabel($name))->join(', ')); ?></td>
                        <td class="px-3 py-2"><?php echo e($user->departments->pluck('name')->join(', ') ?: '—'); ?></td>
                        <td class="px-3 py-2 space-x-3 whitespace-nowrap">
                            <a class="text-brand" href="<?php echo e(route('admin.users.edit', $user)); ?>">Edit</a>
                            <?php if (! ($user->hasRole('admin'))): ?>
                                <form
                                    method="POST"
                                    action="<?php echo e(route('admin.users.destroy', $user)); ?>"
                                    class="inline"
                                    onsubmit='return confirm(<?php echo \Illuminate\Support\Js::from("Delete {$user->name}? This cannot be undone.")->toHtml() ?>)'
                                >
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="text-red-600">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <div class="mt-4"><?php echo e($users->links()); ?></div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/admin/users/index.blade.php ENDPATH**/ ?>