@extends('admin.layouts.app')

@section('panel')
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Today Total Success</h6>
                <h4>{{ number_format((float)$stats['today_total'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Yesterday Total Success</h6>
                <h4>{{ number_format((float)$stats['yesterday_total'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>This Month Success</h6>
                <h4>{{ number_format((float)$stats['this_month_total'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Success Count</h6>
                <h4>{{ $stats['success_count'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Pending</h6>
                <p class="mb-1">Count: {{ $stats['pending_count'] }}</p>
                <h5>{{ number_format((float)$stats['pending_total'], 2) }}</h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Success</h6>
                <p class="mb-1">Count: {{ $stats['success_count'] }}</p>
                <h5>{{ number_format((float)$stats['success_total'], 2) }}</h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Awaiting Payment</h6>
                <p class="mb-1">Count: {{ $stats['awaiting_count'] }}</p>
                <h5>{{ number_format((float)$stats['awaiting_total'], 2) }}</h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Rejected</h6>
                <p class="mb-1">Count: {{ $stats['rejected_count'] }}</p>
                <h5>{{ number_format((float)$stats['rejected_total'], 2) }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card b-radius-10">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-2">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Success</option>
                                <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Awaiting Payment</option>
                                <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>User ID</label>
                            <input type="text" name="user_id" class="form-control" value="{{ request('user_id') }}">
                        </div>

                        <div class="col-md-3">
                            <label>Transaction ID</label>
                            <input type="text" name="trx_id" class="form-control" value="{{ request('trx_id') }}">
                        </div>

                        <div class="col-md-2">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius-10">
            <div class="card-body p-0">
                <div class="table-responsive-md table-responsive">
                    <table class="table table-light style-two">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Method / Currency</th>
                                <th>Amount</th>
                                <th>Charge</th>
                                <th>Payable</th>
                                <th>Payment ID</th>
                                <th>TRX ID</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deposits as $deposit)
                                <tr>
                                    <td>{{ $deposit->id }}</td>
                                    <td>
                                        {{ optional($deposit->user)->name ?? 'N/A' }}<br>
                                        <small>
                                            {{ optional($deposit->user)->username ?? optional($deposit->user)->email }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ $deposit->payment_method_currency ?? '-' }}
                                    </td>
                                    <td>{{ number_format((float)$deposit->amount, 2) }}</td>
                                    <td>{{ number_format((float)$deposit->charge, 2) }}</td>
                                    <td>{{ number_format((float)$deposit->payable_amount, 2) }}</td>
                                    <td>{{ $deposit->payment_id ?? '-' }}</td>
                                    <td>{{ $deposit->trx_id ?? '-' }}</td>
                                    <td>
                                        @if($deposit->status == 0)
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif($deposit->status == 1)
                                            <span class="badge badge-success">Success</span>
                                        @elseif($deposit->status == 2)
                                            <span class="badge badge-info">Awaiting Payment</span>
                                        @elseif($deposit->status == 3)
                                            <span class="badge badge-danger">Rejected</span>
                                        @else
                                            <span class="badge badge-dark">Unknown</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($deposit->created_at)->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No deposits found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($deposits->hasPages())
                    <div class="card-footer py-4">
                        {{ $deposits->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection