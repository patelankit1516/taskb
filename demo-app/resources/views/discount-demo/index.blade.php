<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Discount System Demo - TaskB User Discounts Package</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-xl p-8 mb-6">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">🎁 Discount System Demo</h1>
            <p class="text-gray-600 text-lg">Test the User Discounts Laravel Package (TaskB Interview Assignment)</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-4 shadow">
                <p class="font-bold">✅ Success!</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4 shadow">
                <p class="font-bold">❌ Error!</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Setup Section -->
        @if($discounts->isEmpty() || $users->isEmpty())
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 mb-6 rounded shadow">
                <h3 class="text-lg font-bold text-yellow-800 mb-3">⚠️ Initial Setup Required</h3>
                <p class="text-yellow-700 mb-4">Create sample data to start testing the discount system.</p>
                <div class="flex gap-3">
                    @if($discounts->isEmpty())
                        <form action="{{ route('discount-demo.create-samples') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold shadow-lg transition">
                                📊 Create Sample Discounts
                            </button>
                        </form>
                    @endif
                    @if($users->isEmpty())
                        <form action="{{ route('discount-demo.create-users') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-semibold shadow-lg transition">
                                👥 Create Sample Users
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Available Discounts -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">📊 Available Discounts</h2>
                    <div class="flex gap-2 items-center">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-bold">{{ $discounts->count() }} Total</span>
                        @if($discounts->isNotEmpty())
                            <form action="{{ route('discount-demo.delete-samples') }}" method="POST" onsubmit="return confirm('Delete all discounts and start fresh?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 text-xs font-semibold">
                                    🗑️ Delete All
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                
                @if($discounts->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-lg mb-4">No discounts available</p>
                        <form action="{{ route('discount-demo.create-samples') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                                Create Sample Discounts
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($discounts as $discount)
                            <div class="border-2 rounded-lg p-4 {{ $discount->is_active ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }} transition hover:shadow-md">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-bold text-lg text-gray-800">{{ $discount->name }}</div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <span class="font-mono bg-gray-200 px-2 py-1 rounded">{{ $discount->code }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ $discount->type === 'percentage' ? $discount->value . '%' : '$' . number_format($discount->value, 2) }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ ucfirst($discount->type) }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    <span class="bg-white px-2 py-1 rounded border">Max Usage: {{ $discount->max_usage_per_user }}</span>
                                    <span class="bg-white px-2 py-1 rounded border">Priority: {{ $discount->priority }}</span>
                                    <span class="px-2 py-1 rounded {{ $discount->is_active ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                        {{ $discount->is_active ? '✅ Active' : '❌ Inactive' }}
                                    </span>
                                    @if($discount->expires_at)
                                        <span class="bg-orange-200 text-orange-800 px-2 py-1 rounded">
                                            Expires: {{ $discount->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Registered Users -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">👥 Registered Users</h2>
                    <div class="flex gap-2 items-center">
                        <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm font-bold">{{ $users->count() }} Users</span>
                        @if($users->isNotEmpty())
                            <form action="{{ route('discount-demo.delete-users') }}" method="POST" onsubmit="return confirm('Delete all users? This will also remove their discount assignments.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 text-xs font-semibold">
                                    🗑️ Delete All
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                
                @if($users->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <p class="text-lg mb-4">No users available</p>
                        <form action="{{ route('discount-demo.create-users') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition">
                                Create Sample Users
                            </button>
                        </form>
                    </div>
                @else
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach($users as $user)
                            <div class="border rounded-lg p-3 hover:bg-blue-50 transition cursor-pointer" 
                                 onclick="window.location='{{ route('discount-demo.user-status', $user->id) }}'">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-600">{{ $user->email }}</div>
                                    </div>
                                    <div class="text-blue-500 hover:text-blue-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Assign Discount Section -->
        @if($discounts->isNotEmpty() && $users->isNotEmpty())
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">➕ Assign Discount to User</h2>
                
                <form action="{{ route('discount-demo.assign') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Select User</label>
                        <select name="user_id" class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none" required>
                            <option value="">Choose a user...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Select Discount</label>
                        <select name="discount_id" class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-blue-500 focus:outline-none" required>
                            <option value="">Choose a discount...</option>
                            @foreach($discounts as $discount)
                                <option value="{{ $discount->id }}">
                                    {{ $discount->name }} - {{ $discount->code }} ({{ $discount->type === 'percentage' ? $discount->value . '%' : '$' . $discount->value }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-semibold shadow-lg transition">
                            ✅ Assign Discount
                        </button>
                    </div>
                </form>
            </div>

            <!-- Apply Discounts (Checkout Simulation) -->
            <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg shadow-xl p-6 mb-6 border-2 border-green-200">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">💰 Apply Discounts (Simulate Checkout)</h2>
                <p class="text-gray-600 mb-4">Test discount calculation and application with real-time results.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Select User</label>
                        <select id="apply-user" class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-green-500 focus:outline-none">
                            <option value="">Choose a user...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Cart Amount ($)</label>
                        <input type="number" id="apply-amount" 
                               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:border-green-500 focus:outline-none" 
                               placeholder="100.00" step="0.01" min="0.01" value="100.00">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button onclick="calculateDiscount()" 
                            class="bg-yellow-500 text-white px-6 py-4 rounded-lg hover:bg-yellow-600 font-bold shadow-lg transition transform hover:scale-105">
                        💡 Preview (Calculate Only)
                        <span class="block text-xs mt-1 font-normal">Does NOT use the discount</span>
                    </button>
                    <button onclick="applyDiscount()" 
                            class="bg-green-600 text-white px-6 py-4 rounded-lg hover:bg-green-700 font-bold shadow-lg transition transform hover:scale-105">
                        ✅ Apply Discount (Use It)
                        <span class="block text-xs mt-1 font-normal">Increments usage counter</span>
                    </button>
                </div>

                <!-- Results Display -->
                <div id="discount-result" class="mt-6 hidden">
                    <div class="bg-white border-4 border-green-300 rounded-xl p-6 shadow-xl">
                        <h3 class="text-xl font-bold mb-4 text-gray-800">📊 Discount Calculation Result</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-sm text-gray-600 mb-1">Original Amount</div>
                                <div class="text-3xl font-bold text-gray-800">$<span id="result-original">0</span></div>
                            </div>
                            <div class="text-center p-4 bg-red-50 rounded-lg">
                                <div class="text-sm text-gray-600 mb-1">Discount Amount</div>
                                <div class="text-3xl font-bold text-red-600">-$<span id="result-discount">0</span></div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-sm text-gray-600 mb-1">Final Amount</div>
                                <div class="text-3xl font-bold text-green-600">$<span id="result-final">0</span></div>
                            </div>
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-sm text-gray-600 mb-1">You Saved</div>
                                <div class="text-3xl font-bold text-blue-600"><span id="result-percentage">0</span>%</div>
                            </div>
                        </div>
                        <div id="result-message" class="text-center text-lg font-semibold p-4 bg-gradient-to-r from-green-100 to-blue-100 rounded-lg"></div>
                        <div id="result-discounts" class="mt-3 text-sm text-gray-600"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Quick Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ route('discount-demo.audits') }}" 
               class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gradient-to-br hover:from-purple-50 hover:to-blue-50 transition transform hover:scale-105 cursor-pointer">
                <div class="text-4xl mb-3">📜</div>
                <div class="font-bold text-lg text-gray-800">Audit Trail</div>
                <div class="text-sm text-gray-600 mt-1">View all operations</div>
            </a>

            @if($users->isNotEmpty())
                <a href="{{ route('discount-demo.user-status', $users->first()->id) }}" 
                   class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gradient-to-br hover:from-blue-50 hover:to-green-50 transition transform hover:scale-105 cursor-pointer">
                    <div class="text-4xl mb-3">👤</div>
                    <div class="font-bold text-lg text-gray-800">User Status</div>
                    <div class="text-sm text-gray-600 mt-1">Check user discounts</div>
                </a>
            @endif

            <a href="https://github.com/patelankit1516/taskb" target="_blank"
               class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gradient-to-br hover:from-gray-50 hover:to-purple-50 transition transform hover:scale-105 cursor-pointer">
                <div class="text-4xl mb-3">📚</div>
                <div class="font-bold text-lg text-gray-800">Documentation</div>
                <div class="text-sm text-gray-600 mt-1">View package docs</div>
            </a>

            <a href="{{ route('discount-demo.index') }}" 
               class="bg-white rounded-lg shadow-lg p-6 text-center hover:bg-gradient-to-br hover:from-green-50 hover:to-yellow-50 transition transform hover:scale-105 cursor-pointer">
                <div class="text-4xl mb-3">🔄</div>
                <div class="font-bold text-lg text-gray-800">Refresh</div>
                <div class="text-sm text-gray-600 mt-1">Reload the page</div>
            </a>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-600 text-sm">
            <p class="mb-2">Built with ❤️ for TaskB Interview Assignment</p>
            <p>Laravel User Discounts Package - Demonstrating SOLID Principles & Design Patterns</p>
        </div>
    </div>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function calculateDiscount() {
            const userId = $('#apply-user').val();
            const amount = $('#apply-amount').val();

            if (!userId || !amount) {
                alert('⚠️ Please select user and enter amount');
                return;
            }

            $.post('{{ route("discount-demo.calculate") }}', {
                user_id: userId,
                amount: amount
            }, function(data) {
                showResult(data, false);
            }).fail(function(xhr) {
                alert('❌ Error: ' + (xhr.responseJSON?.error || 'Unknown error occurred'));
            });
        }

        function applyDiscount() {
            const userId = $('#apply-user').val();
            const amount = $('#apply-amount').val();

            if (!userId || !amount) {
                alert('⚠️ Please select user and enter amount');
                return;
            }

            if (!confirm('Are you sure you want to APPLY this discount? This will increment the usage counter.')) {
                return;
            }

            $.post('{{ route("discount-demo.apply") }}', {
                user_id: userId,
                amount: amount
            }, function(data) {
                showResult(data, true);
            }).fail(function(xhr) {
                alert('❌ Error: ' + (xhr.responseJSON?.error || 'Unknown error occurred'));
            });
        }

        function showResult(data, isApplied) {
            $('#result-original').text(data.original_amount);
            $('#result-discount').text(data.discount_amount);
            $('#result-final').text(data.final_amount);
            $('#result-percentage').text(data.discount_percentage);
            
            const discountsList = data.discounts_list ? data.discounts_list.join(', ') : 'None';
            
            if (isApplied) {
                $('#result-message').html(
                    '<div class="text-green-700 text-xl">' + data.message + '</div>' +
                    '<div class="text-sm text-gray-600 mt-2">✅ Usage counter has been incremented</div>'
                );
                $('#result-discounts').html(
                    '<strong>Applied Discounts:</strong> ' + discountsList
                );
            } else {
                $('#result-message').html(
                    '<div class="text-blue-700 text-xl">💡 Preview Mode - ' + data.discounts_available + ' discount(s) available</div>' +
                    '<div class="text-sm text-gray-600 mt-2">ℹ️ This is a preview. Usage counter NOT incremented.</div>'
                );
                $('#result-discounts').html(
                    '<strong>Available Discounts:</strong> ' + discountsList
                );
            }
            
            $('#discount-result').removeClass('hidden').addClass('animate-fade-in');
        }
    </script>
</body>
</html>
