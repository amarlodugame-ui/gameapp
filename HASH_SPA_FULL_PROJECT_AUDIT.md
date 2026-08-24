# Hash SPA Full Project Architecture Audit

Audit date: 2026-08-24  
Project root: `C:\Users\laptop plaza\Downloads\Telegram Desktop\surex.tar\surex\surex\public\core`  
Scope: analysis and migration design only. No application code, route, dependency, database, or admin-panel change was made.

## 1. Executive verdict

The user panel can be migrated to Vue Router using `createWebHashHistory()` while the admin panel remains Laravel Blade. The separation already exists at the routing and authentication layers:

- User pages use Vue 3 + Inertia, the `web` session guard, and routes at `/`, `/user/*`.
- Admin pages use Blade, the independent `admin` session guard, and routes mounted at `/tradex/*`.
- The browser never sends `#/...` to Laravel. Laravel should serve one user SPA shell from `/`; Vue Router handles the fragment; JSON/action requests continue to use real Laravel URLs.
- Backend business logic, models, database schema, callbacks, game engines, queues, and admin Blade can remain in place.

Verdict: **SAFE TO START HASH SPA MIGRATION**, with **HIGH** complexity because 42 rendered user pages consume direct Inertia props and 31 Vue files invoke Inertia navigation/form APIs.

Important correction to the requested examples: the live admin prefix is `/tradex`, not `/admin`. Do not rename it during this migration.

## 2. Installed stack and build architecture

| Item | Installed version / configuration |
|---|---|
| PHP | Constraint `^8.2` |
| Laravel | 12.53.0 |
| Vue | 3.5.35 |
| Inertia Vue adapter | 2.3.25 |
| Inertia Laravel adapter | 2.0.21 |
| Vite | 7.3.5 |
| Vue Vite plugin | 6.0.7 |
| Laravel Vite plugin | 2.1.0 |
| Sanctum | 4.3.1 |
| Ziggy | 2.6.1 |
| Wayfinder | 0.1.14 |
| Vue Router | Not installed |
| Pinia/store | Not installed and no store directory in use |

Current client boot:

1. `vite.config.ts` builds `resources/js/app.ts` and declares `resources/js/ssr.ts`.
2. `resources/js/app.ts` calls `createInertiaApp`, dynamically resolves `resources/js/pages/themes/${name}.vue`, installs the Inertia plugin and `ZiggyVue`, imports `resources/css/app.css` and `resources/js/echo.ts`.
3. `resources/views/app.blade.php` supplies CSRF meta, `@routes`, Vite tags, `@inertiaHead`, and `@inertia`.
4. `HandleInertiaRequests` uses root view `app` and globally shares `name`, `auth.user` (`id`, `name`, `image`) and `flash.toast`.

There is one actual theme. `themes` is a directory name, not a dynamic theme selector: no configuration, database setting, middleware, or resolver chooses among multiple themes. All pages resolve from the single `resources/js/pages/themes` tree.

Recommended build shape after approval:

- Keep `resources/js/app.ts` and `resources/views/app.blade.php` temporarily for legacy Inertia.
- Add a separate `resources/js/user-spa.ts` and `resources/views/user-spa.blade.php` during parallel migration.
- Add that entry to the existing Vite input. It should install Vue Router and Ziggy, import the same CSS and Echo bootstrap, and mount a root `<RouterView>`.
- Do not change the admin Blade asset flow.
- Do not remove Inertia, its Laravel middleware, SSR file, Ziggy, or Wayfinder until legacy user routes are retired. Admin itself does not use Inertia, but legacy user code does.

## 3. Project architecture and file classification

### User side

| Classification | Files / role |
|---|---|
| `USER_FRONTEND` | `resources/js/pages/themes/auth/**`, `Home.vue`, `user/**`, user CSS under `resources/css/user/**` and `resources/js/css/**` |
| `USER_CONTROLLER` | `app/Http/Controllers/User/**` (12 PHP files) |
| `USER_API` | JSON branches/actions in `AviatorGameController`, `WingoGameController`, `DepositController`, `ProfileController`, `PromotionController`, `RegisterController`; callbacks in `StarpagoPaymentController` |
| `USER_SERVICE` | `AviatorRoundService`; reusable controller query/build methods; `StargoHelper` |
| `USER_MODEL` | `CommissionLog`, `DailySignInClaim`, `DailyTaskClaim`, `PhoneVerification`, `ProfileImage`, `RebateHistory`, `StrongBoxTransaction`, `TeamPartnerReword`, `UserLogin`, `UserNotification` |
| `USER_MIDDLEWARE` | `Authenticate` for the `web` guard redirect contract, `RedirectIfAuthenticated`, `DeleteStatusMiddleware` |
| `USER_REQUEST` | Inline validation dominates. `WingoPlaceBetRequest.php` exists but the live user controller validates inline and does not import it. |
| `USER_HELPER` | `promotion_summary`, referral traversal helpers, `requiredTurnoverAmount`, `createUserNotification`, `toast` |
| `USER_SHARED` | layouts, partials, `useLocalToast`, `echo.ts`, shared models/services listed below |

There are no repository, action, listener, notification, mail, or dedicated frontend service/API/store directories in the current project. Business logic is concentrated in controllers, one service, helpers, models, and jobs.

### Admin side — `DO_NOT_TOUCH`

| Classification | Files / role |
|---|---|
| `ADMIN_BLADE` | 38 `*.blade.php` files under `resources/views/admin/**` |
| `ADMIN_CONTROLLER` | 17 files under `app/Http/Controllers/Admin/**` |
| `ADMIN_API` | AJAX/JSON methods in `ManageUsersController`, `WingoGameController`, `AviatorSettingsController`, and `WithdrawReportController` |
| `ADMIN_SERVICE` | shared `AviatorRoundService`; controller-local reporting/settlement methods |
| `ADMIN_MODEL` | `Admin`, `GeneralSetting`, `GameControl`, `Referral`, `UserTurnoverHistory`, `VipMonthlyClaim`, `VipMonthlyState`; plus shared models |
| `ADMIN_MIDDLEWARE` | `RedirectIfAdmin` (`admin.guest`) and `RedirectIfNotAdmin` (`admin`) |
| `ADMIN_SHARED` | `helpers.php`, shared models, `AviatorRoundService`, `WingoRules`, payment/withdrawal records |

Admin Blade has live client requests that must remain unchanged: Wingo round `fetch`, dashboard referral-settlement `fetch`, user-list jQuery AJAX, and Aviator settings `fetch`.

### Other classifications

| Classification | Files |
|---|---|
| `SHARED_CORE` | `bootstrap/app.php`, `config/auth.php`, `config/session.php`, `app/Helpers/helpers.php`, `app/Models/User.php`, shared models/services/support, `resources/css/app.css` |
| `PAYMENT_CALLBACK` | four `/api/*callback|notify` routes; `StarpagoPaymentController`; admin payout notify method |
| `WEBHOOK` | Starpago/OKPay notify and callback endpoints |
| `CRON_JOB` | `ReferralCommissionSettlementController`, public cron closures, `AviatorEngineCommand` |
| `QUEUE_JOB` | `EnsureWingoRunning`, `SettleWingoRound`; scheduled `EnsureWingoRunning` every minute |
| `SYSTEM_CONFIG` | all `config/**`, `bootstrap/**`, manifests, Vite config, migrations |

Changing `SHARED_CORE` can affect admin. In particular, changing guard defaults, session cookie/path, helpers, `User`, transaction/deposit/withdraw models, `AviatorRoundService`, or `WingoRules` is dangerous. Prefer user-only SPA controllers/resources/middleware and leave shared code unchanged.

## 4. Route loading and complete route map

`bootstrap/app.php` explicitly loads:

- `routes/api.php` at `/api`, middleware `api, maintenance`.
- `routes/admin.php` at `/tradex`, middleware `web`, namespace `Admin`, name prefix `admin.`.
- `routes/user.php` at `/user`, middleware `web, maintenance`.
- `routes/web.php` without a prefix, middleware `web, maintenance`.
- `routes/console.php` and `routes/channels.php` separately.

Runtime count from `php artisan route:list --json`: **182 routes**: 88 user/root, 75 admin, 6 `/api`, and 13 framework/public/cron routes.

Legend: `G/P/U/D` = GET/POST/PUT/DELETE; `W` = web; `M` = maintenance; `A` = authenticated user + delete-status; `AG` = admin guard. Page GETs return Inertia unless marked JSON; admin GETs return Blade unless marked JSON/AJAX.

### User page routes (all Inertia today)

| Method + current URL | Name | Controller method | Middleware | Vue component |
|---|---|---|---|---|
| G `/` | `user.home` | `HomeController@index` | W,M,auth | `Home.vue` |
| G `/user/login` | `user.login` | `Auth\\LoginController@showLoginForm` | W,M,guest | `auth/Login.vue` |
| G `/user/register` | `user.register` | `Auth\\RegisterController@showRegistrationForm` | W,M,guest | `auth/Register.vue` |
| G `/user/rpwd` | `user.rpwd` | `Auth\\LoginController@rpwd` | W,M,guest | `auth/Rpwd.vue` |
| G `/user/dev` | `user.dev` | `Auth\\LoginController@dev` | W,M,guest | **Missing `dev/HeaderGuide.vue`** |
| G `/user/activity` | `user.activity` | `ActivityController@index` | A | `user/activity/Index.vue` |
| G `/user/activity/DailyTasks` | `.activity.daily-tasks` | `ActivityController@dailyTasks` | A | `DailyTasks.vue` |
| G `/user/activity/DailyTasks/Record` | `.activity.daily-tasks.record` | `ActivityController@record` | A | `Record.vue` |
| G `/user/activity/FirstRecharge` | `.activity.first-recharge` | `ActivityController@firstRecharge` | A | `FirstRecharge.vue` |
| G `/user/main/Laundry` | `.activity.main.laundry` | `ActivityController@laundry` | A | `Laundry.vue` |
| G `/user/main/Laundry/LaundryRecord` | `.activity.main.laundry.record` | `ActivityController@laundryRecord` | A | `LaundryRecord.vue` |
| G `/user/main/SuperJackpot` | `.activity.main.super-jackpot` | `ActivityController@superJackpot` | A | `SuperJackpot.vue` |
| G `/user/main/SuperJackpot/Rule` | `.activity.main.super-jackpot.rule` | `ActivityController@superJackpotrule` | A | `SuperJackpotRule.vue` |
| G `/user/main/SuperJackpot/Star` | `.activity.main.super-jackpot.star` | `ActivityController@superJackpotstar` | A | `SuperJackpotStar.vue` |
| G `/user/main/RedeemGift` | `.activity.main.redeem-gift` | `ActivityController@redeemGift` | A | `RedeemGift.vue` |
| G `/user/main/DailySignIn` | `.activity.main.daily-sign-in` | `ActivityController@dailySignIn` | A | `DailySignIn.vue` |
| G `/user/main/DailySignIn/Rules` | `.activity.main.daily-sign-in.rules` | `ActivityController@dailySignInRules` | A | `DailySignInRules.vue` |
| G `/user/main/DailySignIn/Record` | `.activity.main.daily-sign-in.record` | `ActivityController@dailySignInRecord` | A | `DailySignInRecord.vue` |
| G `/user/turntable` | `user.turntable` | `PromotionController@turntable` | A | `promotion/Turntable.vue` |
| G `/user/promotion` | `user.promotion` | `PromotionController@index` | A | `promotion/Index.vue` |
| G `/user/promotion/Subordinate` | `.promotion.subordinate` | `PromotionController@subordinate` | A | `Subordinate.vue` |
| G `/user/promotion/PromotionRule` | `.promotion.rule` | `PromotionController@promotionRule` | A | `PromotionRule.vue` |
| G `/user/promotion/RebateRatio` | `.promotion.rebate-ratio` | `PromotionController@rebateRatio` | A | `RebateRatio.vue` |
| G `/user/main/support` | `.promotion.support` | `PromotionController@support` | A | `Support.vue` |
| G `/user/promotion/PromotionShare` | `.promotion.share` | `PromotionController@promotionShare` | A | `PromotionShare.vue` |
| G `/user/promotion/TeamReport` | `.promotion.team-report` | `PromotionController@teamReport` | A | `TeamReport.vue` |
| G `/user/promotion/MyCommission` | `.promotion.my-commission` | `PromotionController@myCommission` | A | `MyCommission.vue` |
| G `/user/promotion/TeamPartner` | `.promotion.team-partner` | `PromotionController@teamPartner` | A | `TeamPartner.vue` |
| G `/user/promotion/TeamPartner/Invitation` | `.promotion.team-partner.invitation` | `PromotionController@teamPartnerInvitation` | A | `TeamPartnerInvitation.vue` |
| G `/user/main` | `user.account` | `AccountController@index` | A | `account/Index.vue` |
| G `/user/main/BetRecords` | `.account.bet-records` | `AccountController@betRecords` | A | `GameHistory.vue` |
| G `/user/wallet` | `.account.wallet` | `AccountController@wallet` | A | `Wallet.vue` |
| G `/user/TransAction` | `.account.transaction-history` | `AccountController@transactionHistory` | A | `TransactionHistory.vue` |
| G `/user/main/Feedback` | `.account.feedback` | `AccountController@feedback` | A | `Feedback.vue` |
| G `/user/main/Notification` | `.account.notification` | `AccountController@notification` | A | `Notification.vue` |
| G `/user/main/Guide` | `.account.guide` | `AccountController@guide` | A | `Guide.vue` |
| G `/user/main/About` | `.account.about` | `AccountController@about` | A | `About.vue` |
| G `/user/main/Language` | `.account.language` | `AccountController@language` | A | `Language.vue` |
| G `/user/vip` | `.account.vip` | `AccountController@vip` | A | `Vip.vue` |
| G `/user/main/StrongBox` | `.account.strong-box` | `AccountController@strongBox` | A | `StrongBox.vue` |
| G `/user/main/StrongBox/StrongBoxRecord` | `.account.strong-box.record` | `AccountController@strongBoxRecord` | A | `strongBoxRecord.vue` |
| G `/user/home/Messages` | `.account.messages` | `AccountController@messages` | A | `Messages.vue` |
| G `/user/main/GameStats` | `.account.game-stats` | `AccountController@gameStats` | A | `GameStats.vue` |
| G `/user/main/SettingCenter` | `.profile.setting-center` | `ProfileController@index` | A | `profile/Index.vue` |
| G `/user/main/Avatar` | `.profile.avatar` | `ProfileController@avatar` | A | `Avatar.vue` |
| G `/user/main/SettingCenter/LoginPassword` | `.profile.setting-center.login-password` | `ProfileController@loginPassword` | A | `LoginPassword.vue` |
| G `/user/home/AllLotteryGames/WinGo` | `.wingo.game` | `WingoGameController@index` | A | `WinGo/Index.vue` |
| G `/user/wallet/Recharge` | `.wallet.recharge` | `DepositController@index` | A | `deposit/Deposit.vue` |
| G `/user/wallet/RechargeHistory` | `.wallet.RechargeHistory` | `DepositController@RechargeHistory` | A | `RechargeHistory.vue` |
| G `/user/wallet/Withdraw` | `.wallet.Withdraw` | `WithdrawController@index` | A | `withdraw/Index.vue` |
| G `/user/wallet/WithdrawHistory` | `.wallet.WithdrawHistory` | `WithdrawController@WithdrawHistory` | A | `WithdrawHistory.vue` |
| G `/user/wallet/welletList` | `.wallet.welletList` | `WithdrawController@welletList` | A | `WelletList.vue` |
| G `/user/wallet/addWellet` | `.wallet.addWellet` | `WithdrawController@addWellet` | A | `AddWellet.vue` |
| G `/user/aviator` | `.aviator.index` | `AviatorGameController@index` | A | `Aviator/Index.vue` |
| G `/user/aviator/history` | `.aviator.history` | `AviatorGameController@history` | A | `Aviator/History.vue` |

### User actions and existing JSON endpoints

| Method + URL(s) | Classification / response |
|---|---|
| P `/user/login`; G `/user/logout`; P `/user/register` | `AUTH`; redirects/validation/flash today |
| P `/user/register/send-otp` | `USER_API`, JSON |
| P `/user/activity/DailyTasks/claim`, `/activity/first-recharge/claim/{id}`, `/activity/laundry/claim`, `/main/RedeemGift`, `/main/DailySignIn/claim` | `USER_ACTION`; redirects/back + flash |
| P `/user/promotion/team-partner/claim` | `USER_ACTION`; back + flash |
| G `/user/promotion/subordinate-data` | `USER_API`, JSON |
| P `/user/account/vip/claim-level-up/{vipLevel}`, `/claim-monthly/{vipLevel}`, `/StrongBox/transfer-in`, `/StrongBox/transfer-out` | `USER_ACTION`; redirects/back + flash |
| D `/user/account/messages/{id}` | `USER_ACTION`; back + flash |
| G `/user/cron/strongbox-interest` | `CRON_JOB`, JSON; incorrectly behind interactive auth |
| P `/user/profile/update-name`, `/user/update/avater`, `/user/login-password/update` | `USER_ACTION`; redirects/back + flash |
| G `/user/profile/balance` | `USER_API`, JSON |
| G `/user/wingo/state`, `/result-by-period`; P `/bet`, `/verify-lookup-password` | `USER_API`, JSON |
| P `/user/wallet/recharge` | `USER_API`, JSON with payment redirect URL |
| P `/user/wallet/Withdraw`, `/welletList/select`, `/addWellet` | `USER_ACTION`; redirects/back + flash |
| G `/user/aviator/state`; P `/bet`, `/cancel`, `/cashout` | `USER_API`, JSON |
| G `/user/aviator/history` with `expectsJson()` | `USER_API` branch returning paginated JSON; otherwise Inertia page |

All authenticated user actions are under W,M,auth,delete-status. Preserve that server enforcement even when the UI guard says `meta.auth=true`.

### Admin route map (all remain normal Laravel URLs)

Every route below has `/tradex` prepended. Login uses W+`admin.guest`; protected groups use W+`admin`. Response is Blade for page GETs and redirect for forms unless marked JSON/AJAX.

| Controller | Method/path/name map |
|---|---|
| `Auth\\LoginController` | G+P `/` (`admin.login`, `.login.submit`); G `/logout` (`admin.logout`) |
| `Auth\\GiftCodeAdminController` | G `/admin/gificode`; P `/verify`, `/logout`, `/generate`; U `/update/{giftCode}`; names `admin.admin.gift.*`; notably outside admin middleware |
| `AdminController` | G `/dashboard` (`admin.dashboard`) |
| `ManageUsersController` | G `/users`, `/ajax-list` (AJAX HTML), `/details/{id}`, `/refferal/details`, `/turnover-required`; P `/store` (JSON), `/balance-update/{id}`, `/change-password/{id}`, `/status-update/{id}`, `/login-as-user/{id}`, `/turnover-required`; names `admin.users.*` |
| `GiftCodeController` | G `/gift`; P `/gift/generate`; U `/gift/update/{giftCode}`; `admin.gift.*` |
| `GameControllController` | G `/game-control`; P `/game-control/game-control`; `admin.game_control.*` |
| `WingoGameController` | G `/wingo`, `/details/{id}`, `/wingo-game/details/{type}`, `/live-data/{type}` (JSON); P `/set-manual`, `/unset-manual`, `/set-future-results`, `/set-round-result`, `/clear-future-round` (JSON); `admin.wingo.*` |
| `GeneralSettingController` | G `/general-setting`, `/password`; P `/update`, `/password/update`; `admin.general.setting.*` |
| `ReferralController` | G `/referrals`, `/status/{id}`, `/deposit-method-commissions`; P `/referrals`, `/deposit-method-commissions/update`; `admin.referrals.*` |
| `HomeBannerController` | G `/home-banners`; P `/store`, `/update/{id}`, `/delete/{id}`; `admin.home.banners.*` |
| `AnnouncementController` | G `/announcements`, `/create`, `/edit/{id}`; P `/store`, `/update/{id}`, `/status/{id}`; `admin.announcements.*` |
| `AviatorSettingsController` | G `/aviator/settings`, `/show` (JSON); P `/update` (JSON); `admin.aviator.settings.*` |
| `AviatorRoundReportController` | G `/aviator/reports`, `/{round}`; `admin.aviator.reports.*` |
| `DepositReportController` | G `/deposits`; name `admin.deposits.index.index` |
| `WithdrawReportController` | G `/withdraws/report`, `/withdraw-wallets`; P `/processing/{id}`, `/approve/{id}`, `/reject/{id}`, `/withdraw-wallets`, `/{wallet}/activate`; U `/withdraw-wallets/{wallet}`; D same; `admin.withdraws.report.*` |
| `DepositCommissionController` | G `/deposit-commissions`; P `/store`, `/update/{id}`, `/delete/{id}`, `/toggle/{id}`; `admin.deposit-commissions.*` |
| `VipAnalyticsController` | G `/vip/analytics`, `/vip-analytics/{user}`; `admin.vip.analytics.*` |

### API, public, callback, framework, cron routes

| Method + URL | Name/controller | Class/response |
|---|---|---|
| G `/api/user` | closure, `auth:sanctum` | `USER_API`, JSON |
| G `/api/current-round` | `HomeController@currentRound` | public JSON |
| P `/api/starpago/notify` | `starpago.payment.notify` | payment webhook JSON |
| P `/api/starpago/payout/notify` | `WithdrawReportController@payoutHandleNotify` | payout webhook JSON |
| P `/api/payment/deposit/callback` | `payment.deposit.callback` | payment callback |
| P `/api/okpay/payout/callback` | `okpay.payout.callback` | payout callback |
| G `/paw-download` | `user.pwa.download` | public external redirect |
| G `/maintenance` | closure | public text |
| G `/cron/referral-settlement`, `/cron/wingo`, `/cron/queue` | controller/closures | cron; currently public |
| G `/wingo-runner`, `/wingo/start` | closures | dispatch jobs; currently public |
| G `/test-broadcast`, `/fire` | closures | diagnostics; currently public |
| GET/POST `/broadcasting/auth` | framework controller | broadcast auth |
| G `/sanctum/csrf-cookie` | Sanctum | CSRF bootstrap |
| G+U `/storage/{path}` | framework closure | storage serve/upload |
| CLI schedule | `EnsureWingoRunning` every minute | queue job |

`routes/admin.php.bak` is not loaded. It is review-only, not a live route file.

## 5. User dependency and data-flow map

### Backend tree

```text
User Vue page
  -> Inertia page GET today / JSON page-data endpoint after migration
  -> User controller (currently presentation + business logic mixed)
  -> AviatorRoundService / StargoHelper / helpers / controller-private builders
  -> Eloquent models and DB transactions
  -> database
```

Feature map:

| Feature | Controller/service/helper | Models/tables |
|---|---|---|
| Authentication/login | Auth `LoginController`, `ClientInfo`, `createUserNotification` | User/users, UserLogin/user_logins, UserNotification/user_notifications, sessions |
| Registration/OTP/referral | Auth `RegisterController`, `ClientInfo`, `gs` | User, PhoneVerification, Transaction; users, phone_verifications, transactions |
| Home | `HomeController` | User, HomeBanner, Announcement, DepositCommission, Transaction, VipLevel/History, GameRound; users/banners/announcements/etc. |
| Wallet/transactions | `AccountController` | User, Deposit, Withdraw, Transaction, Bet |
| Deposit/payment | `DepositController`, `StarpagoPaymentController`, `StargoHelper`, commission/notification helpers | Deposit, Transaction, User, Withdraw |
| Withdrawal/wallets | `WithdrawController`; payout processing shared with admin | Withdraw, WithdrawWallet, User, Deposit, Transaction |
| Profile | `ProfileController` | User, ProfileImage |
| Referral/promotion | `PromotionController`, promotion helpers | User, Bet, AviatorBet, Deposit, CommissionLog, Transaction, TeamPartnerReword |
| Rewards/activity | `ActivityController` | GiftCode/Redemption, Deposit, DailyTaskClaim, DailySignInClaim, Bet, RebateHistory, Transaction, DepositCommission, User |
| VIP | `AccountController` | User, VipLevel, VipRewardHistory; direct `bets` and `aviator_bets` aggregates |
| Notifications | `AccountController`, helper | UserNotification |
| Wingo/orders | `WingoGameController`, `WingoRules`, Wingo jobs/events | Bet, GameRound, Deposit, Transaction, User |
| Aviator/orders | `AviatorGameController` -> `AviatorRoundService` | AviatorBet, AviatorRound, AviatorSetting, User, Transaction |
| Support | `PromotionController@support` | global settings only; the visible upload controls have no submission endpoint |

No database migration is required for hash routing.

### Shared dependencies and admin impact

| Shared dependency | Consumers | Label / instruction |
|---|---|---|
| `User`, `Bet`, `Deposit`, `Transaction`, `Withdraw` | user and admin reports/actions | `DANGEROUS_TO_MODIFY`; keep unchanged |
| `Announcement`, `HomeBanner`, `DepositCommission` | user display; admin CRUD | `SAFE_UNCHANGED`; expose through user-only resource/controller |
| `GiftCode`, `GiftCodeRedemption` | user redemption; admin generation/reporting | `DANGEROUS_TO_MODIFY` |
| `GameRound`, `AviatorBet`, `VipLevel`, `VipRewardHistory`, `WithdrawWallet` | both panels | `DANGEROUS_TO_MODIFY` |
| `AviatorRoundService` | user gameplay and admin settings | `DANGEROUS_TO_MODIFY`; invoke from a user API controller unchanged |
| `WingoRules` | user bet and admin settlement/control | `DANGEROUS_TO_MODIFY` |
| `helpers.php` (`gs`, commission, turnover, notifications) | both panels and callbacks | `DANGEROUS_TO_MODIFY`; do not alter response/navigation behavior here |
| `MaintenanceMode` | user, admin API prefix, public web | `REQUIRES_USER_ONLY_WRAPPER` if SPA-specific JSON behavior is needed |
| `Authenticate`, `RedirectIfAuthenticated`, `DeleteStatusMiddleware` | user routes | `REQUIRES_USER_ONLY_WRAPPER`/content negotiation for JSON; do not change admin middleware |
| `config/auth.php`, `config/session.php`, `bootstrap/app.php` | whole app | `DANGEROUS_TO_MODIFY` |

## 6. User Vue inventory, themes, and reuse

Counts:

- 74 Vue SFCs under `resources/js/pages/themes` total.
- 62 non-layout/non-partial SFCs in the page tree. This count includes six nested feature components (`AviatorPlaneCanvas`, `GameRulesModal`, `Header`, `LiveBetUsers`, `AvatarImage`, `UserName`) and `account/Css.vue`.
- 55 unique controller-referenced Inertia component names, of which 54 exist. `dev/HeaderGuide.vue` is missing.
- `Welcome.vue` exists but is not returned by any live route.
- 42 rendered pages directly use `defineProps` and/or `usePage` data.
- 6 rendered pages contain Axios/fetch behavior: Register, Aviator Index, Aviator History, Deposit, Subordinate, and Wingo. These are **mixed/API-assisted**, not fully independent of Inertia in every case.

Reuse classification:

| Class | Pages/components |
|---|---|
| `DIRECTLY_REUSABLE` | static About, Feedback, Notification, Language, Super Jackpot/Rule/Star, PromotionRule, RebateRatio; most presentational child components |
| `REUSABLE_AFTER_ROUTER_LINK_CHANGE` | shared headers/tabbar/layouts and static pages using `route()`/back links |
| `REUSABLE_AFTER_INERTIA_PROPS_TO_API` | Home; Account Index/Guide/Wallet/GameHistory/Transaction/VIP/StrongBox/records/Messages/GameStats; all data-backed Activity pages; Profile Index/Avatar; most Promotion pages; Withdraw pages; Recharge History |
| `REUSABLE_AFTER_USEPAGE_REPLACEMENT` | Daily tasks/sign-in/records, RedeemGift, Deposit, profile forms, Wingo, withdrawal pages, BaseLayout toast |
| `REQUIRES_MAJOR_CHANGE` | Login/Register form contracts, BaseLayout Inertia progress/flash handling, heavily stateful Wingo and Aviator shells (UI can remain), pagination/filter pages |
| `DO_NOT_USE` | missing `dev/HeaderGuide`; unreferenced `Welcome.vue` and `account/Css.vue` unless separately justified |

Preserve all templates, classes, scoped styles, imported user CSS, SVG sprite, assets, responsive sizing, animations, Echo behavior, and layouts. Only replace routing/data/form plumbing.

## 7. Inertia dependency inventory

Frontend file counts:

- 51 files import `@inertiajs/vue3` (including app and SSR entries).
- 22 Vue files call `usePage()`.
- 8 Vue files call `useForm()`.
- 31 Vue files use `router.visit/get/post/delete/...`.
- 54 Vue files call Ziggy's global `route()`.
- `<Link>` appears in 2 files; `<Head>` in 1.

How dependencies work now:

- `createInertiaApp` turns controller component names into SFCs.
- `usePage` reads per-page props and global auth/flash props.
- `defineProps` receives controller-provided page props.
- `useForm` sends Inertia form requests and maps Laravel 422 errors.
- `router.visit/get/post/delete/reload` performs Inertia navigation/actions, preserves state/scroll, follows server redirects, and refreshes props.
- Ziggy `route()` translates Laravel route names to normal URLs; `@routes` injects the route table.
- BaseLayout listens to Inertia lifecycle events and translates shared session `flash.toast` into a local toast.
- Back-end `redirect()->route`, `to_route`, and `back()` cause Inertia to follow redirects and refresh page props. These are unsuitable as the primary SPA action contract.

Target replacements:

- Inertia `router.visit` -> Vue Router `push/replace` for pages.
- `route()` stays for real Laravel JSON/action URLs during migration; never use it to generate hash fragments.
- `usePage` page props -> feature composable/reactive page-data loaded from JSON.
- shared `auth.user` -> `/api/user` or a dedicated `/user/spa/session` JSON endpoint.
- shared flash -> JSON `{message,type,errors,redirect}` plus `useLocalToast`; retain session flash only for legacy routes.
- `useForm` -> a small Axios form wrapper that preserves `processing`, field errors, reset, and success callbacks.
- Inertia progress events -> Vue Router guards plus Axios interceptors or a shared loading state.

## 8. Authentication and security architecture

### User

- Default `web` guard: session driver, `App\\Models\\User` provider.
- Login explicitly calls `Auth::login`, regenerates the session, logs `UserLogin`, creates a notification, and redirects to Wingo.
- Logout calls `Auth::guard('web')->logout()`, invalidates session, regenerates CSRF token.
- Session defaults to database storage, cookie path `/`, HTTP-only, SameSite `lax`.
- CSRF exclusions are empty. The SPA shell's CSRF meta or Sanctum `/sanctum/csrf-cookie` plus same-origin cookies must be used.
- Sanctum is installed and configured statefully against the `web` guard, but only `/api/user` currently uses `auth:sanctum`. JWT/custom token auth is absent.
- `DeleteStatusMiddleware` logs out status != 1 users.

Safest design: retain the existing first-party `web` session and CSRF cookie. Do not introduce JWT. Put SPA JSON endpoints under `web, maintenance, auth, delete.status` (or correctly configured stateful Sanctum) and make unauthenticated JSON requests return 401, blocked accounts 403/419-style machine-readable responses, and maintenance 503 JSON. Frontend guards improve UX but are not security boundaries.

### Admin

- Independent `admin` session guard and `Admin` provider.
- `admin.guest` redirects authenticated admins to dashboard; `admin` redirects guests to admin login.
- Admin login/logout explicitly use the admin guard.
- `ManageUsersController@loginAsUser` intentionally logs a selected user into the separate `web` guard and redirects to `/`. After migration it should eventually redirect to `/#/home` or the SPA shell, but the admin guard and admin page remain untouched.

Both guards can safely coexist in the same Laravel session because their keys/providers are distinct.

No email verification, conventional password-reset token route, social/OAuth callback, signed URL, mail class, or notification class was found. `Rpwd.vue` is only a UI placeholder and posts to the registration route; password reset is not implemented. This must be recorded as a pre-existing feature gap, not silently invented during routing migration.

## 9. Existing and required JSON APIs

Existing user-facing JSON contracts to reuse unchanged initially:

1. `GET /api/user`
2. `GET /api/current-round`
3. `POST /user/register/send-otp`
4. `GET /user/profile/balance`
5. `GET /user/promotion/subordinate-data`
6. `GET /user/wingo/state`
7. `GET /user/wingo/result-by-period`
8. `POST /user/wingo/verify-lookup-password`
9. `POST /user/wingo/bet`
10. `GET /user/aviator/state`
11. `GET /user/aviator/history` when JSON is expected
12. `POST /user/aviator/bet`
13. `POST /user/aviator/cancel`
14. `POST /user/aviator/cashout`
15. `POST /user/wallet/recharge`

Callbacks are not page APIs and must remain normal server URLs.

Required read contracts: **41 new JSON page-data endpoints** under the conservative one-contract-per-dynamic-page plan. This number is the 42 currently rendered prop-backed pages minus Aviator History, which already has a JSON branch. It deliberately counts a contract even where the payload is small (`pageTitle`, referral code) so migration parity is auditable. Implementation may consolidate related contracts into feature bootstrap resources, but consolidation must not hide authorization or over-fetch unrelated sensitive data.

Recommended namespaces, reusing current controller query/build logic through extracted user-only query/resource classes rather than duplicating it:

- `/user/spa/session`, `/user/spa/home`
- `/user/spa/account`, `/wallet`, `/orders`, `/transactions`, `/vip`, `/strong-box`, `/strong-box/records`, `/messages`, `/game-stats`, `/guide`
- `/user/spa/activity`, `/daily-tasks`, `/daily-task-records`, `/first-recharge`, `/laundry`, `/laundry/records`, `/redeem-gift`, `/daily-sign-in`, `/daily-sign-in/rules`, `/daily-sign-in/records`
- `/user/spa/profile`, `/profile/avatars`
- `/user/spa/promotion`, `/share`, `/team-report`, `/commission`, `/team-partner`, `/team-partner/history`, `/support`, `/turntable`
- `/user/spa/deposit`, `/deposit/history`, `/withdraw`, `/withdraw/history`, `/withdraw/wallets`, `/withdraw/wallet-options`
- `/user/spa/wingo/bootstrap`, `/aviator/bootstrap`
- guest bootstrap endpoints for login/register metadata/referral capture

Response shape should be stable: `{ data, meta?, links? }`; action success: `{ message, redirect?, data? }`; validation: HTTP 422 `{ message, errors }`; auth: 401; blocked: 403; maintenance: 503.

## 10. Form and redirect migration map

| Feature/actions | Current behavior | SPA contract |
|---|---|---|
| Login/register/logout | Inertia form + redirect + flash | Same controller business steps; return JSON; frontend `router.replace('/home' or '/games/wingo')`; logout POST is recommended instead of current GET |
| OTP/deposit/Wingo/Aviator | Already Axios/JSON | Reuse; normalize error envelope only in a user-only wrapper if needed |
| Profile name/avatar/password | `useForm`, redirect/back | Axios POST, retain validation/Hash/auth, return updated resource/message |
| Daily tasks/recharge/laundry/gift/sign-in/team reward/VIP claims | Inertia post/back/redirect + flash | Axios POST, return updated summary/eligibility and message; refresh only relevant endpoint |
| Strong box in/out | Inertia post + redirect | Axios transaction, return updated balances/summary |
| Message delete | Inertia DELETE + back | Axios DELETE, remove item locally or refetch messages |
| Withdrawal/add/select wallet | Inertia POST + redirects | Axios POST with existing validation/transactions; return wallet/withdraw resource and explicit destination |
| Filter/pagination pages | Inertia `router.get` with query | Axios GET with query; Vue Router keeps filter state in hash query; backend returns paginator data/links |

Back-end redirects requiring conversion include login/register, logout, status middleware, profile updates, reward claims, strong-box transfers, withdrawal submission/wallet selection, and all `return back()` failures. Preserve legacy redirects for old routes; add JSON content negotiation or new SPA action controllers. Do not globally change helper `toast()` or shared redirect behavior.

## 11. Proposed Vue Router map

All URLs are shown after the origin; Vue Router paths are the fragment portion. `auth` means `meta: { auth: true }`; `guest` means `meta: { guest: true }`. Static pages need session guard checks but no page-data API.

| Target | Existing Vue component | Guard/data |
|---|---|---|
| `/#/login` | `auth/Login.vue` | guest; guest bootstrap; login action |
| `/#/register` | `auth/Register.vue` | guest; referral/registration bootstrap; OTP/register actions |
| `/#/reset-password` | `auth/Rpwd.vue` | guest; **blocked by missing backend feature** |
| `/#/home` | `Home.vue` | auth; home API |
| `/#/activity` | `activity/Index.vue` | auth; activity API |
| `/#/activity/daily-tasks`, `/records` | `DailyTasks.vue`, `Record.vue` | auth; task APIs |
| `/#/activity/first-recharge` | `FirstRecharge.vue` | auth; first-recharge API |
| `/#/activity/laundry`, `/records` | `Laundry.vue`, `LaundryRecord.vue` | auth; laundry APIs |
| `/#/activity/super-jackpot`, `/rules`, `/stars` | three SuperJackpot SFCs | auth; static |
| `/#/activity/redeem-gift` | `RedeemGift.vue` | auth; gift API/action |
| `/#/activity/daily-sign-in`, `/rules`, `/records` | three DailySignIn SFCs | auth; sign-in APIs |
| `/#/promotion` | `promotion/Index.vue` | auth; promotion API |
| `/#/promotion/subordinates` | `Subordinate.vue` | auth; existing JSON |
| `/#/promotion/rules`, `/rebate-ratio` | rule/ratio SFCs | auth; static |
| `/#/support` | `Support.vue` | auth; support settings API |
| `/#/promotion/share` | `PromotionShare.vue` | auth; share API |
| `/#/promotion/team-report` | `TeamReport.vue` | auth; report API |
| `/#/promotion/commission` | `MyCommission.vue` | auth; commission API |
| `/#/promotion/team-partner`, `/history` | TeamPartner SFCs | auth; partner APIs |
| `/#/rewards/turntable` | `Turntable.vue` | auth; turntable API |
| `/#/account` | `account/Index.vue` | auth; account API |
| `/#/orders` | `account/GameHistory.vue` | auth; bet/order API; no separate Order model exists |
| `/#/transactions` | `TransactionHistory.vue` | auth; transactions API |
| `/#/wallet` | `Wallet.vue` | auth; wallet API |
| `/#/wallet/recharge`, `/history` | Deposit SFCs | auth; deposit APIs/actions |
| `/#/wallet/withdraw`, `/history` | Withdraw SFCs | auth; withdraw APIs/actions |
| `/#/wallet/withdraw/accounts`, `/add` | wallet list/add SFCs | auth; wallet-account APIs/actions |
| `/#/vip` | `Vip.vue` | auth; VIP API/actions |
| `/#/strong-box`, `/records` | StrongBox SFCs | auth; strong-box APIs/actions |
| `/#/notifications` | `Messages.vue` | auth; messages API/action |
| `/#/game-stats` | `GameStats.vue` | auth; stats API |
| `/#/feedback`, `/announcements`, `/guide`, `/about`, `/language` | existing Account SFCs | auth; guide has API, others static |
| `/#/profile` | `profile/Index.vue` | auth; profile API |
| `/#/profile/avatar` | `Avatar.vue` | auth; avatars API/action |
| `/#/profile/password` | `LoginPassword.vue` | auth; password action |
| `/#/games/wingo` | `WinGo/Index.vue` | auth; Wingo bootstrap + existing live APIs/Echo |
| `/#/games/aviator` | `Aviator/Index.vue` | auth; Aviator bootstrap + existing live APIs/Echo |
| `/#/games/aviator/history` | `Aviator/History.vue` | auth; existing JSON branch |
| `/#/:pathMatch(.*)*` | small not-found view | public; redirect based on session if desired |

Route guard sequence:

1. Load/cached session from server, not localStorage.
2. If maintenance response, show a maintenance route/view; APIs still enforce maintenance.
3. `meta.auth` + no user -> `/login`; `meta.guest` + user -> `/home` (or preserve current Wingo landing if product requires exact parity).
4. Status/verification flags from the server may select restricted views; APIs retain middleware/authorization.
5. Handle Axios 401 globally by clearing client session and replacing with `/login`; 419 refreshes CSRF once; 403/503 shows the server message.

## 12. Hash behavior, callbacks, deep links, and parallel migration

For `https://example.com/#/wallet/recharge`, Laravel receives only `GET /`. Therefore:

- `/` must eventually serve the SPA shell for guests and authenticated users. It cannot retain `middleware('auth')` if `/#/login` and `/#/register` are to load for guests.
- Vue Router reads `/wallet/recharge` from the fragment.
- API/callback requests must use real paths before the fragment.
- Server-generated email/signed/OAuth links cannot rely on fragments for server validation. None are implemented now, but future links should land on a normal Laravel validation/callback route and then redirect to `/#/...`.
- Payment provider URLs, Starpago/OKPay callbacks, payout callbacks, broadcasting auth, storage URLs, cron, queue, PWA download, and external deposit redirect URLs remain non-hash routes.

Safest coexistence: add an authenticated/guest-capable temporary shell at `/spa` producing `/spa#/...` while `/` and `/user/*` keep serving Inertia. Convert page-by-page. Because the fragment is not sent, `/spa#/login` always reaches Laravel `/spa`. When parity is complete, move the shell to `/` and add compatibility redirects from old user page GETs to corresponding `/#/...`. Do not redirect actions, callbacks, JSON, cron, storage, or admin URLs.

## 13. Exact file-change plan

### Files to create

| Path | Role/change | Admin impact | Risk |
|---|---|---|---|
| `resources/js/user-spa.ts` | isolated SPA boot with Vue Router | none | low |
| `resources/js/user/router.ts` | `createWebHashHistory()` map/guards | none | medium |
| `resources/js/user/App.vue` | RouterView/root providers | none | low |
| `resources/js/user/http.ts` | same-origin Axios/CSRF/error handling | none | medium |
| `resources/js/user/session.ts` | reactive server-backed user session | none | medium |
| `resources/js/user/useApiForm.ts` | useForm-compatible adapter | none | medium |
| `resources/views/user-spa.blade.php` | standalone SPA shell | none | low |
| `app/Http/Controllers/User/Spa/**` | user-only page-data/action adapters | none if shared services are only called | medium/high |
| `app/Http/Resources/User/**` | stable JSON serialization | none | medium |
| `routes/user-spa.php` | temporary shell + user SPA endpoints | none when loaded separately | medium |
| user SPA feature tests | auth, JSON, authorization, response schemas | none | low |

### Files to change after approval

| Path | Proposed change | Why | Admin impact/risk |
|---|---|---|---|
| `vite.config.ts` | add SPA entry; keep Inertia entry | parallel build | low |
| `bootstrap/app.php` | load isolated `user-spa.php`; later switch `/` shell | route registration | medium; verify admin prefix/middleware unchanged |
| selected existing user SFCs/layouts | replace Inertia navigation/props/forms incrementally | reuse UI | no admin impact |
| `HomeController` and user controllers only if chosen | extract query builders or add JSON content negotiation | avoid duplicate business rules | medium; do not alter shared model behavior |
| `routes/web.php`, `routes/user.php` only at final cutover | shell and compatibility redirects; preserve actions | hash entry | high if done early |
| `resources/views/app.blade.php` only at retirement | stop being user Inertia root, or leave legacy | cleanup | no admin impact |
| `package.json`/lock | install Vue Router only after approval | dependency | low |

### Keep unchanged

- `app/Http/Controllers/Admin/**`
- `resources/views/admin/**`
- `routes/admin.php`
- `app/Http/Middleware/RedirectIfAdmin.php`, `RedirectIfNotAdmin.php`
- `config/auth.php` admin guard/provider
- all migrations/database schema
- payment callback/notify methods and URLs
- jobs, events, console schedule, broadcast channels
- shared models, helpers, `AviatorRoundService`, `WingoRules` unless a separately reviewed business bug requires a change
- admin AJAX route names and `/tradex` prefix

### Review only

- `routes/admin.php.bak`, generated `resources/js/actions/**`, `resources/js/routes/**`, `resources/js/wayfinder/**`
- `resources/js/ssr.ts` until Inertia retirement
- `WingoPlaceBetRequest.php` (currently unused)
- public/debug/cron routes, missing `dev/HeaderGuide`, placeholder password reset, and support upload UI; these are pre-existing issues outside hash-routing implementation

## 14. Explicit admin do-not-touch list

1. `app/Http/Controllers/Admin/**`
2. `resources/views/admin/**`
3. `routes/admin.php`
4. `/tradex` prefix, `admin.*` route names, Blade forms, and admin AJAX endpoints
5. `RedirectIfAdmin`, `RedirectIfNotAdmin`, the `admin` guard/provider, Admin model, and admin session behavior
6. Admin-facing CSS/JS inside Blade layouts and views
7. Shared report/business records: User, Bet, Deposit, Withdraw, Transaction, game/VIP models
8. `AviatorRoundService`, `WingoRules`, payout handling, callbacks, and admin-controlled settings
9. Admin “login as user” semantics; only its final destination may be updated after SPA cutover and admin regression testing

## 15. Risks and findings

| Risk | Severity | Mitigation |
|---|---|---|
| 42 prop-backed pages and large controllers mix presentation/query/business logic | high | user-only JSON adapters/resources; extract reusable query builders with parity tests |
| Global `web` middleware appends Inertia middleware, including admin | medium | leave it during coexistence; a plain SPA Blade still works; remove only after proving no Inertia consumers |
| `/` currently requires auth, incompatible with `/#/login` | high at cutover | temporary `/spa` shell first; later make `/` shell public and secure APIs server-side |
| Redirect/flash form contracts | high | JSON content negotiation/new SPA actions; preserve legacy route behavior |
| Wingo/Aviator realtime state and Echo private channels | high | keep existing APIs/channels/service; test reconnect, 401, duplicate subscriptions, tab suspension |
| Missing `dev/HeaderGuide.vue` | medium | remove/disable legacy dev route only in a separately approved cleanup |
| Password reset UI has no reset backend and appears wired to registration | high feature gap | do not claim parity; design proper reset separately |
| Support UI contains upload visuals but no upload endpoint | medium feature gap | do not invent during router work |
| Public cron/job/debug URLs and gift-code admin routes outside admin middleware | critical pre-existing security concern | separate security remediation; do not mix with migration |
| Payment callbacks sit behind maintenance middleware | high operational risk | preserve now; separately verify providers can callback during maintenance |
| Hard-coded Wingo lookup password in controller | high security concern | separate remediation |
| Route naming anomalies (`user.`, generated names, duplicated admin prefixes) | medium | call action URLs through existing names where valid; do not rename during migration |
| External deposit redirects and QR URL | medium | use full server URLs, never hash paths; validate callback return flow |
| `strongbox-interest` exposed as authenticated GET and separate public cron routes | medium | preserve during migration; move to scheduler/security project later |

## 16. Recommended implementation phases

### Phase 0 — contract tests and safety baseline

Snapshot 182 routes, admin Blade smoke tests, current user response payloads, auth/CSRF behavior, callbacks, and realtime events. Record the pre-existing missing reset/dev/support behavior.

### Phase 1 — parallel shell

Install Vue Router only after approval. Add `/spa` shell, separate entry, router, session endpoint, CSRF/error handling, and shared layouts without deleting/changing legacy Inertia routes.

### Phase 2 — authentication

Convert login/register/logout/OTP. Retain the `web` session guard. Use `/spa#/login` and `/spa#/register`. Verify admin guard and admin login-as-user.

### Phase 3 — shell, Home, navigation

Convert BaseLayout/AppHeader/BottomTabbar, Home, shared toast/loading, account session and balance. Preserve all styling/assets.

### Phase 4 — Account/Profile/Wallet

Convert wallet, deposit, withdrawal, transaction/order history, profile, avatars/password, messages, strong box, VIP. Payment redirects/callbacks remain server URLs.

### Phase 5 — Activity/Promotion/Games

Convert rewards/referrals/support, then Wingo and Aviator last because of realtime and transactional risk.

### Phase 6 — cutover

After parity and admin regression testing, serve the SPA shell at `/`, map old user page GETs to hash compatibility redirects, retain action/JSON/callback routes, then remove obsolete user Inertia rendering. Keep Inertia packages until no imports/renders remain; removal is optional and separate.

## 17. Test checklist

User functional: login by phone/email, invalid credentials, blocked account, logout, registration/referral/OTP, session renewal/expiry, CSRF 419, direct hash refresh, browser back/forward, unknown hash, Home/announcements, Profile/avatar/password, Wallet/balance, Deposit/provider redirect/history/callback credit, Withdrawal/wallet selection/limits/turnover/history, Transactions, Orders/bet history, Referral/share/team reports/commission, rewards/daily tasks/sign-in/redeem/laundry/first recharge/VIP, messages/delete, strong box, support, Wingo bets/state/result/Echo, Aviator bet/cancel/cashout/history/private Echo, responsive/mobile/landscape, CSS/SVG/icons/audio/animations.

Admin regression: `/tradex` login/logout, dashboard/stats/cron fetch, users/list AJAX/create/details/balance/password/status/login-as-user/turnover, deposits, withdrawals/payout actions/wallet CRUD, settings/password, banners, announcements, referrals/commissions, gifts, Wingo controls/live JSON, Aviator settings/reports, VIP analytics, permissions/guard/session/CSRF, Blade layout/assets.

System: Starpago/OKPay deposit and payout callbacks with signatures/retries/idempotency, broadcast auth, storage, queue jobs, scheduler, maintenance responses, PWA download, external redirects, no database schema change, and old/new user panels operating side-by-side.

## 18. Mandatory final answers

1. **Can User become hash SPA without converting Admin Blade?** Yes. User and admin already have separate route groups, rendering systems, and guards.
2. **Can the existing User Vue UI be reused?** Yes. Maximum reuse is practical; templates/styles/assets stay, while navigation, props, forms, and shared flash/loading are adapted.
3. **How many User Vue pages were found?** 62 non-layout/non-partial SFCs; 55 controller-referenced component names, 54 of which exist.
4. **How many User Controllers were found?** 12.
5. **How many Admin Controllers were found?** 17.
6. **How many Admin Blade views were found?** 38.
7. **How many User pages depend directly on Inertia props?** 42 rendered pages use `defineProps` and/or `usePage` data.
8. **How many User pages are already API-driven?** 6 rendered pages are Axios/fetch-assisted; none of those should be assumed wholly independent of Inertia without the page-by-page notes above.
9. **How many new JSON APIs are required?** 41 page-data contracts under the conservative parity plan; related endpoints may later be consolidated without duplicating business logic.
10. **Which shared Services/Models/Middleware are used by both?** `AviatorRoundService`, `WingoRules`, helpers; User, Bet, Deposit, Transaction, Withdraw, Announcement, HomeBanner, DepositCommission, GiftCode/Redemption, GameRound, AviatorBet, VipLevel/History, WithdrawWallet; global web/session/maintenance/bootstrap configuration affects both.
11. **Which files MUST NOT be changed?** Admin controllers/views/routes/auth middleware/guard, shared business models/services/helpers, callbacks, jobs/events/schedule, and database migrations—see sections 13–14.
12. **Safest first implementation step?** Freeze contract/admin smoke tests, then add an isolated `/spa` shell and Vue Router entry alongside legacy Inertia; do not change `/` yet.
13. **Migration complexity:** HIGH.
14. **Final verdict:** **SAFE TO START HASH SPA MIGRATION**.

