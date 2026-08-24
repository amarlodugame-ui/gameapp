@extends('admin.layouts.app')

@section('panel')
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Today Total Completed</h6>
                <h4>{{ number_format((float)$stats['today_total'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Yesterday Total Completed</h6>
                <h4>{{ number_format((float)$stats['yesterday_total'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>This Month Completed</h6>
                <h4>{{ number_format((float)$stats['this_month_total'], 2) }}</h4>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Completed Count</h6>
                <h4>{{ $stats['completed_count'] }}</h4>
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
                <h6>Processing</h6>
                <p class="mb-1">Count: {{ $stats['processing_count'] }}</p>
                <h5>{{ number_format((float)$stats['processing_total'], 2) }}</h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card b-radius-10">
            <div class="card-body">
                <h6>Completed</h6>
                <p class="mb-1">Count: {{ $stats['completed_count'] }}</p>
                <h5>{{ number_format((float)$stats['completed_total'], 2) }}</h5>
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
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Processing</option>
                                <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Completed</option>
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
                                <th>Wallet / Currency</th>
                                <th>Number</th>
                                <th>Amount</th>
                                <th>Charge</th>
                                <th>Net Amount</th>
                                <th>TRX ID</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withdraws as $withdraw)
                                <tr>
                                    <td>{{ $withdraw->id }}</td>
                                    
<td>
    <form class="col-6" action="{{ route('admin.users.login.as.user', $withdraw->user->id) }}" method="POST">
        @csrf
        <button type="submit" class="login_buton">
            {{ optional($withdraw->user)->name ?? 'N/A' }}<br>
            <small>UID: {{ $withdraw->user->referral_code }}</small>
        </button>
    </form>

    @if($withdraw->multi_bet_detected)
        <div class="text-danger fw-bold mt-2"  style="font-size:10px;"> 
           Illiagel Bet Detected
        </div>

        <button type="button"
                class="btn btn-sm btn-danger mt-1"
                data-bs-toggle="modal"
                data-bs-target="#multiBetModal{{ $withdraw->id }}">
            View Details
        </button>

        <!-- Modal -->
        <div class="modal fade" id="multiBetModal{{ $withdraw->id }}" tabindex="-1" aria-labelledby="multiBetModalLabel{{ $withdraw->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="multiBetModalLabel{{ $withdraw->id }}">
                            Illiagel Bet Details - {{ optional($withdraw->user)->name ?? 'N/A' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-2">
                            <strong>User ID:</strong> {{ $withdraw->user->id ?? 'N/A' }}<br>
                            <strong>Referral Code:</strong> {{ $withdraw->user->referral_code ?? 'N/A' }}
                        </div>

                        @foreach($withdraw->multi_bet_details as $detail)
                            <div class="card mb-3 border">
                                <div class="card-body">
                                    <h6 class="mb-2 text-primary">
                                        {{ $detail['game'] }}
                                    </h6>

                                    @if($detail['game'] === 'Aviator')
                                        <p class="mb-1"><strong>Round:</strong> {{ $detail['round_no'] ?? $detail['round_id'] }}</p>
                                    @else
                                        <p class="mb-1"><strong>Game Type:</strong> {{ $detail['game_type'] ?? '-' }}</p>
                                        <p class="mb-1"><strong>Period:</strong> {{ $detail['period_id'] ?? $detail['round_id'] }}</p>
                                    @endif

                                    <p class="mb-1"><strong>Bet Count:</strong> {{ $detail['bet_count'] }}</p>
                                    <p class="mb-1"><strong>Total Amount:</strong> {{ number_format($detail['total_amount'], 2) }}</p>

                                    <div class="mt-2 p-2 bg-light rounded" style="font-size: 13px; max-height: 180px; overflow-y: auto;">
                                        <strong>Bet Details:</strong><br>
                                        {!! nl2br(e(str_replace(' | ', "\n", $detail['bets']))) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>

                </div>
            </div>
        </div>
    @else
        <span class="text-success">No Multi Bet</span>
    @endif
</td>
                                    
                                    <td><small>{{ $withdraw->payout_currency_code ?? '-' }}</small></td>
                                    <td>{{ $withdraw->number ?? '-' }}</td>
                                    <td>{{ number_format((float)$withdraw->amount, 2) }}</td>
                                    <td>{{ number_format((float)$withdraw->charge, 2) }}</td>
                                    <td>{{ number_format((float)$withdraw->net_amount, 2) }}</td>
                                    <td>{{ $withdraw->trx_id ?? '-' }}</td>
                                    <td>
                                        @if($withdraw->status == 0)
                                            <span class="badge badge-warning" style="background-color: #ffc107; color: #212529;">Pending</span>
                                        @elseif($withdraw->status == 1)
                                            <span class="badge badge-info" style="background-color: #17a2b8; color: #fff;">Processing</span>
                                        @elseif($withdraw->status == 2)
                                            <span class="badge badge-success" style="background-color: #28a745; color: #fff;">Completed</span>
                                        @elseif($withdraw->status == 3)
                                            <span class="badge badge-danger" style="background-color: #dc3545; color: #fff;">Rejected</span>
                                        @else
                                            <span class="badge badge-dark" style="background-color: #343a40; color: #fff;">Unknown</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($withdraw->created_at)->format('Y-m-d H:i:s') }}</td>
                                    <td>

                                @if($withdraw->status == 0)

                                <form action="{{ route('admin.withdraws.report.processing',$withdraw->id) }}" method="POST" style="display:inline">
                                @csrf
                                <button class="btn btn-sm btn-warning">Processing</button>
                                </form>

@if($withdraw->status == 0 || $withdraw->status == 1)

    <button
        type="button"
        class="btn btn-sm btn-outline-danger"
        data-bs-toggle="modal"
        data-bs-target="#rejectModal{{ $withdraw->id }}">
        <i class="bi bi-x-circle"></i> Reject
    </button>

    <div class="modal fade" id="rejectModal{{ $withdraw->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $withdraw->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel{{ $withdraw->id }}">
                        Reject Withdraw #{{ $withdraw->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('admin.withdraws.report.reject', $withdraw->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reject Reason</label>
                            <textarea
                                name="feedback"
                                class="form-control"
                                rows="4"
                                placeholder="Enter reject reason..."
                                required></textarea>
                        </div>

                        <div class="alert alert-warning mb-0">
                            This action will mark the withdrawal as rejected.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

                                @endif


                                @if($withdraw->status == 1)

                                <form action="{{ route('admin.withdraws.report.approve',$withdraw->id) }}" method="POST" style="display:inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Approve</button>
                                </form>

@if($withdraw->status == 0 || $withdraw->status == 1)

    <button
        type="button"
        class="btn btn-sm btn-outline-danger"
        data-bs-toggle="modal"
        data-bs-target="#rejectModal{{ $withdraw->id }}">
        <i class="bi bi-x-circle"></i> Reject
    </button>

    <div class="modal fade" id="rejectModal{{ $withdraw->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $withdraw->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectModalLabel{{ $withdraw->id }}">
                        Reject Withdraw #{{ $withdraw->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('admin.withdraws.report.reject', $withdraw->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Reject Reason</label>
                            <textarea
                                name="feedback"
                                class="form-control"
                                rows="4"
                                placeholder="Enter reject reason..."
                                required></textarea>
                        </div>

                        <div class="alert alert-warning mb-0">
                            This action will mark the withdrawal as rejected.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endif

                                @endif

                                </td>
                                </tr>
                                
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No withdraws found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($withdraws->hasPages())
                    <div class="card-footer py-4">
                        {{ $withdraws->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div><script>
function toggleDetails(id) {
    let el = document.getElementById(id);

    if (el.style.display === "none") {
        el.style.display = "block";
    } else {
        el.style.display = "none";
    }
}
</script>
<style>
.login_buton{
    border: 1px solid red;
    padding: 10px;
    border-radius: 5px;
    background: #dddddd87;}</style>
@endsection