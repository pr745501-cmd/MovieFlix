<?php
// sidebar.php — shared across all admin pages
// Usage: include 'sidebar.php'; (pass $activePage before including)
// $activePage: 'dashboard' | 'movies' | 'users' | 'bookings' | 'settings'
if (!isset($activePage)) $activePage = '';
?>
<aside class="adm-sidebar" id="sidebar">
    <!-- Logo -->
    <div class="adm-sidebar-logo">
        <div class="adm-logo-icon">🎬</div>
        <div class="adm-logo-text">
            <span class="adm-logo-name">MovieFlix</span>
            <span class="adm-logo-sub">Admin Panel</span>
        </div>
    </div>

    <!-- Admin Badge -->
    <div class="adm-admin-badge">
        <div class="adm-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
        <div>
            <div class="adm-admin-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
            <div class="adm-admin-role">Administrator</div>
        </div>
        <span class="adm-online-dot"></span>
    </div>

    <!-- Nav -->
    <nav class="adm-nav">
        <div class="adm-nav-section">MAIN MENU</div>

        <a href="dashboard.php" class="adm-nav-link <?= $activePage==='dashboard'?'adm-nav-active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>
            Dashboard
        </a>

        <a href="index.php" class="adm-nav-link <?= $activePage==='movies'?'adm-nav-active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
            Movies
        </a>

        <a href="bookings.php" class="adm-nav-link <?= $activePage==='bookings'?'adm-nav-active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
            Bookings
        </a>

        <a href="users.php" class="adm-nav-link <?= $activePage==='users'?'adm-nav-active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/><path stroke-width="2" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Users
        </a>

        <div class="adm-nav-section" style="margin-top:12px;">SYSTEM</div>

        <a href="settings.php" class="adm-nav-link <?= $activePage==='settings'?'adm-nav-active':'' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
            Settings
        </a>
    </nav>

    <!-- Logout -->
    <div class="adm-sidebar-footer">
        <a href="../auth/logout.php" class="adm-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Sign Out
        </a>
    </div>
</aside>
