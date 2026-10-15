<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $customers = Customer::where('type', 'customer')->withCount('invoices')
            ->when($q, function ($query) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like)->orWhere('passport_no', 'like', $like);
                });
            })
            ->latest()->paginate(15)->withQueryString();

        return view('customers.index', ['customers' => $customers, 'q' => $q]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'passport_no' => 'nullable|string|max:30',
            'nationality' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:255',
            'tax_no' => 'nullable|string|max:40',
            'date_of_birth' => 'nullable|date',
            'country' => 'nullable|string|max:60',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer saved. You can now create an invoice for them.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', ['customer' => $customer->load(['invoices.lines', 'receipts.account'])]);
    }
}
