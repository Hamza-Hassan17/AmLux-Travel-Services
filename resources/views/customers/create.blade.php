@extends('layouts.app')
@section('title', 'Add Customer')
@section('content')
<div class="card">
    <div class="card-header">1.1 Customer Entry</div>
    <div class="card-body">
        <form method="POST" action="{{ route('customers.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6"><label class="form-label">Full Name *</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
            <div class="col-md-6"><label class="form-label">Phone *</label><input name="phone" class="form-control" value="{{ old('phone') }}" required></div>
            <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
            <div class="col-md-6"><label class="form-label">Passport No. *</label><input name="passport_no" class="form-control" value="{{ old('passport_no') }}" required></div>
            <div class="col-md-6"><label class="form-label">Nationality *</label><input name="nationality" class="form-control" value="{{ old('nationality', 'Pakistani') }}" required></div>
            <div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
            <div class="col-12">
                <button class="btn btn-primary">Save</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
