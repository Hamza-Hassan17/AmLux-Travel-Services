<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Agent;
use App\Models\Customer;
use App\Models\FlightBooking;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Support\InvoiceService;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'q' => trim((string) $request->query('q')),
            'status' => $request->query('status'),
            'user' => $request->query('user'),
        ];

        $query = Invoice::with(['customer', 'lines'])->latest('issued_on')->latest('id');
        $this->applyFilters($query, $filters);

        $totals = clone $query;
        $sum = (object) [
            'total' => $totals->get()->sum('total'),
            'balance' => (clone $query)->get()->sum(function ($i) {
                return $i->balance;
            }),
        ];

        $counts = Invoice::selectRaw('payment_status, count(*) c')->groupBy('payment_status')->pluck('c', 'payment_status');

        return view('invoices.index', [
            'invoices' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'sum' => $sum,
        ]);
    }

    private function applyFilters($query, array $f)
    {
        if ($f['from']) {
            $query->whereDate('issued_on', '>=', $f['from']);
        }
        if ($f['to']) {
            $query->whereDate('issued_on', '<=', $f['to']);
        }
        if (in_array($f['status'], ['unpaid', 'partial', 'paid', 'cancelled'], true)) {
            $query->where('payment_status', $f['status']);
        }
        if ($f['q']) {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('invoice_no', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('passport_no', 'like', $like);
                    })
                    ->orWhereHas('lines', function ($l) use ($like) {
                        $l->where('reference', 'like', $like)->orWhere('passenger', 'like', $like);
                    });
            });
        }
    }

    public function create(Request $request)
    {
        $service = in_array($request->query('service'), ['flight', 'hotel'], true) ? $request->query('service') : 'flight';

        return view('invoices.form', $this->formData(null, [
            'customer_id' => $request->query('customer_id'),
            'bill_to_type' => 'customer',
            'issued_on' => now()->toDateString(),
            'due_date' => now()->toDateString(),
        ], [], $service));
    }

    public function store(Request $request, InvoiceService $service)
    {
        [$header, $lines, $payNow] = $this->validated($request);
        $invoice = $service->save(null, $header, $lines, $request->user()->id, $payNow);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice ' . $invoice->invoice_no . ' created.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'agent', 'lines.serviceable', 'lines.supplier', 'allocations.receipt.account', 'refunds.line']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['lines.serviceable', 'customer']);

        $header = $invoice->only(['bill_to_type', 'customer_id', 'agent_id', 'agent_commission_mode', 'agent_commission_value', 'notes']);
        $header['issued_on'] = $invoice->issued_on->toDateString();
        $header['due_date'] = optional($invoice->due_date)->toDateString();
        if ($invoice->bill_to_type === 'walk_in') {
            $header['walk_in_name'] = $invoice->customer->name;
            $header['walk_in_mobile'] = $invoice->customer->phone;
        }

        return view('invoices.form', $this->formData($invoice, $header, $this->linesForForm($invoice), 'flight'));
    }

    public function update(Request $request, Invoice $invoice, InvoiceService $service)
    {
        [$header, $lines] = $this->validated($request, $invoice);
        $service->save($invoice, $header, $lines, $request->user()->id);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->allocations()->exists() || $invoice->refunds()->exists()) {
            return back()->withErrors(['invoice' => 'This invoice has receipts or refunds against it. Remove those first.']);
        }

        DB::transaction(function () use ($invoice) {
            $customer = $invoice->customer;
            foreach ($invoice->lines()->with('serviceable')->get() as $line) {
                optional($line->serviceable)->delete();
            }
            $invoice->delete();
            if ($customer && $customer->type === 'walk_in' && ! $customer->invoices()->exists()) {
                $customer->delete();
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'agent', 'lines.serviceable', 'lines.supplier', 'allocations.receipt']);

        return PDF::loadView('pdf.invoice', ['invoice' => $invoice])
            ->download(str_replace('/', '-', $invoice->invoice_no) . '.pdf');
    }

    // ------------------------------------------------------------------

    private function formData(?Invoice $invoice, array $header, array $lines, $service)
    {
        $settings = Settings::all();

        return [
            'invoice' => $invoice,
            'header' => $header,
            'initialLines' => $lines,
            'startService' => $service,
            'customers' => Customer::where('type', 'customer')->orderBy('name')->get(['id', 'name', 'phone', 'passport_no']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'agents' => Agent::orderBy('name')->get(['id', 'name']),
            'accounts' => Account::where('is_active', true)->orderBy('id')->get(['id', 'name', 'type']),
            'taxRate' => (float) $settings['tax_rate'],
            'taxLabel' => $settings['tax_label'] ?: 'Tax',
        ];
    }

    private function linesForForm(Invoice $invoice)
    {
        return $invoice->lines->map(function ($l) {
            $b = $l->serviceable;
            $row = [
                'id' => $l->id, 'service_type' => $l->service_type, 'supplier_id' => $l->supplier_id,
                'buying_price' => (float) $l->buying_price, 'breakdown' => $l->breakdown,
                'selling_price' => (float) $l->selling_price, 'discount' => (float) $l->discount, 'service_charge' => (float) $l->service_charge,
                'tax_mode' => $l->tax_mode, 'tax_rate' => (float) $l->tax_rate, 'taxable_on_margin' => (bool) $l->taxable_on_margin,
                'description' => $l->description,
            ];
            if ($b instanceof FlightBooking) {
                $row += $b->only(['pnr', 'ticket_no', 'airline', 'flight_no', 'aircraft', 'origin', 'destination', 'origin_airport',
                    'destination_airport', 'cabin_class', 'adults', 'seat', 'baggage', 'meal', 'passenger_name', 'passenger_mobile', 'status']);
                $row['departure_at'] = optional($b->departure_at)->format('Y-m-d\TH:i');
                $row['arrival_at'] = optional($b->arrival_at)->format('Y-m-d\TH:i');
            } elseif ($b) {
                $row += $b->only(['booking_ref', 'confirmation_no', 'hotel_name', 'star_rating', 'city', 'country', 'address', 'hotel_phone',
                    'hotel_email', 'check_in_time', 'check_out_time', 'room_type', 'bed_config', 'rooms', 'adults', 'children',
                    'meal_plan', 'special_request', 'amenities', 'guest_name', 'guest_mobile', 'status']);
                $row['check_in'] = optional($b->check_in)->toDateString();
                $row['check_out'] = optional($b->check_out)->toDateString();
            }

            return $row;
        })->values()->all();
    }

    /** @return array [header, lines, payNow] */
    private function validated(Request $request, ?Invoice $invoice = null)
    {
        $data = $request->validate([
            'bill_to_type' => 'required|in:customer,walk_in',
            'customer_id' => 'required_if:bill_to_type,customer|nullable|exists:customers,id',
            'walk_in_name' => 'required_if:bill_to_type,walk_in|nullable|string|max:255',
            'walk_in_mobile' => 'nullable|string|max:30',
            'issued_on' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issued_on',
            'agent_id' => 'nullable|exists:agents,id',
            'agent_commission_mode' => 'nullable|in:fixed,percent',
            'agent_commission_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'lines' => 'required|array|min:1',
            'lines.*.id' => 'nullable|integer',
            'lines.*.service_type' => 'required|in:flight,hotel',
            'lines.*.supplier_id' => 'nullable|exists:suppliers,id',
            'lines.*.buying_price' => 'nullable|numeric|min:0',
            'lines.*.selling_price' => 'required|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.service_charge' => 'nullable|numeric|min:0',
            'lines.*.tax_mode' => 'nullable|in:none,inclusive,exclusive',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.taxable_on_margin' => 'nullable|boolean',
            'lines.*.breakdown' => 'nullable|array',
            'lines.*.breakdown.*' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:1000',
            'lines.*.status' => 'nullable|in:confirmed,pending,cancelled',
            'pay_amount' => 'nullable|numeric|min:0',
            'pay_account_id' => 'nullable|required_with:pay_amount|exists:accounts,id',
            'pay_reference' => 'nullable|string|max:120',
        ], [
            'lines.required' => 'Add at least one service line to the invoice.',
            'lines.*.selling_price.required' => 'Each line needs a selling price.',
        ]);

        // service specific rules, checked row by row on the raw rows (the first pass only keeps generic fields)
        $rows = $request->input('lines', []);
        $errors = [];
        $seenPnr = [];
        $position = 0;
        foreach ($rows as $i => $row) {
            $position++;
            $rules = $row['service_type'] === 'flight' ? [
                'pnr' => 'required|string|max:20', 'airline' => 'required|string|max:255', 'flight_no' => 'required|string|max:20',
                'origin' => 'required|alpha|size:3', 'destination' => 'required|alpha|size:3',
                'departure_at' => 'required|date', 'arrival_at' => 'nullable|date|after:departure_at',
                'passenger_name' => 'required|string|max:255', 'passenger_mobile' => 'nullable|string|max:30',
                'ticket_no' => 'nullable|string|max:30', 'cabin_class' => 'nullable|string|max:30', 'adults' => 'nullable|integer|min:1|max:9',
                'aircraft' => 'nullable|string|max:255', 'origin_airport' => 'nullable|string|max:255', 'destination_airport' => 'nullable|string|max:255',
                'seat' => 'nullable|string|max:10', 'baggage' => 'nullable|string|max:255', 'meal' => 'nullable|string|max:255',
            ] : [
                'hotel_name' => 'required|string|max:255', 'city' => 'required|string|max:120',
                'check_in' => 'required|date', 'check_out' => 'required|date|after:check_in',
                'guest_name' => 'required|string|max:255', 'guest_mobile' => 'nullable|string|max:30',
                'room_type' => 'nullable|string|max:255', 'rooms' => 'nullable|integer|min:1|max:20', 'adults' => 'nullable|integer|min:1|max:20',
                'children' => 'nullable|integer|min:0|max:20', 'star_rating' => 'nullable|integer|min:1|max:5',
                'country' => 'nullable|string|max:120', 'address' => 'nullable|string|max:255', 'hotel_phone' => 'nullable|string|max:40',
                'hotel_email' => 'nullable|email|max:255', 'confirmation_no' => 'nullable|string|max:40', 'booking_ref' => 'nullable|string|max:30',
                'check_in_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'], 'check_out_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
                'bed_config' => 'nullable|string|max:255', 'meal_plan' => 'nullable|string|max:255', 'special_request' => 'nullable|string|max:255',
                'amenities' => 'nullable|string|max:2000',
            ];
            $v = Validator::make($row, $rules);
            if ($v->fails()) {
                foreach ($v->errors()->messages() as $field => $msgs) {
                    $errors['lines.' . $i . '.' . $field] = 'Line ' . $position . ': ' . $msgs[0];
                }
            }

            if ($row['service_type'] === 'flight' && ! empty($row['pnr'])) {
                $pnr = strtoupper(trim($row['pnr']));
                $dupe = FlightBooking::where('pnr', $pnr)->when(! empty($row['id']), function ($q) use ($row) {
                    $q->where('id', '!=', optional(optional($this->lineModel($row['id']))->serviceable)->id);
                })->exists();
                if ($dupe || in_array($pnr, $seenPnr, true)) {
                    $errors['lines.' . $i . '.pnr'] = 'Line ' . $position . ': PNR ' . $pnr . ' is already used by another booking.';
                }
                $seenPnr[] = $pnr;
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $header = [
            'bill_to_type' => $data['bill_to_type'],
            'customer_id' => $data['customer_id'] ?? null,
            'walk_in_name' => $data['walk_in_name'] ?? null,
            'walk_in_mobile' => $data['walk_in_mobile'] ?? null,
            'issued_on' => $data['issued_on'],
            'due_date' => $data['due_date'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'agent_commission_mode' => $data['agent_commission_mode'] ?? null,
            'agent_commission_value' => $data['agent_commission_value'] ?? 0,
            'notes' => $data['notes'] ?? null,
        ];
        $payNow = ! $invoice && ! empty($data['pay_amount']) ? [
            'amount' => $data['pay_amount'], 'account_id' => $data['pay_account_id'], 'reference' => $data['pay_reference'] ?? null,
        ] : null;

        return [$header, array_values($rows), $payNow];
    }

    private function lineModel($id)
    {
        return \App\Models\InvoiceLine::find($id);
    }
}
