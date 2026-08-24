<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gift Code Management</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    body {
      background-color: #f4f7f6;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.05);
      transition: transform 0.2s;
    }
    .card-header {
      border-radius: 15px 15px 0 0 !important;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    /* Copy Button Styling */
    .copy-btn {
      cursor: pointer;
      color: #0d6efd;
      transition: 0.2s;
      padding: 2px 6px;
      border-radius: 5px;
    }
    .copy-btn:hover {
      background-color: #e9ecef;
      color: #0a58ca;
    }
    .code-text {
      font-family: 'Courier New', Courier, monospace;
      font-weight: bold;
      background: #f8f9fa;
      padding: 2px 5px;
      border-radius: 4px;
      border: 1px solid #dee2e6;
    }
    .table thead {
      background-color: #f8f9fa;
      color: #495057;
    }
    .btn-primary {
      background: var(--primary-gradient);
      border: none;
    }
    .badge {
      padding: 0.5em 0.8em;
      border-radius: 6px;
    }
    /* Toast Styling */
    #copy-toast {
      visibility: hidden;
      min-width: 200px;
      background-color: #333;
      color: #fff;
      text-align: center;
      border-radius: 30px;
      padding: 10px;
      position: fixed;
      z-index: 1050;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
    }
    #copy-toast.show {
      visibility: visible;
      animation: fadein 0.5s, fadeout 0.5s 1.5s;
    }
    @keyframes fadein { from {bottom: 0; opacity: 0;} to {bottom: 30px; opacity: 1;} }
    @keyframes fadeout { from {bottom: 30px; opacity: 1;} to {bottom: 0; opacity: 0;} }
  </style>
</head>
<body>

<div id="copy-toast">Code Copied! 📋</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Left Column: Generate Gift Codes -->
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white py-3">
                    <i class="bi bi-plus-square-fill me-2"></i> Generate Gift Codes
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.admin.gift.generate') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Number of Codes</label>
                            <input type="number" name="count" class="form-control" min="1" max="5000" value="{{ old('count', 1) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Max Users per Code</label>
                            <input type="number" name="max_users" class="form-control" min="1" value="{{ old('max_users', 1) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Reward Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="reward_amount" class="form-control" min="0" value="{{ old('reward_amount', 0) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="e.g. Eid Campaign" value="{{ old('remarks') }}" required>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                <i class="bi bi-lightning-fill"></i> Generate Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Gift Codes List -->
        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                    <span class="text-primary fs-5"><i class="bi bi-list-stars me-2"></i> Gift Code List</span>
                    <form action="{{ route('admin.admin.gift.logout') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Code</th>
                                    <th>Reward</th>
                                    <th>Max Users</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($giftCodes as $giftCode)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="code-text" id="code-{{ $giftCode->id }}">{{ $giftCode->code }}</span>
                                            <i class="bi bi-clipboard copy-btn ms-1" onclick="copyCode('{{ $giftCode->code }}')" title="Copy Code"></i>
                                        </td>
                                        <td class="fw-bold text-success">${{ number_format($giftCode->reward_amount, 2) }}</td>
                                        <td><i class="bi bi-people me-1"></i> {{ $giftCode->max_users }}</td>
                                        <td>
                                            @if($giftCode->is_used)
                                                <span class="badge bg-soft-danger text-danger border border-danger">Locked</span>
                                            @else
                                                <span class="badge bg-soft-success text-success border border-success">Active</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $giftCode->remarks }}</td>
                                        <td class="text-end pe-3">
                                            @if(!$giftCode->is_used)
                                                <button class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="modal" data-bs-target="#editGiftCodeModal{{ $giftCode->id }}">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-light rounded-pill disabled" disabled>
                                                    <i class="bi bi-lock-fill"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    <!-- Modal (Same as before but with updated classes) -->
                                                                  <!-- Edit Gift Code Modal -->
                                <div class="modal fade" id="editGiftCodeModal{{ $giftCode->id }}" tabindex="-1" aria-labelledby="editGiftCodeModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.admin.gift.update', $giftCode->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')

                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editGiftCodeModalLabel">Edit Gift Code</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Gift Code</label>
                                                        <input type="text" name="code" class="form-control" value="{{ old('code', $giftCode->code) }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Reward Amount</label>
                                                        <input type="number" name="reward_amount" class="form-control" value="{{ old('reward_amount', $giftCode->reward_amount) }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Max Users</label>
                                                        <input type="number" name="max_users" class="form-control" value="{{ old('max_users', $giftCode->max_users) }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Remarks</label>
                                                        <input type="text" name="remarks" class="form-control" value="{{ old('remarks', $giftCode->remarks) }}" required>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-warning">Update Gift Code</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Copy Functionality
    function copyCode(text) {
        navigator.clipboard.writeText(text).then(() => {
            var toast = document.getElementById("copy-toast");
            toast.className = "show";
            setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 2000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }

    // Initialize Bootstrap Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>