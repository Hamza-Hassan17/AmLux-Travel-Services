@extends('layouts.app')
@section('title', $agent->exists ? 'Edit agent' : 'Add agent')
@section('content')
<div class="page-head">
    <div>
        <div class="crumbs"><a href="{{ route('agents.index') }}">Directory</a><span class="sep"></span><a href="{{ route('agents.index') }}">Commission agents</a><span class="sep"></span><b>{{ $agent->exists ? 'Edit' : 'New' }}</b></div>
        <h1>{{ $agent->exists ? 'Edit commission agent' : 'Add commission agent' }}</h1>
    </div>
</div>
<form method="POST" action="{{ $agent->exists ? route('agents.update', $agent) : route('agents.store') }}">
    @csrf
    @if ($agent->exists) @method('PUT') @endif
    <section class="panel" style="max-width:760px">
        <div class="panel-head"><x-icon name="user-group" />Agent</div>
        <div class="panel-body grid">
            <div class="field c6"><label for="name">Name <span class="req">*</span></label><input id="name" name="name" class="input" value="{{ old('name', $agent->name) }}" required></div>
            <div class="field c6"><label for="phone">Phone</label><input id="phone" name="phone" class="input" value="{{ old('phone', $agent->phone) }}"></div>
            <div class="field c12"><label for="email">Email</label><input id="email" type="email" name="email" class="input" value="{{ old('email', $agent->email) }}"></div>
            <div class="field c12"><label for="notes">Notes</label><textarea id="notes" name="notes" class="textarea" rows="2">{{ old('notes', $agent->notes) }}</textarea></div>
        </div>
        <div class="panel-body actions" style="border-top:1px solid var(--line)">
            <button class="btn btn-red">Save agent</button>
            <a href="{{ route('agents.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </section>
</form>
@endsection
