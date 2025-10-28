<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Accept any username/password combination
        // The username and password will be used directly as InterFAX credentials
        $user = User::firstOrCreate(
            ['username' => $request->username],
            [
                'name' => $request->username,
                'email' => $request->username,
                'password' => Hash::make($request->password), // Hash for Laravel auth
                'fax_number' => '+0000000000', // Default fax number
                'interfax_username' => $request->username, // Store plain text for InterFAX
                'interfax_password' => $request->password, // Store plain text for InterFAX
            ]
        );

        // Update credentials if they changed (for existing users)
        if ($user->interfax_username !== $request->username || $user->interfax_password !== $request->password) {
            $user->update([
                'password' => Hash::make($request->password),
                'interfax_username' => $request->username,
                'interfax_password' => $request->password,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
