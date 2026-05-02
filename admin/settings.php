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

        body { font-family: 'Inter', sans-serif; }

        .bi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0;
            box-shadow: none;
        }

        .bi-table-header {
            color: #4A47A3;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-primary {
            background-color: #4A47A3;
            color: white;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 10px 20px;
            border-radius: 0;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: #3b3882;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-200 flex overflow-x-hidden">
    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0">
        
        <!-- Consolidated Flat Header -->
        <header class="bg-white border-b border-slate-300 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <div class="p-1 bg-slate-100 border border-slate-300 rounded-none">
                        <svg class="w-4 h-4 text-[#4A47A3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    System Configuration
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">Security / User Governance</p>
            </div>
        </header>

        <main class="p-8 space-y-8 max-w-7xl mx-auto w-full flex-1">
            
            <div class="grid grid-cols-1 gap-8">
                <!-- Administrator Management -->
                <div class="bi-card p-8">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">Administrator Accounts</h2>
                            <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Manage system access privileges</p>
                        </div>
                        <button onclick="toggleModal('newUserModal')" class="btn-primary">
                            + Provision New User
                        </button>
                    </div>

                    <div class="border border-slate-200">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="py-4 px-6 bi-table-header">Identity</th>
                                    <th class="py-4 px-6 bi-table-header text-center">Status</th>
                                    <th class="py-4 px-6 bi-table-header text-right">Last System Access</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <?php foreach ($admins as $admin): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-slate-100 border border-slate-300 text-slate-600 flex items-center justify-center font-black">
                                                    <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="font-bold text-slate-800"><?php echo htmlspecialchars($admin['username']); ?></span>
                                                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-tighter"><?php echo $admin['admin_id'] == 1 ? 'Root Authority' : 'Branch Admin'; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <?php if ($admin['is_active']): ?>
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase tracking-tighter">Authorized</span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 bg-slate-100 text-slate-400 border border-slate-200 text-[9px] font-black uppercase tracking-tighter">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6 text-right font-bold text-slate-500">
                                            <?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'PENDING ACCESS'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Health Card -->
                <div class="bi-card p-6 flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-100 border border-slate-200 text-[#4A47A3] flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">Environment Diagnostic</h2>
                            <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Core system status overview</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-8 w-full md:w-auto">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">PHP Engine</p>
                            <p class="text-xs font-bold text-slate-700"><?php echo PHP_VERSION; ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Database</p>
                            <p class="text-xs font-bold text-slate-700">MySQL 3NF</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Environment</p>
                            <p class="text-xs font-bold text-slate-700">Production</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Latency</p>
                            <span class="text-xs font-bold text-emerald-600 uppercase tracking-tighter">● Optimal</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="newUserModal" class="hidden fixed inset-0 z-50 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/80 transition-opacity" aria-hidden="true" onclick="toggleModal('newUserModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white border border-slate-300 text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="p-8">
                    <div class="flex items-center gap-4 mb-6 pb-4 border-b border-slate-100">
                        <div class="w-10 h-10 bg-slate-100 border border-slate-200 text-[#4A47A3] flex items-center justify-center">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Provision Account</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">New administrative entry</p>
                        </div>
                    </div>

                    <form action="add_admin.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Username</label>
                            <input type="text" name="username" required class="w-full text-xs font-bold border border-slate-300 px-4 py-3 outline-none focus:border-[#4A47A3] bg-slate-50">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Temporary Password</label>
                            <input type="password" name="password" required class="w-full text-xs font-bold border border-slate-300 px-4 py-3 outline-none focus:border-[#4A47A3] bg-slate-50">
                        </div>
                        <div class="pt-4 flex justify-end gap-2">
                            <button type="button" onclick="toggleModal('newUserModal')" class="bg-white border border-slate-300 text-slate-500 px-6 py-2 text-[10px] font-bold uppercase tracking-widest">Cancel</button>
                            <button type="submit" class="bg-[#4A47A3] hover:bg-[#3b3882] text-white px-8 py-2 text-[10px] font-bold uppercase tracking-widest">Create Identity</button>
                        </div>
                    </form>
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