<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GiftCode;

class GiftCodeAdminController extends Controller
{
    private function isGiftCodeAdminLoggedIn(): bool
    {
        return session()->get('is_giftcode_admin_verified') === true;
    }

    private function redirectToPasswordPage()
    {
        return redirect()
            ->route('admin.admin.gift.index')
            ->with('error', 'Please enter gift code admin password first.');
    }

    public function index(Request $request)
    {
        $pageTitle = "Gift Code Admin Login";

        if (!$this->isGiftCodeAdminLoggedIn()) {
            return view('admin.admingift.password', compact('pageTitle'));
        }

        $pageTitle = "Gift Code";

        $q = GiftCode::with(['redemptions.user']);

        if ($request->filled('code')) {
            $q->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->filled('is_used')) {
            $q->where('is_used', (int) $request->is_used);
        }

        $giftCodes = $q->latest()
        ->where('is_giftcode_admin',1)
            ->paginate(20)
            ->appends($request->only('code', 'is_used'));

        return view('admin.admingift.index', compact('pageTitle', 'giftCodes'));
    }

    public function verifyPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $adminPassword = env('GIFTCODE_ADMIN_PASSWORD');

        if (!$adminPassword || $request->password !== $adminPassword) {
            return back()->with('error', 'Invalid password.');
        }

        session()->put('is_giftcode_admin_verified', true);

        return redirect()
            ->route('admin.admin.gift.index')
            ->with('success', 'Gift code admin access verified.');
    }

    public function logoutPassword()
    {
        session()->forget('is_giftcode_admin_verified');

        return redirect()
            ->route('admin.admin.gift.index')
            ->with('success', 'Gift code admin session removed.');
    }

    public function generate(Request $request)
    {
        if (!$this->isGiftCodeAdminLoggedIn()) {
            return $this->redirectToPasswordPage();
        }

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1', 'max:5000'],
            'max_users' => ['required', 'integer', 'min:1'],
            'reward_amount' => ['required', 'numeric', 'min:0'],
            'remarks' => ['required', 'string'],
        ]);

        $rows = [];
        $codes = [];
        $now = now();

        for ($mainIndex = 0; $mainIndex < $data['count']; $mainIndex++) {
            do {
                $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $numbers = '0123456789';
                $code = '';

                for ($numIndex = 0; $numIndex < 15; $numIndex++) {
                    $code .= $numbers[random_int(0, 9)];
                }

                $all = $letters . $numbers;

                for ($charIndex = 0; $charIndex < 12; $charIndex++) {
                    $code .= $all[random_int(0, strlen($all) - 1)];
                }

                $code = str_shuffle($code);
            } while (
                GiftCode::where('code', $code)->exists() ||
                in_array($code, $codes)
            );

            $codes[] = $code;

            $rows[] = [
                'code' => $code,
                'reward_amount' => $data['reward_amount'],
                'remarks' => $data['remarks'],
                'max_users' => $data['max_users'],
                'used_count' => 0,
                'is_used' => 0,
                'is_giftcode_admin' => 1,
                'last_used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        GiftCode::insert($rows);

        return redirect()
            ->route('admin.admin.gift.index')
            ->with('success', 'Gift codes generated')
            ->with('generated_codes', $codes);
    }

    public function update(Request $request, GiftCode $giftCode)
    {
        if (!$this->isGiftCodeAdminLoggedIn()) {
            return $this->redirectToPasswordPage();
        }

        $data = $request->validate([
            'code' => ['required', 'max:60', 'unique:gift_codes,code,' . $giftCode->id],
            'reward_amount' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'remarks' => ['required', 'string'],
        ]);

        if ($giftCode->is_used == 1) {
            return back()->with('error', 'This code already locked');
        }

        $giftCode->update([
            'code' => strtoupper($data['code']),
            'reward_amount' => $data['reward_amount'],
            'max_users' => $data['max_users'],
            'remarks' => $data['remarks'],
        ]);

        return redirect()
            ->route('admin.admin.gift.index')
            ->with('success', 'Gift updated');
    }
}