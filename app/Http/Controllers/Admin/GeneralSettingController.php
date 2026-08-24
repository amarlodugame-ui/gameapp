<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class GeneralSettingController extends Controller
{
    public function index()
    {
        $pageTitle = "General Control";
        $settingRow = GeneralSetting::first();

        return view('admin.general.index', compact('pageTitle', 'settingRow'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name'           => 'required|string|max:255',
            'cur_text'            => 'required|string|max:20',
            'cur_sym'             => 'required|string|max:20',
            'signup_bonus_amount' => 'nullable|numeric|min:0',
            'paw_download'        => 'nullable',
        ]);

        $settingRow = GeneralSetting::first();

        if (!$settingRow) {
            $settingRow = new GeneralSetting();
        }

        $settingRow->site_name = $request->site_name;
        $settingRow->cur_text = $request->cur_text;
        $settingRow->cur_sym = $request->cur_sym;
        $settingRow->maintenance_mode = $request->has('maintenance_mode') ? 1 : 0;
        $settingRow->registration = $request->has('registration') ? 1 : 0;
        $settingRow->signup_bonus_amount = $request->signup_bonus_amount ?? 0;
        $settingRow->deposit_commission = $request->has('deposit_commission') ? 1 : 0;
        $settingRow->paw_download = $request->paw_download;

        $settingRow->save();

       $notify[] = ['success', 'General settings updated successfully'];
       return back()->withNotify($notify);
    }

    public function passwordForm()
    {
        $pageTitle = 'Change Password';
        return view('admin.general.password', compact('pageTitle'));
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.different' => 'New password must be different from current password.',
        ]);

        $admin = auth('admin')->user();

        if (!$admin) {
            $notify[] = ['error', 'Admin is not authenticated.'];
            return back()->withNotify($notify);
        }

        if (!Hash::check($request->current_password, $admin->password)) {
         $notify[] = ['error', 'Current password is incorrect.'];
         return back()->withNotify($notify);
        }

        $admin->password = Hash::make($request->password);
        $admin->remember_token = \Str::random(60);
        $admin->save();

       $notify[] = ['success', 'Password changed successfully'];
       return back()->withNotify($notify);
    }







}