<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Status - {{ $user->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">👤 {{ $user->name }}</h1>
                    <p class="text-gray-600">{{ $user->email }}</p>
                </div>
                <a href="{{ route('discount-demo.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Eligible Discounts -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-green-700">✅ Eligible Discounts (Can Use Now)</h2>
                
                @if($eligibleDiscounts->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <p>No eligible discounts available</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($eligibleDiscounts as $discount)
                            <div class="border-2 border-green-200 rounded-lg p-4 bg-green-50">
                                <div class="font-bold text-lg">{{ $discount->name }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span class="font-mono bg-white px-2 py-1 rounded">{{ $discount->code }}</span>
                                </div>
                                <div class="mt-2 text-sm">
                                    <span class="text-blue-600 font-semibold">
                                        {{ $discount->type === 'percentage' ? $discount->value . '% OFF' : '$' . $discount->value . ' OFF' }}
                                    </span>
                                </div>
                                @if($discount->userDiscounts->isNotEmpty())
                                <div class="mt-2 text-xs text-gray-600">
                                    Usage: {{ $discount->userDiscounts->first()->usage_count }} / {{ $discount->userDiscounts->first()->max_usage_per_user }}
                                </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Assigned Discounts -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4 text-blue-700">📊 All Assigned Discounts</h2>
                
                @if($assignedDiscounts->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <p>No discounts assigned</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($assignedDiscounts as $userDiscount)
                            <div class="border rounded-lg p-4 {{ $userDiscount->isActive() ? 'bg-blue-50' : 'bg-gray-50' }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-bold">{{ $userDiscount->discount->name }}</div>
                                        <div class="text-sm text-gray-600 mt-1">{{ $userDiscount->discount->code }}</div>
                                    </div>
                                    @if($userDiscount->isActive())
                                        <form action="{{ route('discount-demo.revoke') }}" method="POST" onsubmit="return confirm('Are you sure you want to revoke this discount?')">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="discount_id" value="{{ $userDiscount->discount_id }}">
                                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">
                                                🗑️ Revoke
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="mt-2 flex justify-between items-center text-xs">
                                    <span>Usage: {{ $userDiscount->usage_count }} / {{ $userDiscount->discount->max_usage_per_user }}</span>
                                    <span class="px-2 py-1 rounded {{ $userDiscount->isActive() ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                        {{ $userDiscount->isActive() ? 'Active' : 'Revoked' }}
                                    </span>
                                </div>
                                @if($userDiscount->revoked_at)
                                    <div class="mt-2 text-xs text-red-600">
                                        Revoked: {{ $userDiscount->revoked_at->format('M d, Y H:i') }}
                                        @if($userDiscount->revoked_by)
                                            by {{ $userDiscount->revoked_by }}
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4">📜 Recent Activity</h2>
            
            @if($audits->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    <p>No activity found</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($audits as $audit)
                        <div class="border rounded p-3 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-semibold">{{ ucfirst($audit->action) }}</span>
                                    @if($audit->discount)
                                        - {{ $audit->discount->name }}
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500">{{ $audit->created_at->diffForHumans() }}</div>
                            </div>
                            @if($audit->amount)
                                <div class="text-sm text-gray-600 mt-1">Amount: ${{ number_format($audit->amount, 2) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>
