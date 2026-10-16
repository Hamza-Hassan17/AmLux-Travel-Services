<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Refund;
use App\Models\Supplier;
use App\Support\Numbering;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $query = Payment::with(['supplier', 'customer', 'account'])->latest('paid_on')->latest('id');
        if ($request->query('from')) {
            $query->whereDate('paid_on', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->whereDate('paid_on', '<=', $request->query('to'));
        }
        if ($q) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('payment_no', 'like', $like)->orWhere('reference', 'like', $like)
                    ->orWhereHas('supplier', function ($s) use ($like) {
                        $s->where('name', 'like', $like);
                    })
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like);
                    });
            });
        }

        return view('payments.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'total' => (clone $query)->get()->sum('amount'),
            'filters' => ['q' => $q, 'from' => $request->query('from'), 'to' => $request->query('to')],
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('payee_type') === 'customer' ? 'customer' : 'supplier';
        $supplier = $type === 'supplier' && $request->query('supplier_id') ? Supplier::find($request->query('supplier_id')) : null;
        $customer = $type === 'customer' && $request->query('customer_id') ? Customer::find($request->query('customer_id')) : null;

        return view('payments.create', [
            'type' => $type,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'supplier' => $supplier,
            'customer' => $customer,
            'open' => $supplier ? $this->supplierItems($supplier) : ($customer ? $this->refundItems($customer) : null),
            'accounts' => Account::active()->get(),
        ]);
    }

    private function supplierItems(Supplier $supplier)
    {
        return [
            'opening' => max(0, $supplier->opening_due),
            'lines' => $supplier->lines()->with('invoice.customer')->get()->filter(function ($l) {
                return $l->supplier_payable > 0.005;
            })->values(),
        ];
    }

    private function refundItems(Customer $customer)
    {
        return [
            'refunds' => Refund::whereIn('invoice_id', $customer->invoices()->select('id'))->with('invoice', 'line')->get()->filter(function ($r) {
                return $r->outstanding > 0.005;
            })->values(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'payee_type' => 'required|in:supplier,customer',
            'supplier_id' => 'required_if:payee_type,supplier|nullable|exists:suppliers,id',
            'customer_id' => 'required_if:payee_type,customer|nullable|exists:customers,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'paid_on' => 'required|date',
            'reference' => 'nullable|string|max:120',
            'remarks' => 'nullable|string|max:1000',
            'alloc' => 'nullable|array',
            'alloc.*' => 'nullable|numeric|min:0',
            'alloc_opening' => 'nullable|numeric|min:0',
            'auto' => 'nullable|boolean',
        ]);

        $amount = round((float) $data['amount'], 2);
        $isSupplier = $data['payee_type'] === 'supplier';
        $open = $isSupplier
            ? $this->supplierItems(Supplier::findOrFail($data['supplier_id']))
            : $this->refundItems(Customer::findOrFail($data['customer_id']));
        $items = $isSupplier ? $open['lines'] : $open['refunds'];
        $due = function ($item) use ($isSupplier) {
            return $isSupplier ? $item->supplier_payable : $item->outstanding;
        };

        $plan = [];
        $opening = $isSupplier ? (float) ($data['alloc_opening'] ?? 0) : 0;
        $manual = array_filter($data['alloc'] ?? [], function ($v) {
            return (float) $v > 0;
        });

        if ($manual || $opening > 0) {
            foreach ($manual as $id => $value) {
                $item = $items->firstWhere('id', (int) $id);
                if (! $item || (float) $value > $due($item) + 0.005) {
                    throw ValidationException::withMessages(['amount' => 'An amount is more than what is payable for that item.']);
                }
                $plan[(int) $id] = round((float) $value, 2);
            }
            if ($isSupplier && $opening > $open['opening'] + 0.005) {
                throw ValidationException::withMessages(['amount' => 'The opening balance amount is more than what is outstanding.']);
            }
        } elseif ($request->boolean('auto', true)) {
            $left = $amount;
            if ($isSupplier && $open['opening'] > 0) {
                $opening = min($left, $open['opening']);
                $left -= $opening;
            }
            foreach ($items as $item) {
                if ($left <= 0) {
                    break;
                }
                $take = min($left, $due($item));
                $plan[$item->id] = round($take, 2);
                $left -= $take;
            }
        }

        if (round(array_sum($plan) + $opening, 2) > $amount + 0.005) {
            throw ValidationException::withMessages(['amount' => 'The amounts selected are more than the payment amount.']);
        }

        $payment = DB::transaction(function () use ($data, $plan, $opening, $isSupplier, $request) {
            $payment = Payment::create([
                'payment_no' => Numbering::next('payments', 'payment_no', 'PAY'),
                'payee_type' => $data['payee_type'],
                'supplier_id' => $isSupplier ? $data['supplier_id'] : null,
                'customer_id' => $isSupplier ? null : $data['customer_id'],
                'account_id' => $data['account_id'], 'amount' => $data['amount'], 'paid_on' => $data['paid_on'],
                'reference' => $data['reference'] ?? null, 'remarks' => $data['remarks'] ?? null, 'user_id' => $request->user()->id,
            ]);

            foreach ($plan as $id => $value) {
                if ($value <= 0) {
                    continue;
                }
                if ($isSupplier) {
                    PaymentAllocation::create(['payment_id' => $payment->id, 'invoice_line_id' => $id, 'amount' => $value]);
                } else {
                    PaymentAllocation::create(['payment_id' => $payment->id, 'refund_id' => $id, 'amount' => $value]);
                    Refund::where('id', $id)->increment('paid_amount', $value);
                }
            }
            if ($opening > 0) {
                PaymentAllocation::create(['payment_id' => $payment->id, 'amount' => $opening]);
            }

            return $payment;
        });

        return redirect()->route('payments.show', $payment)->with('success', 'Payment ' . $payment->payment_no . ' saved.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['supplier', 'customer', 'account', 'allocations.line.invoice', 'allocations.refund.invoice']);

        return view('payments.show', ['payment' => $payment]);
    }

    public function pdf(Payment $payment)
    {
        $payment->load(['supplier', 'customer', 'account', 'allocations.line.invoice', 'allocations.refund.invoice']);

        return PDF::loadView('pdf.payment', ['payment' => $payment])
            ->download(str_replace('/', '-', $payment->payment_no) . '.pdf');
    }

    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            foreach ($payment->allocations()->whereNotNull('refund_id')->get() as $a) {
                Refund::where('id', $a->refund_id)->decrement('paid_amount', $a->amount);
            }
            $payment->delete();
        });

        return redirect()->route('payments.index')->with('success', 'Payment deleted.');
    }
}
