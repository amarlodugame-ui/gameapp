<?php


use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\Referral;
use App\Models\CommissionLog;
use App\Models\Transaction;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\UserNotification;


use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;


use App\Models\DepositCommission;
use Illuminate\Support\Facades\DB;





function depositcommissionCridet(User $user, $amount, $methodKey = null)
{
    $amount = (float) $amount;

    if ($amount <= 0 || empty($methodKey)) {
        return false;
    }

    $methodKey = strtoupper(trim((string) $methodKey));

    $commission = DB::table('deposit_method_commissions')
        ->where('is_active', 1)
        ->where('method_key', $methodKey)
        ->first();

    if (!$commission) {
        return false;
    }

    $commissionPercent = (float) $commission->commission_percent;

    if ($commissionPercent <= 0) {
        return false;
    }

    $commissionAmount = round(($amount * $commissionPercent) / 100, 2);

    if ($commissionAmount <= 0) {
        return false;
    }

    $user->balance = round((float) $user->balance + $commissionAmount, 2);
    $user->turnover_required = round((float) $user->turnover_required + $commissionAmount, 2);
    $user->save();

    $trx = new Transaction();
    $trx->user_id = $user->id;
    $trx->amount = $commissionAmount;
    $trx->post_balance = (float) $user->balance;
    $trx->charge = 0;
    $trx->trx_type = '+';
    $trx->details = 'Deposit Commission via ' . $methodKey;
    $trx->remark = 'deposit_commission';
    $trx->trx = get_trx();
    $trx->save();

    return true;
}

function gs($key = null) {
    $general = GeneralSetting::first();
    if ($key) {
        return @$general->$key;
    }
    return $general;
}
function toast($message, $type = 'success', $icon = null)
{
    session()->flash('toast', [
        'message' => $message,
        'type' => $type,
        'icon' => $icon,
    ]);
}
function keyToTitle($text) {
    return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
}

    function showDateTime($date, $format = 'Y-m-d H:i:s')
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
    function levelCommission($referee, $amount, $commissionType, $trx)
    {
        $general = gs();
        if (!$general->$commissionType) {
            return false;
        }
        $i = 1;
        $level = Referral::where('commission_type', $commissionType)->get();
        $tempReferee = $referee;
        while ($i <= $level->count()) {
            $referer = $tempReferee->refBy;
            if (!$referer) {
                break;
            }

            $commission = Referral::where('commission_type', $commissionType)->where('level', $i)->first();

            if (!$commission) {
                break;
            }

            $com = ($amount * $commission->percent) / 100;
            // $referer->balance += $com;
            // $referer->save();

            $transactions[] = [
                'user_id' => $referer->id,
                'amount' => $com,
                'post_balance' => $referer->balance,
                'charge' => 0,
                'trx_type' => '+',
                'details' => $i. ' level referral commission from ' . $referee->referral_code,
                'remark' => 'referral_commission',
                'trx' => get_trx(),
                'created_at' => now()
            ];

            $commissionLog[] = [
                'to_id' => $referer->id,
                'from_id' => $referee->id,
                'level' => $i,
                'amount' => $com,
                'details' => $i . ' level referral commission from ' . $referee->referral_code,
                'type' => $commissionType,
                'trx' => get_trx(),
                'created_at' => now()
            ];

            $tempReferee = $referer;
            $i++;
        }
        if (isset($transactions)) {
            Transaction::insert($transactions);
        }
        if (isset($commissionLog)) {
            CommissionLog::insert($commissionLog);
        }
    }
    function selfDepositCommission(User $user, float $depositAmount, ?string $referenceTrx = null){
        $depositAmount = round((float) $depositAmount, 2);

        if ($depositAmount <= 0) {
            return null;
        }

        $rule = DepositCommission::query()
            ->where('deposit_amount', $depositAmount)
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            return null;
        }

        $commissionAmount = round(($depositAmount * (float) $rule->commission_percent) / 100, 2);

        if ($commissionAmount <= 0) {
            return null;
        }

        $freshUser = User::query()->lockForUpdate()->find($user->id);
        if (!$freshUser) {
            return null;
        }

        $beforeBalance = (float) $freshUser->balance;
        $freshUser->increment('balance', $commissionAmount);
        requiredTurnoverAmount($freshUser, $commissionAmount, '+');
        return Transaction::create([
            'user_id'   => $freshUser->id,
            'amount'    => $commissionAmount,
            'balance'   => $beforeBalance,
            'charge'    => 0,
            'trx_type'  => '+',
            'details'   => 'Self deposit commission',
            'remark'   => 'self_deposit_commission',
            'trx'       => get_trx(),
        ]);
    }

function getIpInfo()
{
    //	$ip = '210.1.246.42';
    $ip = null;
    $deep_detect = TRUE;

    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    $xml = @simplexml_load_file("http://www.geoplugin.net/xml.gp?ip=" . $ip);

    $country = @$xml->geoplugin_countryName;
    $city = @$xml->geoplugin_city;
    $area = @$xml->geoplugin_areaCode;
    $code = @$xml->geoplugin_countryCode;
    $long = @$xml->geoplugin_longitude;
    $lat = @$xml->geoplugin_latitude;


    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform = "Unknown OS Platform";
    $os_array = array(
        '/windows nt 10/i' => 'Windows 10',
        '/windows nt 6.3/i' => 'Windows 8.1',
        '/windows nt 6.2/i' => 'Windows 8',
        '/windows nt 6.1/i' => 'Windows 7',
        '/windows nt 6.0/i' => 'Windows Vista',
        '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i' => 'Windows XP',
        '/windows xp/i' => 'Windows XP',
        '/windows nt 5.0/i' => 'Windows 2000',
        '/windows me/i' => 'Windows ME',
        '/win98/i' => 'Windows 98',
        '/win95/i' => 'Windows 95',
        '/win16/i' => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i' => 'Mac OS 9',
        '/linux/i' => 'Linux',
        '/ubuntu/i' => 'Ubuntu',
        '/iphone/i' => 'iPhone',
        '/ipod/i' => 'iPod',
        '/ipad/i' => 'iPad',
        '/android/i' => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i' => 'Mobile'
    );
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
        }
    }
    $browser = "Unknown Browser";
    $browser_array = array(
        '/msie/i' => 'Internet Explorer',
        '/firefox/i' => 'Firefox',
        '/safari/i' => 'Safari',
        '/chrome/i' => 'Chrome',
        '/edge/i' => 'Edge',
        '/opera/i' => 'Opera',
        '/netscape/i' => 'Netscape',
        '/maxthon/i' => 'Maxthon',
        '/konqueror/i' => 'Konqueror',
        '/mobile/i' => 'Handheld Browser'
    );
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser = $value;
        }
    }

    $data['country'] = $country;
    $data['city'] = $city;
    $data['area'] = $area;
    $data['code'] = $code;
    $data['long'] = $long;
    $data['lat'] = $lat;
    $data['os_platform'] = $os_platform;
    $data['browser'] = $browser;
    $data['ip'] = request()->ip();
    $data['time'] = date('d-m-Y h:i:s A');

    return $data;
}


    function menuActive($pattern, $class = 'active')
    {
        return request()->routeIs($pattern) ? $class : '';
    }

    function menuOpen($pattern, $class = 'open')
    {
        return request()->routeIs($pattern) ? $class : '';
    }

    function submenuShow($pattern)
    {
        return request()->routeIs($pattern) ? 'max-height: none;' : '';
    }

function promotion_summary(User $user, int $maxLevel = 9): array
{
    $levels = promotion_referral_levels($user->id, $maxLevel);

    $directIds = $levels[1] ?? [];

    $teamIds = collect($levels)->flatten()->unique()->values()->all();

    $subTeamIds = collect($levels)
        ->except([1])
        ->flatten()
        ->unique()
        ->values()
        ->all();

    $timeZone = 'Asia/Dhaka';
    $now = Carbon::now($timeZone);

    $todayStart = $now->copy()->startOfDay();
    $todayEnd   = $now->copy()->endOfDay();
    $yesderdaystart = $now->copy()->subDay()->startOfDay();
    $yesderdayend = $now->copy()->subDay()->endOfDay();
    $beforeYesterday = $now->copy()->subDay()->startOfDay();

     $column = promotion_commission_amount_column();
    // $teamStats = promotion_group_stats($teamIds, $yesderdaystart, $yesderdayend);
    $subTeamStats = promotion_group_stats($subTeamIds, $yesderdaystart, $yesderdayend);
$teamStats = promotion_group_stats($subTeamIds, $yesderdaystart, $yesderdayend);
    // $teamStats['depositAmount'] = $subTeamStats['depositAmount'];
$yesterdayEnd = $now->copy()->subDay()->endOfDay();

$weekStart = $now->copy()->subDays(7)->startOfDay();
$weekEnd   = $yesterdayEnd;



    return [
        'yesterdayCommission' => promotion_format_amount(
            promotion_commission_between($user, $yesderdaystart, $yesderdayend)
        ),

        'direct' => promotion_group_stats($directIds, $yesderdaystart, $yesderdayend),
        'team'   => $teamStats,

'promotionData' => [

'thisWeekCommission' => promotion_format_amount(
    $user->receivedCommissionLogs()
        ->whereBetween('created_at', [$weekStart, $weekEnd])
        ->sum($column)
),

    'totalCommission' => promotion_format_amount(
        $user->receivedCommissionLogs()
            ->where('created_at', '<=', $yesterdayEnd)
            ->sum($column)
    ),

            'directSubordinateCount' => User::whereIn('id', $directIds)
                ->where('created_at', '<=', $yesterdayEnd)
                ->count(),

            'teamSubordinateCount' => User::whereIn('id', $subTeamIds)
            // 'teamSubordinateCount' => User::whereIn('id', $teamIds)
                ->where('created_at', '<=', $yesterdayEnd)
                ->count(),
],

        'levelWiseTeam' => collect(range(1, $maxLevel))->map(function ($level) use ($levels) {
            return [
                'level' => $level,
                'count' => count($levels[$level] ?? []),
            ];
        })->values()->all(),
    ];
}




    function promotion_referral_levels(int $rootUserId, int $maxLevel = 9): array
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
    function promotion_users_at_level(User $user, int $level)
    {
        $levels = promotion_referral_levels($user->id, $level);
        $ids = $levels[$level] ?? [];

        return User::query()
            ->whereIn('id', $ids)
            ->get();
    }
    function promotion_team_user_ids(User $user, int $maxLevel = 9, bool $includeDirect = true): array
    {
        $levels = promotion_referral_levels($user->id, $maxLevel);

        if (! $includeDirect) {
            unset($levels[1]); // direct বাদ
        }

        return collect($levels)
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
function promotion_group_stats(array $userIds, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
{
    if (empty($userIds)) {
        return [
            'registerCount'     => 0,
            'depositNumber'     => 0,
            'depositAmount'     => '0.00',
            'firstDepositCount' => 0,
        ];
    }

    $userQuery = User::query()->whereIn('id', $userIds);

    $depositQuery = Deposit::query()
        ->success()
        ->whereIn('user_id', $userIds);

    if ($from && $to) {
        $userQuery->whereBetween('created_at', [$from, $to]);
        $depositQuery->whereBetween('created_at', [$from, $to]);
    }

    $firstDepositCount = 0;

    if ($from && $to) {
        $firstDepositCount = Deposit::query()
            ->success()
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, MIN(created_at) as first_deposit_at')
            ->groupBy('user_id')
            ->get()
            ->filter(function ($row) use ($from, $to) {
                $firstDepositAt = Carbon::parse($row->first_deposit_at);
                return $firstDepositAt->between($from, $to);
            })
            ->count();
    }

    return [
        'registerCount'     => $userQuery->count(),
        // 'depositNumber'     => (clone $depositQuery)->count(),
        'depositNumber'     => (clone $depositQuery)->distinct('user_id')->count('user_id'),
        'depositAmount'     => promotion_format_amount((clone $depositQuery)->sum('amount')),
        'firstDepositCount' => $firstDepositCount,
    ];
}

    function promotion_total_commission(User $user): float
    {
        $column = promotion_commission_amount_column();

        return (float) $user->receivedCommissionLogs()->sum($column);
    }
    function promotion_commission_between(User $user, CarbonInterface $from, CarbonInterface $to): float
    {
        $column = promotion_commission_amount_column();

        return (float) $user->receivedCommissionLogs()
            ->whereBetween('created_at', [$from, $to])
            ->sum($column);
    }
    function promotion_commission_amount_column(): string
    {
        foreach (['amount', 'commission', 'commission_amount'] as $candidate) {
            if (Schema::hasColumn('commission_logs', $candidate)) {
                return $candidate;
            }
        }

        return 'amount';
    }
    function promotion_format_amount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }




if (! function_exists('promotion_referral_levels')) {
    function promotion_referral_levels(int $rootUserId, int $maxLevel = 6): array
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
}

if (! function_exists('promotion_find_user_level')) {
    function promotion_find_user_level(array $levels, int $userId): ?int
    {
        foreach ($levels as $level => $ids) {
            if (in_array($userId, $ids, true)) {
                return (int) $level;
            }
        }

        return null;
    }
}

if (! function_exists('promotion_commission_amount_column')) {
    function promotion_commission_amount_column(): string
    {
        foreach (['amount', 'commission', 'commission_amount'] as $candidate) {
            if (Schema::hasColumn('commission_logs', $candidate)) {
                return $candidate;
            }
        }

        return 'amount';
    }
}
function get_trx(){
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $trx = '';
    for ($i = 0; $i < 8; $i++) {
        $trx .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    return $trx;
}
function requiredTurnoverAmount($user, $amount = 0, $operator = '-'): void
{
    // if ($user->turnover_required <= 0) {
    //     return;
    // }
    if ($amount <= 0) {
        return;
    }
    if ($operator === '-') {
        $user->turnover_required = max(0, $user->turnover_required - $amount);
        $user->turnover_completed = ($user->turnover_completed ?? 0) + $amount;
        
    } elseif ($operator === '+') {
        $user->turnover_required += $amount;
    } else {
        return;
    }
    $user->save();
}



    function createUserNotification( int $userId, string $title, string $message, ?string $type = null): UserNotification {
        return UserNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }














