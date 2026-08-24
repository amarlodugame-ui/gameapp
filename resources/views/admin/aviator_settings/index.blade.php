@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card b-radius--10">

<div class="mb-3 text-end">
    <button type="button" class="btn btn-warning fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#aviatorGuideModal">
        <i class="las la-book me-1"></i>Game Control Book – Must Read
    </button>
</div>
<div class="modal fade" id="aviatorGuideModal" tabindex="-1" aria-labelledby="aviatorGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning-subtle border-bottom">
                <div>
                    <h4 class="modal-title fw-bold mb-1" id="aviatorGuideModalLabel">
                     Aviator Game Control Book
                    </h4>
                    <p class="mb-0 text-muted">
                        Admin panel থেকে game settings পরিবর্তনের আগে এই নির্দেশিকা অবশ্যই পড়ুন
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body bg-light">

                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-start">
                        <div class="me-3 fs-3"><i class="las la-exclamation-triangle"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">গুরুত্বপূর্ণ সতর্কতা</h6>
                            <p class="mb-1">
                                এই settings গুলো পরিবর্তন করলে Aviator game-এর <strong>speed, timing, payout risk, server load</strong> এবং
                                <strong>player experience</strong> সরাসরি প্রভাবিত হবে।
                            </p>
                            <p class="mb-0">
                                Production server-এ কোনো setting পরিবর্তনের আগে অবশ্যই value যাচাই করে save করবেন।
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">এই settings দিয়ে কী নিয়ন্ত্রণ করা যায়?</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-white border h-100">
                                    <span class="badge bg-primary mb-2">Game Status</span>
                                    <p class="mb-0 small text-muted">গেম চালু/বন্ধ, round timing, live behaviour</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-white border h-100">
                                    <span class="badge bg-success mb-2">Risk Control</span>
                                    <p class="mb-0 small text-muted">max bet, max payout, house edge, crash cap</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded bg-white border h-100">
                                    <span class="badge bg-info text-dark mb-2">System Performance</span>
                                    <p class="mb-0 small text-muted">heartbeat, engine loop, animation smoothness</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 1. enabled --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">1.Game Control — <code>enabled</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>গেম চালু বা বন্ধ করার মূল সুইচ।</strong></p>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item px-0"><strong>Enabled / ON:</strong> খেলোয়াড়রা game খেলতে পারবে</li>
                            <li class="list-group-item px-0"><strong>Disabled / OFF:</strong> নতুন bet নেওয়া বন্ধ থাকবে</li>
                        </ul>

                        <div class="alert alert-danger py-2">
                            <strong>সতর্কতা:</strong> OFF করলে চলমান operational plan অনুযায়ী ব্যবহার করো। বড় traffic সময়ে হঠাৎ OFF করলে player confusion হতে পারে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white h-100">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>enabled = 1</code> → Game চালু থাকবে, user bet করতে পারবে</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white h-100">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>enabled = 0</code> → Game বন্ধ থাকবে, নতুন round access বন্ধ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. betting_duration_ms --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">2. Betting Time — <code>betting_duration_ms</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>এই সময়ের মধ্যে player bet দিতে পারবে।</strong> সময় শেষ হলে plane উড়া শুরু করবে।</p>

                        <div class="alert alert-info py-2">
                            <strong>মনে রাখুন:</strong> <code>1000 ms = 1 second</code>
                        </div>

                        <div class="alert alert-warning py-2">
                            <strong>সতর্কতা:</strong> খুব কম সময় দিলে user bet ধরতে কষ্ট পাবে, খুব বেশি সময় দিলে round ধীর লাগবে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>4000</code> → ৪ সেকেন্ড bet window</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>6000</code> → ৬ সেকেন্ড সময়, casual player-এর জন্য বেশি আরামদায়ক</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. result_duration_ms --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">3. Result Display Time — <code>result_duration_ms</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>plane crash হওয়ার পরে result কতক্ষণ screen-এ দেখাবে।</strong></p>

                        <div class="alert alert-warning py-2">
                            <strong>সতর্কতা:</strong> খুব কম দিলে player result clear বুঝবে না, খুব বেশি দিলে পরের round দেরি মনে হবে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>3000</code> → ৩ সেকেন্ড result দেখাবে</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>5000</code> → ৫ সেকেন্ড result থাকবে, player বেশি সময় পাবে দেখতে</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 4. slow_factor --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">4. Game Speed — <code>slow_factor</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Plane কত দ্রুত multiplier বাড়াবে সেটা নিয়ন্ত্রণ করে।</strong></p>
                        <p class="mb-3">মান বেশি হলে multiplier দ্রুত বাড়বে, মান কম হলে ধীরে বাড়বে।</p>

                        <div class="alert alert-danger py-2">
                            <strong>সতর্কতা:</strong> খুব বেশি speed দিলে game aggressive হয়ে যাবে, inexperienced player-দের জন্য risky হতে পারে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>1.00</code> → Normal speed</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>1.45</code> → তুলনামূলক fast, game বেশি dynamic লাগবে</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 5. max_bet_amount --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">5. Max Bet Limit — <code>max_bet_amount</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>একজন player এক বেটে সর্বোচ্চ কত amount bet করতে পারবে।</strong></p>

                        <div class="alert alert-warning py-2">
                            <strong>সতর্কতা:</strong> খুব বেশি limit দিলে high roller exposure বাড়বে। Risk management দেখে set করুন।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>10000</code> → এক বেটে সর্বোচ্চ ১০,০০০</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>100000</code> → high-limit user allow করা যাবে</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 6. max_payout_amount --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">6. Max Payout Limit — <code>max_payout_amount</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>একটা bet থেকে player সর্বোচ্চ কত payout পেতে পারবে।</strong></p>

                        <div class="alert alert-danger py-2">
                            <strong>সতর্কতা:</strong> এটা casino exposure control-এর জন্য খুব গুরুত্বপূর্ণ। কম রাখলে বড় multiplier-এও payout cap হবে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>500000</code> → সর্বোচ্চ payout ৫ লক্ষ পর্যন্ত</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>5000000</code> → high exposure setup, বড় operation-এর জন্য</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 7. house_edge_bps --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">7. House Edge — <code>house_edge_bps</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Game-এর expected long-term profit margin।</strong></p>
                        <p><code>100 = 1%</code>, <code>200 = 2%</code></p>

                        <div class="alert alert-warning py-2">
                            <strong>সতর্কতা:</strong> বেশি house edge দিলে player fairness perception খারাপ হতে পারে। Balance রেখে set করা জরুরি।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>100</code> → Casino long-term average ১% edge</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>250</code> → Casino edge ২.৫%, একটু বেশি aggressive margin</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 8. max_crash_multiplier --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">8. Crash Cap — <code>max_crash_multiplier</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Random mode-এ multiplier সর্বোচ্চ কত পর্যন্ত যেতে পারবে।</strong></p>

                        <div class="alert alert-danger py-2">
                            <strong>সতর্কতা:</strong> খুব বেশি রাখলে rare round-এ বড় payout risk তৈরি হতে পারে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>100.00</code> → 100x এর বেশি যাবে না</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>1000.00</code> → বড় multiplier allow, তবে exposure বেশি</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 9. sound_enabled_by_default --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">9. Sound Option — <code>sound_enabled_by_default</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Game open হলে default sound on থাকবে নাকি off থাকবে।</strong></p>

                        <div class="alert alert-info py-2">
                            <strong>নোট:</strong> User চাইলে পরে manually sound on/off করতে পারবে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>1</code> → default sound ON</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>0</code> → default sound OFF</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 10. public_heartbeat_ms --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">10. Live Broadcast Speed — <code>public_heartbeat_ms</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Server কত interval-এ public multiplier/state update পাঠাবে।</strong></p>

                        <div class="alert alert-warning py-2">
                            <strong>সতর্কতা:</strong> value খুব কম হলে smooth animation হবে, কিন্তু server/network load বাড়বে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>200</code> → প্রতি 0.2 second-এ update যাবে</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>100</code> → আরো smooth update, তবে load বেশি</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 11. engine_sleep_ms --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">11. Engine Loop Speed — <code>engine_sleep_ms</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Game engine কত interval-এ loop করে logic update করবে।</strong></p>

                        <div class="alert alert-danger py-2">
                            <strong>সতর্কতা:</strong> খুব কম মান দিলে CPU usage বাড়তে পারে। Production server capability দেখে set করো।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>80</code> → engine প্রতি 0.08 second-এ update হবে</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>150</code> → load কমবে, তবে responsiveness সামান্য কম হতে পারে</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 12. manual_next_crash_multiplier --}}
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 fw-bold">12. Manual Testing Crash — <code>manual_next_crash_multiplier</code></h5>
                    </div>
                    <div class="card-body">
                        <p><strong>পরের round ঠিক কত multiplier-এ crash করবে সেটা manually set করা যায়।</strong></p>

                        <div class="alert alert-danger">
                            <strong>কঠোর সতর্কতা:</strong>
                            এটা শুধুমাত্র <strong>local / staging / testing</strong> environment-এর জন্য।
                            <strong>Production-এ ব্যবহার করা যাবে না</strong>, কারণ এতে fairness এবং trust ক্ষতিগ্রস্ত হতে পারে।
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ১</h6>
                                    <p class="mb-0"><code>5.50</code> → পরের round 5.50x-এ crash করবে</p>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <div class="border rounded p-3 bg-white">
                                    <h6 class="fw-bold">উদাহরণ ২</h6>
                                    <p class="mb-0"><code>2.00</code> → testing-এর জন্য low multiplier crash check করা যাবে</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Close Guide
                </button>
            </div>
        </div>
    </div>
</div>
            <div class="card-body">
                <form id="aviatorSettingsForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Game Enabled</label>
                            <select name="enabled" id="enabled" class="form-control">
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Betting Duration (ms)</label>
                            <input type="number" name="betting_duration_ms" id="betting_duration_ms" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Result Duration (ms)</label>
                            <input type="number" name="result_duration_ms" id="result_duration_ms" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Slow Factor</label>
                            <input type="number" step="0.01" name="slow_factor" id="slow_factor" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Max Bet Amount</label>
                            <input type="number" name="max_bet_amount" id="max_bet_amount" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Max Payout Amount</label>
                            <input type="number" name="max_payout_amount" id="max_payout_amount" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">House Edge (bps)</label>
                            <input type="number" name="house_edge_bps" id="house_edge_bps" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Max Crash Multiplier</label>
                            <input type="number" step="0.01" name="max_crash_multiplier" id="max_crash_multiplier" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sound Enabled By Default</label>
                            <select name="sound_enabled_by_default" id="sound_enabled_by_default" class="form-control">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Public Heartbeat (ms)</label>
                            <input type="number" name="public_heartbeat_ms" id="public_heartbeat_ms" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Engine Sleep (ms)</label>
                            <input type="number" name="engine_sleep_ms" id="engine_sleep_ms" class="form-control" readonly>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Manual Next Crash Multiplier</label>
                            <input type="number" step="0.01" name="manual_next_crash_multiplier" id="manual_next_crash_multiplier" class="form-control">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn--primary">
                            Update Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    "use strict";

    const form = document.getElementById('aviatorSettingsForm');

    function showMessage(type, message) {
        if (typeof notify !== 'undefined') {
            notify(type, message);
        } else {
            alert(message);
        }
    }

    function fillForm(data) {
        document.getElementById('enabled').value = Number(data.enabled ?? 1);
        document.getElementById('betting_duration_ms').value = data.betting_duration_ms ?? '';
        document.getElementById('result_duration_ms').value = data.result_duration_ms ?? '';
        document.getElementById('slow_factor').value = data.slow_factor ?? '';
        document.getElementById('max_bet_amount').value = data.max_bet_amount ?? '';
        document.getElementById('max_payout_amount').value = data.max_payout_amount ?? '';
        document.getElementById('house_edge_bps').value = data.house_edge_bps ?? '';
        document.getElementById('max_crash_multiplier').value = data.max_crash_multiplier ?? '';
        document.getElementById('sound_enabled_by_default').value = Number(data.sound_enabled_by_default ?? 0);
        document.getElementById('public_heartbeat_ms').value = data.public_heartbeat_ms ?? '';
        document.getElementById('engine_sleep_ms').value = data.engine_sleep_ms ?? '';
        document.getElementById('manual_next_crash_multiplier').value = data.manual_next_crash_multiplier ?? '';
    }

    async function loadSettings() {
        try {
            const response = await fetch("{{ route('admin.aviator.settings.show') }}", {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            fillForm(data);
        } catch (error) {
            showMessage('error', 'Failed to load Aviator settings.');
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const response = await fetch("{{ route('admin.aviator.settings.update') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    const firstMessage = data.errors[firstKey][0];
                    showMessage('error', firstMessage);
                    return;
                }

                showMessage('error', data.message || 'Update failed.');
                return;
            }

            showMessage('success', data.message || 'Settings updated.');
            fillForm(data.settings);
        } catch (error) {
            showMessage('error', 'Something went wrong while updating settings.');
        }
    });

    loadSettings();
})();
</script>
@endpush
@push('style')
<style>
    #aviatorGuideModal .modal-content {
        border-radius: 18px;
        overflow: hidden;
    }

    #aviatorGuideModal .card {
        border-radius: 14px;
    }

    #aviatorGuideModal .alert,
    #aviatorGuideModal .border.rounded {
        border-radius: 12px !important;
    }

    #aviatorGuideModal .table thead th {
        white-space: nowrap;
    }

    .btn-warning.shadow-sm {
        border-radius: 10px;
    }
</style>
@endpush