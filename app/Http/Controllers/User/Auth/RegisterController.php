<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use App\Models\PhoneVerification;
use App\Models\Transaction;
use App\Models\User;
use App\Lib\ClientInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        $pageTitle = "Register";
        $invitationCode = $request->query('invitationCode');

        if ($invitationCode) {
            $request->session()->put('register_referral_code', $invitationCode);
        }

        $referralCode = $request->session()->get('register_referral_code', '');

        return UserSpaPage::render('auth/Register', [
            'pageTitle' => $pageTitle,
            'referralCode' => $referralCode,
        ]);
    }

    public function sendSmsOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ], [
            'phone.required' => 'Phone number is required.',
        ]);

        $phone = $this->normalizeBdPhone($request->phone);

        if (!$phone) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Bangladeshi phone number.',
            ], 422);
        }

        if (User::where('phone', $phone)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This phone number is already registered.',
            ], 422);
        }

        $lastActiveOtp = PhoneVerification::where('phone', $phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($lastActiveOtp) {
            $secondsLeft = now()->diffInSeconds($lastActiveOtp->expires_at, false);

            if ($secondsLeft > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Please wait {$secondsLeft} seconds before requesting a new code.",
                    'seconds_left' => $secondsLeft,
                ], 429);
            }
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(5);
        $mobileNumber = $this->toMimSmsFormat($phone);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://api.mimsms.com/api/SmsSending/SMS', [
                "UserName" => env('MIMSMS_USERNAME'),
                "Apikey" => env('MIMSMS_APIKEY'),
                "MobileNumber" => $mobileNumber,
                "CampaignId" => '',
                "SenderName" => env('MIMSMS_SENDER'),
                "TransactionType" => "T",
                "Message" => "Welcome to TradeUx. Your login verification code is: {$otp}. This code will expire in 5 minutes."
            ]);

            $result = $response->json();

            if (!$response->successful() || (($result['statusCode'] ?? null) != "200")) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['responseResult'] ?? 'SMS sending failed.',
                ], 500);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to send verification code right now.',
            ], 500);
        }

        PhoneVerification::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'sent_at' => now(),
            'attempts' => 0,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Verification code sent successfully via SMS.',
            'seconds_left' => 300,
        ]);
    }

    public function register(Request $request)
    {
        $wantsJson = $request->expectsJson() || $request->header('X-User-Spa') === '1';

        $validated = $request->validate([
            'phone' => ['required', 'string'],
            // 'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'referral_code' => ['required', 'string', 'exists:users,referral_code'],
        ], [
            'phone.required' => 'Phone number is required.',
            // 'otp.required' => 'Verification code is required.',
            // 'otp.digits' => 'Verification code must be 6 digits.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'referral_code.required' => 'Referral code is required.',
            'referral_code.exists' => 'Invalid referral code.',
        ]);

        $phone = $this->normalizeBdPhone($validated['phone']);

        if (!$phone) {
            if ($wantsJson) {
                return response()->json([
                    'message' => 'Invalid Bangladeshi phone number.',
                    'errors' => [
                        'phone' => ['Invalid Bangladeshi phone number.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'phone' => 'Invalid Bangladeshi phone number.',
            ])->withInput();
        }

        if (User::where('phone', $phone)->exists()) {
            if ($wantsJson) {
                return response()->json([
                    'message' => 'This phone number is already registered.',
                    'errors' => [
                        'phone' => ['This phone number is already registered.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'phone' => 'This phone number is already registered.',
            ])->withInput();
        }

        // $verification = PhoneVerification::where('phone', $phone)
        //     ->where('otp', $validated['otp'])
        //     ->whereNull('verified_at')
        //     ->latest()
        //     ->first();

        // if (!$verification) {
        //     return back()->withErrors([
        //         'otp' => 'Invalid verification code.',
        //     ])->withInput();
        // }

        // if ($verification->expires_at->isPast()) {
        //     return back()->withErrors([
        //         'otp' => 'Verification code has expired. Please request a new one.',
        //     ])->withInput();
        // }

        // return DB::transaction(function () use ($validated, $phone, $verification) {
        return DB::transaction(function () use ($request, $validated, $phone, $wantsJson) {
            $referredBy = null;

            if (!empty($validated['referral_code'])) {
                $refUser = User::where('referral_code', $validated['referral_code'])
                    ->lockForUpdate()
                    ->first();

                if ($refUser) {
                    $referredBy = $refUser->id;
                }
            }

            do {
                $username = 'user' . random_int(100000, 999999);
            } while (User::where('username', $username)->exists());

            do {
                $newReferralCode = (string) random_int(10000000, 99999999);
            } while (User::where('referral_code', $newReferralCode)->exists());

            $user = User::create([
                'name' => 'User' . random_int(1000, 9999),
                'ip_address' => ClientInfo::get_ip(),
                'username' => $username,
                'email' => null,
                'phone_code' => '+880',
                'phone' => $phone,
                'image' => 3,
                'password' => Hash::make($validated['password']),
                'balance' => gs('signup_bonus_amount') ?? 0,
                'turnover_required' => gs('signup_bonus_amount') ?? 0,
                'referral_code' => $newReferralCode,
                'referred_by' => $referredBy,
            ]);

            if (gs('signup_bonus_amount') > 0) {
                $refTransaction = new Transaction();
                $refTransaction->user_id = $user->id;
                $refTransaction->amount = gs('signup_bonus_amount') ?? 0;
                $refTransaction->charge = 0;
                $refTransaction->post_balance = $user->balance;
                $refTransaction->trx_type = '+';
                $refTransaction->trx = get_trx();
                $refTransaction->details = 'Registration Bonus';
                $refTransaction->remark = "signup_bonus";
                $refTransaction->save();
            }

            // $verification->update([
            //     'verified_at' => now(),
            // ]);

            // PhoneVerification::where('phone', $phone)
            //     ->where('id', '!=', $verification->id)
            //     ->whereNull('verified_at')
            //     ->delete();

            Auth::login($user);
            $request->session()->regenerate();

            if ($wantsJson) {
                return response()->json([
                    'message' => 'Registration Successful!',
                    'toast' => [
                        'message' => 'Registration Successful!',
                        'type' => 'success',
                        'icon' => 'success',
                    ],
                    'authenticated' => true,
                    'data' => [
                        'user' => $user->only(['id', 'name', 'image', 'status']),
                    ],
                    'redirect' => '/games/wingo',
                    'csrf_token' => csrf_token(),
                ]);
            }

            toast('Registration Successful!', 'success', 'success');

            return redirect()->route('user.wingo.game');
        });
    }

    private function normalizeBdPhone(string $phone): ?string
    {
        $phone = trim($phone);
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+880')) {
            $phone = '0' . substr($phone, 4);
        } elseif (str_starts_with($phone, '880')) {
            $phone = '0' . substr($phone, 3);
        } elseif (preg_match('/^1[3-9][0-9]{8}$/', $phone)) {
            $phone = '0' . $phone;
        }

        if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    private function toMimSmsFormat(string $normalizedPhone): string
    {
        return '88' . $normalizedPhone;
    }
}
