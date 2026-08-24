<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4" style="width:40px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                        </div>
                    </th>
                    <th>User</th>
                    <th>Phone</th>
                    <th>Ip Address</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Balance</th>
                    <th>Referral</th>
                    <th>Joined</th>
                    <th class="text-end pe-4" style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="ps-4">
                            <div class="form-check">
                                <input class="form-check-input row-checkbox" type="checkbox" value="{{ $user->id }}">
                            </div>
                        </td>

                        <td>
                            <div class="d-flex align-items-center">
                                 <form class="col-6" action="{{ route('admin.users.login.as.user', $user->id) }}" method="POST">
                                        @csrf
                                          <button type="submit" class="login_buton"> 
                                <div class="user-avatar">
                                    <img
                                        src="{{ $user->image ? asset('assets/profile/' . $user->image . '.webp') : asset('assets/img/avatars/avatar-placeholder.webp') }}"
                                        alt="{{ $user->name }}"
                                        style="width:45px;height:45px;object-fit:cover;border-radius:50%;"
                                    >
                                </div>
                                </button> </form>
                                <div>
                                    <div class="fw-semibold text-dark">{{ $user->name }}</div>
                                    <div class="text-muted small">UID: {{ $user->referral_code}}</div>
                                    <div class="text-muted small">Username: {{ $user->username ?: 'N/A' }}</div>
@php
    $userfourn = $user->referred_by ? App\Models\User::find($user->referred_by) : null;
@endphp

<div class="text-muted small">
    Ref By: {{ $userfourn?->referral_code ?? 'No Referral Found' }}
</div>
                                    
                                    <!-- <div class="text-muted small">ID: {{ $user->id }}</div> -->
                                </div>
                            </div>
                        </td>

                        <td class="text-muted"> {{ $user->phone }}</td>
                   <td class="text-muted">
    <span class="ip-text">{{ $user->ip_address }}</span>

    <button
        type="button"
        class="btn btn-sm p-0 border-0 bg-transparent copy-ip-btn"
        data-ip="{{ $user->ip_address }}"
        title="Copy IP"
        style="margin-left: 8px;"
    >
        <i class="bi bi-copy copy-icon"></i>
    </button>
</td>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.copy-ip-btn').forEach(function (button) {
        button.addEventListener('click', async function () {
            const ip = this.getAttribute('data-ip');
            const icon = this.querySelector('.copy-icon');

            try {
                await navigator.clipboard.writeText(ip);

                icon.classList.remove('bi-copy');
                icon.classList.add('bi-check2');
                this.setAttribute('title', 'Copied');

                setTimeout(() => {
                    icon.classList.remove('bi-check2');
                    icon.classList.add('bi-copy');
                    this.setAttribute('title', 'Copy IP');
                }, 2000);

            } catch (error) {
                console.error('Copy failed:', error);
                alert('Failed to copy IP address');
            }
        });
    });
});
</script>

                        <td>
                            @if($user->role === 'demo')
                                <span class="badge bg-warning-light text-warning">Demo</span>
                            @else
                                <span class="badge bg-info-light text-info">User</span>
                            @endif
                        </td>

                        <td>
                            @if($user->status == 1)
                                <span class="badge bg-success-light text-success">Active</span>
                            @else
                                <span class="badge bg-danger-light text-danger">Blocked</span>
                            @endif
                        </td>

                        <td class="text-muted">{{ number_format($user->balance, 2) }}</td>
                        <td class="text-muted">{{ $user->referral_code ?: 'N/A' }}</td>
                        <td class="text-muted">{{ optional($user->created_at)->format('M d, Y') }}</td>

                        <td class="text-end pe-4">
                            <div class="btn-group">
                               <a href="{{ route('admin.users.details.data', $user->id) }}" class="btn btn-sm btn-light" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-light" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- <button class="btn btn-sm btn-light text-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button> -->
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">No users found</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small">Rows per page:</span>
            <select class="form-select form-select-sm" id="perPage" style="width:auto;">
                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
            </select>

            <span class="text-muted small ms-2">
                Showing {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} of {{ $users->total() }}
            </span>
        </div>

        @if($users->lastPage() > 1)
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $users->onFirstPage() ? 'disabled' : '' }}">
                        <a href="javascript:void(0)" class="page-link js-page-link" data-page="{{ $users->currentPage() - 1 }}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    @php
                        $start = max(1, $users->currentPage() - 2);
                        $end   = min($users->lastPage(), $users->currentPage() + 2);
                    @endphp

                    @if($start > 1)
                        <li class="page-item">
                            <a href="javascript:void(0)" class="page-link js-page-link" data-page="1">1</a>
                        </li>
                        @if($start > 2)
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        <li class="page-item {{ $users->currentPage() == $i ? 'active' : '' }}">
                            <a href="javascript:void(0)" class="page-link js-page-link" data-page="{{ $i }}">{{ $i }}</a>
                        </li>
                    @endfor

                    @if($end < $users->lastPage())
                        @if($end < $users->lastPage() - 1)
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        @endif
                        <li class="page-item">
                            <a href="javascript:void(0)" class="page-link js-page-link" data-page="{{ $users->lastPage() }}">{{ $users->lastPage() }}</a>
                        </li>
                    @endif

                    <li class="page-item {{ $users->hasMorePages() ? '' : 'disabled' }}">
                        <a href="javascript:void(0)" class="page-link js-page-link" data-page="{{ $users->currentPage() + 1 }}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        @endif
    </div>
</div>