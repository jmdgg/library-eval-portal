<?php
/**
 * audit_logs.php
 * Displays the immutable system audit trail.
 */

session_start();
require_once '../db_connect.php';

// 1. SECURITY: Kick out unauthorized users
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}

try {
    // 2. Fetch the last 100 logs, joining with the admin_user table to get the username
    $query = "
        SELECT 
            al.log_id,
            al.action_type,
            al.action_details,
            al.ip_address,
            al.created_at,
            au.username
        FROM audit_log al
        JOIN admin_user au ON al.admin_id = au.admin_id
        ORDER BY al.created_at DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit Logs - AUF Library</title>
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

        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; }
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    System Audit Trail
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">Security / Access Governance</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="bg-blue-50 text-[#4A47A3] px-3 py-1.5 border border-blue-100 text-[9px] font-black uppercase tracking-widest">
                    Immutable Ledger Active
                </div>
            </div>
        </header>

        <main class="p-8 space-y-8 max-w-7xl mx-auto w-full flex-1 flex flex-col">
            
            <div class="bi-card flex flex-col flex-1 overflow-hidden">
                <div class="p-6 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
                    <div>
                        <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">System Transactions</h2>
                        <p class="text-[10px] text-slate-500 font-bold mt-1 uppercase">Historical record of administrative actions</p>
                    </div>
                </div>

                <div class="overflow-y-auto flex-1 custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white sticky top-0 z-10 border-b-2 border-slate-200">
                            <tr>
                                <th class="py-4 px-6 bi-table-header">Timestamp</th>
                                <th class="py-4 px-6 bi-table-header">Administrator</th>
                                <th class="py-4 px-6 bi-table-header">Event</th>
                                <th class="py-4 px-6 bi-table-header">Description</th>
                                <th class="py-4 px-6 bi-table-header text-right">Source IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 text-xs font-bold uppercase">No records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6 text-[11px] font-bold text-slate-700 whitespace-nowrap">
                                            <?php echo date('Y-m-d', strtotime($log['created_at'])); ?><br>
                                            <span class="text-slate-400 font-medium"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 bg-slate-100 border border-slate-300 text-slate-600 flex items-center justify-center font-black text-[9px]">
                                                    <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                                </div>
                                                <span class="text-xs font-bold text-slate-800"><?php echo htmlspecialchars($log['username']); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php
                                            $badge_class = 'bg-slate-100 text-slate-600 border-slate-300';
                                            if ($log['action_type'] == 'LOGIN') $badge_class = 'bg-blue-50 text-blue-700 border-blue-200';
                                            if ($log['action_type'] == 'EXPORT') $badge_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                            if ($log['action_type'] == 'DELETE') $badge_class = 'bg-rose-50 text-rose-700 border-rose-200';
                                            ?>
                                            <span class="px-2 py-0.5 border text-[9px] font-black uppercase tracking-tighter <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($log['action_type']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-slate-600 font-medium max-w-xs truncate" title="<?php echo htmlspecialchars($log['action_details']); ?>">
                                            <?php echo htmlspecialchars($log['action_details']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-[10px] font-mono text-slate-400 text-right">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>
</html>