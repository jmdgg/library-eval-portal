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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    </style>
</head>
<body class="bg-slate-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen relative z-0 overflow-hidden bg-slate-50/80">
        
        <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-emerald-900/5 to-transparent -z-10 pointer-events-none"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
        <div class="absolute top-48 -left-24 w-72 h-72 bg-teal-400/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>

        <header class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-20 flex items-center px-8 sticky top-0 z-20">
            <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                System Settings
            </h1>
        </header>

        <main class="p-8 space-y-8 max-w-7xl mx-auto w-full">
            
            <div class="grid grid-cols-1 gap-8">
                
                <!-- Administrator Management (Full Width) -->
                <div class="bg-white p-8 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-emerald-500 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-48 h-48 bg-gradient-to-br from-emerald-50 to-transparent rounded-full blur-3xl opacity-50 pointer-events-none transition-opacity group-hover:opacity-100"></div>
                    
                    <div class="flex justify-between items-center mb-8 relative z-10">
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Administrator Management</h2>
                            <p class="text-sm text-slate-500 font-medium tracking-tight">Provision and manage secure system access for library staff.</p>
                        </div>
                        <button onclick="toggleModal('newUserModal')" class="bg-emerald-600 text-white hover:bg-emerald-700 transition-all px-8 py-3 rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 hover:-translate-y-0.5 active:translate-y-0">
                            + Provision New User
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100 shadow-sm relative z-10 bg-white">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50/50 border-b border-slate-100">
                                <tr>
                                    <th class="py-5 px-6 text-xs font-black text-slate-400 uppercase tracking-widest">Username & Privilege</th>
                                    <th class="py-5 px-6 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Current Status</th>
                                    <th class="py-5 px-6 text-xs font-black text-slate-400 uppercase tracking-widest text-right">Last System Access</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-5 px-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-sm border border-emerald-100 shadow-sm">
                                                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 text-base"><?php echo htmlspecialchars($admin['username']); ?></span>
                                                <span class="text-[11px] text-slate-400 font-black uppercase tracking-widest"><?php echo $admin['admin_id'] == 1 ? 'System Root' : 'Branch Administrator'; ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-5 px-6 text-center">
                                        <?php if ($admin['is_active']): ?>
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.6)]"></span>
                                                <span class="text-[11px] font-black text-emerald-600 uppercase tracking-widest">Authorized</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex items-center justify-center gap-2 opacity-50">
                                                <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Suspended</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-5 px-6 text-right text-xs text-slate-500 font-bold">
                                        <?php echo $admin['last_login'] ? date('M d, Y • h:i A', strtotime($admin['last_login'])) : 'Initial Access Pending'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Health Card -->
                <div class="bg-white p-8 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden group">
                    <div class="flex items-center gap-6 relative z-10">
                        <div class="w-16 h-16 rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 shadow-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">System Environment</h2>
                            <p class="text-sm text-slate-500 font-medium tracking-tight">Diagnostic overview of the evaluation portal core.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-12 gap-y-4 relative z-10 w-full md:w-auto">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">PHP Engine</p>
                            <p class="text-sm font-bold text-slate-700"><?php echo PHP_VERSION; ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Database</p>
                            <p class="text-sm font-bold text-slate-700">MySQL 3NF</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Environment</p>
                            <p class="text-sm font-bold text-slate-700">Production</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">DB Latency</p>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                                <span class="text-sm font-bold text-slate-700">Optimal</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="newUserModal" class="hidden fixed inset-0 z-50 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="toggleModal('newUserModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-gradient-to-br from-emerald-50 to-transparent rounded-full blur-2xl opacity-50 pointer-events-none"></div>
                    <div class="sm:flex sm:items-start relative z-10">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-emerald-100 sm:mx-0 sm:h-12 sm:w-12 border border-emerald-200">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-6 sm:text-left w-full">
                            <h3 class="text-xl leading-6 font-extrabold text-slate-800 tracking-tight" id="modal-title">Provision New Account</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 mb-6 font-medium">Create a new administrative account. Passwords will be securely hashed before storage.</p>
                                <form action="add_admin.php" method="POST" class="space-y-5">
                                    <div>
                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Username</label>
                                        <input type="text" name="username" required class="w-full text-sm font-bold border border-slate-200 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Temporary Password</label>
                                        <input type="password" name="password" required class="w-full text-sm font-bold border border-slate-200 rounded-xl px-4 py-3 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white">
                                    </div>
                                    <div class="pt-4 flex justify-end gap-3">
                                        <button type="button" onclick="toggleModal('newUserModal')" class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl text-sm font-black uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">Cancel</button>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 rounded-xl text-sm font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 transition-all active:scale-95">Create Account</button>
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