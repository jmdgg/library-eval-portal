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

// 1. Fetch Submissions with Likert Pivot
try {
    $stmt = $pdo->query("
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
        GROUP BY ss.submission_id
        ORDER BY created_at DESC
    ");
    $allSubmissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for JS
    foreach ($allSubmissions as &$sub) {
        if ($sub['college'] && preg_match('/\((.*?)\)/', $sub['college'], $matches)) {
            $sub['college'] = $matches[1];
        }
        $scores = $sub['likert_scores'] ? explode(',', $sub['likert_scores']) : [];
        $sub['likert_scores'] = array_pad(array_slice(array_map('intval', $scores), 0, 4), 4, 0);
    }
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .checkbox-custom:checked { background-color: #3b82f6; border-color: #3b82f6; }
    </style>
</head>
<body class="min-h-screen bg-slate-200 flex overflow-x-hidden">
    <?php require_once 'sidebar.php'; ?>
    
    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen flex flex-col relative z-0">
        <!-- Consolidated Glassmorphic Header -->
        <header class="bg-white/60 backdrop-blur-lg shadow-sm border-b border-slate-200/60 h-20 flex items-center justify-between px-8 sticky top-0 z-30 flex-shrink-0">
            <div class="flex flex-col">
                <h1 class="text-xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    Respondent Log
                </h1>
                <p class="text-sm text-slate-500 font-medium">Viewing all library service evaluations.</p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Batch Actions (Hidden by default) -->
                <div id="batchActionContainer" class="hidden animate-in fade-in slide-in-from-right-4 duration-200">
                    <button onclick="confirmBatchDelete()" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest border border-rose-200 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>

                <div class="relative group">
                    <input type="text" id="advancedSearch" placeholder="Search evaluation records..." 
                           class="w-96 bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none pl-10 font-bold shadow-sm group-hover:border-slate-300">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </header>

        <main class="p-8 space-y-6">
            <!-- Deletion Messages -->
            <?php if(isset($_GET['msg'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 p-4 rounded-xl text-sm font-bold animate-in fade-in zoom-in duration-300">
                    <?php echo $_GET['msg'] === 'deleted' ? 'Record successfully removed.' : 'Selected records have been deleted.'; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <select id="filterCollege" class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                        <option value="All Colleges">All Colleges</option>
                        <?php $colleges = ["CAMP", "CAS", "CBA", "CCS", "CCJE", "CEA", "CED", "CON", "SOL", "SOM", "GS", "IS", "N/A"];
                        foreach ($colleges as $c) echo "<option value=\"$c\">$c</option>"; ?>
                    </select>
                    <select id="filterUserType" class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                        <option value="All User Types">All User Types</option>
                        <option value="Student">Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Alumni">Alumni</option>
                        <option value="NTP">NTP</option>
                        <option value="Other Researcher">Other Researcher</option>
                    </select>
                </div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                    Showing <span id="visibleCount" class="text-blue-600"><?php echo count($allSubmissions); ?></span> Records
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="expandedSubmissionsTable">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="py-4 px-6 w-10">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer transition-all">
                                </th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">College</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">User Type</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider">Library Dept.</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-50">
                            <?php if (!empty($allSubmissions)): ?>
                                <?php foreach ($allSubmissions as $sub): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6">
                                        <input type="checkbox" name="submission_checkbox" value="<?php echo $sub['submission_id']; ?>" onchange="handleRowSelect()" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer transition-all">
                                    </td>
                                    <td class="py-4 px-6 text-gray-500 font-medium"><?php echo htmlspecialchars(date('M d, Y', strtotime($sub['submission_date']))); ?></td>
                                    <td class="py-4 px-6 font-bold text-gray-800"><?php echo htmlspecialchars($sub['respondent_name'] ?: 'Anonymous'); ?></td>
                                    <td class="py-4 px-6 font-bold text-blue-600"><?php echo htmlspecialchars($sub['college'] ?: 'N/A'); ?></td>
                                    <td class="py-4 px-6 font-medium text-gray-500"><?php echo htmlspecialchars($sub['role'] ?: 'N/A'); ?></td>
                                    <td class="py-4 px-6 font-medium text-gray-500"><?php echo htmlspecialchars($sub['department'] ?: 'N/A'); ?></td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button id="btn-view-<?php echo $sub['submission_id']; ?>" 
                                                    onclick="openModal(this)" 
                                                    data-respondent="<?php echo htmlspecialchars(json_encode($sub), ENT_QUOTES, 'UTF-8'); ?>" 
                                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors group" title="View Details">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $sub['submission_id']; ?>)" 
                                                    class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors group" title="Delete Log">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-gray-400 font-medium italic">No evaluation records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Component -->
    <div id="viewModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl transform scale-95 transition-transform duration-300 relative z-10 overflow-hidden mx-4 flex flex-col max-h-[90vh]">
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center shrink-0">
                <h2 class="text-white font-bold text-lg">Respondent Evaluation Details</h2>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div><span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Name</span><span id="modalName" class="font-bold text-gray-800 text-sm"></span></div>
                    <div><span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Role & College</span><span id="modalRoleCollege" class="font-bold text-gray-800 text-sm"></span></div>
                    <div><span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Date</span><span id="modalDate" class="font-bold text-gray-800 text-sm"></span></div>
                    <div><span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Library Department</span><span id="modalDepartment" class="font-bold text-gray-800 text-sm"></span></div>
                </div>
                <div class="space-y-2">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 px-1">Evaluation Indicators</h3>
                    <div id="modalMetrics" class="space-y-2">
                        <?php 
                        $questions = [
                            "Sufficient resources for research and information needs",
                            "Staff provided assistance in a timely and helpful manner",
                            "Borrowing, returning and renewal is convenient",
                            "Information/procedure provided were easy to understand"
                        ];
                        foreach($questions as $i => $q): ?>
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-100 shadow-sm bg-white">
                            <span class="text-sm font-medium text-gray-700 leading-snug pr-4"><?php echo $q; ?></span>
                            <div id="modalQ<?php echo $i+1; ?>" class="flex-shrink-0 px-3 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase text-center min-w-[130px]"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="space-y-3">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider px-1">Qualitative Feedback</h3>
                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100"><p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Recommendations</p><p id="modalRecs" class="text-sm text-blue-800 italic"></p></div>
                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100"><p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Additional Comments</p><p id="modalComments" class="text-sm text-gray-700"></p></div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200 shrink-0">
                <button onclick="closeModal()" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">Close</button>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Actions -->
    <form id="actionForm" method="POST" class="hidden">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="id" id="formId">
        <input type="hidden" name="ids" id="formIds">
    </form>

    <script>
        const likertLabels = { 5: "Strongly Agree", 4: "Agree", 3: "Neutral", 2: "Disagree", 1: "Strongly Disagree" };
        const likertColors = { 5: "bg-emerald-100 text-emerald-700", 4: "bg-teal-100 text-teal-700", 3: "bg-slate-100 text-slate-700", 2: "bg-orange-100 text-orange-700", 1: "bg-rose-100 text-rose-700" };

        function openModal(btn) {
            const data = JSON.parse(btn.getAttribute('data-respondent'));
            document.getElementById('modalName').textContent = data.respondent_name || "Anonymous";
            document.getElementById('modalRoleCollege').textContent = (data.role || "N/A") + (data.college ? " • " + data.college : "");
            document.getElementById('modalDate').textContent = new Date(data.submission_date).toLocaleDateString();
            document.getElementById('modalDepartment').textContent = data.department || "N/A";
            document.getElementById('modalRecs').textContent = data.recommendations || "No recommendations provided.";
            document.getElementById('modalComments').textContent = data.comments || "No comments provided.";
            for(let i = 1; i <= 4; i++) {
                const score = data.likert_scores[i-1] || 0;
                const el = document.getElementById('modalQ' + i);
                el.textContent = likertLabels[score] || "N/A";
                el.className = "flex-shrink-0 px-3 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase text-center min-w-[130px] " + (likertColors[score] || "bg-gray-100 text-gray-700");
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
            const query = document.getElementById('advancedSearch').value.toLowerCase();
            const college = document.getElementById('filterCollege').value;
            const userType = document.getElementById('filterUserType').value;
            const rows = document.querySelectorAll('#expandedSubmissionsTable tbody tr');
            let visibleCount = 0;
            rows.forEach(row => {
                if (row.cells.length < 7) return;
                const text = row.innerText.toLowerCase();
                const rowCollege = row.cells[3].innerText;
                const rowType = row.cells[4].innerText;
                const matchesSearch = !query || text.includes(query);
                const matchesCollege = college === 'All Colleges' || rowCollege === college;
                const matchesType = userType === 'All User Types' || rowType === userType;
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