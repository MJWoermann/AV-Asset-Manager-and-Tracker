<?php if($paginator->hasPages()): ?>
    <nav role="navigation" aria-label="<?php echo e(__('Pagination Navigation')); ?>">
        <div class="flex gap-2 items-center justify-between sm:hidden">
            <?php if($paginator->onFirstPage()): ?>
                <span class="inline-flex items-center px-4 py-2 text-sm border border-brand-charcoal/30 text-brand-charcoal/50 cursor-not-allowed dark:border-brand-charcoal dark:text-brand-silver/50">
                    <?php echo __('pagination.previous'); ?>

                </span>
            <?php else: ?>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" class="inline-flex items-center px-4 py-2 text-sm border border-brand-charcoal dark:border-brand-silver hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal">
                    <?php echo __('pagination.previous'); ?>

                </a>
            <?php endif; ?>

            <?php if($paginator->hasMorePages()): ?>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" class="inline-flex items-center px-4 py-2 text-sm border border-brand-charcoal dark:border-brand-silver hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal">
                    <?php echo __('pagination.next'); ?>

                </a>
            <?php else: ?>
                <span class="inline-flex items-center px-4 py-2 text-sm border border-brand-charcoal/30 text-brand-charcoal/50 cursor-not-allowed dark:border-brand-charcoal dark:text-brand-silver/50">
                    <?php echo __('pagination.next'); ?>

                </span>
            <?php endif; ?>
        </div>

        <div class="hidden sm:flex sm:items-center">
            <span class="inline-flex rtl:flex-row-reverse">
                <?php if($paginator->onFirstPage()): ?>
                    <span aria-disabled="true" aria-label="<?php echo e(__('pagination.previous')); ?>">
                        <span class="inline-flex items-center px-2 py-2 text-sm border border-brand-charcoal/30 text-brand-charcoal/50 cursor-not-allowed rounded-l-md dark:border-brand-charcoal dark:text-brand-silver/50" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                <?php else: ?>
                    <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" class="inline-flex items-center px-2 py-2 text-sm border border-brand-charcoal dark:border-brand-silver rounded-l-md hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal" aria-label="<?php echo e(__('pagination.previous')); ?>">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php endif; ?>

                <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(is_string($element)): ?>
                        <span aria-disabled="true">
                            <span class="inline-flex items-center px-4 py-2 -ml-px text-sm border border-brand-charcoal dark:border-brand-silver cursor-default"><?php echo e($element); ?></span>
                        </span>
                    <?php endif; ?>

                    <?php if(is_array($element)): ?>
                        <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($page == $paginator->currentPage()): ?>
                                <span aria-current="page">
                                    <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium border border-brand-charcoal bg-brand-silver dark:border-brand-silver dark:bg-brand-charcoal cursor-default"><?php echo e($page); ?></span>
                                </span>
                            <?php else: ?>
                                <a href="<?php echo e($url); ?>" class="inline-flex items-center px-4 py-2 -ml-px text-sm border border-brand-charcoal dark:border-brand-silver hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal" aria-label="<?php echo e(__('Go to page :page', ['page' => $page])); ?>">
                                    <?php echo e($page); ?>

                                </a>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($paginator->hasMorePages()): ?>
                    <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" class="inline-flex items-center px-2 py-2 -ml-px text-sm border border-brand-charcoal dark:border-brand-silver rounded-r-md hover:bg-brand-silver/40 dark:hover:bg-brand-charcoal" aria-label="<?php echo e(__('pagination.next')); ?>">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span aria-disabled="true" aria-label="<?php echo e(__('pagination.next')); ?>">
                        <span class="inline-flex items-center px-2 py-2 -ml-px text-sm border border-brand-charcoal/30 text-brand-charcoal/50 cursor-not-allowed rounded-r-md dark:border-brand-charcoal dark:text-brand-silver/50" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                <?php endif; ?>
            </span>
        </div>
    </nav>
<?php endif; ?>
<?php /**PATH C:\Users\MatthewWoermann\Software Projects\AV-Asset-Manager-and-Tracker\resources\views/vendor/pagination/numbers.blade.php ENDPATH**/ ?>