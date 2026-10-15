<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Refund;
use App\Support\Numbering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['invoice.customer', 'line'])->latest('refunded_on')->latest('id');
        if ($request->query('from')) {
            $query->whereDate('refunded_on', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->whereDate('refunded_on', '<=', $request->query('to'));
        }

        return view('refunds.index', [
            'refunds' => $query->paginate(15)->withQueryString(),
            'filters' => ['from' => $request->query('from'), 'to' => $request->query('to')],
        ]);
    }

    public function create(Request $request)
    {
        $invoice = $request->query('invoice_id') ? Invoice::with(['customer', 'lines.serviceable', 'lines.refunds'])->find($request->query('invoice_id')) : null;

        return view('refunds.create', [
            'invoices' => Invoice::with('customer')->where('payment_status', '!=', 'cancelled')->latest('issued_on')->latest('id')->limit(300)->get(),
            'invoice' => $invoice,
            'line_id' => $request->query('line_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'invoice_line_id' => 'required|exists:invoice_lines,id',
            'refunded_on' => 'required|date',
            'supplier_refund' => 'required|numeric|min:0',
            'margin' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $line = $invoice->lines()->where('id', $data['invoice_line_id'])->first();
        if (! $line) {
            throw ValidationException::withMessages(['invoice_line_id' => 'That service is not on the selected invoice.']);
        }

        $supplierRefund = round((float) $data['supplier_refund'], 2);
        $margin = round((float) ($data['margin'] ?? 0), 2);
        if ($margin > $supplierRefund) {
            throw ValidationException::withMessages(['margin' => 'The refund charge cannot be more than the amount refunded by the supplier.']);
        }
        $already = $line->refunds()->sum('supplier_refund');
        if ($line->buying_price > 0 && $supplierRefund + $already > $line->buying_price + 0.005) {
            throw ValidationException::withMessages(['supplier_refund' => 'More than the buying price of this service would be refunded.']);
        }
        $customerRefund = round($supplierRefund - $margin, 2);
        $alreadyCustomer = $line->refunds()->sum('customer_refund');
        if ($customerRefund + $alreadyCustomer > $line->line_total + 0.005) {
            throw ValidationException::withMessages(['supplier_refund' => 'The customer refund would be more than the amount charged for this service.']);
        }

        $refund = DB::transaction(function () use ($data, $invoice, $supplierRefund, $margin, $customerRefund, $request) {
            // part of the refund simply reduces what the customer still owes
            $stillDue = max(0, $invoice->total - $invoice->refunded_amount - $invoice->paid_amount);
            $offset = round(min($customerRefund, $stillDue), 2);

            $refund = Refund::create([
                'refund_no' => Numbering::next('refunds', 'refund_no', 'REF'),
                'invoice_id' => $invoice->id, 'invoice_line_id' => $data['invoice_line_id'],
                'refunded_on' => $data['refunded_on'], 'supplier_refund' => $supplierRefund, 'margin' => $margin,
                'customer_refund' => $customerRefund, 'offset_amount' => $offset,
                'payable_amount' => round($customerRefund - $offset, 2), 'paid_amount' => 0,
                'remarks' => $data['remarks'] ?? null, 'user_id' => $request->user()->id,
            ]);
            $invoice->recalc();

            return $refund;
        });

        return redirect()->route('refunds.index')->with('success', 'Refund ' . $refund->refund_no . ' recorded.');
    }

    public function destroy(Refund $refund)
    {
        if ($refund->paid_amount > 0) {
            return back()->withErrors(['refund' => 'A payment has already been made against this refund. Delete that payment first.']);
        }
        $invoice = $refund->invoice;
        $refund->delete();
        $invoice->recalc();

        return redirect()->route('refunds.index')->with('success', 'Refund deleted.');
    }
}
