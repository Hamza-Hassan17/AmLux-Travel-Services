<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index', ['customers' => Customer::latest()->paginate(15)]);
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
            'email' => 'required|email|max:255',
            'passport_no' => 'required|string|max:30',
            'nationality' => 'required|string|max:60',
            'address' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create($data);

        return redirect()->route('flights.create', ['customer_id' => $customer->id])
            ->with('success', 'Customer saved. Now enter the flight details.');
    }

    public function show(Customer $customer)
    {
        return view('customers.show', ['customer' => $customer->load('flightBookings')]);
    }
}
