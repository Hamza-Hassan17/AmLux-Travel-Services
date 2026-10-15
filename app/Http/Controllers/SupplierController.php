<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $suppliers = Supplier::when($q, function ($query) use ($q) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where('name', 'like', $like)->orWhere('phone', 'like', $like);
        })->orderBy('name')->paginate(15)->withQueryString();

        return view('suppliers.index', ['suppliers' => $suppliers, 'q' => $q]);
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier(['opening_balance' => 0])]);
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier saved.');
    }

    public function show(Supplier $supplier)
    {
        $lines = $supplier->lines()->with(['invoice.customer', 'refunds'])->latest('id')->get();
        $payments = $supplier->payments()->with('account')->latest('paid_on')->latest('id')->get();

        return view('suppliers.show', ['supplier' => $supplier, 'lines' => $lines, 'payments' => $payments]);
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    private function validated(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        return $data;
    }
}
