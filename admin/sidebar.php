<?php
// sidebar.php
$current_page = basename($_SERVER['SCRIPT_NAME']);

// Upgraded active/inactive styling for a premium feel
function navLinkClass($page_name, $current_page)
{
    if ($current_page === $page_name) {
        // Active: Frosted glass background, glowing gold border, crisp white text
        return 'bg-white/10 border-l-4 border-yellow-500 text-white shadow-lg shadow-black/10 backdrop-blur-sm';
    }
    // Inactive: Muted blue text, invisible border, subtle background on hover
    return 'text-blue-200/60 hover:bg-white/5 hover:text-white border-l-4 border-transparent';
}
?>
<script>
    // Synchronous render-blocking state check to prevent FOUC (Flash of Unstyled Content)
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.body.classList.add('collapsed-sidebar');
    }
</script>

<aside id="sidebar"
    class="bg-gradient-to-b from-slate-900 via-blue-950 to-slate-900 text-white flex flex-col w-64 [.collapsed-sidebar_&]:w-20 shadow-2xl z-50 flex-shrink-0 border-r border-white/10 h-screen fixed left-0 top-0 transition-all duration-300">

    <button id="sidebar-toggle" title="Toggle Sidebar"
        class="w-full flex items-center justify-center border-b border-white/10 transition-all duration-300 focus:outline-none hover:bg-white/5 flex-shrink-0 [.collapsed-sidebar_&]:h-16 h-28 cursor-pointer [.collapsed-sidebar_&]:p-3 p-4 group">
        <img src="../auf_ul_logo.png" alt="AUF Logo"
            class="object-contain w-full h-full transition-all duration-500 transform group-hover:scale-105 drop-shadow-xl">
    </button>

    <nav class="flex-1 py-6 space-y-1.5 overflow-y-auto overflow-x-hidden flex flex-col custom-scrollbar">

        <a href="dashboard.php"
            class="flex items-center px-5 py-3 [.collapsed-sidebar_&]:px-0 [.collapsed-sidebar_&]:justify-center group <?php echo navLinkClass('dashboard.php', $current_page); ?> transition-all duration-300"
            title="Dashboard">
            <div class="w-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
            </div>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-semibold opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300 transform group-hover:translate-x-1">Dashboard</span>
        </a>

        <a href="respondents.php"
            class="flex items-center px-5 py-3 [.collapsed-sidebar_&]:px-0 [.collapsed-sidebar_&]:justify-center group <?php echo navLinkClass('respondents.php', $current_page); ?> transition-all duration-300"
            title="Respondents">
            <div class="w-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>
            </div>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-semibold opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300 transform group-hover:translate-x-1">Respondents</span>
        </a>

        <a href="feedback.php"
            class="flex items-center px-5 py-3 [.collapsed-sidebar_&]:px-0 [.collapsed-sidebar_&]:justify-center group <?php echo navLinkClass('feedback.php', $current_page); ?> transition-all duration-300"
            title="Feedback Inbox">
            <div class="w-6 flex items-center justify-center flex-shrink-0 relative">
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                    </path>
                </svg>
            </div>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-semibold opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300 transform group-hover:translate-x-1">Feedback Inbox</span>
        </a>

        <a href="analytics.php"
            class="flex items-center px-5 py-3 [.collapsed-sidebar_&]:px-0 [.collapsed-sidebar_&]:justify-center group <?php echo navLinkClass('analytics.php', $current_page); ?> transition-all duration-300"
            title="Analytics & Reports">
            <div class="w-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                    </path>
                </svg>
            </div>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-semibold opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300 transform group-hover:translate-x-1">Analytics
                & Reports</span>
        </a>

        <div class="pt-8 pb-2">
            <p
                class="px-5 text-[10px] font-bold text-blue-300/40 uppercase tracking-widest sidebar-text opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300">
                Administration</p>
        </div>

        <a href="audit_logs.php"
            class="flex items-center px-5 py-3 [.collapsed-sidebar_&]:px-0 [.collapsed-sidebar_&]:justify-center group <?php echo navLinkClass('audit_logs.php', $current_page); ?> transition-all duration-300"
            title="Audit Logs">
            <div class="w-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
            </div>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-semibold opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300 transform group-hover:translate-x-1">Audit
                Logs</span>
        </a>

        <a href="settings.php"
            class="flex items-center px-5 py-3 [.collapsed-sidebar_&]:px-0 [.collapsed-sidebar_&]:justify-center group <?php echo navLinkClass('settings.php', $current_page); ?> transition-all duration-300"
            title="System Settings">
            <div class="w-6 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-semibold opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:hidden overflow-hidden transition-all duration-300 transform group-hover:translate-x-1">System
                Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-white/10 bg-white/5 backdrop-blur-md">
        <div class="flex items-center [.collapsed-sidebar_&]:justify-center justify-between">
            <div
                class="flex items-center gap-3 overflow-hidden opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 sidebar-text transition-all duration-300">
                <div
                    class="w-9 h-9 rounded-xl bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-blue-950 font-black text-sm flex-shrink-0 shadow-lg shadow-yellow-500/20">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="flex flex-col justify-center">
                    <p class="text-sm font-bold text-white tracking-tight truncate w-24 leading-tight">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                    </p>
                    <p class="text-[10px] text-blue-200/70 font-medium uppercase tracking-wider mt-0.5">Administrator
                    </p>
                </div>
            </div>
            <a href="logout.php"
                class="text-blue-200/50 hover:text-red-400 transition-all duration-300 p-2.5 rounded-xl hover:bg-white/10 flex-shrink-0 hover:scale-110"
                title="Logout">
                <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
            </a>
        </div>
    </div>
</aside>

<style>
    /* Custom Scrollbar to match the dark theme */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const texts = document.querySelectorAll('.sidebar-text');

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                sidebar.classList.add('transition-all', 'duration-300', 'ease-in-out');
                toggleBtn.classList.add('transition-all', 'duration-300');
                texts.forEach(t => t.classList.add('transition-all', 'duration-300'));
            });
        });

        toggleBtn.addEventListener('click', () => {
            const isCollapsed = document.body.classList.contains('collapsed-sidebar');
            if (isCollapsed) {
                document.body.classList.remove('collapsed-sidebar');
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                document.body.classList.add('collapsed-sidebar');
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        });
    });
</script>