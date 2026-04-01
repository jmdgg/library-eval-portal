<?php
// sidebar.php
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<script>
    // Synchronous render-blocking state check to prevent FOUC
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.body.classList.add('collapsed-sidebar');
    }
</script>

<!-- Sidebar -->
<aside id="sidebar" class="bg-acad-blue text-white flex flex-col w-64 [.collapsed-sidebar_&]:w-20 shadow-xl z-20 flex-shrink-0 border-r border-blue-900">
    <!-- Sidebar Header / Logo area -->
    <div id="sidebar-header" class="h-16 flex items-center px-4 border-b border-blue-800 justify-between [.collapsed-sidebar_&]:justify-center">
        <span id="sidebar-brand" class="font-bold text-lg whitespace-nowrap overflow-hidden opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0">Eval Portal</span>
        <button id="sidebar-toggle" class="text-blue-200 hover:text-white focus:outline-none transition-colors p-1 rounded-md hover:bg-blue-800">
            <!-- Chevron left by default, turns to hamburger on collapse -->
            <svg id="toggle-icon-collapse" class="w-6 h-6 block [.collapsed-sidebar_&]:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg id="toggle-icon-expand" class="w-6 h-6 hidden [.collapsed-sidebar_&]:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 py-4 space-y-2 overflow-y-auto overflow-x-hidden flex flex-col">
        
        <!-- Upload (index.php) -->
        <a href="index.php" class="flex items-center px-4 py-3 group <?php echo ($current_page == 'index.php') ? 'bg-blue-800 border-l-4 border-acad-gold text-white' : 'text-blue-100 hover:bg-blue-800 hover:text-white border-l-4 border-transparent'; ?> transition-colors" title="Upload">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
            <span class="ml-3 whitespace-nowrap sidebar-text tracking-wide font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Upload</span>
        </a>

        <!-- Records (history.php) -->
        <a href="history.php" class="flex items-center px-4 py-3 group <?php echo ($current_page == 'history.php') ? 'bg-blue-800 border-l-4 border-acad-gold text-white' : 'text-blue-100 hover:bg-blue-800 hover:text-white border-l-4 border-transparent'; ?> transition-colors" title="Records">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
            <span class="ml-3 whitespace-nowrap sidebar-text tracking-wide font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Records</span>
        </a>

        <!-- Dashboard / Preview (preview.php) - shown if active to maintain UX context -->
        <?php if ($current_page == 'preview.php'): ?>
        <a href="preview.php" class="flex items-center px-4 py-3 group bg-blue-800 border-l-4 border-acad-gold text-white transition-colors" title="Dashboard">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            <span class="ml-3 whitespace-nowrap sidebar-text tracking-wide font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Dashboard</span>
        </a>
        <?php endif; ?>

        <!-- Settings (settings.php) -->
        <a href="settings.php" class="flex items-center px-4 py-3 group <?php echo ($current_page == 'settings.php') ? 'bg-blue-800 border-l-4 border-acad-gold text-white' : 'text-blue-100 hover:bg-blue-800 hover:text-white border-l-4 border-transparent'; ?> transition-colors" title="Settings">
            <svg class="w-6 h-6 flex-shrink-0 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span class="ml-3 whitespace-nowrap sidebar-text tracking-wide font-medium opacity-100 w-auto [.collapsed-sidebar_&]:opacity-0 [.collapsed-sidebar_&]:w-0 [.collapsed-sidebar_&]:overflow-hidden">Settings</span>
        </a>
    </nav>
</aside>

<!-- Sidebar JS Logic -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const brand = document.getElementById('sidebar-brand');
        const texts = document.querySelectorAll('.sidebar-text');
        const header = document.getElementById('sidebar-header');

        // Delay adding the transition classes until the browser completes the initial paint
        // This ensures the layout is completely static and mathematically instant on load.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                sidebar.classList.add('transition-all', 'duration-300', 'ease-in-out');
                header.classList.add('transition-all', 'duration-300');
                brand.classList.add('transition-all', 'duration-300');
                texts.forEach(t => t.classList.add('transition-all', 'duration-300'));
            });
        });

        // Toggle event simply triggers the parent class, Tailwind handles the rest
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