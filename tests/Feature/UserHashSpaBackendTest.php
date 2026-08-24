<?php

use App\Http\Middleware\NegotiateUserSpaResponse;
use App\Http\Middleware\RedirectLegacyUserPage;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

test('serves the user SPA shell at root without authentication', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<div id="app"></div>', false);
});

test('reports a guest session without requiring authentication', function () {
    $this->getJson('/user/spa/session', [
        'X-User-Spa' => '1',
    ])->assertOk()
        ->assertJsonPath('authenticated', false)
        ->assertJsonPath('user', null)
        ->assertJsonStructure(['name', 'flash', 'csrf_token']);
});

test('reports the authenticated user and serves the Home data contract', function () {
    $user = User::factory()->create([
        'phone' => '01700000001',
        'image' => '3',
        'status' => true,
    ]);

    $this->actingAs($user)
        ->getJson('/user/spa/session', [
            'X-User-Spa' => '1',
        ])->assertOk()
        ->assertJsonPath('authenticated', true)
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.status', true);

    $this->getJson('/user/spa/home', [
        'X-User-Spa' => '1',
    ])->assertOk()
        ->assertJsonPath('component', 'Home')
        ->assertJsonPath('props.auth.user.id', $user->id)
        ->assertJsonPath('props.auth.user.status', true)
        ->assertJsonStructure([
            'props' => [
                'page',
                'announcements',
                'bonusItems',
                'hasClaimedFirstRechargeBonus',
            ],
        ]);
});

test('logs out through POST and rotates the SPA session token', function () {
    $user = User::factory()->create([
        'phone' => '01700000002',
        'status' => true,
    ]);

    $this->actingAs($user)
        ->postJson('/user/spa/logout', [], [
            'X-User-Spa' => '1',
        ])->assertOk()
        ->assertJsonPath('authenticated', false)
        ->assertJsonPath('redirect', '/login')
        ->assertJsonStructure(['csrf_token']);

    $this->assertGuest('web');
});

test('returns 403 and ends the session for a blocked user', function () {
    $user = User::factory()->create([
        'phone' => '01700000003',
        'status' => false,
    ]);

    $this->actingAs($user)
        ->getJson('/user/spa/home', [
            'X-User-Spa' => '1',
        ])->assertForbidden()
        ->assertJsonPath('authenticated', false)
        ->assertJsonPath('redirect', '/login');

    $this->assertGuest('web');
});

test('redirects legacy browser page requests to their hash routes', function () {
    $hashRoutes = (new ReflectionClass(RedirectLegacyUserPage::class))
        ->getConstant('HASH_ROUTES');

    foreach ($hashRoutes as $routeName => $hashPath) {
        $this->get(route($routeName, absolute: false))
            ->assertRedirect('/#'.$hashPath);
    }

    $this->get('/user/register?invitationCode=12345678')
        ->assertRedirect('/#/register?invitationCode=12345678');
});

test('returns a direct page envelope without the Inertia wire protocol', function () {
    $this->get('/user/login', [
        'X-User-Spa' => '1',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ])->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertHeaderMissing('X-Inertia')
        ->assertJsonPath('component', 'auth/Login')
        ->assertJsonPath('props.pageTitle', 'Login')
        ->assertJsonPath('props.auth.user', null)
        ->assertJsonStructure([
            'component',
            'props' => ['errors', 'name', 'auth', 'flash', 'pageTitle'],
            'url',
            'version',
        ]);
});

test('serves a direct SPA data contract for every live user page', function () {
    // These structures exist in the deployed application's legacy database,
    // but are absent from the repository's historical SQLite migration
    // snapshot. Recreate only the minimum test schema needed to exercise all
    // page controllers; production migrations and data remain untouched.
    Schema::create('team_partner_rewords', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->decimal('bonus_amount', 12, 2)->default(0);
        $table->decimal('current_turnover', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('deposit_method_commissions', function (Blueprint $table) {
        $table->id();
        $table->string('method_key')->unique();
        $table->decimal('commission_percent', 5, 2)->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::table('aviator_settings', function (Blueprint $table) {
        $table->decimal('slow_factor', 5, 2)->default(1.45);
        $table->decimal('max_crash_multiplier', 10, 2)->default(1000);
        $table->unsignedInteger('public_heartbeat_ms')->default(250);
        $table->unsignedInteger('engine_sleep_ms')->default(100);
        $table->decimal('manual_next_crash_multiplier', 10, 2)->nullable();
    });

    $headers = [
        'X-User-Spa' => '1',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ];

    $failures = [];

    foreach (['user.login', 'user.register', 'user.rpwd'] as $routeName) {
        $response = $this->get(route($routeName, absolute: false), $headers);
        $payload = $response->json();

        if ($response->status() !== 200
            || ! is_array($payload)
            || ! isset($payload['component'], $payload['props'], $payload['url'])) {
            $failures[$routeName] = [
                'status' => $response->status(),
                'body' => $response->getContent(),
            ];
        }
    }

    $user = User::factory()->create([
        'phone' => '01700000010',
        'status' => true,
    ]);

    $this->actingAs($user, 'web');

    $homeResponse = $this->get('/user/spa/home', $headers);
    if ($homeResponse->status() !== 200
        || $homeResponse->json('component') !== 'Home'
        || ! is_array($homeResponse->json('props'))) {
        $failures['user.spa.home'] = [
            'status' => $homeResponse->status(),
            'body' => $homeResponse->getContent(),
        ];
    }

    $hashRoutes = (new ReflectionClass(RedirectLegacyUserPage::class))
        ->getConstant('HASH_ROUTES');

    foreach (array_keys($hashRoutes) as $routeName) {
        if (in_array($routeName, ['user.login', 'user.register', 'user.rpwd'], true)) {
            continue;
        }

        $response = $this->get(route($routeName, absolute: false), $headers);
        $payload = $response->json();

        if ($response->status() !== 200
            || ! is_array($payload)
            || ! isset($payload['component'], $payload['props'], $payload['url'])) {
            $failures[$routeName] = [
                'status' => $response->status(),
                'body' => $response->getContent(),
            ];
        }
    }

    $this->assertSame([], $failures, json_encode($failures, JSON_PRETTY_PRINT));
});

test('keeps native Inertia page-data responses available for legacy callers', function () {
    $headers = [
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ];

    if (file_exists(public_path('build/manifest.json'))) {
        $headers['X-Inertia-Version'] = hash_file('xxh128', public_path('build/manifest.json'));
    }

    $this->get('/user/login', $headers)
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'auth/Login')
        ->assertJsonPath('props.pageTitle', 'Login');
});

test('keeps protected data endpoints protected on the server', function () {
    $this->get('/user/wallet', [
        'X-User-Spa' => '1',
        'Accept' => 'text/html, application/xhtml+xml',
    ])->assertUnauthorized();

    $this->postJson('/user/spa/logout', [], [
        'X-User-Spa' => '1',
    ])->assertUnauthorized();
});

test('keeps the shell available and returns 503 JSON to the SPA during maintenance', function () {
    GeneralSetting::create([
        'site_name' => 'Surex',
        'maintenance_mode' => true,
    ]);

    $this->get('/')->assertOk();

    $this->getJson('/user/spa/session', [
        'X-User-Spa' => '1',
    ])->assertStatus(503)
        ->assertJsonPath('status', 'error');
});

test('negotiates legacy action redirects into SPA JSON', function () {
    Route::post('/_test/user-spa-action', function () {
        toast('Transfer success', 'success');

        return back();
    })->middleware(['web', NegotiateUserSpaResponse::class]);

    $this->postJson('/_test/user-spa-action', [], [
        'X-User-Spa' => '1',
        'X-User-Spa-Page-Url' => '/user/main/StrongBox',
    ])->assertOk()
        ->assertJsonPath('message', 'Transfer success')
        ->assertJsonPath('toast.type', 'success')
        ->assertJsonPath('redirect', '/strong-box');
});

test('negotiates a direct SPA page redirect into an explicit SPA location', function () {
    $user = User::factory()->create([
        'phone' => '01700000004',
        'status' => true,
    ]);

    Transaction::create([
        'user_id' => $user->id,
        'amount' => 1,
        'charge' => 0,
        'post_balance' => 1,
        'trx_type' => '+',
        'trx' => 'SPA-REDIRECT-TEST',
        'details' => 'SPA redirect contract test',
        'remark' => 'complete_deposit_bonus_1',
    ]);

    $this->actingAs($user)
        ->get('/user/activity/FirstRecharge', [
            'X-User-Spa' => '1',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
        ])->assertStatus(409)
        ->assertHeader('X-User-Spa-Location', '/home')
        ->assertJsonPath('redirect', '/home');
});

test('negotiates legacy error bags into 422 JSON', function () {
    Route::post('/_test/user-spa-validation', fn () => back()->withErrors([
        'amount' => 'The amount is invalid.',
    ]))->middleware(['web', NegotiateUserSpaResponse::class]);

    $this->postJson('/_test/user-spa-validation', [], [
        'X-User-Spa' => '1',
        'X-User-Spa-Page-Url' => '/user/wallet/Withdraw',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.amount.0', 'The amount is invalid.');
});

test('logs in through the SPA contract and persists the web session', function () {
    // The production login flow persists this existing model field, while the
    // repository's historical SQLite migration snapshot omits the column.
    Schema::table('users', function (Blueprint $table) {
        $table->string('ip_address')->nullable();
    });

    $user = User::factory()->create([
        'phone' => '01700000005',
        'email' => 'spa-login@gmail.com',
        'password' => Hash::make('Password123'),
        'status' => true,
    ]);

    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'Hash SPA feature test';
    $_SERVER['HTTP_ACCEPT'] = 'application/json';

    $this->withServerVariables([
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Hash SPA feature test',
        'HTTP_ACCEPT' => 'application/json',
    ])->postJson('/user/login', [
        'email' => $user->email,
        'password' => 'Password123',
        'remember' => true,
    ], [
        'X-User-Spa' => '1',
    ])->assertOk()
        ->assertJsonPath('authenticated', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('redirect', '/games/wingo')
        ->assertJsonStructure(['csrf_token']);

    $this->assertAuthenticatedAs($user, 'web');
    $this->assertDatabaseHas('user_logins', ['user_id' => $user->id]);
});

test('returns stable login errors without starting a session', function () {
    $user = User::factory()->create([
        'phone' => '01700000006',
        'email' => 'spa-invalid@gmail.com',
        'password' => Hash::make('Password123'),
        'status' => true,
    ]);

    $this->postJson('/user/login', [
        'email' => $user->email,
        'password' => 'NotThePassword',
    ], [
        'X-User-Spa' => '1',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'Invalid credentials.')
        ->assertJsonPath('errors.login.0', 'Invalid credentials.');

    $this->assertGuest('web');
});

test('registers and authenticates a user through the SPA contract', function () {
    // Production registration already writes this model field, but the
    // repository's historical SQLite migration snapshot omits the column.
    // Add it only to this isolated in-memory test schema; no migration or
    // application database is changed by this test.
    Schema::table('users', function (Blueprint $table) {
        $table->string('ip_address')->nullable();
    });

    $referrer = User::factory()->create([
        'phone' => '01700000007',
        'referral_code' => '87654321',
        'status' => true,
    ]);

    $this->postJson('/user/register', [
        'phone' => '01700000008',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'referral_code' => $referrer->referral_code,
    ], [
        'X-User-Spa' => '1',
    ])->assertOk()
        ->assertJsonPath('authenticated', true)
        ->assertJsonPath('redirect', '/games/wingo')
        ->assertJsonStructure(['data' => ['user'], 'csrf_token']);

    $registered = User::where('phone', '01700000008')->firstOrFail();

    expect($registered->referred_by)->toBe($referrer->id);
    $this->assertAuthenticatedAs($registered, 'web');
});

test('updates profile data through the negotiated SPA action contract', function () {
    $user = User::factory()->create([
        'phone' => '01700000009',
        'name' => 'Old Name',
        'status' => true,
    ]);

    $this->actingAs($user, 'web')
        ->postJson('/user/profile/update-name', [
            'name' => 'Updated Name',
        ], [
            'X-User-Spa' => '1',
            'X-User-Spa-Page-Url' => '/user/main/SettingCenter',
        ])->assertOk()
        ->assertJsonPath('redirect', '/profile')
        ->assertJsonPath('toast.type', 'success');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});
