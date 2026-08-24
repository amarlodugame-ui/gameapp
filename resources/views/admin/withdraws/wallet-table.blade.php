@if($walletRows->count())
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Account</th>
                    <th>Status</th>
                    <th width="250">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($walletRows as $wallet)
                    @php
                        $type = strtoupper($wallet->type);
                        $badgeClass = match($type) {
                            'BKASH' => 'bg-success',
                            'NAGAD' => 'bg-warning ',
                            'ROCKET' => 'bg-danger',
                            'USDT' => 'bg-info ',
                            default => 'bg-secondary',
                        };
                    @endphp

                    <tr>
                        <td>#{{ $wallet->id }}</td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                        </td>
                        <td>{{ $wallet->full_name }}</td>
                        <td>{{ $wallet->masked_account }}</td>
                        <td>
                            @if($wallet->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                @if(!$wallet->is_active)
                                    <form action="{{ route('admin.withdraws.report.withdraw-wallets.activate', $wallet->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <input type="hidden" name="page" value="{{ $currentPage }}">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            Set Active
                                        </button>
                                    </form>
                                @endif

                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editWalletModal{{ $wallet->id }}"
                                >
                                    Edit
                                </button>

                                <form
                                    action="{{ route('admin.withdraws.report.withdraw-wallets.destroy', $wallet->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this wallet?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <input type="hidden" name="page" value="{{ $currentPage }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <div class="modal fade" id="editWalletModal{{ $wallet->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('admin.withdraws.report.withdraw-wallets.update', $wallet->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <input type="hidden" name="page" value="{{ $currentPage }}">

                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Wallet #{{ $wallet->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Wallet Type</label>
                                            <select name="type" class="form-select" required>
                                                @foreach($walletTypes as $typeOption)
                                                    <option value="{{ $typeOption }}" @selected(strtoupper($wallet->type) === $typeOption)>
                                                        {{ $typeOption }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input
                                                type="text"
                                                name="full_name"
                                                class="form-control"
                                                value="{{ $wallet->full_name }}"
                                                required
                                            >
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Account Number / Address</label>
                                            <input
                                                type="text"
                                                name="account_number"
                                                class="form-control"
                                                value="{{ $wallet->account_number }}"
                                                required
                                            >
                                        </div>

                                        <div class="form-check form-switch">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                id="is_active_{{ $wallet->id }}"
                                                @checked($wallet->is_active)
                                            >
                                            <label class="form-check-label" for="is_active_{{ $wallet->id }}">
                                                Set as active
                                            </label>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Update Wallet</button>
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
    <div class="border rounded p-4 text-center ">
        No wallet found in this section.
    </div>
@endif