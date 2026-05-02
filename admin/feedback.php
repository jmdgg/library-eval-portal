<?php
/**
 * feedback.php
 * Feedback Inbox for the Analytics UI
 */

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}

require_once '../db_connect.php';

// --- MARK AS READ HANDLER (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $sub_id = (int) $_POST['id'];
    $stmt = $pdo->prepare("UPDATE survey_submission SET is_read = 1 WHERE submission_id = ?");
    $stmt->execute([$sub_id]);
    echo json_encode(['status' => 'success']);
    exit;
}

// --- DATE RANGE LOGIC ---
$boundsStmt = $pdo->query("SELECT MIN(created_at) as earliest, MAX(created_at) as latest FROM survey_submission");
$bounds = $boundsStmt->fetch();
$dbEarliest = $bounds['earliest'] ? date('Y-m-d', strtotime($bounds['earliest'])) : date('Y-m-01');
$dbLatest = $bounds['latest'] ? date('Y-m-d', strtotime($bounds['latest'])) : date('Y-m-d');

function getSqlDate($monthName, $year, $isEnd = false) {
    $monthNum = date('m', strtotime($monthName));
    if ($isEnd) return date('Y-m-t 23:59:59', strtotime("$year-$monthNum-01"));
    return "$year-$monthNum-01 00:00:00";
}

$startMonth = $_GET['start_month'] ?? date('F', strtotime($dbEarliest));
$startYear = $_GET['start_year'] ?? date('Y', strtotime($dbEarliest));
$endMonth = $_GET['end_month'] ?? date('F', strtotime($dbLatest));
$endYear = $_GET['end_year'] ?? date('Y', strtotime($dbLatest));

$startDate = getSqlDate($startMonth, $startYear);
$endDate = getSqlDate($endMonth, $endYear, true);

// --- FETCH REAL FEEDBACK DATA ---
try {
    $stmt = $pdo->prepare("
        SELECT 
            ss.submission_id as id, 
            ss.created_at as submission_date, 
            ss.email as respondent_name, 
            ss.email, 
            pt.type_name as role, 
            COALESCE(c.college_name, 'N/A') as college, 
            ld.dept_name as department, 
            ss.recommendations, 
            ss.comments, 
            ss.is_read
        FROM survey_submission ss
        JOIN patron_type pt ON ss.patron_type_id = pt.patron_type_id
        LEFT JOIN college c ON ss.college_id = c.college_id
        JOIN library_department ld ON ss.lib_dept_id = ld.lib_dept_id
        WHERE ((ss.comments IS NOT NULL AND ss.comments != '') 
           OR (ss.recommendations IS NOT NULL AND ss.recommendations != '')
           OR ss.overall_rating < 3.00)
           AND ss.created_at BETWEEN ? AND ?
        ORDER BY ss.created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $allFeedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allFeedback = [];
}
// --------------------------------------

$username = $_SESSION['username'] ?? 'Admin';
$is_superadmin = $_SESSION['is_superadmin'] ?? 0;
$role_display = $is_superadmin ? 'Super Administrator' : 'Branch Administrator';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Inbox - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    borderRadius: {
                        'none': '0',
                        'sm': '0',
                        'DEFAULT': '0',
                        'md': '0',
                        'lg': '0',
                        'xl': '0',
                        '2xl': '0',
                        '3xl': '0',
                        'full': '0',
                    },
                    colors: {
                        slate: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a' },
                        biblue: '#4A47A3',
                        bigrey: '#E0E0E0'
                    }
                }
            },
            safelist: [
                'bg-blue-50', 'border-blue-500', 'border-transparent',
                'text-gray-900', 'text-gray-600', 'text-[#4A47A3]',
                'text-gray-400', 'text-gray-800', 'text-gray-500'
            ]
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * {
            border-radius: 0 !important;
        }

        body {
            background-color: #e2e8f0;
            color: #334155;
            font-family: 'Inter', sans-serif;
        }

        .shadow-sm, .shadow, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl, .shadow-inner {
            box-shadow: none !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
        }
        
        .bi-section-title {
            color: #4A47A3;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        #loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(226, 232, 240, 0.5);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }

        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #FFF;
            border-bottom-color: #4A47A3;
            border-radius: 50% !important;
            display: inline-block;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }

        @keyframes rotation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="bg-slate-200 flex h-screen overflow-hidden font-sans">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 flex flex-col h-screen">

        <!-- Consolidated Flat Header -->
        <header
            class="bg-white border-b border-slate-300 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <div class="p-1 bg-slate-100 border border-slate-300">
                        <svg class="w-4 h-4 text-[#4A47A3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    Feedback Inbox
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">Communications / Evaluation Feedback</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-white border border-slate-300 p-1">
                    <div class="flex items-center gap-1 px-3 py-1 bg-slate-50 border border-slate-200">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1">From</span>
                        <select id="header_start_month" onchange="applyDateFilter()" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            foreach ($months as $m) {
                                $selected = (strtoupper($m) === strtoupper($startMonth)) ? 'selected' : '';
                                echo "<option value='" . strtoupper($m) . "' class='bg-white text-slate-800' $selected>$m</option>";
                            } ?>
                        </select>
                        <select id="header_start_year" onchange="applyDateFilter()" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php for ($y = date('Y', strtotime($dbEarliest)); $y <= date('Y'); $y++) {
                                $selected = ($y == $startYear) ? 'selected' : '';
                                echo "<option value='$y' class='bg-white text-slate-800' $selected>$y</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="flex items-center gap-1 px-3 py-1 bg-slate-50 border border-slate-200 ml-1">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1">To</span>
                        <select id="header_end_month" onchange="applyDateFilter()" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php foreach ($months as $m) {
                                $selected = (strtoupper($m) === strtoupper($endMonth)) ? 'selected' : '';
                                echo "<option value='" . strtoupper($m) . "' class='bg-white text-slate-800' $selected>$m</option>";
                            } ?>
                        </select>
                        <select id="header_end_year" onchange="applyDateFilter()" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php for ($y = date('Y', strtotime($dbEarliest)); $y <= date('Y'); $y++) {
                                $selected = ($y == $endYear) ? 'selected' : '';
                                echo "<option value='$y' class='bg-white text-slate-800' $selected>$y</option>";
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
        </header>

        <!-- Inbox Layout -->
        <main class="flex-1 flex overflow-hidden">

            <!-- Left Pane: List of Feedback -->
            <div class="w-1/3 min-w-[320px] bg-white border-r border-gray-200 flex flex-col z-0">
                <div class="p-4 border-b border-slate-200 bg-slate-50 space-y-3">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" id="feedbackSearch" placeholder="Search feedback..."
                            class="w-full bg-white border border-slate-300 pl-9 pr-4 py-1.5 text-xs font-bold outline-none focus:border-[#4A47A3]">
                    </div>
                    <select id="feedbackFilter"
                        class="bg-white border border-slate-300 px-3 py-1.5 text-[10px] font-bold text-gray-600 outline-none focus:border-[#4A47A3] w-full cursor-pointer uppercase tracking-wider">
                        <option value="all">All Feedback</option>
                        <option value="unread">Unread Only</option>
                        <option value="read">Read Only</option>
                        <option value="comments_only">Comments Only</option>
                        <option value="recommendations_only">Recommendations Only</option>
                        <option value="both">Both Comments & Recommendations</option>
                    </select>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-slate-200" id="feedbackList">
                    <?php foreach ($allFeedback as $fb): ?>
                        <?php
                        $snippet = '';
                        if (!empty($fb['recommendations']))
                            $snippet = $fb['recommendations'];
                        else if (!empty($fb['comments']))
                            $snippet = $fb['comments'];

                        $isUnread = !$fb['is_read'];
                        ?>
                        <button onclick="viewFeedback(this)"
                            data-feedback="<?php echo htmlspecialchars(json_encode($fb), ENT_QUOTES, 'UTF-8'); ?>"
                            class="feedback-item w-full text-left p-5 hover:bg-blue-50/50 transition-colors relative group focus:outline-none"
                            style="border-left: 4px solid transparent;">
                            <?php if ($isUnread): ?>
                                <span
                                    class="absolute top-0 left-0 w-1 h-full bg-[#4A47A3] unread-indicator shadow-none"></span>
                            <?php endif; ?>

                            <div class="ml-2 py-1">
                                <div class="flex justify-between items-center mb-0.5">
                                    <span
                                        class="font-bold <?php echo $isUnread ? 'text-gray-900' : 'text-gray-600'; ?> truncate pr-2 tracking-tight text-xs"><?php echo htmlspecialchars($fb['respondent_name']); ?></span>
                                    <div class="flex items-center flex-shrink-0">
                                        <div
                                            class="active-indicator hidden flex items-center space-x-1 bg-[#4A47A3] text-white text-[7px] font-black px-1 py-0.5 shadow-sm uppercase tracking-widest">
                                            <span>Viewing</span>
                                        </div>
                                        <span
                                            class="date-display text-[9px] font-bold <?php echo $isUnread ? 'text-[#4A47A3]' : 'text-slate-400'; ?> uppercase tracking-wider"><?php echo date('M d', strtotime($fb['submission_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="text-[8px] font-bold text-gray-400 mb-1 truncate uppercase tracking-widest">
                                    <?php echo htmlspecialchars($fb['role'] . ' • ' . $fb['college']); ?></div>
                                <p
                                    class="text-[11px] <?php echo $isUnread ? 'font-bold text-gray-800' : 'text-gray-500'; ?> truncate leading-snug">
                                    <?php echo htmlspecialchars($snippet); ?></p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Pane: Reading Area -->
            <div class="flex-1 bg-gray-50/50 flex flex-col relative">
                <!-- Empty State -->
                <div id="emptyState"
                    class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 bg-gray-50/50 z-10">
                    <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                    <p class="text-lg font-medium">Select a feedback to read</p>
                </div>

                <!-- Content State -->
                <div id="contentState" class="flex-1 overflow-y-auto custom-scrollbar hidden">
                    <div class="max-w-3xl mx-auto p-8 lg:p-12 space-y-8">                        <!-- Meta Info -->
                        <div
                            class="bg-white p-6 border border-slate-300 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div>
                                <h2 id="readName" class="text-xl font-bold text-gray-800 tracking-tight"></h2>
                                <p id="readEmail"
                                    class="text-xs font-bold text-gray-500 mb-2 mt-1 flex items-center hidden">
                                    <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg> <span></span></p>
                                <p id="readRoleCol" class="text-[10px] font-bold text-[#4A47A3] uppercase tracking-widest">
                                </p>
                                <p id="readDept" class="text-[10px] font-bold text-gray-400 mt-2 flex items-center uppercase tracking-widest">
                                    <svg class="w-4 h-4 mr-1.5 opacity-50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                        </path>
                                    </svg>
                                    <span></span>
                                </p>
                            </div>
                            <div class="sm:text-right">
                                <p id="readDate" class="text-xs font-bold text-gray-700"></p>
                                <p id="readTime" class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-wider">
                                </p>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div id="readRecContainer" class="hidden space-y-2">
                            <h3 class="bi-section-title flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Recommendations
                            </h3>
                            <div class="bg-slate-50 border border-slate-300 p-6 sm:p-8">
                                <p id="readRec"
                                    class="text-sm text-[#4A47A3] leading-relaxed whitespace-pre-wrap font-bold">
                                </p>
                            </div>
                        </div>

                        <!-- Comments -->
                        <div id="readComContainer" class="hidden space-y-2">
                            <h3 class="bi-section-title flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                                    </path>
                                </svg>
                                General Comments
                            </h3>
                            <div class="bg-white border border-slate-300 p-6 sm:p-8">
                                <p id="readCom"
                                    class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap font-medium">
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </main>
    </div>    <div id="loading-overlay">
        <span class="loader"></span>
    </div>

    <script>
        let currentlyActiveBtn = null;

        // --- FILTER SYNC LOGIC ---
        function updateRange() {
            const sm = document.getElementById('header_start_month').value;
            const sy = document.getElementById('header_start_year').value;
            const em = document.getElementById('header_end_month').value;
            const ey = document.getElementById('header_end_year').value;
            
            showLoading();
            window.location.href = `feedback.php?start_month=${sm}&start_year=${sy}&end_month=${em}&end_year=${ey}`;
        }

        if (document.getElementById('header_start_month')) {
            document.getElementById('header_start_month').addEventListener('change', updateRange);
            document.getElementById('header_start_year').addEventListener('change', updateRange);
            document.getElementById('header_end_month').addEventListener('change', updateRange);
            document.getElementById('header_end_year').addEventListener('change', updateRange);
        }

        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function viewFeedback(btn) {
            const data = JSON.parse(btn.getAttribute('data-feedback'));

            // UI Switch
            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('contentState').classList.remove('hidden');

            // Highlight active in list using foolproof inline styles
            document.querySelectorAll('.feedback-item').forEach(item => {
                item.style.borderLeft = "4px solid transparent";
                item.style.backgroundColor = ""; // Clear background to allow CSS hover

                // Hide viewing indicator and show date
                const indicator = item.querySelector('.active-indicator');
                if (indicator) indicator.classList.add('hidden');
                const dateEl = item.querySelector('.date-display');
                if (dateEl) dateEl.classList.remove('hidden');
            });
            btn.style.borderLeft = "4px solid #4A47A3";
            btn.style.backgroundColor = "#f1f5f9"; // Tailwind slate-100

            // Show viewing indicator and hide date
            const indicator = btn.querySelector('.active-indicator');
            if (indicator) indicator.classList.remove('hidden');
            const dateEl = btn.querySelector('.date-display');
            if (dateEl) dateEl.classList.add('hidden');

            // Mark as read visually and persistently
            markItemAsReadVisually(btn);
            saveReadId(data.id);

            // Populate Metadata
            document.getElementById('readName').textContent = data.respondent_name || "Anonymous";

            const emailEl = document.getElementById('readEmail');
            if (data.email && data.email.trim() !== '') {
                emailEl.querySelector('span').textContent = data.email;
                emailEl.classList.remove('hidden');
            } else {
                emailEl.classList.add('hidden');
            }

            document.getElementById('readRoleCol').textContent = (data.role || "N/A") + (data.college && data.college !== 'N/A' ? " • " + data.college : "");
            document.getElementById('readDept').querySelector('span').textContent = data.department || "N/A";

            const dateObj = new Date(data.submission_date);
            document.getElementById('readDate').textContent = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            document.getElementById('readTime').textContent = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            // Populate Feedback Content
            const recCont = document.getElementById('readRecContainer');
            const recText = document.getElementById('readRec');
            if (data.recommendations && data.recommendations.trim()) {
                recText.textContent = data.recommendations.trim();
                recCont.classList.remove('hidden');
            } else {
                recCont.classList.add('hidden');
            }

            const comCont = document.getElementById('readComContainer');
            const comText = document.getElementById('readCom');
            if (data.comments && data.comments.trim()) {
                comText.textContent = data.comments.trim();
                comCont.classList.remove('hidden');
            } else {
                comCont.classList.add('hidden');
            }
        }

        // --- Filtering and Searching Logic ---
        const searchInput = document.getElementById('feedbackSearch');
        const filterSelect = document.getElementById('feedbackFilter');
        const feedbackItems = document.querySelectorAll('.feedback-item');

        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterValue = filterSelect.value;

            feedbackItems.forEach(item => {
                const data = JSON.parse(item.getAttribute('data-feedback'));

                // 1. Text Search Check
                let matchesSearch = true;
                if (searchTerm.trim() !== '') {
                    const searchableText = `
                        ${data.respondent_name || ''} 
                        ${data.role || ''} 
                        ${data.college || ''} 
                        ${data.department || ''} 
                        ${data.recommendations || ''} 
                        ${data.comments || ''}
                    `.toLowerCase();

                    if (!searchableText.includes(searchTerm)) {
                        matchesSearch = false;
                    }
                }

                // 2. Dropdown Filter Check
                let matchesFilter = true;
                // Check if the item currently has the unread indicator
                const isUnread = item.querySelector('.unread-indicator') !== null;
                const hasComments = data.comments && data.comments.trim() !== '';
                const hasRecs = data.recommendations && data.recommendations.trim() !== '';

                if (filterValue === 'unread' && !isUnread) matchesFilter = false;
                if (filterValue === 'read' && isUnread) matchesFilter = false;
                if (filterValue === 'comments_only' && (!hasComments || hasRecs)) matchesFilter = false;
                if (filterValue === 'recommendations_only' && (!hasRecs || hasComments)) matchesFilter = false;
                if (filterValue === 'both' && (!hasComments || !hasRecs)) matchesFilter = false;

                // Apply visibility
                if (matchesSearch && matchesFilter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', applyFilters);
        filterSelect.addEventListener('change', applyFilters);

        // --- Persistence Logic ---
        function saveReadId(id) {
            // Server-side update
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('id', id);

            fetch('feedback.php', {
                method: 'POST',
                body: formData
            }).catch(err => console.error('Error marking as read:', err));
        }

        function markItemAsReadVisually(btn) {
            const dot = btn.querySelector('.unread-indicator');
            if (dot) dot.remove();

            const nameEl = btn.querySelector('.text-gray-900');
            if (nameEl) {
                nameEl.classList.remove('text-gray-900');
                nameEl.classList.add('text-gray-600');
            }

            const dateEl = btn.querySelector('.date-display');
            if (dateEl) {
                dateEl.classList.remove('text-[#4A47A3]');
                dateEl.classList.add('text-gray-400');
            }

            const snippetEl = btn.querySelector('p.font-bold.text-gray-800');
            if (snippetEl) {
                snippetEl.classList.remove('font-bold', 'text-gray-800');
                snippetEl.classList.add('text-gray-500');
            }
        }

        function initializeReadStates() {
            feedbackItems.forEach(item => {
                const data = JSON.parse(item.getAttribute('data-feedback'));
                // Use the database-driven is_read property
                if (data.is_read == 1 || data.is_read === true) {
                    markItemAsReadVisually(item);
                }
            });
            // Re-apply filters after initializing states
            applyFilters();
        }

        // Run on load
        initializeReadStates();
    </script>
</body>

</html>