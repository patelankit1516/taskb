<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TaskB\UserDiscounts\Services\DiscountService;
use TaskB\UserDiscounts\Models\Discount;
use TaskB\UserDiscounts\Models\UserDiscount;
use TaskB\UserDiscounts\Models\DiscountAudit;
use App\Models\User;

class DiscountDemoController extends Controller
{
    public function __construct(
        private DiscountService $discountService
    ) {}

    /**
     * Display the demo dashboard
     */
    public function index()
    {
        $discounts = Discount::latest()->get();
        $users = User::latest()->take(10)->get();
        
        return view('discount-demo.index', compact('discounts', 'users'));
    }

    /**
     * Create sample discounts
     */
    public function createSamples()
    {
        try {
            // Create some sample discounts - Each can only be used ONCE per user
            $discounts = [
                [
                    'name' => '10% Summer Sale',
                    'code' => 'SUMMER10',
                    'type' => Discount::TYPE_PERCENTAGE,
                    'value' => 10.00,
                    'is_active' => true,
                    'max_usage_per_user' => 1,  // ONE TIME USE
                    'priority' => 1,
                ],
                [
                    'name' => '20% VIP Discount',
                    'code' => 'VIP20',
                    'type' => Discount::TYPE_PERCENTAGE,
                    'value' => 20.00,
                    'is_active' => true,
                    'max_usage_per_user' => 1,  // ONE TIME USE
                    'priority' => 2,
                ],
                [
                    'name' => '$25 Off First Order',
                    'code' => 'FIRST25',
                    'type' => Discount::TYPE_FIXED,
                    'value' => 25.00,
                    'is_active' => true,
                    'max_usage_per_user' => 1,  // ONE TIME USE
                    'priority' => 3,
                ],
                [
                    'name' => '15% Holiday Special',
                    'code' => 'HOLIDAY15',
                    'type' => Discount::TYPE_PERCENTAGE,
                    'value' => 15.00,
                    'starts_at' => now(),
                    'expires_at' => now()->addDays(30),
                    'is_active' => true,
                    'max_usage_per_user' => 1,  // ONE TIME USE
                    'priority' => 1,
                ],
                [
                    'name' => '5% Loyalty Discount',
                    'code' => 'LOYAL5',
                    'type' => Discount::TYPE_PERCENTAGE,
                    'value' => 5.00,
                    'is_active' => true,
                    'max_usage_per_user' => 1,  // ONE TIME USE
                    'priority' => 5,
                ],
            ];

            $created = 0;
            $updated = 0;
            $restored = 0;
            $errors = [];
            
            foreach ($discounts as $discountData) {
                try {
                    // Check including soft-deleted records
                    $discount = Discount::withTrashed()->where('code', $discountData['code'])->first();
                    
                    if ($discount) {
                        // If soft-deleted, restore it first
                        if ($discount->trashed()) {
                            $discount->restore();
                            $restored++;
                        }
                        $discount->update($discountData);
                        $updated++;
                    } else {
                        Discount::create($discountData);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = $discountData['code'] . ': ' . $e->getMessage();
                }
            }

            $total = Discount::count();
            
            if (!empty($errors)) {
                return redirect()->route('discount-demo.index')
                    ->with('error', 'Errors: ' . implode(' | ', $errors));
            }
            
            $message = "Sample discounts processed! Created: {$created}, Updated: {$updated}";
            if ($restored > 0) {
                $message .= ", Restored: {$restored}";
            }
            $message .= ", Total in DB: {$total}";
            
            return redirect()->route('discount-demo.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('discount-demo.index')
                ->with('error', 'Error creating discounts: ' . $e->getMessage());
        }
    }

    /**
     * Create sample users
     */
    public function createUsers()
    {
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com'],
            ['name' => 'Alice Brown', 'email' => 'alice@example.com'],
            ['name' => 'Charlie Davis', 'email' => 'charlie@example.com'],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, ['password' => bcrypt('password')])
            );
        }

        return redirect()->route('discount-demo.index')
            ->with('success', 'Sample users created successfully!');
    }

    /**
     * Delete all sample discounts
     */
    public function deleteSamples()
    {
        try {
            DB::transaction(function () {
                // Delete in correct order to respect foreign key constraints
                UserDiscount::query()->delete();
                DiscountAudit::query()->delete();
                Discount::query()->delete();
            });

            return redirect()->route('discount-demo.index')
                ->with('success', 'All discounts deleted! You can now create fresh samples with updated settings.');
        } catch (\Exception $e) {
            return redirect()->route('discount-demo.index')
                ->with('error', 'Error deleting discounts: ' . $e->getMessage());
        }
    }

    /**
     * Delete all sample users
     */
    public function deleteUsers()
    {
        DB::transaction(function () {
            // Delete in correct order to respect foreign key constraints
            UserDiscount::query()->delete();
            DiscountAudit::query()->delete();
            User::query()->delete();
        });

        return redirect()->route('discount-demo.index')
            ->with('success', 'All users deleted! You can now create fresh sample users.');
    }

    /**
     * Assign discount to user
     */
    public function assign(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'discount_id' => 'required|exists:discounts,id',
        ]);

        try {
            $this->discountService->assignDiscount(
                userId: $request->user_id,
                discountId: $request->discount_id,
                assignedBy: 'admin'
            );

            return back()->with('success', 'Discount assigned successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Apply discounts (simulate checkout)
     */
    public function apply(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $result = $this->discountService->applyDiscounts(
                userId: $request->user_id,
                amount: $request->amount
            );

            return response()->json([
                'success' => true,
                'original_amount' => number_format($result->originalAmount, 2),
                'discount_amount' => number_format($result->discountAmount, 2),
                'final_amount' => number_format($result->finalAmount, 2),
                'discount_percentage' => number_format($result->getDiscountPercentage(), 2),
                'discounts_applied' => count($result->appliedDiscounts),
                'discounts_list' => array_map(fn($d) => $d['name'], $result->appliedDiscounts),
                'message' => "🎉 You saved $" . number_format($result->discountAmount, 2) . "!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Calculate discounts (preview without applying)
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $result = $this->discountService->calculateDiscounts(
                userId: $request->user_id,
                amount: $request->amount
            );

            return response()->json([
                'success' => true,
                'original_amount' => number_format($result->originalAmount, 2),
                'discount_amount' => number_format($result->discountAmount, 2),
                'final_amount' => number_format($result->finalAmount, 2),
                'discount_percentage' => number_format($result->getDiscountPercentage(), 2),
                'discounts_available' => count($result->appliedDiscounts),
                'discounts_list' => array_map(fn($d) => $d['name'], $result->appliedDiscounts),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Revoke discount
     */
    public function revoke(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'discount_id' => 'required|exists:discounts,id',
        ]);

        try {
            $this->discountService->revokeDiscount(
                userId: $request->user_id,
                discountId: $request->discount_id,
                revokedBy: 'admin'
            );

            return back()->with('success', 'Discount revoked successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get user's eligible discounts
     */
    public function eligible(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $discounts = $this->discountService->getEligibleDiscounts($request->user_id);

        return response()->json([
            'success' => true,
            'count' => $discounts->count(),
            'discounts' => $discounts->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'type' => $d->type,
                'value' => $d->value,
                'usage_remaining' => $d->pivot->max_usage_per_user - $d->pivot->usage_count,
            ])
        ]);
    }

    /**
     * View audit history
     */
    public function audits(Request $request)
    {
        $userId = $request->input('user_id');
        
        $audits = DiscountAudit::when($userId, function($q, $userId) {
                return $q->where('user_id', $userId);
            })
            ->with(['discount', 'user'])
            ->latest('created_at')
            ->paginate(20);

        return view('discount-demo.audits', compact('audits'));
    }

    /**
     * User's discount status
     */
    public function userStatus($userId)
    {
        $user = User::findOrFail($userId);
        $eligibleDiscounts = $this->discountService->getEligibleDiscounts($userId);
        
        $assignedDiscounts = UserDiscount::where('user_id', $userId)
            ->with('discount')
            ->get();

        $audits = DiscountAudit::where('user_id', $userId)
            ->with('discount')
            ->latest('created_at')
            ->take(20)
            ->get();

        return view('discount-demo.user-status', compact(
            'user',
            'eligibleDiscounts',
            'assignedDiscounts',
            'audits'
        ));
    }
}
