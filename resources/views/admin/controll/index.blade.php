@extends('admin.layouts.app')

@section('panel')
<div class="row">
  <div class="col-12">
    {{-- NOTICE --}}
    <div class="card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Game Control Notice</h5>
      </div>
      <div class="card-body">
        <ul class="mb-0" style="line-height: 1.8;">
          <li><b>Random</b>: রেজাল্ট সম্পূর্ণ র‍্যান্ডম (0-9)</li>
          <li><b>Manual</b>: আপনি যে নাম্বার দিবেন (0-9), রেজাল্ট সেটাই হবে</li>
          <li><b>Profit Control</b>: সিস্টেম চেষ্টা করবে আপনার দেওয়া <b>Profit Margin (%)</b> অনুযায়ী হাউস প্রফিট রাখতে</li>
          <li><b>Profit Margin</b>:0% = fair, 5-15% = balanced, 20%+ = বেশি aggressive</li>
        </ul>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Game Control Settings</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table--light style--two mb-0">
            <thead>
              <tr>
                <th>Game Type</th>
                <th>Mode</th>
                <th>Manual Number</th>
                <th>Profit Margin (%)</th>
                <th>Last Update</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rows as $row)
              <tr>
                <td><b>{{ $row['game_type'] }}</b></td>
                <td>{{ $row['mode'] }}</td>
                <td>{{ $row['manual_number'] === null ? '-' : $row['manual_number'] }}</td>
                <td>{{ number_format((float)$row['profit_margin'], 2) }}</td>
                <td>{{ $row['updated_at'] ? $row['updated_at']->format('Y-m-d H:i:s') : '-' }}</td>
                <td>
                  <button type="button" class="btn btn-sm btn--primary" onclick="openGameControlModal('{{ $row['game_type'] }}','{{ $row['mode'] }}','{{ $row['manual_number'] === null ? '' : $row['manual_number'] }}','{{ number_format((float)$row['profit_margin'], 2, '.', '') }}')"> Edit</button>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- MODAL (Popup) --}}
<div class="modal fade" id="gameControlModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width: 520px;">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.game_control.store') }}">
        @csrf

        <div class="modal-header">
          <h5 class="modal-title">Edit Game Control: <span id="gcTitleType">-</span></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeGameControlModal()">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="game_type" id="gc_game_type">

          <div class="alert alert-info" style="font-size: 14px;">
            <b>নোট:</b>
            <ul class="mb-0">
              <li>Manual নিলে Manual Number (0-9) দিতে হবে</li>
              <li>Profit Control নিলে Profit Margin (%) যত বেশি দিবেন, system তত বেশি লাভ করার চেষ্টা করবে</li>
            </ul>
          </div>

          <div class="form-group">
            <label class="form-label">Mode</label>
            <select name="mode" id="gc_mode" class="form-control" required onchange="toggleManualNumber()">
              <option value="random">Random</option>
              <option value="manual">Manual</option>
              <option value="profit_control">Profit Control</option>
            </select>
          </div>

          <div class="form-group" id="manualNumberWrap">
            <label class="form-label">Manual Number (0-9)</label>
            <input type="number" min="0" max="9" name="manual_number" id="gc_manual_number" class="form-control" placeholder="Only for Manual mode">
            <small class="text-muted">Manual ছাড়া দিলে কাজ হবে না (অটো ignore হবে)।</small>
          </div>

          <div class="form-group">
            <label class="form-label">Profit Margin (%)</label>
            <input type="number" min="0" max="100" step="0.01" name="profit_margin" id="gc_profit_margin" class="form-control" required>
            <small class="text-muted">Profit Control mode এ এটা বেশি কাজ করবে।</small>
          </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn--dark" onclick="closeGameControlModal()">Cancel</button>
          <button type="submit" class="btn btn--primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  function openGameControlModal(gameType, mode, manualNumber, profitMargin) {
    document.getElementById('gcTitleType').innerText = gameType;
    document.getElementById('gc_game_type').value = gameType;

    document.getElementById('gc_mode').value = mode || 'random';
    document.getElementById('gc_manual_number').value = manualNumber || '';
    document.getElementById('gc_profit_margin').value = profitMargin || '0.00';

    toggleManualNumber();
    if (window.$ && $('#gameControlModal').modal) {
      $('#gameControlModal').modal('show');
    } else {
      document.getElementById('gameControlModal').style.display = 'block';
      document.getElementById('gameControlModal').classList.add('show');
    }
  }

  function closeGameControlModal() {
    if (window.$ && $('#gameControlModal').modal) {
      $('#gameControlModal').modal('hide');
    } else {
      document.getElementById('gameControlModal').classList.remove('show');
      document.getElementById('gameControlModal').style.display = 'none';
    }
  }

  function toggleManualNumber() {
    const mode = document.getElementById('gc_mode').value;
    const wrap = document.getElementById('manualNumberWrap');
    const input = document.getElementById('gc_manual_number');

    if (mode === 'manual') {
      wrap.style.display = 'block';
      input.required = true;
    } else {
      wrap.style.display = 'none';
      input.required = false;
      input.value = '';
    }
  }
</script>
@endsection