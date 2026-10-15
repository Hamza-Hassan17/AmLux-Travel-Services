<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();
        $pnl = $this->profitAndLoss($from, $to);
        $sales = Invoice::whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])->get();

        return view('reports.index', [
            'from' => $from, 'to' => $to,
            'salesTotal' => $sales->sum('total'), 'salesCount' => $sales->count(),
            'netProfit' => $pnl['netProfit'],
        ]);
    }

    // ---------------------------------------------------------------- sales

    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);
        $customerId = $request->query('customer_id');

        $invoices = $this->invoices($from, $to, $customerId);
        $lines = $invoices->flatMap->lines;

        $byService = [];
        foreach (['flight' => 'Flights', 'hotel' => 'Hotels'] as $key => $label) {
            $set = $lines->where('service_type', $key);
            $byService[$key] = [
                'label' => $label, 'count' => $set->count(),
                'net' => $set->sum(function ($l) {
                    return $l->line_total - $l->tax_amount;
                }),
                'tax' => $set->sum('tax_amount'), 'total' => $set->sum('line_total'),
            ];
        }

        return view('reports.sales', [
            'from' => $from, 'to' => $to, 'customerId' => $customerId,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'invoices' => $invoices,
            'byService' => $byService,
            'totals' => [
                'net' => $invoices->sum(function ($i) {
                    return $i->total - $i->tax_total;
                }),
                'tax' => $invoices->sum('tax_total'),
                'total' => $invoices->sum('total'),
                'received' => $invoices->sum('paid_amount'),
                'refunded' => $invoices->sum('refunded_amount'),
                'balance' => $invoices->sum(function ($i) {
                    return $i->balance;
                }),
            ],
            'presets' => $this->presets(),
        ]);
    }

    public function salesExport(Request $request)
    {
        [$from, $to] = $this->range($request);
        $invoices = $this->invoices($from, $to, $request->query('customer_id'));

        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Invoice', 'Customer', 'Services', 'Net sales', 'Tax', 'Total', 'Received', 'Refunded', 'Balance', 'Status']);
            foreach ($invoices as $i) {
                fputcsv($out, [
                    $i->issued_on->format('Y-m-d'), $i->invoice_no, $i->customer->name,
                    $i->lines->map(function ($l) {
                        return ucfirst($l->service_type) . ' ' . $l->reference;
                    })->implode('; '),
                    $i->total - $i->tax_total, $i->tax_total, $i->total, $i->paid_amount, $i->refunded_amount, $i->balance, $i->payment_status,
                ]);
            }
            fclose($out);
        }, 'sales-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ------------------------------------------------------- profit & loss

    public function pnl(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('reports.pnl', [
            'from' => $from, 'to' => $to,
            'p' => $this->profitAndLoss($from, $to),
            'presets' => $this->presets(),
        ]);
    }

    public function pnlExport(Request $request)
    {
        [$from, $to] = $this->range($request);
        $p = $this->profitAndLoss($from, $to);

        return response()->streamDownload(function () use ($p) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Item', 'Amount']);
            foreach ([
                ['Sales - Flights', $p['sales']['flight']], ['Sales - Hotels', $p['sales']['hotel']], ['Total sales (excl. tax)', $p['salesTotal']],
                ['Less: refunds to customers', -$p['customerRefunds']], ['Net sales', $p['netSales']],
                ['Cost - Flights', $p['cost']['flight']], ['Cost - Hotels', $p['cost']['hotel']], ['Total cost of sales', $p['costTotal']],
                ['Less: supplier refunds received', -$p['supplierRefunds']], ['Net cost of sales', $p['netCost']],
                ['Gross profit', $p['grossProfit']], ['Less: agent commissions', -$p['commissions']], ['Net profit', $p['netProfit']],
            ] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'profit-and-loss-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function profitAndLoss(Carbon $from, Carbon $to)
    {
        $invoices = $this->invoices($from, $to, null);
        $lines = $invoices->flatMap->lines;

        $sales = $cost = [];
        foreach (['flight', 'hotel'] as $svc) {
            $set = $lines->where('service_type', $svc);
            $sales[$svc] = round($set->sum(function ($l) {
                return $l->line_total - $l->tax_amount;
            }), 2);
            $cost[$svc] = round($set->sum('buying_price'), 2);
        }

        $refunds = Refund::whereBetween('refunded_on', [$from->toDateString(), $to->toDateString()])->get();
        $customerRefunds = round($refunds->sum('customer_refund'), 2);
        $supplierRefunds = round($refunds->sum('supplier_refund'), 2);
        $commissions = round($invoices->sum('agent_commission'), 2);

        $salesTotal = array_sum($sales);
        $costTotal = array_sum($cost);
        $netSales = $salesTotal - $customerRefunds;
        $netCost = $costTotal - $supplierRefunds;
        $gross = $netSales - $netCost;

        return [
            'sales' => $sales, 'salesTotal' => $salesTotal, 'customerRefunds' => $customerRefunds, 'netSales' => $netSales,
            'cost' => $cost, 'costTotal' => $costTotal, 'supplierRefunds' => $supplierRefunds, 'netCost' => $netCost,
            'grossProfit' => $gross, 'commissions' => $commissions, 'netProfit' => $gross - $commissions,
            'margin' => $netSales > 0 ? round(($gross - $commissions) / $netSales * 100, 1) : 0,
            'tax' => round($invoices->sum('tax_total'), 2), 'invoiceCount' => $invoices->count(),
        ];
    }

    // -------------------------------------------------------------- shared

    private function invoices(Carbon $from, Carbon $to, $customerId)
    {
        return Invoice::with(['customer', 'lines'])
            ->whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->orderBy('issued_on')->orderBy('id')->get();
    }

    private function range(Request $request)
    {
        try {
            $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->startOfMonth();
            $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        } catch (\Throwable $e) {
            $from = now()->startOfMonth();
            $to = now()->endOfDay();
        }
        if ($to->lessThan($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function presets()
    {
        $now = now();

        return [
            'Today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'This month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'Last month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'This year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        ];
    }
}
