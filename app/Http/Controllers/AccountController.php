<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        return view('accounts.index', ['accounts' => Account::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:accounts,name',
            'type' => 'required|in:cash,bank,gateway',
            'opening_balance' => 'nullable|numeric',
        ]);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        Account::create($data);

        return redirect()->route('accounts.index')->with('success', 'Account added.');
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:accounts,name,' . $account->id,
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
        ]);
        $account->update([
            'name' => $data['name'],
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('accounts.index')->with('success', 'Account updated.');
    }
}
