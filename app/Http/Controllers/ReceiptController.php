<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Support\Numbering;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $query = Receipt::with(['customer', 'account'])->latest('received_on')->latest('id');
        if ($request->query('from')) {
            $query->whereDate('received_on', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->whereDate('received_on', '<=', $request->query('to'));
        }
        if ($q) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('receipt_no', 'like', $like)->orWhere('reference', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                    });
            });
        }

        return view('receipts.index', [
            'receipts' => $query->paginate(15)->withQueryString(),
            'total' => (clone $query)->get()->sum('amount'),
            'filters' => ['q' => $q, 'from' => $request->query('from'), 'to' => $request->query('to')],
        ]);
    }

    public function create(Request $request)
    {
        $customer = $request->query('customer_id') ? Customer::find($request->query('customer_id')) : null;

        return view('receipts.create', [
            'customers' => Customer::orderBy('type')->orderBy('name')->get(['id', 'name', 'phone', 'type']),
            'customer' => $customer,
            'open' => $customer ? $this->openItems($customer) : null,
            'accounts' => Account::where('is_active', true)->get(),
            'invoice_id' => $request->query('invoice_id'),
        ]);
    }

    /** Invoices with a balance, oldest first, plus any unsettled opening balance. */
    private function openItems(Customer $customer)
    {
        return [
            'opening' => max(0, $customer->opening_due),
            'invoices' => $customer->invoices()->orderBy('issued_on')->orderBy('id')->get()->filter(function ($i) {
                return $i->balance > 0;
            })->values(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'received_on' => 'required|date',
            'reference' => 'nullable|string|max:120',
            'remarks' => 'nullable|string|max:1000',
            'alloc' => 'nullable|array',
            'alloc.*' => 'nullable|numeric|min:0',
            'alloc_opening' => 'nullable|numeric|min:0',
            'auto' => 'nullable|boolean',
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $open = $this->openItems($customer);
        $amount = round((float) $data['amount'], 2);

        $plan = [];   // invoice_id|null => amount
        $manual = array_filter($data['alloc'] ?? [], function ($v) {
            return (float) $v > 0;
        });
        $opening = (float) ($data['alloc_opening'] ?? 0);

        if ($manual || $opening > 0) {
            foreach ($manual as $invoiceId => $value) {
                $inv = $open['invoices']->firstWhere('id', (int) $invoiceId);
                if (! $inv || (float) $value > $inv->balance + 0.005) {
                    throw ValidationException::withMessages(['amount' => 'An amount is more than the balance of invoice ' . ($inv->invoice_no ?? '#' . $invoiceId) . '.']);
                }
                $plan[(int) $invoiceId] = round((float) $value, 2);
            }
            if ($opening > $open['opening'] + 0.005) {
                throw ValidationException::withMessages(['amount' => 'The opening balance amount is more than what is outstanding.']);
            }
        } elseif ($request->boolean('auto', true)) {
            $left = $amount;
            if ($open['opening'] > 0 && $left > 0) {
                $opening = min($left, $open['opening']);
                $left -= $opening;
            }
            foreach ($open['invoices'] as $inv) {
                if ($left <= 0) {
                    break;
                }
                $take = min($left, $inv->balance);
                $plan[$inv->id] = round($take, 2);
                $left -= $take;
            }
        }

        if (round(array_sum($plan) + $opening, 2) > $amount + 0.005) {
            throw ValidationException::withMessages(['amount' => 'The amounts selected are more than the receipt amount.']);
        }

        $receipt = DB::transaction(function () use ($data, $plan, $opening, $request) {
            $receipt = Receipt::create([
                'receipt_no' => Numbering::next('receipts', 'receipt_no', 'RCT'),
                'customer_id' => $data['customer_id'], 'account_id' => $data['account_id'],
                'amount' => $data['amount'], 'received_on' => $data['received_on'],
                'reference' => $data['reference'] ?? null, 'remarks' => $data['remarks'] ?? null, 'user_id' => $request->user()->id,
            ]);
            $this->applyAllocations($receipt, $plan, $opening);

            return $receipt;
        });

        return redirect()->route('receipts.show', $receipt)->with('success', 'Receipt ' . $receipt->receipt_no . ' saved.');
    }

    private function applyAllocations(Receipt $receipt, array $plan, $opening)
    {
        foreach ($plan as $invoiceId => $amount) {
            if ($amount <= 0) {
                continue;
            }
            ReceiptAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => $invoiceId, 'amount' => $amount]);
            $inv = Invoice::find($invoiceId);
            $inv->recalc();
            $inv->update(['payment_method' => $receipt->account->name]);
        }
        if ($opening > 0) {
            ReceiptAllocation::create(['receipt_id' => $receipt->id, 'invoice_id' => null, 'amount' => $opening]);
        }
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['customer', 'account', 'allocations.invoice']);

        return view('receipts.show', [
            'receipt' => $receipt,
            'open' => $receipt->unallocated > 0 ? $this->openItems($receipt->customer) : null,
        ]);
    }

    /** Match the unallocated (advance) part of a receipt to invoices afterwards. */
    public function allocate(Request $request, Receipt $receipt)
    {
        $data = $request->validate(['alloc' => 'nullable|array', 'alloc.*' => 'nullable|numeric|min:0', 'alloc_opening' => 'nullable|numeric|min:0']);
        $open = $this->openItems($receipt->customer);
        $plan = [];
        foreach (array_filter($data['alloc'] ?? [], function ($v) {
            return (float) $v > 0;
        }) as $invoiceId => $value) {
            $inv = $open['invoices']->firstWhere('id', (int) $invoiceId);
            if (! $inv || (float) $value > $inv->balance + 0.005) {
                throw ValidationException::withMessages(['alloc' => 'An amount is more than the invoice balance.']);
            }
            $plan[(int) $invoiceId] = round((float) $value, 2);
        }
        $opening = (float) ($data['alloc_opening'] ?? 0);
        if ($opening > $open['opening'] + 0.005 || round(array_sum($plan) + $opening, 2) > $receipt->unallocated + 0.005) {
            throw ValidationException::withMessages(['alloc' => 'The amounts are more than what is available on this receipt.']);
        }

        DB::transaction(function () use ($receipt, $plan, $opening) {
            $this->applyAllocations($receipt, $plan, $opening);
        });

        return redirect()->route('receipts.show', $receipt)->with('success', 'Receipt matched to invoices.');
    }

    public function pdf(Receipt $receipt)
    {
        $receipt->load(['customer', 'account', 'allocations.invoice.lines']);

        return PDF::loadView('pdf.receipt', ['receipt' => $receipt])
            ->download(str_replace('/', '-', $receipt->receipt_no) . '.pdf');
    }

    public function destroy(Receipt $receipt)
    {
        DB::transaction(function () use ($receipt) {
            $invoices = $receipt->allocations()->whereNotNull('invoice_id')->pluck('invoice_id');
            $receipt->delete();
            Invoice::whereIn('id', $invoices)->get()->each->recalc();
        });

        return redirect()->route('receipts.index')->with('success', 'Receipt deleted.');
    }
}
