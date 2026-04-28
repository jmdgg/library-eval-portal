<?php
// sidebar.php
$current_page = basename($_SERVER['SCRIPT_NAME']);

// Reusable function for the active/inactive link styling
function navLinkClass($page_name, $current_page)
{
    if ($current_page === $page_name) {
        return 'bg-white/10 border-l-4 border-[#3b82f6] text-white shadow-[0_0_20px_rgba(255,255,255,0.05)] font-bold';
    }
    return 'text-blue-100/60 hover:bg-white/5 hover:text-white border-l-4 border-transparent';
}
?>
<script>
    // Synchronous render-blocking state check to prevent FOUC (Flash of Unstyled Content)
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.body.classList.add('collapsed-sidebar');
    }
</script>

<aside id="sidebar"
    class="bg-gradient-to-b from-[#0a1628] via-[#0f2460] to-[#1740b0] text-white flex flex-col w-64 [.collapsed-sidebar_&]:w-20 shadow-2xl z-20 flex-shrink-0 border-r border-white/5 h-screen fixed left-0 top-0">

    <div id="sidebar-header"
        class="h-16 flex items-center px-4 border-b border-white/10 justify-between [.collapsed-sidebar_&]:justify-center">
        <div id="sidebar-brand"
            class="flex items-center gap-2 overflow-hidden opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0">
            <div class="w-8 h-8 bg-white/10 border border-white/20 rounded-lg flex items-center justify-center flex-shrink-0 backdrop-blur-md shadow-sm">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
            </div>
            <span class="font-bold text-base whitespace-nowrap tracking-tight text-white font-display">Library Portal</span>
        </div>

        <button id="sidebar-toggle"
            class="text-blue-200/60 hover:text-white focus:outline-none transition-colors p-1.5 rounded-md hover:bg-white/10 flex-shrink-0">
            <svg id="toggle-icon-collapse" class="w-5 h-5 block [.collapsed-sidebar_&]:hidden" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg id="toggle-icon-expand" class="w-5 h-5 hidden [.collapsed-sidebar_&]:block" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 py-4 space-y-1 overflow-y-auto overflow-x-hidden flex flex-col">

        <a href="dashboard.php"
            class="flex items-center px-4 py-3 group <?php echo navLinkClass('dashboard.php', $current_page); ?> transition-colors"
            title="Dashboard">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                </path>
            </svg>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Dashboard</span>
        </a>

        <a href="respondents.php"
            class="flex items-center px-4 py-3 group <?php echo navLinkClass('respondents.php', $current_page); ?> transition-colors"
            title="Respondents">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Respondents</span>
        </a>

        <a href="analytics.php"
            class="flex items-center px-4 py-3 group <?php echo navLinkClass('analytics.php', $current_page); ?> transition-colors"
            title="Analytics & Reports">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                </path>
            </svg>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Analytics
                & Reports</span>
        </a>

        <div class="pt-6 pb-2">
            <p
                class="px-4 text-[10px] font-bold text-blue-300/50 uppercase tracking-widest sidebar-text opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">
                Administration</p>
            <div class="hidden [.collapsed-sidebar_&]:block border-b border-white/10 mx-4 my-2"></div>
        </div>

        <a href="audit_logs.php"
            class="flex items-center px-4 py-3 group <?php echo navLinkClass('audit_logs.php', $current_page); ?> transition-colors"
            title="Audit Logs">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Audit
                Logs</span>
        </a>

        <a href="settings.php"
            class="flex items-center px-4 py-3 group <?php echo navLinkClass('settings.php', $current_page); ?> transition-colors"
            title="System Settings">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                </path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <span
                class="ml-3 whitespace-nowrap sidebar-text tracking-wide text-sm font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">System
                Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-white/10 bg-black/20">
        <div class="flex items-center [.collapsed-sidebar_&]:justify-center justify-between">
            <div
                class="flex items-center gap-3 overflow-hidden opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 sidebar-text">
                <div
                    class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#2563eb] to-[#3b82f6] flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-lg">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <p class="text-xs font-bold text-white truncate w-24 font-display">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                    <p class="text-[10px] text-blue-200/60 font-medium italic">Administrator</p>
                </div>
            </div>
            <a href="logout.php"
                class="text-blue-200/60 hover:text-red-400 transition-colors p-2 rounded-lg hover:bg-red-500/10 flex-shrink-0"
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const brand = document.getElementById('sidebar-brand');
        const texts = document.querySelectorAll('.sidebar-text');
        const header = document.getElementById('sidebar-header');

        // Delay transition classes to prevent slide-in animation on initial page load
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                sidebar.classList.add('transition-all', 'duration-300', 'ease-in-out');
                header.classList.add('transition-all', 'duration-300');
                brand.classList.add('transition-all', 'duration-300');
                texts.forEach(t => t.classList.add('transition-all', 'duration-300'));
            });
        });

        // Toggle Sidebar state
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