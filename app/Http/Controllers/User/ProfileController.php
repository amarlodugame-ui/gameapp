<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use Illuminate\Http\Request;
use App\Models\ProfileImage;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return UserSpaPage::render('user/profile/Index', [
            'userName' => $user->name,
            'userRefcode' => $user->referral_code,
        ]);
    }

    public function updateName(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:12'],
        ], [
            'name.required' => 'Nickname is required.',
            'name.max' => 'Please do not enter a Nickname with more than 12 characters.',
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->save();

        toast('Nickname updated successfully.', 'success', 'success');

        return redirect()->route('user.profile.setting-center');
    }

    public function avatar(Request $request)
    {
        $avatars = ProfileImage::query()->where('is_active', true)->select('id', 'path')->orderBy('id')->get();

        return UserSpaPage::render('user/profile/Avatar', [
            'avatars' => $avatars,
        ]);
    }
    public function avaterUpdate(Request $request)
    {
        $data = $request->validate([
            'image' => ['required', 'integer', 'exists:profile_images,id'],
        ]);

        $request->user()->update([
            'image' => $data['image'],
        ]);


      toast('Avatar updated', 'success', 'success');
      return redirect()->route('user.profile.setting-center');
    }

    public function loginPassword()
    {
        return UserSpaPage::render('user/profile/LoginPassword');
    }
  
public function updateLoginPassword(Request $request)
{
    $request->validate([
        'current_password' => ['required', 'string'],
        'new_password' => ['required', 'string', 'min:8', 'max:15', 'confirmed'],
    ], [
        'current_password.required' => 'Current password is required.',
        'new_password.required' => 'New password is required.',
        'new_password.min' => 'New password must be at least 8 characters.',
        'new_password.max' => 'New password may not be greater than 15 characters.',
        'new_password.confirmed' => 'Entered twice the password does not match!',
    ]);

    $user = Auth::guard('web')->user();

    if (! $user) {
        throw ValidationException::withMessages([
            'current_password' => 'User not authenticated.',
        ]);
    }

    if (! Hash::check($request->current_password, $user->password)) {
        throw ValidationException::withMessages([
            'current_password' => 'Original login password is incorrect.',
        ]);
    }

    if (Hash::check($request->new_password, $user->password)) {
        throw ValidationException::withMessages([
            'new_password' => 'New password cannot be the same as the current password.',
        ]);
    }

    $user->update([
        'password' => Hash::make($request->new_password),
    ]);
toast('Login password updated successfully.', 'success', 'success');
    return back();
}

        public function balance(Request $request){
            
        $user = $request->user();
        return response()->json([
            'balance' => $user->balance, 
        ]);
    }


}
