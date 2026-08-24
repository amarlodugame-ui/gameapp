@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid px-2">
    <div class="row g-2">
        @foreach($commissionTypes as $key => $type)
            @php
                $status = gs($key);
                $rows = $referrals->where('commission_type', $key);
            @endphp

            <div class="col-lg-6">
                <div class="card compact-referral-card">
                    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{{ __($type) }}</h5>

                        <a href="{{ route('admin.referrals.status', $key) }}"
                           class="btn btn-sm {{ $status ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            {{ $status ? 'Off' : 'On' }}
                        </a>
                    </div>

                    <div class="card-body py-2 px-3">
                        <div class="browser-list mb-3">
                            @forelse($rows as $referral)
                                <div class="browser-item">
                                    <div class="browser-info">
                                        <i class="bi bi-diagram-3 text-primary"></i>
                                        <span class="browser-name">Level {{ $referral->level }}</span>
                                    </div>
                                    <div class="browser-stats">
                                        <span class="browser-percent">{{ $referral->percent }}%</span>
                                    </div>
                                </div>
                            @empty
                                <div class="browser-item">
                                    <div class="browser-info">
                                        <i class="bi bi-info-circle text-muted"></i>
                                        <span class="browser-name">No levels found</span>
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <input type="number"
                                   min="1"
                                   name="level"
                                   class="form-control"
                                   placeholder="Level count">
                            <button type="button" class="btn btn-primary generate">Generate</button>
                        </div>

                        <small class="text-danger required-message d-none">Enter valid number</small>

                        <form action="{{ route('admin.referrals.update') }}" method="post" class="levelForm d-none mt-2">
                            @csrf
                              <h6 class="text-danger mb-3">@lang('The Old setting will be removed after generating new')</h6>
                            <input type="hidden" name="commission_type" value="{{ $key }}">
                            <div class="referralLevels"></div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                                Save
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('style')

@endpush

@push('script')
<script>
(function($){
    "use strict";

    $('[name="level"]').on('focus', function () {
        $(this).on('keyup', function (e) {
            if (e.which == 13) {
                generateLevels($(this));
            }
        });
    });

    $('.generate').on('click', function () {
        let $this = $(this).closest('.card-body').find('[name="level"]');
        generateLevels($this);
    });

    $(document).on('click', '.deleteBtn', function () {
        $(this).closest('.input-group').remove();
    });

    function generateLevels($this) {
        let numberOfLevel = parseInt($this.val());
        let parent = $this.closest('.card-body');
        let html = '';

        if (numberOfLevel && numberOfLevel > 0) {
            parent.find('.levelForm').removeClass('d-none');
            parent.find('.required-message').addClass('d-none');

            for (let i = 1; i <= numberOfLevel; i++) {
                html += `
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text">L-${i}</span>
                        <input type="hidden" name="level[]" value="${i}">
                        <input name="percent[]" class="form-control" required placeholder="Percent">
                        <button class="btn btn-outline-danger deleteBtn" type="button">×</button>
                    </div>
                `;
            }

            parent.find('.referralLevels').html(html);
        } else {
            parent.find('.levelForm').addClass('d-none');
            parent.find('.required-message').removeClass('d-none');
            parent.find('.referralLevels').html('');
        }
    }

})(jQuery);
</script>
@endpush