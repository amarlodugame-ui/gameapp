@extends('admin.layouts.app')

@section('panel')
<div class="row mb-3">
    <div class="col-lg-12">
        <div class="card b-radius-10">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Round No</label>
                            <input type="text" name="round_no" class="form-control" value="{{ request('round_no') }}" placeholder="e.g. 1205">
                        </div>

                        <div class="col-md-2">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>

                        <div class="col-md-2">
                            <label>Include Demo</label>
                            <select name="include_demo" class="form-control">
                                <option value="1" {{ request('include_demo', 1) == 1 ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ request('include_demo') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>Per Page</label>
                            <select name="per_page" class="form-control">
                                @foreach([10,20,50,100] as $size)
                                    <option value="{{ $size }}" {{ (int)request('per_page',20) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
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
                                <th>#</th>
                                <th>Period</th>
                                <th>Type</th>
                                <th>Result</th>
                                <th>Total Bet</th>
                                <th>Total Win Payout</th>
                                <th>Total Users</th>
                                <th>Profit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rounds as $round)
                                @php
                                    $profit = (float)$round->total_bet - (float)$round->total_win_payout;
                                @endphp
                                <tr>
                                    <td>{{ $rounds->firstItem() + $loop->index }}</td>
                                    <td>
                                        <strong>#{{ $round->round_no }}</strong><br>
                                        <small>{{ optional($round->betting_starts_at)->format('Y-m-d H:i:s') }}</small>
                                    </td>
                                    <td>
                                        Aviator
                                    </td>
                                    <td>
                                        @if($round->crash_multiplier)
                                            <span class="badge badge-danger" style="background:red;">{{ number_format((float)$round->crash_multiplier, 2) }}x</span>
                                        @else
                                            <span class="badge badge-warning">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float)$round->total_bet, 2) }}</td>
                                    <td>{{ number_format((float)$round->total_win_payout, 2) }}</td>
                                    <td>{{ (int)$round->total_users }}</td>
                                    <td>
                                        @if($profit >= 0)
                                            <span class="text-success">{{ number_format($profit, 2) }}</span>
                                        @else
                                            <span class="text-danger">{{ number_format($profit, 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.aviator.reports.show', ['round' => $round->id, 'include_demo' => request('include_demo', 1)]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No betting rounds found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rounds->hasPages())
                    <div class="card-footer py-4">
                        {{ $rounds->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection