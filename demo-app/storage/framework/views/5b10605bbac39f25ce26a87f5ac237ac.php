<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Discount Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">📜 Audit Trail</h1>
                <a href="<?php echo e(route('discount-demo.index')); ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="mb-4">
                <h2 class="text-xl font-semibold mb-2">Discount Operations History</h2>
                <p class="text-gray-600">Complete audit log of all discount-related actions</p>
            </div>

            <?php if($audits->isEmpty()): ?>
                <div class="text-center py-12 text-gray-500">
                    <p class="text-lg">No audit records found</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php $__currentLoopData = $audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $audit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm"><?php echo e($audit->created_at->format('M d, Y H:i:s')); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if($audit->user): ?>
                                            <div class="font-semibold"><?php echo e($audit->user->name); ?></div>
                                            <div class="text-xs text-gray-500">ID: <?php echo e($audit->user_id); ?></div>
                                        <?php else: ?>
                                            <span class="font-mono text-gray-600">ID: <?php echo e($audit->user_id); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if($audit->discount): ?>
                                            <div class="font-semibold"><?php echo e($audit->discount->name); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo e($audit->discount->code); ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-semibold
                                            <?php if($audit->action === 'assigned'): ?> bg-green-100 text-green-800
                                            <?php elseif($audit->action === 'applied'): ?> bg-blue-100 text-blue-800
                                            <?php elseif($audit->action === 'revoked'): ?> bg-red-100 text-red-800
                                            <?php else: ?> bg-gray-100 text-gray-800
                                            <?php endif; ?>">
                                            <?php echo e(ucfirst($audit->action)); ?>

                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if($audit->amount): ?>
                                            $<?php echo e(number_format($audit->amount, 2)); ?>

                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?php echo e($audit->performed_by ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <?php echo e($audits->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/laravel/taskb/demo-app/resources/views/discount-demo/audits.blade.php ENDPATH**/ ?>