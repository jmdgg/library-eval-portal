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

// Optional: If you only want Super Admins to see this page, uncomment the block below:
/*
if ($_SESSION['is_superadmin'] != 1) {
    die("Access Denied. Only Super Administrators can view audit logs.");
}
*/

try {
    // 2. Fetch the last 100 logs, joining with the admin_user table to get the username
    $query = "
        SELECT 
            al.log_id,
            al.action_type,
            al.action_details,
            al.ip_address,
            al.created_at,
            au.username,
            d.department_name
        FROM audit_log al
        JOIN admin_user au ON al.admin_id = au.admin_id
        LEFT JOIN department d ON au.department_id = d.department_id
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
</head>

<body class="bg-slate-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div
        class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0 overflow-hidden bg-slate-50/80">

        <div
            class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-indigo-900/5 to-transparent -z-10 pointer-events-none">
        </div>
        <div
            class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl -z-10 pointer-events-none">
        </div>
        <div class="absolute top-48 -left-24 w-72 h-72 bg-blue-400/10 rounded-full blur-3xl -z-10 pointer-events-none">
        </div>

        <header
            class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-16 flex items-center px-8 sticky top-0 z-20 flex-shrink-0">
            <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                System Audit Logs
            </h1>
        </header>

        <main class="p-8 flex-1 overflow-hidden flex flex-col max-w-7xl mx-auto w-full">

            <div
                class="bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 border-t-4 border-t-indigo-500 flex flex-col flex-1 overflow-hidden relative z-0">

                <div
                    class="p-6 border-b border-slate-100 flex justify-between items-center bg-white flex-shrink-0 relative z-10">
                    <div>
                        <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">Recent Security Events</h2>
                        <p class="text-sm text-slate-500 mt-1">Immutable ledger of the last 100 system events across all
                            administrative accounts.</p>
                    </div>
                    <div
                        class="bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-indigo-100 shadow-sm">
                        Live Tracking Active
                    </div>
                </div>

                <div class="overflow-y-auto flex-1 p-0 custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50/80 sticky top-0 z-10 backdrop-blur-md shadow-sm">
                            <tr>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    Timestamp</th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    Administrator</th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    Event Type</th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                                    Details</th>
                                <th
                                    class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">
                                    IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">

                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-400 font-medium">No system events
                                        recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors group">

                                        <td class="py-4 px-6 text-sm text-slate-600 whitespace-nowrap font-medium">
                                            <?php echo date('M d, Y', strtotime($log['created_at'])); ?> <br>
                                            <span
                                                class="text-xs text-slate-400 font-normal"><?php echo date('h:i A', strtotime($log['created_at'])); ?></span>
                                        </td>

                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-9 h-9 rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 flex items-center justify-center font-black text-sm border border-slate-200 shadow-sm group-hover:scale-105 transition-transform">
                                                    <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-slate-800">
                                                        <?php echo htmlspecialchars($log['username']); ?></p>
                                                    <p class="text-xs text-slate-500 font-medium">
                                                        <?php echo $log['department_name'] ? htmlspecialchars($log['department_name']) : 'Super Administrator'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-4 px-6">
                                            <?php
                                            $badge_color = 'bg-slate-100 text-slate-700 border-slate-200';
                                            if ($log['action_type'] == 'EXPORT_MASTER')
                                                $badge_color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                            if ($log['action_type'] == 'LOGIN')
                                                $badge_color = 'bg-blue-50 text-blue-700 border-blue-200';
                                            if ($log['action_type'] == 'DELETE')
                                                $badge_color = 'bg-red-50 text-red-700 border-red-200';
                                            ?>
                                            <span
                                                class="px-3 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-wider border shadow-sm <?php echo $badge_color; ?>">
                                                <?php echo htmlspecialchars($log['action_type']); ?>
                                            </span>
                                        </td>

                                        <td class="py-4 px-6 text-sm text-slate-700 max-w-md truncate font-medium"
                                            title="<?php echo htmlspecialchars($log['action_details']); ?>">
                                            <?php echo htmlspecialchars($log['action_details']); ?>
                                        </td>

                                        <td class="py-4 px-6 text-sm text-slate-400 font-mono text-right whitespace-nowrap">
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

    <style>
        /* Styling the table scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

</body>

</html>