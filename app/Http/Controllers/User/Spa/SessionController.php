<?php

namespace App\Http\Controllers\User\Spa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user('web');

        return response()->json([
            'authenticated' => $user !== null,
            'user' => $this->userPayload($user),
            'name' => config('app.name'),
            'flash' => [
                'toast' => $request->session()->get('toast'),
            ],
            'csrf_token' => csrf_token(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->forget('announcements_shown');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
            'authenticated' => false,
            'user' => null,
            'redirect' => '/login',
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function userPayload(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'image' => $user->image,
            'status' => (bool) $user->status,
        ];
    }
}
