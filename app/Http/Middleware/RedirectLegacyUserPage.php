<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacyUserPage
{
    /**
     * Legacy Laravel page route names and their Vue Router destinations.
     *
     * JSON endpoints and mutation routes are deliberately absent. An
     * X-Inertia request is also allowed through so the SPA can reuse the
     * original controller's props while the user pages are being migrated.
     *
     * @var array<string, string>
     */
    private const HASH_ROUTES = [
        'user.login' => '/login',
        'user.register' => '/register',
        'user.rpwd' => '/reset-password',
        // user.dev is intentionally omitted: its legacy Vue component is missing.
        'user.activity' => '/activity',
        'user.activity.daily-tasks' => '/activity/daily-tasks',
        'user.activity.daily-tasks.record' => '/activity/daily-tasks/records',
        'user.activity.first-recharge' => '/activity/first-recharge',
        'user.activity.main.laundry' => '/activity/laundry',
        'user.activity.main.laundry.record' => '/activity/laundry/records',
        'user.activity.main.super-jackpot' => '/activity/super-jackpot',
        'user.activity.main.super-jackpot.rule' => '/activity/super-jackpot/rules',
        'user.activity.main.super-jackpot.star' => '/activity/super-jackpot/stars',
        'user.activity.main.redeem-gift' => '/activity/redeem-gift',
        'user.activity.main.daily-sign-in' => '/activity/daily-sign-in',
        'user.activity.main.daily-sign-in.rules' => '/activity/daily-sign-in/rules',
        'user.activity.main.daily-sign-in.record' => '/activity/daily-sign-in/records',
        'user.promotion' => '/promotion',
        'user.promotion.subordinate' => '/promotion/subordinates',
        'user.promotion.rule' => '/promotion/rules',
        'user.promotion.rebate-ratio' => '/promotion/rebate-ratio',
        'user.promotion.share' => '/promotion/share',
        'user.promotion.team-report' => '/promotion/team-report',
        'user.promotion.my-commission' => '/promotion/commission',
        'user.promotion.team-partner' => '/promotion/team-partner',
        'user.promotion.team-partner.invitation' => '/promotion/team-partner/history',
        'user.promotion.support' => '/support',
        'user.turntable' => '/rewards/turntable',
        'user.account' => '/account',
        'user.account.bet-records' => '/orders',
        'user.account.transaction-history' => '/transactions',
        'user.account.wallet' => '/wallet',
        'user.account.feedback' => '/feedback',
        'user.account.notification' => '/announcements',
        'user.account.guide' => '/guide',
        'user.account.about' => '/about',
        'user.account.language' => '/language',
        'user.account.vip' => '/vip',
        'user.account.strong-box' => '/strong-box',
        'user.account.strong-box.record' => '/strong-box/records',
        'user.account.messages' => '/notifications',
        'user.account.game-stats' => '/game-stats',
        'user.profile.setting-center' => '/profile',
        'user.profile.avatar' => '/profile/avatar',
        'user.profile.setting-center.login-password' => '/profile/password',
        'user.wingo.game' => '/games/wingo',
        'user.wallet.recharge' => '/wallet/recharge',
        'user.wallet.RechargeHistory' => '/wallet/recharge/history',
        'user.wallet.Withdraw' => '/wallet/withdraw',
        'user.wallet.WithdrawHistory' => '/wallet/withdraw/history',
        'user.wallet.welletList' => '/wallet/withdraw/accounts',
        'user.wallet.addWellet' => '/wallet/withdraw/accounts/add',
        'user.aviator.index' => '/games/aviator',
        'user.aviator.history' => '/games/aviator/history',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-User-Spa') === '1'
            && $request->headers->has('X-Inertia')
            && ! $request->headers->has('X-Inertia-Version')) {
            $request->headers->set('X-Inertia-Version', Inertia::getVersion());
        }

        if (! $request->isMethod('GET')
            || $request->headers->has('X-Inertia')
            || $request->header('X-User-Spa') === '1'
            || $request->expectsJson()) {
            return $next($request);
        }

        $hashPath = self::hashPathForRouteName($request->route()?->getName());

        if ($hashPath === null) {
            return $next($request);
        }

        $query = $request->getQueryString();
        $target = '/#'.$hashPath.($query ? '?'.$query : '');

        return redirect()->to($target);
    }

    public static function hashPathForRouteName(?string $routeName): ?string
    {
        return $routeName === null ? null : (self::HASH_ROUTES[$routeName] ?? null);
    }

    /**
     * Convert an absolute or relative legacy redirect URL to a Vue route.
     */
    public static function hashPathForLocation(string $location): ?string
    {
        $parts = parse_url($location);

        if ($parts === false) {
            return null;
        }

        $path = $parts['path'] ?? '/';

        if (rtrim($path, '/') === '') {
            return '/home';
        }

        $path = '/'.ltrim($path, '/');

        foreach (self::HASH_ROUTES as $routeName => $hashPath) {
            $route = app('router')->getRoutes()->getByName($routeName);

            if ($route === null || $route->parameterNames() !== []) {
                continue;
            }

            if ('/'.ltrim($route->uri(), '/') === $path) {
                $query = $parts['query'] ?? null;

                return $hashPath.(is_string($query) && $query !== '' ? '?'.$query : '');
            }
        }

        return null;
    }
}
