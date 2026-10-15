@extends('layouts.app')
@section('title', 'Commission agents')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs">Directory<span class="sep"></span><b>Commission agents</b></div>
        <h1>Commission agents</h1>
    </div>
    <div class="actions"><a class="btn btn-red btn-lg" href="{{ route('agents.create') }}"><x-icon name="plus" />Add agent</a></div>
</div>

<section class="panel panel-clip">
    <div class="table-wrap">
        <table class="data narrow">
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th class="right">Invoices</th><th class="right">Commission earned</th><th></th></tr></thead>
            <tbody>
            @forelse ($agents as $a)
                <tr>
                    <td class="strong">{{ $a->name }}</td>
                    <td class="nowrap">{{ $a->phone ?: '—' }}</td>
                    <td>{{ $a->email ?: '—' }}</td>
                    <td class="right">{{ $a->invoices_count }}</td>
                    <td class="right strong">{{ number_format($earned[$a->id] ?? 0) }}</td>
                    <td class="right"><a class="icon-btn" href="{{ route('agents.edit', $a) }}" title="Edit"><x-icon name="pen" /></a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No commission agents yet. <a href="{{ route('agents.create') }}">Add one</a> to pick them on invoices.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
