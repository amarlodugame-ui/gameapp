import { createRouter, createWebHashHistory, type RouteLocationRaw, type RouteRecordRaw } from 'vue-router'
import { emitNavigation } from './navigation'
import { loadRoutePage, page, setPageError, SpaPageRedirect } from './page'
import { clearSession, loadSession, session } from './session'

declare module 'vue-router' {
    interface RouteMeta {
        auth?: boolean
        guest?: boolean
        dataUrl?: string
        legacyName?: string
        title?: string
    }
}

const routes: RouteRecordRaw[] = [
    { path: '/', name: 'spa-root', component: () => import('./NotFound.vue'), meta: { title: 'Loading' } },
    { path: '/login', name: 'login', component: () => import('@/pages/themes/auth/Login.vue'), meta: { guest: true, dataUrl: '/user/login', legacyName: 'user.login', title: 'Login' } },
    { path: '/register', name: 'register', component: () => import('@/pages/themes/auth/Register.vue'), meta: { guest: true, dataUrl: '/user/register', legacyName: 'user.register', title: 'Register' } },
    { path: '/reset-password', name: 'reset-password', component: () => import('@/pages/themes/auth/Rpwd.vue'), meta: { guest: true, dataUrl: '/user/rpwd', legacyName: 'user.rpwd', title: 'Reset password' } },

    { path: '/home', name: 'home', component: () => import('@/pages/themes/Home.vue'), meta: { auth: true, dataUrl: '/user/spa/home', legacyName: 'user.home', title: 'Home' } },

    { path: '/activity', name: 'activity', component: () => import('@/pages/themes/user/activity/Index.vue'), meta: { auth: true, dataUrl: '/user/activity', legacyName: 'user.activity', title: 'Activity' } },
    { path: '/activity/daily-tasks', name: 'activity.daily-tasks', component: () => import('@/pages/themes/user/activity/DailyTasks.vue'), meta: { auth: true, dataUrl: '/user/activity/DailyTasks', legacyName: 'user.activity.daily-tasks', title: 'Daily tasks' } },
    { path: '/activity/daily-tasks/records', name: 'activity.daily-tasks.records', component: () => import('@/pages/themes/user/activity/Record.vue'), meta: { auth: true, dataUrl: '/user/activity/DailyTasks/Record', legacyName: 'user.activity.daily-tasks.record', title: 'Task records' } },
    { path: '/activity/first-recharge', name: 'activity.first-recharge', component: () => import('@/pages/themes/user/activity/FirstRecharge.vue'), meta: { auth: true, dataUrl: '/user/activity/FirstRecharge', legacyName: 'user.activity.first-recharge', title: 'First recharge' } },
    { path: '/activity/laundry', name: 'activity.laundry', component: () => import('@/pages/themes/user/activity/Laundry.vue'), meta: { auth: true, dataUrl: '/user/main/Laundry', legacyName: 'user.activity.main.laundry', title: 'Rebate' } },
    { path: '/activity/laundry/records', name: 'activity.laundry.records', component: () => import('@/pages/themes/user/activity/LaundryRecord.vue'), meta: { auth: true, dataUrl: '/user/main/Laundry/LaundryRecord', legacyName: 'user.activity.main.laundry.record', title: 'Rebate history' } },
    { path: '/activity/super-jackpot', name: 'activity.super-jackpot', component: () => import('@/pages/themes/user/activity/SuperJackpot.vue'), meta: { auth: true, dataUrl: '/user/main/SuperJackpot', legacyName: 'user.activity.main.super-jackpot', title: 'Super jackpot' } },
    { path: '/activity/super-jackpot/rules', name: 'activity.super-jackpot.rules', component: () => import('@/pages/themes/user/activity/SuperJackpotRule.vue'), meta: { auth: true, dataUrl: '/user/main/SuperJackpot/Rule', legacyName: 'user.activity.main.super-jackpot.rule', title: 'Super jackpot rules' } },
    { path: '/activity/super-jackpot/stars', name: 'activity.super-jackpot.stars', component: () => import('@/pages/themes/user/activity/SuperJackpotStar.vue'), meta: { auth: true, dataUrl: '/user/main/SuperJackpot/Star', legacyName: 'user.activity.main.super-jackpot.star', title: 'Winning stars' } },
    { path: '/activity/redeem-gift', name: 'activity.redeem-gift', component: () => import('@/pages/themes/user/activity/RedeemGift.vue'), meta: { auth: true, dataUrl: '/user/main/RedeemGift', legacyName: 'user.activity.main.redeem-gift', title: 'Gift' } },
    { path: '/activity/daily-sign-in', name: 'activity.daily-sign-in', component: () => import('@/pages/themes/user/activity/DailySignIn.vue'), meta: { auth: true, dataUrl: '/user/main/DailySignIn', legacyName: 'user.activity.main.daily-sign-in', title: 'Attendance' } },
    { path: '/activity/daily-sign-in/rules', name: 'activity.daily-sign-in.rules', component: () => import('@/pages/themes/user/activity/DailySignInRules.vue'), meta: { auth: true, dataUrl: '/user/main/DailySignIn/Rules', legacyName: 'user.activity.main.daily-sign-in.rules', title: 'Attendance rules' } },
    { path: '/activity/daily-sign-in/records', name: 'activity.daily-sign-in.records', component: () => import('@/pages/themes/user/activity/DailySignInRecord.vue'), meta: { auth: true, dataUrl: '/user/main/DailySignIn/Record', legacyName: 'user.activity.main.daily-sign-in.record', title: 'Attendance history' } },

    { path: '/promotion', name: 'promotion', component: () => import('@/pages/themes/user/promotion/Index.vue'), meta: { auth: true, dataUrl: '/user/promotion', legacyName: 'user.promotion', title: 'Promotion' } },
    { path: '/promotion/subordinates', name: 'promotion.subordinates', component: () => import('@/pages/themes/user/promotion/Subordinate.vue'), meta: { auth: true, dataUrl: '/user/promotion/Subordinate', legacyName: 'user.promotion.subordinate', title: 'Subordinates' } },
    { path: '/promotion/rules', name: 'promotion.rules', component: () => import('@/pages/themes/user/promotion/PromotionRule.vue'), meta: { auth: true, dataUrl: '/user/promotion/PromotionRule', legacyName: 'user.promotion.rule', title: 'Promotion rules' } },
    { path: '/promotion/rebate-ratio', name: 'promotion.rebate-ratio', component: () => import('@/pages/themes/user/promotion/RebateRatio.vue'), meta: { auth: true, dataUrl: '/user/promotion/RebateRatio', legacyName: 'user.promotion.rebate-ratio', title: 'Rebate ratio' } },
    { path: '/promotion/share', name: 'promotion.share', component: () => import('@/pages/themes/user/promotion/PromotionShare.vue'), meta: { auth: true, dataUrl: '/user/promotion/PromotionShare', legacyName: 'user.promotion.share', title: 'Invite' } },
    { path: '/promotion/team-report', name: 'promotion.team-report', component: () => import('@/pages/themes/user/promotion/TeamReport.vue'), meta: { auth: true, dataUrl: '/user/promotion/TeamReport', legacyName: 'user.promotion.team-report', title: 'Team report' } },
    { path: '/promotion/commission', name: 'promotion.commission', component: () => import('@/pages/themes/user/promotion/MyCommission.vue'), meta: { auth: true, dataUrl: '/user/promotion/MyCommission', legacyName: 'user.promotion.my-commission', title: 'Commission' } },
    { path: '/promotion/team-partner', name: 'promotion.team-partner', component: () => import('@/pages/themes/user/promotion/TeamPartner.vue'), meta: { auth: true, dataUrl: '/user/promotion/TeamPartner', legacyName: 'user.promotion.team-partner', title: 'Partner rewards' } },
    { path: '/promotion/team-partner/history', name: 'promotion.team-partner.history', component: () => import('@/pages/themes/user/promotion/TeamPartnerInvitation.vue'), meta: { auth: true, dataUrl: '/user/promotion/TeamPartner/Invitation', legacyName: 'user.promotion.team-partner.invitation', title: 'Partner history' } },
    { path: '/support', name: 'support', component: () => import('@/pages/themes/user/promotion/Support.vue'), meta: { auth: true, dataUrl: '/user/main/support', legacyName: 'user.promotion.support', title: 'Customer service' } },
    { path: '/rewards/turntable', name: 'rewards.turntable', component: () => import('@/pages/themes/user/promotion/Turntable.vue'), meta: { auth: true, dataUrl: '/user/turntable', legacyName: 'user.turntable', title: 'Turntable' } },

    { path: '/account', name: 'account', component: () => import('@/pages/themes/user/account/Index.vue'), meta: { auth: true, dataUrl: '/user/main', legacyName: 'user.account', title: 'Account' } },
    { path: '/orders', name: 'orders', component: () => import('@/pages/themes/user/account/GameHistory.vue'), meta: { auth: true, dataUrl: '/user/main/BetRecords', legacyName: 'user.account.bet-records', title: 'Bet history' } },
    { path: '/transactions', name: 'transactions', component: () => import('@/pages/themes/user/account/TransactionHistory.vue'), meta: { auth: true, dataUrl: '/user/TransAction', legacyName: 'user.account.transaction-history', title: 'Transactions' } },
    { path: '/wallet', name: 'wallet', component: () => import('@/pages/themes/user/account/Wallet.vue'), meta: { auth: true, dataUrl: '/user/wallet', legacyName: 'user.account.wallet', title: 'Wallet' } },
    { path: '/wallet/recharge', name: 'wallet.recharge', component: () => import('@/pages/themes/user/deposit/Deposit.vue'), meta: { auth: true, dataUrl: '/user/wallet/Recharge', legacyName: 'user.wallet.recharge', title: 'Deposit' } },
    { path: '/wallet/recharge/history', name: 'wallet.recharge.history', component: () => import('@/pages/themes/user/deposit/RechargeHistory.vue'), meta: { auth: true, dataUrl: '/user/wallet/RechargeHistory', legacyName: 'user.wallet.RechargeHistory', title: 'Deposit history' } },
    { path: '/wallet/withdraw', name: 'wallet.withdraw', component: () => import('@/pages/themes/user/withdraw/Index.vue'), meta: { auth: true, dataUrl: '/user/wallet/Withdraw', legacyName: 'user.wallet.Withdraw', title: 'Withdraw' } },
    { path: '/wallet/withdraw/history', name: 'wallet.withdraw.history', component: () => import('@/pages/themes/user/withdraw/WithdrawHistory.vue'), meta: { auth: true, dataUrl: '/user/wallet/WithdrawHistory', legacyName: 'user.wallet.WithdrawHistory', title: 'Withdrawal history' } },
    { path: '/wallet/withdraw/accounts', name: 'wallet.withdraw.accounts', component: () => import('@/pages/themes/user/withdraw/WelletList.vue'), meta: { auth: true, dataUrl: '/user/wallet/welletList', legacyName: 'user.wallet.welletList', title: 'Payment methods' } },
    { path: '/wallet/withdraw/accounts/add', name: 'wallet.withdraw.accounts.add', component: () => import('@/pages/themes/user/withdraw/AddWellet.vue'), meta: { auth: true, dataUrl: '/user/wallet/addWellet', legacyName: 'user.wallet.addWellet', title: 'Add payment method' } },
    { path: '/vip', name: 'vip', component: () => import('@/pages/themes/user/account/Vip.vue'), meta: { auth: true, dataUrl: '/user/vip', legacyName: 'user.account.vip', title: 'VIP' } },
    { path: '/strong-box', name: 'strong-box', component: () => import('@/pages/themes/user/account/StrongBox.vue'), meta: { auth: true, dataUrl: '/user/main/StrongBox', legacyName: 'user.account.strong-box', title: 'Safe' } },
    { path: '/strong-box/records', name: 'strong-box.records', component: () => import('@/pages/themes/user/account/strongBoxRecord.vue'), meta: { auth: true, dataUrl: '/user/main/StrongBox/StrongBoxRecord', legacyName: 'user.account.strong-box.record', title: 'Safe history' } },
    { path: '/notifications', name: 'notifications', component: () => import('@/pages/themes/user/account/Messages.vue'), meta: { auth: true, dataUrl: '/user/home/Messages', legacyName: 'user.account.messages', title: 'Notifications' } },
    { path: '/game-stats', name: 'game-stats', component: () => import('@/pages/themes/user/account/GameStats.vue'), meta: { auth: true, dataUrl: '/user/main/GameStats', legacyName: 'user.account.game-stats', title: 'Game statistics' } },
    { path: '/feedback', name: 'feedback', component: () => import('@/pages/themes/user/account/Feedback.vue'), meta: { auth: true, dataUrl: '/user/main/Feedback', legacyName: 'user.account.feedback', title: 'Feedback' } },
    { path: '/announcements', name: 'announcements', component: () => import('@/pages/themes/user/account/Notification.vue'), meta: { auth: true, dataUrl: '/user/main/Notification', legacyName: 'user.account.notification', title: 'Announcements' } },
    { path: '/guide', name: 'guide', component: () => import('@/pages/themes/user/account/Guide.vue'), meta: { auth: true, dataUrl: '/user/main/Guide', legacyName: 'user.account.guide', title: 'Guide' } },
    { path: '/about', name: 'about', component: () => import('@/pages/themes/user/account/About.vue'), meta: { auth: true, dataUrl: '/user/main/About', legacyName: 'user.account.about', title: 'About' } },
    { path: '/language', name: 'language', component: () => import('@/pages/themes/user/account/Language.vue'), meta: { auth: true, dataUrl: '/user/main/Language', legacyName: 'user.account.language', title: 'Language' } },
    { path: '/profile', name: 'profile', component: () => import('@/pages/themes/user/profile/Index.vue'), meta: { auth: true, dataUrl: '/user/main/SettingCenter', legacyName: 'user.profile.setting-center', title: 'Profile' } },
    { path: '/profile/avatar', name: 'profile.avatar', component: () => import('@/pages/themes/user/profile/Avatar.vue'), meta: { auth: true, dataUrl: '/user/main/Avatar', legacyName: 'user.profile.avatar', title: 'Avatar' } },
    { path: '/profile/password', name: 'profile.password', component: () => import('@/pages/themes/user/profile/LoginPassword.vue'), meta: { auth: true, dataUrl: '/user/main/SettingCenter/LoginPassword', legacyName: 'user.profile.setting-center.login-password', title: 'Password' } },
    { path: '/games/wingo', name: 'games.wingo', component: () => import('@/pages/themes/user/WinGo/Index.vue'), meta: { auth: true, dataUrl: '/user/home/AllLotteryGames/WinGo', legacyName: 'user.wingo.game', title: 'Win Go' } },
    { path: '/games/aviator', name: 'games.aviator', component: () => import('@/pages/themes/user/Aviator/Index.vue'), meta: { auth: true, dataUrl: '/user/aviator', legacyName: 'user.aviator.index', title: 'Aviator' } },
    { path: '/games/aviator/history', name: 'games.aviator.history', component: () => import('@/pages/themes/user/Aviator/History.vue'), meta: { auth: true, dataUrl: '/user/aviator/history', legacyName: 'user.aviator.history', title: 'Aviator history' } },

    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('./NotFound.vue'), meta: { title: 'Page not found' } },
]

const serverPathToSpa = new Map<string, string>()
const legacyNameToSpa = new Map<string, string>()
const spaPaths = new Set<string>()

for (const record of routes) {
    if (record.path.includes(':')) continue
    spaPaths.add(record.path)

    if (record.meta?.dataUrl) {
        serverPathToSpa.set(record.meta.dataUrl.toLowerCase(), record.path)
    }

    if (record.meta?.legacyName) {
        legacyNameToSpa.set(record.meta.legacyName, record.path)
    }
}

serverPathToSpa.set('/', '/home')

function parseUrl(input: string): URL | null {
    try {
        return new URL(input, window.location.origin)
    } catch {
        return null
    }
}

export function resolveSpaLocation(input: string | RouteLocationRaw): RouteLocationRaw | null {
    if (typeof input !== 'string') return input

    if (input.startsWith('#')) {
        return input.slice(1) || '/home'
    }

    const parsed = parseUrl(input)
    if (!parsed || parsed.origin !== window.location.origin) return null

    if (parsed.hash.startsWith('#/')) {
        return `${parsed.hash.slice(1)}${parsed.search}`
    }

    const spaPath = spaPaths.has(parsed.pathname)
        ? parsed.pathname
        : serverPathToSpa.get(parsed.pathname.toLowerCase())

    if (!spaPath) return null

    return `${spaPath}${parsed.search}`
}

export function spaPathForLegacyName(name: string): string | null {
    return legacyNameToSpa.get(name) ?? null
}

let preserveScrollOnce = false

export function preserveNextScroll(): void {
    preserveScrollOnce = true
}

export const spaRouter = createRouter({
    history: createWebHashHistory(),
    routes,
    scrollBehavior(_to, _from, savedPosition) {
        if (preserveScrollOnce) {
            preserveScrollOnce = false
            return false
        }

        return savedPosition ?? { left: 0, top: 0 }
    },
})

spaRouter.beforeEach(async (to) => {
    emitNavigation('start', { to })

    try {
        await loadSession()

        if (to.path === '/') {
            return { path: session.authenticated ? '/home' : '/login', replace: true }
        }

        if (to.meta.auth && !session.authenticated) {
            return { path: '/login', query: { redirect: to.fullPath }, replace: true }
        }

        if (to.meta.guest && session.authenticated) {
            return { path: '/games/wingo', replace: true }
        }

        await loadRoutePage(to)
        return true
    } catch (error: any) {
        if (error instanceof SpaPageRedirect) {
            const target = resolveSpaLocation(error.location) ?? error.location
            const resolved = spaRouter.resolve(target)

            return {
                path: resolved.path,
                query: resolved.query,
                replace: true,
            }
        }

        if (error?.response?.status === 401 && to.path !== '/login') {
            try {
                await loadSession(true)
            } catch {
                clearSession()
            }

            return { path: '/login', query: { redirect: to.fullPath }, replace: true }
        }

        if (error?.response?.status === 409) {
            await loadSession(true)
            const location = error.response.headers?.['x-inertia-location']
            const target = typeof location === 'string' ? resolveSpaLocation(location) : null

            if (target) return target
            return { path: session.authenticated ? '/games/wingo' : '/login', replace: true }
        }

        if (error?.response?.status === 403 && error.response.data?.authenticated === false) {
            clearSession(error.response.data?.csrf_token)
            return { path: '/login', replace: true }
        }

        setPageError(error?.response?.status ?? null, error?.response?.data?.message)
        emitNavigation('error', error)
        return true
    }
})

spaRouter.afterEach((to) => {
    const appName = import.meta.env.VITE_APP_NAME || 'Laravel'
    document.title = to.meta.title ? `${to.meta.title} - ${appName}` : appName
    if (!page.error) emitNavigation('success', { to })
    emitNavigation('finish', { to })
})

spaRouter.onError((error) => {
    emitNavigation('error', error)
    emitNavigation('finish', error)
})

export default spaRouter
