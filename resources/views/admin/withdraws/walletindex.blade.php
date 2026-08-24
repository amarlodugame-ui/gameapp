@extends('admin.layouts.app')

@section('panel')
@php
    $normalWallets = $wallets->filter(fn ($wallet) => strtoupper($wallet->type) !== 'USDT')->values();
    $usdtWallets = $wallets->filter(fn ($wallet) => strtoupper($wallet->type) === 'USDT')->values();

    $badgeMap = [
        'BKASH' => 'success',
        'NAGAD' => 'warning ',
        'ROCKET' => 'danger',
        'USDT' => 'info ',
    ];
@endphp


<div class="wallet-page row g-4">
    <div class="col-12">
        <div class="card search-wrap">
            <div class="card-body">
                <form id="walletSearchForm" action="{{ route('admin.withdraws.report.withdraw-wallets.index') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label class="form-label fw-semibold">Search User</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input
                                    type="text"
                                    id="walletSearchInput"
                                    name="q"
                                    class="form-control"
                                    placeholder="Type ref code / username / name"
                                    value="{{ $q }}"
                                    autocomplete="off"
                                >
                                @if($q !== '')
                                    <a href="{{ route('admin.withdraws.report.withdraw-wallets.index') }}" class="btn btn-outline-secondary">
                                        Clear
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <a href="{{ route('admin.withdraws.report.withdraw-wallets.index') }}" class="btn btn-light">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card user-list-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Users</h5>
                    <div class="section-subtitle">
                        {{ $users->total() }} total user{{ $users->total() !== 1 ? 's' : '' }}
                    </div>
                </div>
                <span class="badge bg-secondary" id="visibleUserCount">{{ $users->count() }}</span>
            </div>

            <div class="card-body p-0">
                @if($users->count())
                    <div class="list-group list-group-flush user-scroll" id="userListWrap">
                        @foreach($users as $user)
                            @php
                                $filterText = strtolower(
                                    trim(
                                        ($user->referral_code ?? '') . ' ' .
                                        ($user->username ?? '') . ' ' .
                                        ($user->name ?? '') . ' ' .
                                        ($user->phone ?? '')
                                    )
                                );
                            @endphp

                            <a
                                href="{{ route('admin.withdraws.report.withdraw-wallets.index', ['user_id' => $user->id, 'q' => $q, 'page' => $currentPage]) }}"
                                class="list-group-item list-group-item-action user-item js-user-item {{ $selectedUser && $selectedUser->id === $user->id ? 'active' : '' }}"
                                data-filter="{{ $filterText }}"
                            >
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-bold">{{ $user->referral_code ?: 'N/A' }}</div>
                                        <div class="small ">{{ $user->name ?: 'N/A' }}</div>
                                        <div class="small ">
                                            {{ $user->username ?: 'N/A' }}
                                            @if($user->phone)
                                                · {{ $user->phone }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class=" d-block">Balance</small>
                                        <strong>৳{{ number_format((float) $user->balance, 2) }}</strong>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center ">
                        No users found.
                    </div>
                @endif
            </div>

            @if($users->hasPages())
                <div class="card-footer">
                    {{ $users->appends(['q' => $q, 'user_id' => $selectedUser?->id])->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="col-xl-8">
        @if($selectedUser)
            <div class="sticky-side">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <h4 class="mb-1">{{ $selectedUser->name ?: 'N/A' }}</h4>
                                <div class=" small">Username: {{ $selectedUser->username ?: 'N/A' }}</div>
                                <div class=" small">Ref Code: {{ $selectedUser->referral_code ?: 'N/A' }}</div>
                                <div class=" small">Phone: {{ $selectedUser->phone ?: 'N/A' }}</div>
                            </div>

                            <div class="text-end">
                                <div class="small ">Current Balance</div>
                                <div class="fs-3 fw-bold">৳{{ number_format((float) $selectedUser->balance, 2) }}</div>
                            </div>
                        </div>

                        <hr>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="wallet-stat">
                                    <div class="label">Total Wallets</div>
                                    <div class="value">{{ $wallets->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="wallet-stat">
                                    <div class="label">Normal Wallets</div>
                                    <div class="value">{{ $normalWallets->count() }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="wallet-stat">
                                    <div class="label">USDT Wallets</div>
                                    <div class="value">{{ $usdtWallets->count() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card wallet-panel-card">
                            <div class="card-header border-0">
                                <h5 class="mb-0">Add Wallet</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.withdraws.report.withdraw-wallets.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <input type="hidden" name="page" value="{{ $currentPage }}">

                                    <div class="mb-3">
                                        <label class="form-label">Wallet Type</label>
                                        <select name="type" class="form-select" required>
                                            <option value="">Select type</option>
                                            @foreach($walletTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Account Number / Address</label>
                                        <input type="text" name="account_number" class="form-control" required>
                                    </div>

                                    <div class="form-check form-switch mb-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_new" checked>
                                        <label class="form-check-label" for="is_active_new">Set as active</label>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        Add Wallet
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card wallet-panel-card">
                            <div class="card-header border-0">
                                <h5 class="mb-0">Wallet Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="section-title">Normal Wallets</h6>
                                            <div class="section-subtitle">BKASH / NAGAD / ROCKET</div>
                                        </div>
                                        <span class="badge bg-primary">{{ $normalWallets->count() }}</span>
                                    </div>

                                    @include('admin.withdraws.wallet-table', [
                                        'walletRows' => $normalWallets,
                                        'walletTypes' => $walletTypes,
                                        'q' => $q,
                                        'currentPage' => $currentPage
                                    ])
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="section-title">USDT Wallets</h6>
                                            <div class="section-subtitle">USDT only</div>
                                        </div>
                                        <span class="badge bg-info ">{{ $usdtWallets->count() }}</span>
                                    </div>

                                    @include('admin.withdraws.wallet-table', [
                                        'walletRows' => $usdtWallets,
                                        'walletTypes' => $walletTypes,
                                        'q' => $q,
                                        'currentPage' => $currentPage
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-center ">
                    No user selected.
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between {
    overflow-x: scroll;
    flex-direction: column;
    text-align: center;
}
</style>
@endsection

@push('script')
<script>
(function () {
    const searchInput = document.getElementById('walletSearchInput');
    const searchForm = document.getElementById('walletSearchForm');
    const userItems = Array.from(document.querySelectorAll('.js-user-item'));
    const visibleCount = document.getElementById('visibleUserCount');

    if (!searchInput || !searchForm || !userItems.length) return;

    let debounceTimer = null;
    const initialQuery = @json($q ?? '');

    function applyClientFilter(keyword) {
        const q = (keyword || '').trim().toLowerCase();
        let count = 0;

        userItems.forEach((item) => {
            const haystack = item.dataset.filter || '';
            const matched = q === '' || haystack.includes(q);
            item.style.display = matched ? '' : 'none';
            if (matched) count++;
        });

        if (visibleCount) {
            visibleCount.textContent = count;
        }
    }

    applyClientFilter(searchInput.value);

    searchInput.addEventListener('input', function () {
        const value = this.value;
        applyClientFilter(value);

        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(function () {
            const current = (value || '').trim();
            const initial = (initialQuery || '').trim();

            if (current === initial) return;

            const url = new URL(searchForm.action, window.location.origin);
            if (current !== '') {
                url.searchParams.set('q', current);
            }
            url.searchParams.delete('user_id');
            url.searchParams.set('page', '1');

            window.location.href = url.toString();
        }, 500);
    });
})();
</script>
@endpush