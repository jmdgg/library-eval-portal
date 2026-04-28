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

<body class="bg-gray-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col">

        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center px-8 flex-shrink-0">
            <h1 class="text-xl font-bold text-gray-800">System Audit Logs</h1>
        </header>

        <main class="p-8 flex-1 overflow-hidden flex flex-col">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col flex-1 overflow-hidden">

                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Recent Activity</h2>
                        <p class="text-sm text-gray-500">Showing the latest 100 system events across all administrative
                            accounts.</p>
                    </div>
                </div>

                <div class="overflow-y-auto flex-1 p-0">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50/50 sticky top-0 z-10 backdrop-blur-sm">
                            <tr>
                                <th
                                    class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    Timestamp</th>
                                <th
                                    class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    Administrator</th>
                                <th
                                    class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    Action Type</th>
                                <th
                                    class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    Details</th>
                                <th
                                    class="py-3 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200 text-right">
                                    IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">

                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-400 font-medium">No audit logs found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">

                                        <td class="py-4 px-6 text-sm text-gray-600 whitespace-nowrap">
                                            <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                        </td>

                                        <td class="py-4 px-6">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-8 h-8 rounded bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs mr-3">
                                                    <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-gray-800">
                                                        <?php echo htmlspecialchars($log['username']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <?php echo $log['department_name'] ? htmlspecialchars($log['department_name']) : 'Super Admin'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-4 px-6">
                                            <?php
                                            // Color-code the badges based on the action
                                            $badge_color = 'bg-gray-100 text-gray-700';
                                            if ($log['action_type'] == 'EXPORT_MASTER')
                                                $badge_color = 'bg-green-100 text-green-700';
                                            if ($log['action_type'] == 'LOGIN')
                                                $badge_color = 'bg-blue-100 text-blue-700';
                                            if ($log['action_type'] == 'DELETE')
                                                $badge_color = 'bg-red-100 text-red-700';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $badge_color; ?>">
                                                <?php echo htmlspecialchars($log['action_type']); ?>
                                            </span>
                                        </td>

                                        <td class="py-4 px-6 text-sm text-gray-700 max-w-md truncate"
                                            title="<?php echo htmlspecialchars($log['action_details']); ?>">
                                            <?php echo htmlspecialchars($log['action_details']); ?>
                                        </td>

                                        <td class="py-4 px-6 text-sm text-gray-400 font-mono text-right whitespace-nowrap">
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