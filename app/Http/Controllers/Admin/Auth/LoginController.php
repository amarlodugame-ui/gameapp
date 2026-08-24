<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{

    public function showLoginForm()
    {
        $pageTitle = "Admin Login";
        return view('admin.auth.login', compact('pageTitle'));
    }

    protected function guard()
    {
        return auth()->guard('admin');
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($this->guard()->attempt([
            'email' => $request->email,
            'password' => $request->password
        ], $request->remember)) {

            $request->session()->regenerate();

            $notify[] = ['success', 'Admin Login Successful'];
            return redirect()->route('admin.dashboard')->withNotify($notify);
        }

        return back()->withErrors([
            'email' => 'Invalid email or password'
        ]);
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $notify[] = ['success', 'Admin Logout Successful'];
        return redirect()->route('admin.login')->withNotify($notify);
    }

}