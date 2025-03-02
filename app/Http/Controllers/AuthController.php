<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
/*     public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        $token = $request->bearerToken();
        Log::info('Received Token: ' . $token);

        return response()->json([
            'access_token' => $user->createToken('authToken')->plainTextToken,
            'user' => $user
        ]);
    } */
 public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            //log::info($request->password . $user->password);

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Generate a Sanctum token
        $token = $user->createToken('API Token')->plainTextToken;
        //Log::info('test login');
        
        // Return the token and user information
        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
    public function validateToken(Request $request)
    {
        return response()->json(['message' => 'Token is valid']);
    }
}
