<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\ReusableAuthentication;

class AuthController extends Controller
{
    use ReusableAuthentication;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'showLoginForm', 'register']]);
    }

    /**
     * Show the login form
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Process the login request
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // Use the trait method for authentication
            $user = $this->authenticateUser(
                $request->email,
                $request->password,
                $request->boolean('remember')
            );

            // Redirect to intended page or dashboard
            return redirect()->intended(route('reports.overall_revenue_summary'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors($e->errors());
        }
    }

    /**
     * Log the user out
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Use the trait method for logout
        $this->logoutUser();

        return redirect()->route('login');
    }

    /**
     * Generate API token for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createApiToken(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string',
        ]);

        $token = $this->generateApiToken(Auth::user(), $request->device_name);

        return response()->json([
            'token' => $token,
            'device_name' => $request->device_name,
        ]);
    }

    /**
     * Update user password
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Verify current password
        if (!$this->verifyUserPassword($user, $request->current_password)) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match our records.',
            ]);
        }

        // Update password
        $this->updateUserPassword($user, $request->password);

        return redirect()->route('profile.edit')
            ->with('status', 'Password updated successfully.');
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = Auth::user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ]
        ]);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        // For Sanctum, we create a new token instead of refreshing
        $user = Auth::user();
        $deviceName = $request->input('device_name', 'Refreshed Token');

        // Delete current token
        $request->user()->currentAccessToken()->delete();

        // Create a new token
        $token = $this->generateApiToken($user, $deviceName);

        return response()->json([
            'token' => $token,
            'device_name' => $deviceName,
        ]);
    }
}
