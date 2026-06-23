<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('role')->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|in:sso_admin,admin,approval,user,viewer',
            'is_active' => 'required|boolean',
        ]);

        $user->update($data);

        return back()->with('success', 'User berhasil diperbarui.');
    }
}
