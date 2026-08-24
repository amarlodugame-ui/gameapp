<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\UserTurnoverHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManageUsersController extends Controller
{
    
    public function refferalusersAllData(Request $request)
    {
        $pageTitle = "Refferal Users Details";

        $referralCode = trim((string) $request->input('referral_code', ''));
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $rootUser = null;
        $result = null;

        if ($referralCode !== '') {
            $rootUser = User::query()
                ->where('referral_code', $referralCode)
                ->first();

            if (!$rootUser) {
                return view('admin.users.refferal_details', compact(
                    'pageTitle',
                    'referralCode',
                    'dateFrom',
                    'dateTo',
                    'rootUser',
                    'result'
                ))->with('error', 'Referral code not found');
            }

            $result = $this->buildReferralReport($rootUser, $dateFrom, $dateTo, 6);
        }

        return view('admin.users.refferal_details', compact(
            'pageTitle',
            'referralCode',
            'dateFrom',
            'dateTo',
            'rootUser',
            'result'
        ));
    }


private function buildReferralReport(User $rootUser, ?string $dateFrom = null, ?string $dateTo = null, int $maxLevel = 6): array
{
    $levels = $this->promotionReferralLevels($rootUser->id, $maxLevel);

    $allIds = collect($levels)
        ->flatten()
        ->unique()
        ->values()
        ->all();

    $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
    $to   = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

    /**
     * Demo user referral tree থেকে বাদ
     */
    $realUserIds = [];
    if (!empty($allIds)) {
        $realUserIds = User::query()
            ->whereIn('id', $allIds)
            ->where('role', '!=', 'demo')
            ->pluck('id')
            ->all();
    }

    $levels = collect($levels)->map(function ($ids) use ($realUserIds) {
        return array_values(array_intersect($ids, $realUserIds));
    })->toArray();

    $allIds = $realUserIds;

    $directIds = $levels[1] ?? [];

    $teamIds = collect(range(2, $maxLevel))
        ->flatMap(fn ($level) => $levels[$level] ?? [])
        ->unique()
        ->values()
        ->all();

    /**
     * Root user যদি demo হয়, নিজের all-time data 0 দেখাবে
     */
    $ownIds = $rootUser->role === 'demo' ? [] : [$rootUser->id];

    /**
     * =========================
     * ALL TIME DATA (NO FILTER)
     * =========================
     */
    $ownDepositAgg     = $this->getDepositAgg($ownIds, null, null);
    $ownWithdrawAgg    = $this->getWithdrawAgg($ownIds, null, null);

    $directDepositAgg  = $this->getDepositAgg($directIds, null, null);
    $directWithdrawAgg = $this->getWithdrawAgg($directIds, null, null);

    $teamDepositAgg    = $this->getDepositAgg($teamIds, null, null);
    $teamWithdrawAgg   = $this->getWithdrawAgg($teamIds, null, null);

    $allTimeSummary = [
        // 1) Own total deposit / withdraw
        'own_deposit_count_all_time'   => (int) ($ownDepositAgg[$rootUser->id]['deposit_count'] ?? 0),
        'own_deposit_amount_all_time'  => $this->formatAmount($ownDepositAgg[$rootUser->id]['deposit_amount'] ?? 0),
        'own_withdraw_count_all_time'  => (int) ($ownWithdrawAgg[$rootUser->id]['withdraw_count'] ?? 0),
        'own_withdraw_amount_all_time' => $this->formatAmount($ownWithdrawAgg[$rootUser->id]['withdraw_amount'] ?? 0),

        // 2) Direct (Level 1) total deposit / withdraw
        'direct_total_members_all_time'   => count($directIds),
        'direct_deposit_count_all_time'   => collect($directDepositAgg)->sum(fn ($row) => (int) ($row['deposit_count'] ?? 0)),
        'direct_deposit_amount_all_time'  => $this->formatAmount(collect($directDepositAgg)->sum(fn ($row) => (float) ($row['deposit_amount'] ?? 0))),
        'direct_withdraw_count_all_time'  => collect($directWithdrawAgg)->sum(fn ($row) => (int) ($row['withdraw_count'] ?? 0)),
        'direct_withdraw_amount_all_time' => $this->formatAmount(collect($directWithdrawAgg)->sum(fn ($row) => (float) ($row['withdraw_amount'] ?? 0))),

        // 3) Team (Level 2-6) total deposit / withdraw
        'team_2to6_total_members_all_time'   => count($teamIds),
        'team_2to6_deposit_count_all_time'   => collect($teamDepositAgg)->sum(fn ($row) => (int) ($row['deposit_count'] ?? 0)),
        'team_2to6_deposit_amount_all_time'  => $this->formatAmount(collect($teamDepositAgg)->sum(fn ($row) => (float) ($row['deposit_amount'] ?? 0))),
        'team_2to6_withdraw_count_all_time'  => collect($teamWithdrawAgg)->sum(fn ($row) => (int) ($row['withdraw_count'] ?? 0)),
        'team_2to6_withdraw_amount_all_time' => $this->formatAmount(collect($teamWithdrawAgg)->sum(fn ($row) => (float) ($row['withdraw_amount'] ?? 0))),

        // 4) Direct level 1 members who deposited 3+ times
        'direct_three_deposit_members_all_time' => collect($directDepositAgg)
            ->filter(fn ($row) => (int) ($row['deposit_count'] ?? 0) >= 3)
            ->count(),
    ];

    if (empty($allIds)) {
        return [
            'summary' => array_merge([
                'team_count' => 0,
                'direct_count' => 0,
                'wingo_bet_count' => 0,
                'wingo_bet_amount' => '0.00',
                'aviator_bet_count' => 0,
                'aviator_bet_amount' => '0.00',
                'deposit_user_count' => 0,
                'deposit_count' => 0,
                'deposit_amount' => '0.00',
                'commission_amount' => '0.00',
            ], $allTimeSummary),
            'levelSummary' => [],
            'details' => collect(),
        ];
    }

    $levelMap = [];
    foreach ($levels as $level => $ids) {
        foreach ($ids as $id) {
            $levelMap[$id] = $level;
        }
    }

    $usersQuery = User::query()
        ->whereIn('id', $allIds)
        ->where('role', '!=', 'demo')
        ->select('id', 'name', 'username', 'email', 'phone', 'referral_code', 'referred_by', 'created_at');

    $users = $usersQuery->get();

    $teamUserIds = $users->pluck('id')->all();

    /**
     * =========================
     * DATE FILTERED DATA
     * =========================
     */
    $wingoAgg      = $this->getWingoAgg($teamUserIds, $from, $to);
    $aviatorAgg    = $this->getAviatorAgg($teamUserIds, $from, $to);
    $depositAgg    = $this->getDepositAgg($teamUserIds, $from, $to);
    $commissionAgg = $this->getCommissionAgg($rootUser->id, $teamUserIds, $from, $to);

    $details = $users->map(function ($user) use ($levelMap, $wingoAgg, $aviatorAgg, $depositAgg, $commissionAgg) {
        $uid = $user->id;

        $wingo = $wingoAgg[$uid] ?? ['bet_count' => 0, 'bet_amount' => 0];
        $aviator = $aviatorAgg[$uid] ?? ['bet_count' => 0, 'bet_amount' => 0];
        $deposit = $depositAgg[$uid] ?? ['deposit_count' => 0, 'deposit_amount' => 0];
        $commission = $commissionAgg[$uid] ?? 0;

        return (object) [
            'id' => $uid,
            'level' => $levelMap[$uid] ?? null,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'referral_code' => $user->referral_code,
            'created_at' => $user->created_at,
            'wingo_bet_count' => (int) $wingo['bet_count'],
            'wingo_bet_amount' => $this->formatAmount($wingo['bet_amount']),
            'aviator_bet_count' => (int) $aviator['bet_count'],
            'aviator_bet_amount' => $this->formatAmount($aviator['bet_amount']),
            'deposit_count' => (int) $deposit['deposit_count'],
            'deposit_amount' => $this->formatAmount($deposit['deposit_amount']),
            'commission_amount' => $this->formatAmount($commission),
        ];
    })->sortBy(['level', 'id'])->values();

    $levelSummary = [];
    for ($level = 1; $level <= $maxLevel; $level++) {
        $levelUsers = $details->where('level', $level);

        $depositUserCount = $levelUsers->filter(function ($row) {
            return $row->deposit_count > 0;
        })->count();

        $levelSummary[] = [
            'level' => $level,
            'user_count' => $levelUsers->count(),
            'wingo_bet_count' => $levelUsers->sum('wingo_bet_count'),
            'wingo_bet_amount' => $this->formatAmount($levelUsers->sum(fn ($r) => (float) $r->wingo_bet_amount)),
            'aviator_bet_count' => $levelUsers->sum('aviator_bet_count'),
            'aviator_bet_amount' => $this->formatAmount($levelUsers->sum(fn ($r) => (float) $r->aviator_bet_amount)),
            'deposit_user_count' => $depositUserCount,
            'deposit_count' => $levelUsers->sum('deposit_count'),
            'deposit_amount' => $this->formatAmount($levelUsers->sum(fn ($r) => (float) $r->deposit_amount)),
            'commission_amount' => $this->formatAmount($levelUsers->sum(fn ($r) => (float) $r->commission_amount)),
        ];
    }

    $summary = array_merge([
        'team_count' => $details->count(),
        'direct_count' => $details->where('level', 1)->count(),
        'wingo_bet_count' => $details->sum('wingo_bet_count'),
        'wingo_bet_amount' => $this->formatAmount($details->sum(fn ($r) => (float) $r->wingo_bet_amount)),
        'aviator_bet_count' => $details->sum('aviator_bet_count'),
        'aviator_bet_amount' => $this->formatAmount($details->sum(fn ($r) => (float) $r->aviator_bet_amount)),
        'deposit_user_count' => $details->filter(fn ($r) => $r->deposit_count > 0)->count(),
        'deposit_count' => $details->sum('deposit_count'),
        'deposit_amount' => $this->formatAmount($details->sum(fn ($r) => (float) $r->deposit_amount)),
        'commission_amount' => $this->formatAmount($details->sum(fn ($r) => (float) $r->commission_amount)),
    ], $allTimeSummary);

    return [
        'summary' => $summary,
        'levelSummary' => $levelSummary,
        'details' => $details,
    ];
}
private function getWithdrawAgg(array $userIds, $from = null, $to = null): array
{
    if (empty($userIds)) {
        return [];
    }

    $query = DB::table('withdraws')
        ->whereIn('user_id', $userIds)
        ->where('status', 2)
        ->selectRaw('user_id, COUNT(id) as withdraw_count, COALESCE(SUM(amount),0) as withdraw_amount')
        ->groupBy('user_id');

    $this->applyDateRange($query, 'created_at', $from, $to);

    return $query->get()->mapWithKeys(function ($row) {
        return [
            $row->user_id => [
                'withdraw_count' => (int) $row->withdraw_count,
                'withdraw_amount' => (float) $row->withdraw_amount,
            ]
        ];
    })->toArray();
}

    private function promotionReferralLevels(int $rootUserId, int $maxLevel = 6): array
    {
        $levels = [];
        $parentIds = [$rootUserId];

        for ($level = 1; $level <= $maxLevel; $level++) {
            if (empty($parentIds)) {
                $levels[$level] = [];
                continue;
            }

            $childIds = User::query()
                ->whereIn('referred_by', $parentIds)
                ->pluck('id')
                ->unique()
                ->values()
                ->all();

            $levels[$level] = $childIds;
            $parentIds = $childIds;
        }

        return $levels;
    }

    private function getWingoAgg(array $userIds, $from = null, $to = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $query = DB::table('bets')
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(id) as bet_count, COALESCE(SUM(amount),0) as bet_amount')
            ->groupBy('user_id');

        $this->applyDateRange($query, 'created_at', $from, $to);

        return $query->get()->mapWithKeys(function ($row) {
            return [
                $row->user_id => [
                    'bet_count' => (int) $row->bet_count,
                    'bet_amount' => (float) $row->bet_amount,
                ]
            ];
        })->toArray();
    }

    private function getAviatorAgg(array $userIds, $from = null, $to = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $query = DB::table('aviator_bets')
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(id) as bet_count, COALESCE(SUM(amount),0) as bet_amount')
            ->groupBy('user_id');

        $this->applyDateRange($query, 'created_at', $from, $to);

        return $query->get()->mapWithKeys(function ($row) {
            return [
                $row->user_id => [
                    'bet_count' => (int) $row->bet_count,
                    'bet_amount' => (float) $row->bet_amount,
                ]
            ];
        })->toArray();
    }

    private function getDepositAgg(array $userIds, $from = null, $to = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $query = DB::table('deposits')
            ->whereIn('user_id', $userIds)
            ->where('status', 1)
            ->selectRaw('user_id, COUNT(id) as deposit_count, COALESCE(SUM(amount),0) as deposit_amount')
            ->groupBy('user_id');

        $this->applyDateRange($query, 'created_at', $from, $to);

        return $query->get()->mapWithKeys(function ($row) {
            return [
                $row->user_id => [
                    'deposit_count' => (int) $row->deposit_count,
                    'deposit_amount' => (float) $row->deposit_amount,
                ]
            ];
        })->toArray();
    }

    private function getCommissionAgg(int $rootUserId, array $userIds, $from = null, $to = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $query = DB::table('commission_logs')
            ->where('to_id', $rootUserId)
            ->whereIn('from_id', $userIds)
            ->selectRaw('from_id, COALESCE(SUM(amount),0) as commission_amount')
            ->groupBy('from_id');

        $this->applyDateRange($query, 'created_at', $from, $to);

        return $query->get()->mapWithKeys(function ($row) {
            return [$row->from_id => (float) $row->commission_amount];
        })->toArray();
    }

    private function applyDateRange($query, string $column, $from = null, $to = null): void
    {
        if ($from && $to) {
            $query->whereBetween($column, [$from, $to]);
        } elseif ($from) {
            $query->where($column, '>=', $from);
        } elseif ($to) {
            $query->where($column, '<=', $to);
        }
    }

    private function formatAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }












    public function index(Request $request){
        $pageTitle = 'User List';
        $stats = $this->getUserStats();
        $perPage = $this->resolvePerPage($request);
        $users   = $this->userListQuery($request)->paginate($perPage)->withQueryString();
        return view('admin.users.list', compact('pageTitle', 'stats', 'users', 'perPage'));
    }
    public function turnoverForm(Request $request)
    {
        $pageTitle = 'Manage User Turnover Required';

        $user = null;
        $histories = collect();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $user = User::query()
                ->where('referral_code', $search)
                ->orWhere('username', $search)
                ->first();

            if ($user) {
                $histories = UserTurnoverHistory::where('user_id', $user->id)
                    ->latest('id')
                    ->paginate(20)
                    ->withQueryString();
            }
        }

        return view('admin.users.turnover_required', compact('pageTitle', 'user', 'histories'));
    }

    public function turnoverUpdate(Request $request)
    {
        $request->validate([
            'uid' => ['required', 'string'],
            'type' => ['required', 'in:add,subtract'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $admin = Auth::user();

        $user = User::query()
            ->where('referral_code', $request->uid)
            ->orWhere('username', $request->uid)
            ->first();

        if (!$user) {
            return back()->withErrors(['uid' => 'User not found by UID / referral code.'])->withInput();
        }

        DB::transaction(function () use ($request, $user, $admin) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $oldTurnover = (float) $lockedUser->turnover_required;
            $changeAmount = (float) $request->amount;

            if ($request->type === 'add') {
                $newTurnover = $oldTurnover + $changeAmount;
            } else {
                $newTurnover = $oldTurnover - $changeAmount;
                if ($newTurnover < 0) {
                    $newTurnover = 0;
                }
            }

            $lockedUser->update([
                'turnover_required' => $newTurnover,
            ]);

            UserTurnoverHistory::create([
                'user_id' => $lockedUser->id,
                'old_turnover_required' => $oldTurnover,
                'change_amount' => $changeAmount,
                'type' => $request->type,
                'new_turnover_required' => $newTurnover,
                'note' => $request->note,
            ]);
        });

        return redirect()->route('admin.users.turnover.form', ['search' => $user->referral_code])->with('success', 'Turnover required updated successfully.');
    }
    public function details($id){
        $pageTitle = 'User Details';
        $user = User::select(['id','name','username','email','phone_code','phone','email_verified_at','balance','strong_box_balance','game_balance','turnover_required','turnover_completed','referred_by','referral_code','referred_by','image','role','status','created_at','updated_at',])->findOrFail($id);
        return view('admin.users.details', compact('pageTitle', 'user'));
    }
    public function balanceUpdate(Request $request, $id){
        $request->validate([
            'balance_type' => 'required|in:balance,strong_box_balance,game_balance',
            'action_type'  => 'required|in:add,subtract',
            'amount'       => 'required|numeric|min:0.01',
            'resone'       => 'required',
        ]);

        $user = User::findOrFail($id);
        $field = $request->balance_type;
        $amount = (float) $request->amount;
        if ($request->action_type === 'add') {
            $user->$field = $user->$field + $amount;
        } else {
            if ($user->$field < $amount) {
                return back()->with('error', 'Insufficient balance to subtract');
            }
            $user->$field = $user->$field - $amount;
        }
        $user->save();
        
        $trx = new Transaction();
        $trx->user_id = $user->id;
        $trx->amount = $amount;
        $trx->trx_type = $request->action_type === 'add' ? '+' : '-';
        $trx->details = $request->resone ?? 'Admin balance adjustment';
        $trx->remark = 'balance_adjustment';
        $trx->save();   
        
        return back()->with('success', 'Balance updated successfully');
    }

    public function changePassword(Request $request, $id){
        $request->validate(['password' => 'required|string|min:6|confirmed',]);
        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();
        return back()->with('success', 'Password changed successfully');
    }

    public function statusUpdate(Request $request, $id){
        $request->validate(['status' => 'required|in:0,1',]);
        $user = User::findOrFail($id);
        $user->status = $request->status;
        $user->save();
        return back()->with('success', 'User status updated successfully');
    }

    public function loginAsUser($id){
        $user = User::findOrFail($id);
        Auth::guard('web')->login($user);
        return redirect('/')->with('success', 'Logged in as user successfully');
    }

    public function ajaxList(Request $request){
        $perPage = $this->resolvePerPage($request);
        $users   = $this->userListQuery($request)->paginate($perPage)->withQueryString();
        return response()->json(['status' => true,'html'   => view('admin.users.partials.table', compact('users', 'perPage'))->render(),]);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name'      => ['required', 'string', 'max:255'],
            'username'  => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'email'     => ['nullable', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone_code'=> ['nullable', 'string', 'max:10'],
            'phone'     => ['required', 'string', 'max:20', 'unique:users,phone'],
            'role'      => ['required', 'in:demo,user'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
            'status'    => ['nullable', 'in:0,1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false,'errors' => $validator->errors(),], 422);
        }

        $data = $validator->validated();
        $user = new User();
        $user->name       = $data['name'];
        $user->username   = $data['username'] ?? $this->generateUsername($data['name']);
        $user->email      = $data['email'] ?? null;
        $user->phone_code = $data['phone_code'] ?? null;
        $user->phone      = $data['phone'];
        $user->role       = $data['role'];
        $user->status     = $data['status'] ?? 1;
        $user->password   = Hash::make($data['password']);

        $user->balance             = 0;
        $user->strong_box_balance  = 0;
        $user->game_balance        = 0;
        $user->turnover_required   = 0;
        $user->turnover_completed  = 0;
        $user->image               = 1;
        $user->referral_code       = $this->generateReferralCode();
        $user->save();
        return response()->json(['status'  => true,'message' => 'User created successfully',]);
    }

    private function userListQuery(Request $request){
        $search = trim((string) $request->search);
        $status = $request->status;
        $role   = $request->role ?: $request->type;

        $query = User::query()->select(['id','name','ip_address','username','email','phone_code','phone','image','role','status','balance','referral_code','referred_by','created_at',]);
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', (int) $status);
        }

        if ($role !== null && $role !== '' && $role !== 'all') {
            $query->where('role', $role);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $lowerSearch = \Illuminate\Support\Str::lower($search);
                if (is_numeric($search)) {$q->orWhere('id', (int) $search)->orWhere('phone', 'like', $search . '%');}
                if (filter_var($search, FILTER_VALIDATE_EMAIL)) {$q->orWhere('email', 'like', $search . '%');}
                if (in_array($lowerSearch, ['active', 'enabled'])) {$q->orWhere('status', 1);}
                if (in_array($lowerSearch, ['blocked', 'inactive', 'disabled'])) {$q->orWhere('status', 0);}
                if (in_array($lowerSearch, ['demo', 'user'])) {$q->orWhere('role', $lowerSearch);}
                $q->orWhere('username', 'like', $search . '%')->orWhere('email', 'like', $search . '%')->orWhere('phone_code', 'like', $search . '%')->orWhere('referral_code', 'like', $search . '%')->orWhere('name', 'like', $search . '%');
                  $q->orWhere('ip_address', 'like', $search . '%');
                if (mb_strlen($search) >= 3) {$q->orWhere('name', 'like', '%' . $search . '%')->orWhere('username', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%')->orWhere('phone', 'like', '%' . $search . '%')->orWhere('referral_code', 'like', '%' . $search . '%');}
            });
        }

        return $query->orderByDesc('id');
    }

    private function getUserStats(): array{
        return ['totalUsers'    => User::count(),'activeUsers'   => User::where('status', 1)->count(),'blockedUsers'  => User::where('status', 0)->count(),'thisMonthUsers'=> User::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),];
    }

    private function resolvePerPage(Request $request): int{
        $allowed = [10, 25, 50, 100];
        $perPage = (int) $request->get('per_page', 10);
        return in_array($perPage, $allowed) ? $perPage : 10;
    }

    private function generateUsername(string $name): string{
        $base = Str::slug($name, '_');
        if ($base === '') {
            $base = 'user';
        }
        $username = $base;
        $count = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $count;
            $count++;
        }
        return $username;
    }

    private function generateReferralCode(): string{
        $lastReferralCode = User::max('referral_code');
        $referralCode = $lastReferralCode ? $lastReferralCode + 1 : random_int(10000000, 99999999); 
        return $referralCode;
    }
}