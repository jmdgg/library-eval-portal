<?php
/**
 * settings.php
 * Master configuration shell for the system.
 */
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// 1. Fetch All Administrators
$admin_query = "SELECT admin_id, username, is_active, last_login FROM admin_user ORDER BY username ASC";
$stmt = $pdo->query($admin_query);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen relative z-0 overflow-hidden bg-slate-50/80">
        
        <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-emerald-900/5 to-transparent -z-10 pointer-events-none"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
        <div class="absolute top-48 -left-24 w-72 h-72 bg-teal-400/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>

        <header class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-16 flex items-center px-8 sticky top-0 z-20">
            <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                System Settings
            </h1>
        </header>

        <main class="p-8 space-y-6 max-w-7xl mx-auto w-full">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="bg-white p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-emerald-500 lg:col-span-2 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-gradient-to-br from-emerald-50 to-transparent rounded-full blur-2xl opacity-50 pointer-events-none transition-opacity group-hover:opacity-100"></div>
                    
                    <div class="flex justify-between items-start mb-6 relative z-10">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Administrator Accounts</h2>
                            <p class="text-sm text-slate-500">Manage system access for library staff.</p>
                        </div>
                        <button onclick="toggleModal('newUserModal')" class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors px-4 py-2 rounded-lg text-sm font-bold shadow-sm border border-emerald-100">
                            + New User
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200 shadow-sm relative z-10">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Username</th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                                    <th class="py-3 px-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Last Login</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                                                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                            </div>
                                            <span class="font-bold text-slate-700 text-sm"><?php echo htmlspecialchars($admin['username']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($admin['is_active']): ?>
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shadow-[0_0_8px_rgba(16,185,129,0.8)]" title="Active"></span>
                                        <?php else: ?>
                                            <span class="w-2 h-2 rounded-full bg-red-500 inline-block" title="Disabled"></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right text-xs text-slate-400 font-mono">
                                        <?php echo $admin['last_login'] ? date('M d, Y', strtotime($admin['last_login'])) : 'Never'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-teal-500 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-gradient-to-br from-teal-50 to-transparent rounded-full blur-2xl opacity-50 pointer-events-none transition-opacity group-hover:opacity-100"></div>
                    
                    <div class="mb-6 relative z-10">
                        <h2 class="text-lg font-bold text-slate-800">Evaluation Periods</h2>
                        <p class="text-sm text-slate-500">Manage data collection cycles.</p>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-xl flex justify-between items-center">
                            <div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Current Period</p>
                                <p class="text-base font-black text-slate-800"><?php echo strtoupper(date('F Y')); ?></p>
                            </div>
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-teal-500"></span>
                            </span>
                        </div>
                        
                        <button class="w-full bg-white border border-slate-300 hover:border-teal-500 hover:text-teal-600 text-slate-600 transition-colors px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm">
                            Manage Historical Periods
                        </button>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-rose-500 lg:col-span-3 mt-2 relative overflow-hidden group">
                    <div class="mb-4 relative z-10">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                            Database Maintenance
                        </h2>
                        <p class="text-sm text-slate-500">Advanced utilities for system administration.</p>
                    </div>

                    <div class="flex gap-4">
                        <button class="bg-white border border-slate-300 hover:border-slate-400 hover:bg-slate-50 text-slate-700 transition-colors px-5 py-2.5 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Backup Database
                        </button>
                        <button class="bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-colors px-5 py-2.5 rounded-lg text-sm font-bold shadow-sm border border-rose-100 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Purge Old Records
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div id="newUserModal" class="hidden fixed inset-0 z-50 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="toggleModal('newUserModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-gradient-to-br from-emerald-50 to-transparent rounded-full blur-2xl opacity-50 pointer-events-none"></div>
                    
                    <div class="sm:flex sm:items-start relative z-10">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10 border border-emerald-200">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-extrabold text-slate-800 tracking-tight" id="modal-title">Provision New Account</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 mb-4">Create a new administrative account. Passwords will be securely hashed before storage.</p>
                                
                                <form action="add_admin.php" method="POST" class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Username</label>
                                        <input type="text" name="username" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-shadow bg-slate-50 focus:bg-white">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Temporary Password</label>
                                        <input type="password" name="password" required class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-shadow bg-slate-50 focus:bg-white">
                                    </div>

                                    <div class="pt-4 flex justify-end gap-3">
                                        <button type="button" onclick="toggleModal('newUserModal')" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm">Cancel</button>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all active:scale-95">
                                            Create Account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalID) {
            document.getElementById(modalID).classList.toggle("hidden");
        }
    </script>
</body>
</html>