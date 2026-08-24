<?php

use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\Spa\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('spa/session', [SessionController::class, 'show'])->name('user.spa.session');

Route::middleware('auth')->group(function () {
    Route::post('spa/logout', [SessionController::class, 'destroy'])->name('user.spa.logout');
});

Route::middleware(['auth', 'delete.status'])->group(function () {
    Route::get('spa/home', [HomeController::class, 'spa'])->name('user.spa.home');
});

Route::namespace('User\Auth')->name('user.')->middleware('guest')->group(function () {
    
    Route::controller('LoginController')->group(function () {
        Route::get('login', 'showLoginForm')->name('login');
        Route::get('rpwd', 'rpwd')->name('rpwd');
        Route::get('dev', 'dev')->name('dev');

        Route::post('login', 'login')->name('login.submit');
        Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
    });

    Route::controller('RegisterController')->group(function () {
        Route::get('register', 'showRegistrationForm')->name('register');
        Route::post('register', 'register')->name('register.submit');
        
                Route::post('register/send-otp',  'sendSmsOtp')->name('register.sendOtp');
    });
});

Route::middleware('auth', 'delete.status')->name('user.')->group(function () {
    Route::namespace('User')->group(function () {

        Route::controller('ActivityController')->group(function () {
            Route::get('activity', 'index')->name('activity');
            Route::get('activity/DailyTasks', 'dailyTasks')->name('activity.daily-tasks');
            Route::post('activity/DailyTasks/claim', 'dailyTasksClaim')->name('activity.daily-tasks.claim');
            Route::get('activity/DailyTasks/Record', 'record')->name('activity.daily-tasks.record');
            Route::get('main/Laundry', 'laundry')->name('activity.main.laundry');
            Route::post('activity/laundry/claim', 'claimLaundryRebate')->name('activity.main.laundry.claim');

            Route::get('main/Laundry/LaundryRecord', 'laundryRecord')->name('activity.main.laundry.record');
            Route::get('main/SuperJackpot', 'superJackpot')->name('activity.main.super-jackpot');
            Route::get('main/SuperJackpot/Rule', 'superJackpotrule')->name('activity.main.super-jackpot.rule');
            Route::get('main/SuperJackpot/Star', 'superJackpotstar')->name('activity.main.super-jackpot.star');

            Route::get('main/RedeemGift', 'redeemGift')->name('activity.main.redeem-gift');
            Route::post('main/RedeemGift', 'redeemGiftSubmit')->name('activity.main.redeem-gift.submit');

            Route::get('main/DailySignIn', 'dailySignIn')->name('activity.main.daily-sign-in');
            Route::post('main/DailySignIn/claim', 'dailySignInClaim')->name('main.daily-sign-in.claim');
            Route::get('main/DailySignIn/Rules', 'dailySignInRules')->name('activity.main.daily-sign-in.rules');
            Route::get('main/DailySignIn/Record', 'dailySignInRecord')->name('activity.main.daily-sign-in.record');



            Route::get('activity/FirstRecharge', 'firstRecharge')->name('activity.first-recharge');
            Route::post('activity/first-recharge/claim/{id}', 'claimFirstRecharge')->name('activity.firstRecharge.claim');
        });

        Route::controller('PromotionController')->group(function () {
            Route::get('turntable', 'turntable')->name('turntable');
            Route::get('promotion', 'index')->name('promotion');
            Route::get('promotion/Subordinate', 'subordinate')->name('promotion.subordinate');
            Route::get('promotion/subordinate-data', 'subordinateData')->name('promotion.subordinate.data');
            Route::get('promotion/PromotionRule', 'promotionRule')->name('promotion.rule');
            Route::get('promotion/RebateRatio', 'rebateRatio')->name('promotion.rebate-ratio');
            Route::get('main/support', 'support')->name('promotion.support');


            Route::get('promotion/PromotionShare', 'promotionShare')->name('promotion.share');
            Route::get('promotion/TeamReport', 'teamReport')->name('promotion.team-report');
            Route::get('promotion/MyCommission', 'myCommission')->name('promotion.my-commission');
            
            
            
            Route::get('promotion/TeamPartner', 'teamPartner')->name('promotion.team-partner');
            Route::get('promotion/TeamPartner/Invitation', 'teamPartnerInvitation')->name('promotion.team-partner.invitation');
            Route::post('promotion/team-partner/claim','claimTeamPartnerReward')->name('team.partner.claim');
            
        });

        Route::controller('AccountController')->group(function () {
            Route::get('main', 'index')->name('account');
            Route::get('main/BetRecords', 'betRecords')->name('account.bet-records');
            Route::get('wallet', 'wallet')->name('account.wallet');
            Route::get('TransAction', 'transactionHistory')->name('account.transaction-history');
            
            Route::get('main/Feedback', 'feedback')->name('account.feedback');
            Route::get('main/Notification', 'notification')->name('account.notification');
            Route::get('main/Guide', 'guide')->name('account.guide');
            Route::get('main/About', 'about')->name('account.about');
            Route::get('main/Language', 'language')->name('account.language');

            Route::get('vip', 'vip')->name('account.vip');
            Route::post('account/vip/claim-level-up/{vipLevel}',  'claimVipLevelUp')->name('vip.claim-level-up');
            Route::post('account/vip/claim-monthly/{vipLevel}', 'claimVipMonthly')->name('vip.claim-monthly');
            
            
            Route::get('main/StrongBox', 'strongBox')->name('account.strong-box');
            Route::get('main/StrongBox/StrongBoxRecord', 'strongBoxRecord')->name('account.strong-box.record');
            Route::post('StrongBox/transfer-in', 'transferIn')->name('account.strong-box.transfer-in');
            Route::post('StrongBox/transfer-out', 'transferOut')->name('account.strong-box.transfer-out');
            Route::get('cron/strongbox-interest','runStrongBoxInterest')->name('cron.strongbox-interest');

            Route::get('home/Messages', 'messages')->name('account.messages');
            Route::get('main/GameStats', 'gameStats')->name('account.game-stats');
            Route::delete('account/messages/{id}', 'deleteMessage')->name('messages.delete');
        });
        Route::controller('ProfileController')->group(function () {
            Route::get('main/SettingCenter', 'index')->name('profile.setting-center');
            Route::post('profile/update-name', 'updateName')->name('profile.update-name');
            Route::get('profile/balance','balance')->name('profile.balance');

            Route::get('main/Avatar', 'avatar')->name('profile.avatar');
            Route::post('update/avater', 'avaterUpdate')->name('profile.avatar.update');
            Route::get('main/SettingCenter/LoginPassword', 'loginPassword')->name('profile.setting-center.login-password');
              Route::post('login-password/update', 'updateLoginPassword')->name('login-password.update');
        });
        Route::controller('WingoGameController')->group(function () {
            Route::get('home/AllLotteryGames/WinGo', 'index')->name('wingo.game');
            Route::get('/wingo/state',  'state'); 
            Route::post('/wingo/bet', 'placeBet'); 
            
                        Route::post('wingo/verify-lookup-password', 'verifyLookupPassword');
            Route::get('wingo/result-by-period', 'resultByPeriod');
        });
        Route::controller('DepositController')->prefix('wallet')->name('wallet.')->group(function () {
            Route::get('Recharge', 'index')->name('recharge');
            Route::get('RechargeHistory',  'RechargeHistory')->name('RechargeHistory'); 
            Route::post('recharge', 'store')->name('recharge.store');
        });
        Route::controller('WithdrawController')->prefix('wallet')->name('wallet.')->group(function () {
            Route::get('Withdraw', 'index')->name('Withdraw');
            Route::post('Withdraw', 'store')->name('Withdraw.store');

            Route::get('WithdrawHistory', 'WithdrawHistory')->name('WithdrawHistory');
            Route::get('welletList', 'welletList')->name('welletList');
            Route::post('welletList/select', 'selectWellet')->name('welletList.select');

            Route::get('addWellet', 'addWellet')->name('addWellet');
            Route::post('addWellet', 'storeWellet')->name('addWellet.store');
        });

        Route::controller('AviatorGameController')->prefix('aviator')->name('aviator.')->group(function () {
            Route::get('/', 'index')->name('index');
             Route::get('history', 'history')->name('history');
            Route::get('state', 'state')->name('state');
            Route::post('bet', 'placeBet')->name('bet');
            Route::post('cancel', 'cancelBet')->name('cancel');
            Route::post('cashout', 'cashOut')->name('cashout');
        });




    });
});
