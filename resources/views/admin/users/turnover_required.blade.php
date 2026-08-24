@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">

    <div class="row justify-content-center mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Search User by UID</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users.turnover.form') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">UID</label>
                                <input
                                    type="text"
                                    name="search"
                                    class="form-control"
                                    value="{{ request('search') }}"
                                    placeholder="Enter referral code"
                                    required
                                >
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100" type="submit">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($user)
    <div class="row justify-content-center mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">User Info</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">UID</label>
                            <input type="text" class="form-control" value="{{ $user->referral_code }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="{{ $user->username }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Current Turnover Required</label>
                            <input type="text" class="form-control" value="{{ number_format((float)$user->turnover_required, 2) }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Add / Subtract Turnover</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.turnover.update') }}">
                        @csrf

                        <input type="hidden" name="uid" value="{{ $user->referral_code }}">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">UID</label>
                                <input type="text" class="form-control" value="{{ $user->referral_code }}" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Action</label>
                                <select name="type" class="form-control" required>
                                    <option value="add">Add</option>
                                    <option value="subtract">Subtract</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="Enter amount" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Note</label>
                                <input type="text" name="note" class="form-control" placeholder="Optional note">
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Turnover History</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Time</th>
                                <th>UID</th>
                                <th>Username</th>
                                <th>Old</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>New</th>
                                <th>Admin</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($histories as $history)
                                <tr>
                                    <td>{{ $history->id }}</td>
                                    <td>{{ $history->created_at }}</td>
                                    <td>{{ $user->referral_code }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ number_format((float)$history->old_turnover_required, 2) }}</td>
                                    <td>
                                        @if($history->type === 'add')
                                            <span class="badge bg-success">Add</span>
                                        @else
                                            <span class="badge bg-danger">Subtract</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float)$history->change_amount, 2) }}</td>
                                    <td>{{ number_format((float)$history->new_turnover_required, 2) }}</td>
                                    <td>{{ $history->admin?->username ?? $history->admin?->name ?? 'N/A' }}</td>
                                    <td>{{ $history->note ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No history found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if(method_exists($histories, 'links'))
                        <div class="mt-3">
                            {{ $histories->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('style')
<style>
    .card {
        border-radius: 12px;
    }
    .card-header h4 {
        font-weight: 600;
    }
    .form-label {
        margin-bottom: .5rem;
    }
    .form-control,
    .input-group-text {
        min-height: 45px;
        border-radius: 8px;
    }
    .btn-primary {
        border-radius: 8px;
        font-weight: 500;
    }
    .table th, .table td {
        vertical-align: middle;
    }
</style>
@endpush