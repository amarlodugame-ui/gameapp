@extends('admin.layouts.app')

@section('panel')
<div class="row mb-3">
    <div class="col-lg-12">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                Add New Commission
            </button>
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
                                <th>Deposit Amount</th>
                                <th>Commission ৳</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($commissions as $commission)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ number_format((float)$commission->deposit_amount, 2) }}</td>
                                    <td>{{ number_format((float)$commission->commission_percent, 2) }} ৳</td>
                                    <td>
                                        @if($commission->is_active)
                                            <span class="badge badge-success" style="background-color: #28a745; color: white;">Active</span>
                                        @else
                                            <span class="badge badge-danger" style="background-color: #dc3545; color: white;">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($commission->created_at)->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary editBtn" data-id="{{ $commission->id }}" data-deposit_amount="{{ $commission->deposit_amount }}" data-commission_percent="{{ $commission->commission_percent }}" data-is_active="{{ $commission->is_active ? 1 : 0 }}" data-bs-toggle="modal" data-bs-target="#editModal">Edit
                                        </button>

                                        <form action="{{ route('admin.deposit-commissions.toggle', $commission->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                {{ $commission->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <!-- <form action="{{ route('admin.deposit-commissions.delete', $commission->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure to delete this row?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>
                                        </form> -->
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No commission data found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.deposit-commissions.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Deposit Commission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Deposit Amount</label>
                        <input type="number" step="0.01" name="deposit_amount" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Commission Amount</label>
                        <input type="number" step="0.01" name="commission_percent" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Status</label>
                        <select name="is_active" class="form-control" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Deposit Commission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label>Deposit Amount</label>
                        <input type="number" step="0.01" name="deposit_amount" id="edit_deposit_amount" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Commission Amount</label>
                        <input type="number" step="0.01" name="commission_percent" id="edit_commission_percent" class="form-control" required>
                    </div>

                    <div class="form-group mb-3">
                        <label>Status</label>
                        <select name="is_active" id="edit_is_active" class="form-control" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('script')
<script>
    (function () {
        'use strict';

        const editButtons = document.querySelectorAll('.editBtn');
        const editForm = document.getElementById('editForm');
        const editDepositAmount = document.getElementById('edit_deposit_amount');
        const editCommissionPercent = document.getElementById('edit_commission_percent');
        const editIsActive = document.getElementById('edit_is_active');

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const depositAmount = this.dataset.deposit_amount;
                const commissionPercent = this.dataset.commission_percent;
                const isActive = this.dataset.is_active;

                editForm.action = "{{ route('admin.deposit-commissions.update', ':id') }}".replace(':id', id);
                editDepositAmount.value = depositAmount;
                editCommissionPercent.value = commissionPercent;
                editIsActive.value = isActive;
            });
        });
    })();
</script>
@endpush