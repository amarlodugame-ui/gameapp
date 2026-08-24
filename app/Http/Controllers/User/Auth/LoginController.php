<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Lib\ClientInfo;
class LoginController extends Controller
{
    public function showLoginForm()
    {
        $pageTitle = "Login";
        return UserSpaPage::render('auth/Login', compact('pageTitle'));
    }

    public function dev()
    {
        $pageTitle = "dev Login";
        return UserSpaPage::render('dev/HeaderGuide', compact('pageTitle'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => ['nullable','regex:/^[0-9]{8,15}$/'],
            'email' => ['nullable','email:rfc,dns'],
            'password' => ['required','string'],
        ]);
        

        if (!$request->phone && !$request->email) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'message' => 'Please provide either phone number or email.',
                    'errors' => [
                        'login' => ['Please provide either phone number or email.'],
                    ],
                ], 422);
            }

            toast('Please provide either phone number or email.', 'error', 'cross');
            return back()->withInput();
        }



        $phone = $request->phone ? trim($request->phone) : null;
        if ($phone) {
            $phone = preg_replace('/^\+?880/', '', $phone);
            $phone = ltrim($phone, '0');
        }
        
        $user = User::query()
            ->when($phone, function ($q) use ($phone) {
                $q->whereRaw("TRIM(LEADING '0' FROM phone) = ?", [$phone]);
            })
            ->when($request->email, fn($q) => $q->where('email', trim($request->email)))
            ->first();


        // $user = User::query()
        //     ->when($request->phone, fn($q) => $q->where('phone', trim($request->phone)))
        //     ->when($request->email, fn($q) => $q->where('email', trim($request->email)))
        //     ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'message' => 'Invalid credentials.',
                    'errors' => [
                        'login' => ['Invalid credentials.'],
                    ],
                ], 422);
            }

            toast('Invalid credentials.', 'error', 'cross');
            return back()->withInput();
        }

        if ($user->status != 1) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'message' => 'Your account has been blocked.',
                    'errors' => [
                        'account' => ['Your account has been blocked.'],
                    ],
                ], 403);
            }

            toast('Your account has been blocked.', 'error', 'cross');
            return back()->withInput();
        }

        Auth::login($user, $request->remember);
        $request->session()->regenerate();

        $info = json_decode(json_encode(getIpInfo()), true) ?? [];

        UserLogin::create([
            'user_id' => Auth::id(),
            'longitude' => $info['long'] ?? null,
            'latitude' => $info['lat'] ?? null,
            'country' => $info['country'] ?? null,
            'user_ip' => ClientInfo::get_ip(),
            'browser' => ClientInfo::get_browsers(),
            'os' => ClientInfo::get_os(),
            'get_device' => ClientInfo::get_device(),
        ]);
        createUserNotification(Auth::id(), 'LOGIN NOTIFICATION', 'Your account is logged in ' . now()->format('Y-m-d H:i:s'), 'security');

        // if user ip adddress not null
        if($user->ip_address == null){
           $user->ip_address = ClientInfo::get_ip();
           $user->save();
        }
        session()->forget('announcements_shown');

        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => 'Login Successful!',
                'toast' => [
                    'message' => 'Login Successful!',
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

        toast('Login Successful!', 'success','success');
        return redirect()->route('user.wingo.game');
    }
    


        public function logout()
        {
            Auth::guard('web')->logout(); 

            request()->session()->invalidate();
            request()->session()->regenerateToken();
            session()->forget('announcements_shown');
            toast('Logged out successfully.', 'success', 'success');
            return to_route('user.login');
        }
        public function rpwd(){
            return UserSpaPage::render('auth/Rpwd');
        }

        private function wantsJson(Request $request): bool
        {
            return $request->expectsJson() || $request->header('X-User-Spa') === '1';
        }
}
