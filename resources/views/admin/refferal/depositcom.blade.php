@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid px-2">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive-md table-responsive">
                        <table class="table table-light style-two">
                            <thead>
                                <tr>
                                    <th>আইডি</th>
                                    <th>মেথড</th>
                                    <!--<th>কমিশন %</th>-->
                                    <th>মেথডের অবস্থা</th>
                                    <th width="150">অ্যাকশন</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($commissions as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>

                                        <td>{{ $item->method_key }}</td>

                                        <!--<td>-->
                                        <!--    {{ rtrim(rtrim(number_format($item->commission_percent, 2, '.', ''), '0'), '.') }}%-->
                                        <!--</td>-->

                                        <td>
                                            @if($item->is_active)
                                                <span class="badge bg-success">
                                                    চালু আছে
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    বন্ধ আছে
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            


    @if($item->is_active)
        {{-- চালু থাকলে বন্ধ করার বাটন দেখাবে --}}
        <form action="{{ route('admin.referrals.deposit-method-commissions.update') }}" method="POST" class="d-inline">
            @csrf

            <input type="hidden" name="id" value="{{ $item->id }}">
            <input type="hidden" name="is_active" value="0">

            <button type="submit" class="btn btn-danger btn-sm">
                মেথড বন্ধ করুন
            </button>
        </form>
    @else
        {{-- বন্ধ থাকলে চালু করার বাটন দেখাবে --}}
        <form action="{{ route('admin.referrals.deposit-method-commissions.update') }}" method="POST" class="d-inline">
            @csrf

            <input type="hidden" name="id" value="{{ $item->id }}">
            <input type="hidden" name="is_active" value="1">

            <button type="submit" class="btn btn-success btn-sm">
                মেথড চালু করুন
            </button>
        </form>
    @endif
                                            
                                            <!--<button-->
                                            <!--    type="button"-->
                                            <!--    class="btn btn-primary btn-sm editCommissionBtn"-->
                                            <!--    data-bs-toggle="modal"-->
                                            <!--    data-bs-target="#editCommissionModal"-->
                                            <!--    data-id="{{ $item->id }}"-->
                                            <!--    data-method="{{ $item->method_key }}"-->
                                            <!--    data-commission="{{ $item->commission_percent }}"-->
                                            <!--    data-status="{{ $item->is_active ? 1 : 0 }}"-->
                                            <!-->
                                            <!--    এডিট করুন-->
                                            <!--</button>-->
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            কোনো ডাটা পাওয়া যায়নি
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- Edit Modal --}}
    <div class="modal fade" id="editCommissionModal" tabindex="-1" aria-labelledby="editCommissionModalLabel" aria-hidden="true">
        <div class="modal-dialog">

            <form action="{{ route('admin.referrals.deposit-method-commissions.update') }}" method="POST">
                @csrf

                <input type="hidden" name="id" id="modal_id">

                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editCommissionModalLabel">
                            কমিশন আপডেট করুন
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">মেথড</label>

                            <input 
                                type="text" 
                                class="form-control" 
                                id="modal_method" 
                                readonly
                            >
                        </div>

                        <div class="mb-3" style="display:none;">
                            <label for="modal_commission" class="form-label">
                                কমিশন পারসেন্ট
                            </label>

                            <input
                                type="number"
                                step="0.000001"
                                min="0"
                                max="100"
                                name="commission_percent"
                                id="modal_commission"
                                class="form-control"
                                placeholder="কমিশন পারসেন্ট লিখুন"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label for="modal_status" class="form-label">
                                আপনি কি এই মেথডটি বন্ধ রাখতে চান?
                            </label>

                            <select name="is_active" id="modal_status" class="form-select" required>
                                <option value="0">হ্যাঁ</option>
                                <option value="1">না</option>
                            </select>

                            <small class="text-muted">
                                হ্যাঁ দিলে মেথড বন্ধ থাকবে, না দিলে মেথড চালু থাকবে।
                            </small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            বাতিল করুন
                        </button>

                        <button type="submit" class="btn btn-primary">
                            আপডেট করুন
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

</div>
@endsection


@push('style')

@endpush


@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.editCommissionBtn');

        buttons.forEach(button => {
            button.addEventListener('click', function () {
                document.getElementById('modal_id').value = this.getAttribute('data-id');
                document.getElementById('modal_method').value = this.getAttribute('data-method');
                document.getElementById('modal_commission').value = this.getAttribute('data-commission');
                document.getElementById('modal_status').value = this.getAttribute('data-status');
            });
        });
    });
</script>
@endpush