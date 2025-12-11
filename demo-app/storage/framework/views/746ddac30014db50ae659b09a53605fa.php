<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Status - <?php echo e($user->name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">👤 <?php echo e($user->name); ?></h1>
                    <p class="text-gray-600"><?php echo e($user->email); ?></p>
                </div>
                <a href="<?php echo e(route('discount-demo.index')); ?>" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Eligible Discounts -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-green-700">✅ Eligible Discounts (Can Use Now)</h2>
                
                <?php if($eligibleDiscounts->isEmpty()): ?>
                    <div class="text-center py-8 text-gray-500">
                        <p>No eligible discounts available</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $eligibleDiscounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $discount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="border-2 border-green-200 rounded-lg p-4 bg-green-50">
                                <div class="font-bold text-lg"><?php echo e($discount->name); ?></div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span class="font-mono bg-white px-2 py-1 rounded"><?php echo e($discount->code); ?></span>
                                </div>
                                <div class="mt-2 text-sm">
                                    <span class="text-blue-600 font-semibold">
                                        <?php echo e($discount->type === 'percentage' ? $discount->value . '% OFF' : '$' . $discount->value . ' OFF'); ?>

                                    </span>
                                </div>
                                <?php if($discount->userDiscounts->isNotEmpty()): ?>
                                <div class="mt-2 text-xs text-gray-600">
                                    Usage: <?php echo e($discount->userDiscounts->first()->usage_count); ?> / <?php echo e($discount->userDiscounts->first()->max_usage_per_user); ?>

                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Assigned Discounts -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-blue-700">📊 All Assigned Discounts</h2>
                
                <?php if($assignedDiscounts->isEmpty()): ?>
                    <div class="text-center py-8 text-gray-500">
                        <p>No discounts assigned</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $assignedDiscounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $userDiscount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="border rounded-lg p-4 <?php echo e($userDiscount->isActive() ? 'bg-blue-50' : 'bg-gray-50'); ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-bold"><?php echo e($userDiscount->discount->name); ?></div>
                                        <div class="text-sm text-gray-600 mt-1"><?php echo e($userDiscount->discount->code); ?></div>
                                    </div>
                                    <?php if($userDiscount->isActive()): ?>
                                        <form action="<?php echo e(route('discount-demo.revoke')); ?>" method="POST" onsubmit="return confirm('Are you sure you want to revoke this discount?')">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?php echo e($user->id); ?>">
                                            <input type="hidden" name="discount_id" value="<?php echo e($userDiscount->discount_id); ?>">
                                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">
                                                🗑️ Revoke
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2 flex justify-between items-center text-xs">
                                    <span>Usage: <?php echo e($userDiscount->usage_count); ?> / <?php echo e($userDiscount->discount->max_usage_per_user); ?></span>
                                    <span class="px-2 py-1 rounded <?php echo e($userDiscount->isActive() ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'); ?>">
                                        <?php echo e($userDiscount->isActive() ? 'Active' : 'Revoked'); ?>

                                    </span>
                                </div>
                                <?php if($userDiscount->revoked_at): ?>
                                    <div class="mt-2 text-xs text-red-600">
                                        Revoked: <?php echo e($userDiscount->revoked_at->format('M d, Y H:i')); ?>

                                        <?php if($userDiscount->revoked_by): ?>
                                            by <?php echo e($userDiscount->revoked_by); ?>

                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4">📜 Recent Activity</h2>
            
            <?php if($audits->isEmpty()): ?>
                <div class="text-center py-8 text-gray-500">
                    <p>No activity found</p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php $__currentLoopData = $audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $audit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="border rounded p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-semibold"><?php echo e(ucfirst($audit->action)); ?></span>
                                    <?php if($audit->discount): ?>
                                        - <?php echo e($audit->discount->name); ?>

                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo e($audit->created_at->diffForHumans()); ?></div>
                            </div>
                            <?php if($audit->amount): ?>
                                <div class="text-sm text-gray-600 mt-1">Amount: $<?php echo e(number_format($audit->amount, 2)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/laravel/taskb/demo-app/resources/views/discount-demo/user-status.blade.php ENDPATH**/ ?>