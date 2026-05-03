<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}

require_once '../db_connect.php';

// --- DELETION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_single' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM survey_submission WHERE submission_id = ?");
        $stmt->execute([$_POST['id']]);
        header("Location: respondents.php?msg=deleted");
        exit;
    }
    if ($_POST['action'] === 'delete_batch' && isset($_POST['ids'])) {
        $ids = json_decode($_POST['ids']);
        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM survey_submission WHERE submission_id IN ($placeholders)");
            $stmt->execute($ids);
            header("Location: respondents.php?msg=batch_deleted");
            exit;
        }
    }
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

// 1. Fetch Submissions with Likert Pivot and Date Filter
try {
    $stmt = $pdo->prepare("
        SELECT 
            ss.submission_id, 
            ss.created_at as submission_date, 
            pt.type_name as role, 
            c.college_name as college, 
            ss.email as respondent_name, 
            ld.dept_name as department, 
            ss.overall_rating,
            ss.recommendations,
            ss.comments,
            GROUP_CONCAT(rd.score ORDER BY rd.question_id ASC) as likert_scores
        FROM survey_submission ss
        LEFT JOIN library_department ld ON ss.lib_dept_id = ld.lib_dept_id
        LEFT JOIN patron_type pt ON ss.patron_type_id = pt.patron_type_id
        LEFT JOIN college c ON ss.college_id = c.college_id
        LEFT JOIN response_detail rd ON ss.submission_id = rd.submission_id
        WHERE ss.created_at BETWEEN ? AND ?
        GROUP BY ss.submission_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $allSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allSubmissions as &$sub) {
        if ($sub['college'] && preg_match('/\((.*?)\)/', $sub['college'], $matches)) {
            $sub['college'] = $matches[1];
        }
        $scores = $sub['likert_scores'] ? explode(',', $sub['likert_scores']) : [];
        $sub['likert_scores'] = array_pad(array_slice(array_map('intval', $scores), 0, 4), 4, 0);
    }
    unset($sub); // Break the reference to avoid doubling/overwriting bug
} catch (Exception $e) {
    $allSubmissions = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respondents - AUF Library</title>
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
                        slate: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        },
                        apricot: '#F7882F',
                        offwhite: '#FAFAFA',
                        purewhite: '#FFFFFF',
                        biblue: '#4A47A3',
                        bigrey: '#E0E0E0'
                    }
                }
            }
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

        .checkbox-custom:checked {
            background-color: #4A47A3;
            border-color: #4A47A3;
        }
        
        .bi-table-header {
            color: #4A47A3;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
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

<body class="min-h-screen bg-slate-200 flex overflow-x-hidden">
    <?php require_once 'sidebar.php'; ?>

    <div
        class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0">
        <!-- Consolidated Flat Header -->
        <header
            class="bg-white border-b border-slate-300 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
                    <div class="p-1 bg-slate-100 border border-slate-300 rounded-none">
                        <svg class="w-4 h-4 text-[#4A47A3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    Respondent Log
                </h1>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">Data Management / Respondents</p>
            </div>
            <!-- 'Control Center' Date Filters -->
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-white border border-slate-300 p-1">
                    <div class="flex items-center gap-1 px-3 py-1 bg-slate-50 border border-slate-200">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1">From</span>
                        <select id="header_start_month" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            foreach ($months as $m) {
                                $selected = (strtoupper($m) === strtoupper($startMonth)) ? 'selected' : '';
                                echo "<option value='" . strtoupper($m) . "' class='bg-white text-slate-800' $selected>$m</option>";
                            } ?>
                        </select>
                        <select id="header_start_year" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php for ($y = date('Y', strtotime($dbEarliest)); $y <= date('Y'); $y++) {
                                $selected = ($y == $startYear) ? 'selected' : '';
                                echo "<option value='$y' class='bg-white text-slate-800' $selected>$y</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="flex items-center gap-1 px-3 py-1 bg-slate-50 border border-slate-200 ml-1">
                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-tighter mr-1">To</span>
                        <select id="header_end_month" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php foreach ($months as $m) {
                                $selected = (strtoupper($m) === strtoupper($endMonth)) ? 'selected' : '';
                                echo "<option value='" . strtoupper($m) . "' class='bg-white text-slate-800' $selected>$m</option>";
                            } ?>
                        </select>
                        <select id="header_end_year" class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 cursor-pointer py-0 px-1">
                            <?php for ($y = date('Y', strtotime($dbEarliest)); $y <= date('Y'); $y++) {
                                $selected = ($y == $endYear) ? 'selected' : '';
                                echo "<option value='$y' class='bg-white text-slate-800' $selected>$y</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="relative group">
                    <input type="text" id="advancedSearch" placeholder="Search..."
                        class="w-64 bg-white border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#4A47A3] font-bold pl-10">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </header>

        <main class="p-4 space-y-4">
            <!-- Deletion Messages -->
            <?php if (isset($_GET['msg'])): ?>
                <div
                    class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl text-sm font-bold animate-in fade-in zoom-in duration-300">
                    <?php echo $_GET['msg'] === 'deleted' ? 'Record successfully removed.' : 'Selected records have been deleted.'; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <!-- Batch Actions (Hidden by default) -->
                    <div id="batchActionContainer" class="hidden mr-4">
                        <button onclick="confirmBatchDelete()"
                            class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1 text-[10px] font-bold uppercase tracking-widest border border-rose-200 transition-all flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Delete (<span id="selectedCount">0</span>)
                        </button>
                    </div>

                    <select id="filterCollege"
                        class="bg-white border border-slate-300 px-3 py-1.5 text-xs font-bold text-gray-700 outline-none focus:border-[#4A47A3]">
                        <option value="All Colleges">All Colleges</option>
                        <?php $colleges = ["CAMP", "CAS", "CBA", "CCS", "CCJE", "CEA", "CED", "CON", "SOL", "SOM", "GS", "IS", "N/A"];
                        foreach ($colleges as $c)
                            echo "<option value=\"$c\">$c</option>"; ?>
                    </select>
                    <select id="filterUserType"
                        class="bg-white border border-slate-300 px-3 py-1.5 text-xs font-bold text-gray-700 outline-none focus:border-[#4A47A3]">
                        <option value="All User Types">All User Types</option>
                        <option value="Student">Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Alumni">Alumni</option>
                        <option value="NTP">NTP</option>
                        <option value="Other Researcher">Other Researcher</option>
                    </select>
                </div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    Showing <span id="visibleCount" class="text-[#4A47A3] font-black"><?php echo count($allSubmissions); ?></span>
                    Records
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white border border-slate-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="expandedSubmissionsTable">
                        <thead class="bg-gray-50 border-b border-gray-300">
                            <tr>
                                <th class="py-1 px-3 w-10 border-r border-slate-200 text-center">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                        class="w-3 h-3 border-gray-300 text-[#4A47A3] focus:ring-0 cursor-pointer transition-all">
                                </th>
                                <th class="py-1 px-3 bi-table-header border-r border-slate-200">Date</th>
                                <th class="py-1 px-3 bi-table-header border-r border-slate-200">Name</th>
                                <th class="py-1 px-3 bi-table-header border-r border-slate-200">College
                                </th>
                                <th class="py-1 px-3 bi-table-header border-r border-slate-200">User Type
                                </th>
                                <th class="py-1 px-3 bi-table-header border-r border-slate-200">Library
                                    Dept.</th>
                                <th
                                    class="py-1 px-3 bi-table-header text-right">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-200">
                            <?php if (!empty($allSubmissions)): ?>
                                <?php foreach ($allSubmissions as $sub): ?>
                                    <tr class="hover:bg-gray-50 transition-colors" 
                                        data-college="<?php echo htmlspecialchars($sub['college'] ?: 'N/A'); ?>" 
                                        data-user-type="<?php echo htmlspecialchars($sub['role'] ?: 'N/A'); ?>">
                                        <td class="py-1 px-3 border-r border-slate-100 text-center">
                                            <input type="checkbox" name="submission_checkbox"
                                                value="<?php echo $sub['submission_id']; ?>" onchange="handleRowSelect()"
                                                class="w-3 h-3 border-gray-300 text-[#4A47A3] focus:ring-0 cursor-pointer transition-all">
                                        </td>
                                        <td class="py-1 px-3 text-gray-500 font-medium border-r border-slate-100">
                                            <?php echo htmlspecialchars(date('M d, Y', strtotime($sub['submission_date']))); ?>
                                        </td>
                                        <td class="py-1 px-3 font-bold text-gray-800 border-r border-slate-100 whitespace-nowrap">
                                            <?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?></td>
                                        <td class="py-1 px-3 font-bold text-[#4A47A3] border-r border-slate-100">
                                            <?php echo htmlspecialchars($sub['college'] ?: 'N/A'); ?></td>
                                        <td class="py-1 px-3 font-medium text-gray-500 border-r border-slate-100">
                                            <?php echo htmlspecialchars($sub['role'] ?: 'N/A'); ?></td>
                                        <td class="py-1 px-3 font-medium text-gray-500 border-r border-slate-100">
                                            <?php echo htmlspecialchars($sub['department'] ?: 'N/A'); ?></td>
                                        <td class="py-1 px-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button id="btn-view-<?php echo $sub['submission_id']; ?>"
                                                    onclick="openModal(this)"
                                                    data-respondent="<?php echo htmlspecialchars(json_encode($sub), ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="p-1 text-[#4A47A3] hover:bg-slate-100 transition-colors group"
                                                    title="View Details">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                        </path>
                                                    </svg>
                                                </button>
                                                <button onclick="confirmDelete(<?php echo $sub['submission_id']; ?>)"
                                                    class="p-1 text-rose-500 hover:bg-rose-50 transition-colors group"
                                                    title="Delete Log">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-gray-400 font-medium italic">No evaluation
                                        records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Component -->
    <div id="viewModal"
        class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none">
        <div class="absolute inset-0 bg-slate-900/40" onclick="closeModal()"></div>
        <div
            class="bg-white border border-slate-300 w-full max-w-2xl transform scale-95 transition-transform duration-200 relative z-10 overflow-hidden mx-4 flex flex-col max-h-[90vh]">
            <div class="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shrink-0">
                <h2 class="text-[#4A47A3] font-bold text-sm uppercase tracking-wider">Evaluation Details</h2>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 border border-slate-200">
                    <div><span
                            class="block text-[10px] font-bold text-[#4A47A3] uppercase tracking-wider mb-1">Name</span><span
                            id="modalName" class="font-bold text-black text-sm"></span></div>
                    <div><span class="block text-[10px] font-bold text-[#4A47A3] uppercase tracking-wider mb-1">Role &
                            College</span><span id="modalRoleCollege" class="font-bold text-black text-sm"></span>
                    </div>
                    <div><span
                            class="block text-[10px] font-bold text-[#4A47A3] uppercase tracking-wider mb-1">Date</span><span
                            id="modalDate" class="font-bold text-black text-sm"></span></div>
                    <div><span class="block text-[10px] font-bold text-[#4A47A3] uppercase tracking-wider mb-1">Department</span><span id="modalDepartment" class="font-bold text-black text-sm"></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 px-1">Evaluation Indicators
                    </h3>
                    <div id="modalMetrics" class="space-y-1.5">
                        <?php
                        $questions = [
                            "Sufficient resources for research and information needs",
                            "Staff provided assistance in a timely and helpful manner",
                            "Borrowing, returning and renewal is convenient",
                            "Information/procedure provided were easy to understand"
                        ];
                        foreach ($questions as $i => $q): ?>
                            <div
                                class="flex items-center justify-between px-4 py-2 bg-white border border-slate-200">
                                <span class="text-xs font-medium text-gray-700 leading-snug pr-4"><?php echo $q; ?></span>
                                <div id="modalQ<?php echo $i + 1; ?>"
                                    class="flex-shrink-0 px-2 py-1 text-[10px] font-bold tracking-wider uppercase text-center min-w-[120px] border">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Recommendations</h3>
                        <div class="p-3 bg-slate-50 border border-slate-200 min-h-[80px]">
                            <p id="modalRecs" class="text-xs text-gray-700"></p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Comments</h3>
                        <div class="p-3 bg-slate-50 border border-slate-200 min-h-[80px]">
                            <p id="modalComments" class="text-xs text-gray-700"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-3 flex justify-end border-t border-slate-200 shrink-0">
                <button onclick="closeModal()"
                    class="bg-white border border-slate-300 text-gray-700 px-4 py-1.5 text-xs font-bold uppercase tracking-wider transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Actions -->
    <form id="actionForm" method="POST" class="hidden">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="id" id="formId">
        <input type="hidden" name="ids" id="formIds">
    </form>

    <div id="loading-overlay">
        <span class="loader"></span>
    </div>

    <script>
        const likertLabels = { 5: "Strongly Agree", 4: "Agree", 3: "Neutral", 2: "Disagree", 1: "Strongly Disagree" };
        const likertColors = { 
            5: "bg-emerald-50 text-emerald-700 border-emerald-200", 
            4: "bg-teal-50 text-teal-700 border-teal-200", 
            3: "bg-slate-50 text-slate-700 border-slate-200", 
            2: "bg-orange-50 text-orange-700 border-orange-200", 
            1: "bg-rose-50 text-rose-700 border-rose-200" 
        };

        // --- FILTER SYNC LOGIC ---
        function updateRange() {
            const sm = document.getElementById('header_start_month').value;
            const sy = document.getElementById('header_start_year').value;
            const em = document.getElementById('header_end_month').value;
            const ey = document.getElementById('header_end_year').value;
            
            showLoading();
            window.location.href = `respondents.php?start_month=${sm}&start_year=${sy}&end_month=${em}&end_year=${ey}`;
        }

        document.getElementById('header_start_month').addEventListener('change', updateRange);
        document.getElementById('header_start_year').addEventListener('change', updateRange);
        document.getElementById('header_end_month').addEventListener('change', updateRange);
        document.getElementById('header_end_year').addEventListener('change', updateRange);

        function showLoading() {
            document.getElementById('loading-overlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loading-overlay').style.display = 'none';
        }

        function openModal(btn) {
            const data = JSON.parse(btn.getAttribute('data-respondent'));
            document.getElementById('modalName').textContent = data.respondent_name || "Anonymous";
            document.getElementById('modalRoleCollege').textContent = (data.role || "N/A") + (data.college ? " • " + data.college : "");
            document.getElementById('modalDate').textContent = new Date(data.submission_date).toLocaleDateString();
            document.getElementById('modalDepartment').textContent = data.department || "N/A";
            document.getElementById('modalRecs').textContent = data.recommendations || "No recommendations provided.";
            document.getElementById('modalComments').textContent = data.comments || "No comments provided.";
            for (let i = 1; i <= 4; i++) {
                const score = data.likert_scores[i - 1] || 0;
                const el = document.getElementById('modalQ' + i);
                el.textContent = likertLabels[score] || "N/A";
                el.className = "flex-shrink-0 px-2 py-1 text-[10px] font-bold tracking-wider uppercase text-center min-w-[120px] border " + (likertColors[score] || "bg-gray-50 text-gray-700 border-slate-200");
            }
            const modal = document.getElementById('viewModal');
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.querySelector('.transform').classList.replace('scale-95', 'scale-100');
        }

        function closeModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.add('opacity-0', 'pointer-events-none');
            modal.querySelector('.transform').classList.replace('scale-100', 'scale-95');
        }

        // --- DELETION LOGIC ---
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this specific evaluation log? This action cannot be undone.")) {
                document.getElementById('formAction').value = 'delete_single';
                document.getElementById('formId').value = id;
                document.getElementById('actionForm').submit();
            }
        }

        function toggleSelectAll() {
            const master = document.getElementById('selectAll');
            const checks = document.querySelectorAll('input[name="submission_checkbox"]');
            checks.forEach(c => c.checked = master.checked);
            handleRowSelect();
        }

        function handleRowSelect() {
            const checks = document.querySelectorAll('input[name="submission_checkbox"]:checked');
            const container = document.getElementById('batchActionContainer');
            const countEl = document.getElementById('selectedCount');

            if (checks.length > 0) {
                container.classList.remove('hidden');
                countEl.textContent = checks.length;
            } else {
                container.classList.add('hidden');
            }
        }

        function confirmBatchDelete() {
            const checks = document.querySelectorAll('input[name="submission_checkbox"]:checked');
            const ids = Array.from(checks).map(c => parseInt(c.value));

            if (confirm(`Are you sure you want to delete ${ids.length} selected evaluation logs? This action is permanent.`)) {
                document.getElementById('formAction').value = 'delete_batch';
                document.getElementById('formIds').value = JSON.stringify(ids);
                document.getElementById('actionForm').submit();
            }
        }

        function applyFilters() {
            const query = document.getElementById('advancedSearch').value.toLowerCase().trim();
            const collegeFilter = document.getElementById('filterCollege').value;
            const userTypeFilter = document.getElementById('filterUserType').value;
            const rows = document.querySelectorAll('#expandedSubmissionsTable tbody tr');
            
            let visibleCount = 0;

            rows.forEach(row => {
                // If it's the "No records" row, skip
                if (row.cells.length < 2) return;

                const rowCollege = row.getAttribute('data-college');
                const rowType = row.getAttribute('data-user-type');
                const rowText = row.textContent.toLowerCase();

                const matchesSearch = !query || rowText.includes(query);
                const matchesCollege = collegeFilter === 'All Colleges' || rowCollege === collegeFilter;
                const matchesType = userTypeFilter === 'All User Types' || rowType === userTypeFilter;

                if (matchesSearch && matchesCollege && matchesType) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        document.getElementById('advancedSearch').addEventListener('input', applyFilters);
        document.getElementById('filterCollege').addEventListener('change', applyFilters);
        document.getElementById('filterUserType').addEventListener('change', applyFilters);

        // Run once on load to sync the count and check for deep-linked view
        window.addEventListener('load', () => {
            applyFilters();

            // Check for deep-linked view_id
            const params = new URLSearchParams(window.location.search);
            const viewId = params.get('view_id');
            if (viewId) {
                const btn = document.getElementById('btn-view-' + viewId);
                if (btn) {
                    // Slight delay to ensure everything is ready
                    setTimeout(() => openModal(btn), 100);
                }
            }
        });
    </script>
</body>

</html>