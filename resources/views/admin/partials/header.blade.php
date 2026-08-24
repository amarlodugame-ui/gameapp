  <header class="header">
    <!-- Header Left -->
    <div class="header-left">
      <a href="{{ route('admin.dashboard') }}" class="header-logo">
        <img src="{{ asset('assets/admin/img/logo.webp') }}" alt="ModernAdmin">
        <span>{{ gs('site_name') }}</span>
      </a>
      <button class="sidebar-toggle" title="Toggle Sidebar">
        <i class="bi bi-list"></i>
      </button>
    </div>

    <!-- Header Search (Desktop) -->
    <div class="header-search">
      <form class="search-form" action="search-results.html" method="GET">
        <i class="bi bi-search search-icon"></i>
        <input type="search" name="q" placeholder="Search..." autocomplete="off">
        <kbd class="search-shortcut">Ctrl+K</kbd>
      </form>
    </div>

    <!-- Header Right -->
    <div class="header-right">
      <!-- Desktop Actions (hidden on mobile, shown in mobile menu) -->
      <div class="header-actions-desktop">
        <!-- Apps Dropdown -->
        <div class="header-action dropdown apps-dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Apps">
            <i class="bi bi-grid-3x3-gap"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end apps-menu">
            <div class="apps-menu-header">
              <h6>Quick Access</h6>
            </div>
            <div class="apps-grid">
              <a href="{{ route('admin.dashboard') }}" class="apps-item">
                <div class="apps-item-icon primary">
                  <i class="bi bi-speedometer2"></i>
                </div>
                <span class="apps-item-title">Dashboard</span>
              </a>
              <a href="apps-kanban.html" class="apps-item">
                <div class="apps-item-icon success">
                  <i class="bi bi-kanban"></i>
                </div>
                <span class="apps-item-title">Projects</span>
              </a>
              <a href="apps-chat.html" class="apps-item">
                <div class="apps-item-icon info">
                  <i class="bi bi-chat-square-text"></i>
                </div>
                <span class="apps-item-title">Messages</span>
              </a>
              <a href="apps-contacts.html" class="apps-item">
                <div class="apps-item-icon warning">
                  <i class="bi bi-person-lines-fill"></i>
                </div>
                <span class="apps-item-title">Contacts</span>
              </a>
              <a href="apps-todo.html" class="apps-item">
                <div class="apps-item-icon danger">
                  <i class="bi bi-check2-square"></i>
                </div>
                <span class="apps-item-title">Tasks</span>
              </a>
              <a href="apps-calendar.html" class="apps-item">
                <div class="apps-item-icon secondary">
                  <i class="bi bi-calendar-event"></i>
                </div>
                <span class="apps-item-title">Calendar</span>
              </a>
              <a href="apps-file-manager.html" class="apps-item">
                <div class="apps-item-icon primary">
                  <i class="bi bi-folder2-open"></i>
                </div>
                <span class="apps-item-title">Files</span>
              </a>
              <a href="apps-email.html" class="apps-item">
                <div class="apps-item-icon info">
                  <i class="bi bi-envelope"></i>
                </div>
                <span class="apps-item-title">Email</span>
              </a>
              <a href="settings.html" class="apps-item">
                <div class="apps-item-icon muted">
                  <i class="bi bi-gear"></i>
                </div>
                <span class="apps-item-title">Settings</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Theme Toggle -->
        <button class="header-action theme-toggle" title="Toggle Theme">
            <i class="bi bi-moon theme-icon-dark"></i>
            <i class="bi bi-sun theme-icon-light"></i>
        </button>

        <!-- Language Selector -->
        <div class="header-action dropdown language-dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Language">
            <span class="lang-flag">🇺🇸</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item active" href="#">
                <span class="lang-flag">🇺🇸</span>
                <span>English</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <span class="lang-flag">🇪🇸</span>
                <span>Español</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <span class="lang-flag">🇫🇷</span>
                <span>Français</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <span class="lang-flag">🇩🇪</span>
                <span>Deutsch</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="#">
                <span class="lang-flag">🇨🇳</span>
                <span>中文</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Notifications -->
        <div class="header-action dropdown notification-dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="badge">3</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end">
            <div class="notification-header">
              <h6>Notifications</h6>
              <span class="notification-count">3 new</span>
            </div>
            <div class="notification-list">
              <a href="#" class="notification-item unread">
                <div class="notification-avatar success">
                  <i class="bi bi-check-circle"></i>
                </div>
                <div class="notification-content">
                  <div class="notification-title">Order Completed</div>
                  <div class="notification-text">Your order #12345 has been delivered successfully</div>
                  <div class="notification-time">
                    <i class="bi bi-clock"></i>
                    5 min ago
                  </div>
                </div>
              </a>
              <a href="#" class="notification-item unread">
                <div class="notification-avatar warning">
                  <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="notification-content">
                  <div class="notification-title">Low Storage Warning</div>
                  <div class="notification-text">Server storage is running low (85% used)</div>
                  <div class="notification-time">
                    <i class="bi bi-clock"></i>
                    1 hour ago
                  </div>
                </div>
              </a>
              <a href="#" class="notification-item unread">
                <div class="notification-avatar info">
                  <i class="bi bi-person-plus"></i>
                </div>
                <div class="notification-content">
                  <div class="notification-title">New Team Member</div>
                  <div class="notification-text">Sarah Johnson joined the design team</div>
                  <div class="notification-time">
                    <i class="bi bi-clock"></i>
                    2 hours ago
                  </div>
                </div>
              </a>
              <a href="#" class="notification-item">
                <div class="notification-avatar primary">
                  <i class="bi bi-chat-left-text"></i>
                </div>
                <div class="notification-content">
                  <div class="notification-title">New Comment</div>
                  <div class="notification-text">Mike commented on your post</div>
                  <div class="notification-time">
                    <i class="bi bi-clock"></i>
                    Yesterday
                  </div>
                </div>
              </a>
            </div>
            <div class="notification-footer">
              <a href="notifications.html">
                View all notifications
                <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- User Dropdown -->
        <div class="header-action dropdown user-dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="assets/img/profile-img.webp" alt="User" class="avatar">
          </button>
          <div class="dropdown-menu dropdown-menu-end">
            <div class="user-dropdown-header">
              <img src="assets/img/profile-img.webp" alt="User" class="user-dropdown-avatar">
              <div class="user-dropdown-info">
                <div class="user-dropdown-name">John Doe</div>
                <div class="user-dropdown-role">Administrator</div>
              </div>
            </div>
            <div class="user-dropdown-body">
              <a class="user-dropdown-item" href="profile.html">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
              <a class="user-dropdown-item" href="settings.html">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
              </a>
              <a class="user-dropdown-item" href="activity.html">
                <i class="bi bi-clock-history"></i>
                <span>Activity Log</span>
              </a>
            </div>
            <div class="user-dropdown-footer">
              <a class="user-dropdown-logout" href="auth-login.html">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Actions (visible only on mobile) -->
      <div class="header-actions-mobile">
        <!-- Search Toggle (Mobile) -->
        <button class="header-action search-toggle" title="Search">
          <i class="bi bi-search"></i>
        </button>

        <!-- Mobile Menu Toggle -->
        <button class="header-action mobile-menu-toggle" title="More">
          <i class="bi bi-three-dots-vertical"></i>
        </button>
      </div>
    </div>
  </header>