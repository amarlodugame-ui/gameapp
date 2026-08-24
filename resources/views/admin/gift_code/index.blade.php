@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid p-0">
    @if(session('generated_codes') && is_array(session('generated_codes')))
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <strong>Generated Codes</strong>
            </div>
            <div class="card-body p-2">
                <p class="mb-2">These codes were generated successfully:</p>
                <div class="row">
                    @foreach(session('generated_codes') as $generatedCode)
                        <div class="col-md-3 col-sm-4 col-6 mb-2">
                            <div class="border rounded px-2 py-2 bg-light d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small">{{ $generatedCode }}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" data-code="{{ $generatedCode }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="copyAllGenerated">
                        <i class="bi bi-clipboard-check"></i> Copy All
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <strong>Generate Gift Codes</strong>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.gift.generate') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">How many codes?</label>
                            <input type="number" name="count" class="form-control" min="1" max="5000" value="{{ old('count', 1) }}" required>
                            @error('count')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Max users per code</label>
                            <input type="number" name="max_users" class="form-control" min="1" value="{{ old('max_users', 1) }}" required>
                            @error('max_users')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reward amount</label>
                            <input type="number" step="0.01" min="0" name="reward_amount" class="form-control" value="{{ old('reward_amount', 0) }}" required>
                            @error('reward_amount')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks Text</label>
                            <input type="text"  name="remarks" class="form-control" value="{{ old('remarks') }}" required>
                            @error('remarks')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Generate
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <strong>Filter Gift Codes</strong>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.gift.index') }}">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Search by code</label>
                                <input type="text" name="code" class="form-control" value="{{ request('code') }}" placeholder="Enter code">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="is_used" class="form-select">
                                    <option value="">All</option>
                                    <option value="0" @selected(request('is_used') === '0')>Unused</option>
                                    <option value="1" @selected(request('is_used') === '1')>Used / Locked</option>
                                </select>
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <div class="w-100 d-flex gap-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                    <a href="{{ route('admin.gift.index') }}" class="btn btn-outline-secondary w-100">
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<div class="card mt-4 gift-table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Gift Code List</strong>
        <span class="badge bg-primary">Total: {{ $giftCodes->total() }}</span>
    </div>

    <div class="card-body p-0">
        @if($giftCodes->count())
            <div class="table-responsive gift-table-wrap">
                <table class="table align-middle mb-0 gift-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Reward</th>
                            <th>Max Users</th>
                            <th>Used Count</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Last Used</th>
                            <th>Redemptions</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($giftCodes as $giftCode)
                            <tr>
                                <td>{{ $giftCodes->firstItem() + $loop->index }}</td>

                                <td>
                                    <div class="fw-semibold text-break">{{ $giftCode->code }}</div>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none copy-btn" data-code="{{ $giftCode->code }}">
                                        Copy
                                    </button>
                                </td>

                                <td>{{ number_format($giftCode->reward_amount, 2) }}</td>
                                <td>{{ $giftCode->max_users }}</td>
                                <td>{{ $giftCode->used_count }}</td>

                                <td>
                                    @if($giftCode->is_used)
                                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">
                                            Locked
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ $giftCode->remarks }}
                                </td>

                                <td>
                                    @if($giftCode->last_used_at)
                                        {{ \Carbon\Carbon::parse($giftCode->last_used_at)->format('d M Y, h:i A') }}
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>

                                <td>
                                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#redemptions-{{ $giftCode->id }}">View ({{ $giftCode->redemptions->count() }})</button>
                                </td>
                                <td class="text-end">
                                    @if(!$giftCode->is_used)
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editGiftCodeModal{{ $giftCode->id }}"><i class="bi bi-pencil-square"></i> Edit</button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                            Locked
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <tr class="gift-collapse-row">
                                <td colspan="9" class="p-0 border-0">
                                    <div class="collapse" id="redemptions-{{ $giftCode->id }}">
                                        <div class="gift-redemption-box p-3">
                                            <h6 class="mb-3">Redemption History - {{ $giftCode->code }}</h6>

                                            @if($giftCode->redemptions->count())
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0 gift-inner-table">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>User</th>
                                                                <th>User ID</th>
                                                                <th>Reward Amount</th>
                                                                <th>Redeemed At</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($giftCode->redemptions as $redeem)
                                                                <tr>
                                                                    <td>{{ $loop->iteration }}</td>
                                                                    <td>
                                                                        {{ optional($redeem->user)->fullname ?? optional($redeem->user)->username ?? optional($redeem->user)->name ?? 'N/A' }} <br>
                                                                     UID:   {{ optional($redeem->user)->referral_code  }}
                                                                    </td>
                                                                    <td>{{ $redeem->user_id }}</td>
                                                                    <td>{{ number_format($redeem->reward_amount, 2) }}</td>
                                                                    <td>
                                                                        @if($redeem->redeemed_at)
                                                                            {{ \Carbon\Carbon::parse($redeem->redeemed_at)->format('d M Y, h:i A') }}
                                                                        @else
                                                                            <span class="text-muted">N/A</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <p class="text-muted mb-0">No redemption history found.</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editGiftCodeModal{{ $giftCode->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.gift.update', $giftCode->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Gift Code</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Code</label>
                                                    <input type="text" name="code" class="form-control" value="{{ old('code', $giftCode->code) }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Reward Amount</label>
                                                    <input type="number" step="0.01" min="0" name="reward_amount" class="form-control" value="{{ old('reward_amount', $giftCode->reward_amount) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Remarks Text</label>
                                                    <input type="text"  name="remarks" class="form-control" value="{{ old('remarks', $giftCode->remarks) }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Max Users</label>
                                                    <input type="number" min="1" name="max_users" class="form-control" value="{{ old('max_users', $giftCode->max_users) }}" required>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4 text-center text-muted">
                No gift codes found.
            </div>
        @endif
    </div>

    @if($giftCodes->hasPages())
        <div class="card-footer">
            {{ $giftCodes->links() }}
        </div>
    @endif
</div>
</div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const copyButtons = document.querySelectorAll('.copy-btn');

        copyButtons.forEach(button => {
            button.addEventListener('click', function () {
                const code = this.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    this.innerHTML = '<i class="bi bi-check2"></i>';
                    setTimeout(() => {
                        this.innerHTML = this.classList.contains('btn-link')
                            ? 'Copy'
                            : '<i class="bi bi-clipboard"></i>';
                    }, 1200);
                });
            });
        });

        const copyAllBtn = document.getElementById('copyAllGenerated');
        if (copyAllBtn) {
            copyAllBtn.addEventListener('click', function () {
                const codes = @json(session('generated_codes', []));
                navigator.clipboard.writeText(codes.join("\n")).then(() => {
                    this.innerHTML = '<i class="bi bi-check2-all"></i> Copied';
                    setTimeout(() => {
                        this.innerHTML = '<i class="bi bi-clipboard-check"></i> Copy All';
                    }, 1500);
                });
            });
        }
    });
</script>
@endpush