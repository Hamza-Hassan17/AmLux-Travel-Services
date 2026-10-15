@php $preselect = $preselect ?? null; @endphp
<div class="table-wrap">
    <table class="data narrow">
        <thead><tr><th>#</th><th>Document</th><th>Date</th><th class="right">Balance</th><th class="right">Apply</th><th></th></tr></thead>
        <tbody>
        @if ($open['opening'] > 0)
            <tr>
                <td>OB</td><td class="strong">Opening balance</td><td>—</td>
                <td class="right strong nowrap">{{ number_format($open['opening']) }}</td>
                <td class="right"><input class="input alloc-input" type="number" step="0.01" min="0" max="{{ $open['opening'] }}" id="alloc-opening" name="alloc_opening" value="{{ old('alloc_opening') }}"></td>
                <td class="right"><button type="button" class="btn btn-ghost btn-sm" data-select="#alloc-opening" data-max="{{ $open['opening'] }}">Select</button></td>
            </tr>
        @endif
        @forelse ($open['invoices'] as $n => $inv)
            <tr>
                <td>{{ $n + 1 }}</td>
                <td><a class="pnr" href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_no }}</a></td>
                <td class="nowrap">{{ $inv->issued_on->format('d M Y') }}</td>
                <td class="right strong nowrap">{{ number_format($inv->balance) }}</td>
                <td class="right"><input class="input alloc-input" type="number" step="0.01" min="0" max="{{ $inv->balance }}" id="alloc-{{ $inv->id }}" name="alloc[{{ $inv->id }}]" value="{{ old('alloc.' . $inv->id, (string) $preselect === (string) $inv->id ? $inv->balance : '') }}"></td>
                <td class="right"><button type="button" class="btn btn-ghost btn-sm" data-select="#alloc-{{ $inv->id }}" data-max="{{ $inv->balance }}">Select</button></td>
            </tr>
        @empty
            @if ($open['opening'] <= 0)
                <tr><td colspan="6" class="empty">No open invoices for this customer. A receipt will be kept as an advance.</td></tr>
            @endif
        @endforelse
        </tbody>
    </table>
</div>
