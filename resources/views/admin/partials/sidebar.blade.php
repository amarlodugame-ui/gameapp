
  <!- Sidebar ->
  <aside class="sidebar">
    <!- Sidebar Header ->
    <div class="sidebar-header">
      <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
        <img src="{{ asset('assets/admin/img/logo.webp') }}" alt="ModernAdmin">
        <span>{{ gs('site_name') }}</span>
      </a>
      <button class="sidebar-close">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <!- Sidebar Navigation ->
    <nav class="sidebar-nav">
      <ul class="nav-menu">
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.announcements.index') }}" href="{{ route('admin.announcements.index') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Announcements</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.users.turnover.form') }}" href="{{ route('admin.users.turnover.form') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Turnover Add Sub</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.vip.analytics.index') }}" href="{{ route('admin.vip.analytics.index') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Vip Analytics</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.gift.index') }}" href="{{ route('admin.gift.index') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Gift</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.aviator.settings.index') }}" href="{{ route('admin.aviator.settings.index') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Aviator Control Panel</span>
            </a>
        </li>
<li class="nav-item has-submenu {{ request()->routeIs('admin.aviator.reports.*') ? 'open' : '' }}">
    <a class="nav-link" href="#">
        <i class="ph-light ph-airplane"></i>
        <span>Aviator Reports</span>
        <i class="ph-light ph-caret-down nav-arrow"></i>
    </a>

    <ul class="nav-submenu" style="{{ request()->routeIs('admin.aviator.reports.*') ? 'display:block;' : '' }}">

        <li>
            <a class="nav-link {{ request()->routeIs('admin.aviator.reports.index') && !request('role') && !request('slot') && !request('status') ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index') }}">
                All Rounds
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('role') === 'real' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['role' => 'real']) }}">
                Real Bets
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('role') === 'demo' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['role' => 'demo']) }}">
                Demo Bets
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('slot') === '1' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['slot' => 1]) }}">
                Slot 1
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('slot') === '2' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['slot' => 2]) }}">
                Slot 2
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === 'queued' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['status' => 'queued']) }}">
                Queued
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === 'pending' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['status' => 'pending']) }}">
                Pending
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === 'cashed_out' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['status' => 'cashed_out']) }}">
                Cashed Out
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === 'lost' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['status' => 'lost']) }}">
                Lost
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === 'cancelled' ? 'active' : '' }}"
               href="{{ route('admin.aviator.reports.index', ['status' => 'cancelled']) }}">
                Cancelled
            </a>
        </li>

    </ul>
</li>


<li class="nav-item">
    <a class="nav-link {{ menuActive('admin.game_control.index') }}" href="{{ route('admin.game_control.index') }}">
        <i class="ph-light ph-squares-four"></i>
        <span>Wingo Game control</span>
    </a>
</li>

<li class="nav-item has-submenu {{ request()->routeIs('admin.wingo.*') ? 'open' : '' }}">
    <a class="nav-link" href="#">
        <i class="ph-light ph-game-controller"></i>
        <span>Wingo Games</span>
        <i class="ph-light ph-caret-down nav-arrow"></i>
    </a>

    <ul class="nav-submenu" style="{{ request()->routeIs('admin.wingo.*') ? 'display: block;' : '' }}">
        
        {{-- Round List --}}
        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.index') && !request('game_type') && !request('status') && !request('role') ? 'active' : '' }}"
               href="{{ route('admin.wingo.index') }}">
                All Rounds
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.index') && request('game_type') === '30s' ? 'active' : '' }}"
               href="{{ route('admin.wingo.index', ['game_type' => '30s']) }}">
                30s Game
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.index') && request('game_type') === '1m' ? 'active' : '' }}"
               href="{{ route('admin.wingo.index', ['game_type' => '1m']) }}">
                1m Game
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.index') && request('game_type') === '3m' ? 'active' : '' }}"
               href="{{ route('admin.wingo.index', ['game_type' => '3m']) }}">
                3m Game
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.index') && request('game_type') === '5m' ? 'active' : '' }}"
               href="{{ route('admin.wingo.index', ['game_type' => '5m']) }}">
                5m Game
            </a>
        </li>

        <li class="mt-2">
            <span class="nav-link text-muted" style="pointer-events: none; opacity: .7;">
                Game Control
            </span>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.color') && request()->route('type') === '30s' ? 'active' : '' }}"
               href="{{ route('admin.wingo.color.details', '30s') }}">
                30s Control
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.color') && request()->route('type') === '1m' ? 'active' : '' }}"
               href="{{ route('admin.wingo.color.details', '1m') }}">
                1m Control
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.color') && request()->route('type') === '3m' ? 'active' : '' }}"
               href="{{ route('admin.wingo.color.details', '3m') }}">
                3m Control
            </a>
        </li>

        <li>
            <a class="nav-link {{ request()->routeIs('admin.wingo.color') && request()->route('type') === '5m' ? 'active' : '' }}"
               href="{{ route('admin.wingo.color.details', '5m') }}">
                5m Control
            </a>
        </li>

    </ul>
</li>
        <li class="nav-item has-submenu {{ menuOpen('admin.users.*') }}">
            <a class="nav-link" href="#">
                <i class="ph-light ph-users"></i>
                <span>Users</span>
                <i class="ph-light ph-caret-down nav-arrow"></i>
            </a>
            <ul class="nav-submenu" style="{{ submenuShow('admin.users.*') }}">
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.users.list') && !request('type') && !request('status') ? 'active' : '' }}"
                      href="{{ route('admin.users.list') }}">
                        All Users
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ request()->routeIs('admin.users.list') && request('status') === '1' ? 'active' : '' }}"
                      href="{{ route('admin.users.list', ['status' => 1]) }}">
                        Active Users
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ request()->routeIs('admin.users.list') && request('status') === '0' ? 'active' : '' }}"
                      href="{{ route('admin.users.list', ['status' => 0]) }}">
                        Blocked Users
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ request()->routeIs('admin.users.list') && request('type') === 'demo' ? 'active' : '' }}"
                      href="{{ route('admin.users.list', ['type' => 'demo']) }}">
                        Demo Users
                    </a>
                </li>

                <li>
                    <a class="nav-link {{ request()->routeIs('admin.users.list') && request('type') === 'user' ? 'active' : '' }}"
                      href="{{ route('admin.users.list', ['type' => 'user']) }}">
                        Real Users
                    </a>
                </li>
                <li>
                    <a class="nav-link {{ request()->routeIs('admin.users.refferalusersAllData') ? 'active' : '' }}"
                      href="{{ route('admin.users.refferalusersAllData') }}">
                        Chack Refferal
                    </a>
                </li>
            </ul>
        </li>

<li class="nav-item has-submenu {{ request()->routeIs('admin.deposits.index.*') ? 'open' : '' }}">
    <a class="nav-link" href="#">
        <i class="ph-light ph-wallet"></i>
        <span>Deposit Reports</span>
        <i class="ph-light ph-caret-down nav-arrow"></i>
    </a>

    <ul class="nav-submenu" style="{{ request()->routeIs('admin.deposits.index.*') ? 'display:block;' : '' }}">
        <li>
            <a class="nav-link {{ request()->routeIs('admin.deposits.index.index') && request('status') === null ? 'active' : '' }}"
               href="{{ route('admin.deposits.index.index') }}">
                All Deposits
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '0' ? 'active' : '' }}"
               href="{{ route('admin.deposits.index.index', ['status' => 0]) }}">
                Pending
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '1' ? 'active' : '' }}"
               href="{{ route('admin.deposits.index.index', ['status' => 1]) }}">
                Success
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '2' ? 'active' : '' }}"
               href="{{ route('admin.deposits.index.index', ['status' => 2]) }}">
                Awaiting Payment
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '3' ? 'active' : '' }}"
               href="{{ route('admin.deposits.index.index', ['status' => 3]) }}">
                Rejected
            </a>
        </li>
    </ul>
</li>
<li class="nav-item has-submenu {{ request()->routeIs('admin.withdraws.*') ? 'open' : '' }}">
    <a class="nav-link" href="#">
        <i class="ph-light ph-bank"></i>
        <span>Withdraw Reports</span>
        <i class="ph-light ph-caret-down nav-arrow"></i>
    </a>

    <ul class="nav-submenu" style="{{ request()->routeIs('admin.withdraws.report.*') ? 'display:block;' : '' }}">
        <li>
            <a class="nav-link {{ request()->routeIs('admin.withdraws.report.index') && request('status') === null ? 'active' : '' }}"
               href="{{ route('admin.withdraws.report.index') }}">
                All Withdraws
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '0' ? 'active' : '' }}"
               href="{{ route('admin.withdraws.report.index', ['status' => 0]) }}">
                Pending
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '1' ? 'active' : '' }}"
               href="{{ route('admin.withdraws.report.index', ['status' => 1]) }}">
                Processing
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '2' ? 'active' : '' }}"
               href="{{ route('admin.withdraws.report.index', ['status' => 2]) }}">
                Completed
            </a>
        </li>

        <li>
            <a class="nav-link {{ request('status') === '3' ? 'active' : '' }}"
               href="{{ route('admin.withdraws.report.index', ['status' => 3]) }}">
                Rejected
            </a>
        </li>
        <li>
            <a class="nav-link {{ request()->routeIs('admin.withdraws.report.withdraw-wallets.index') ? 'active' : '' }}"
               href="{{ route('admin.withdraws.report.withdraw-wallets.index') }}">
                Wellet Control
            </a>
        </li>
    </ul>
</li>

        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.referrals.index') }}" href="{{ route('admin.referrals.index') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Manage Referral</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('admin.deposit-commissions.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.deposit-commissions.index') }}">
                <i class="ph-light ph-percent"></i>
                <span>Deposit Commissions</span>
            </a>
        </li>
        <li class="nav-item {{ request()->routeIs('admin.referrals.deposit-method-commissions.index.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.referrals.deposit-method-commissions.index') }}">
                <i class="ph-light ph-percent"></i>
                <span>Deposit method ON/OFF</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.general.setting.index') }}" href="{{ route('admin.general.setting.index') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>General Setting</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ menuActive('admin.general.setting.password.form') }}" href="{{ route('admin.general.setting.password.form') }}">
                <i class="ph-light ph-squares-four"></i>
                <span>Change Password</span>
            </a>
        </li>

      </ul>
    </nav>

    <!- Sidebar Footer ->
    <div class="sidebar-footer">
      <div class="sidebar-footer-user">
        <a href="profile.html" class="sidebar-footer-profile">
          <img src="{{ asset('assets/admin/img/logo.webp') }}" alt="User" class="sidebar-footer-avatar">
          <div class="sidebar-footer-info">
            <div class="sidebar-footer-name">John Doe</div>
            <div class="sidebar-footer-role">Administrator</div>
          </div>
        </a>
        <div class="sidebar-footer-actions">
          <a href="#" class="sidebar-footer-action" title="Settings">
            <i class="ph-light ph-gear"></i>
          </a>
          <a href="#" class="sidebar-footer-action sidebar-footer-logout" title="Logout">
            <i class="ph-light ph-sign-out"></i>
          </a>
        </div>
      </div>
    </div>
  </aside>
  <!- Sidebar Overlay (Mobile) ->
  <div class="sidebar-overlay"></div>
