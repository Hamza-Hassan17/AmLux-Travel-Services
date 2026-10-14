<?php

namespace App\Http\Controllers;

use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', ['agency' => Settings::all(), 'user' => auth()->user()]);
    }

    public function agency(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'tagline' => 'nullable|string|max:160',
            'head_office' => 'nullable|string|max:255',
            'helpline' => 'nullable|string|max:40',
            'mobile' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:255',
            'reservations_email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'default_currency' => 'required|alpha|size:3',
        ]);
        $data['default_currency'] = strtoupper($data['default_currency']);

        Settings::save($data);

        return redirect()->route('settings.index')->with('success', 'Agency details saved. They now appear on new invoices and vouchers.');
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $user->update($data);

        return redirect()->route('settings.index')->with('success', 'Your profile was updated.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('settings.index')->with('success', 'Your password was changed.');
    }
}
