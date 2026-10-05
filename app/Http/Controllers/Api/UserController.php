<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Everyone can list people (for owner / member pickers). */
    public function index()
    {
        return User::orderByDesc('active')->orderBy('name')
            ->get(['id', 'name', 'email', 'title', 'role', 'active', 'auth_source']);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $this->validated($request);
        $temp = $data['password'] ?? Str::password(12, symbols: false);
        $user = User::create([...$data, 'password' => $temp]);

        return response()->json(['user' => $user, 'temporary_password' => empty($request->password) ? $temp : null], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $this->validated($request, $user);
        if ($user->is($request->user())) {
            $data['role'] = 'admin';   // never lock yourself out
            $data['active'] = true;
        }
        $temp = null;
        if ($request->boolean('reset_password')) {
            $temp = Str::password(12, symbols: false);
            $data['password'] = $temp;
        } elseif (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return ['user' => $user, 'temporary_password' => $temp];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user?->id)],
            'title' => 'nullable|string|max:120',
            'role' => ['required', Rule::in(array_keys(Pulse::ROLES))],
            'active' => 'boolean',
            'password' => 'nullable|string|min:8',
        ]);
        $data['email'] = strtolower($data['email']);

        return $data;
    }
}
