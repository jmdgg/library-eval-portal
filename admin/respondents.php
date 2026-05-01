<?php
/**
 * dashboard.php
 * The Secure Container for the Analytics UI
 */

session_start();

// 1. The Bouncer: Kick out anyone without a valid session token
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}

require_once '../db_connect.php';

// Fetch All Submissions
/* --- COMMENTED OUT FOR UI DESIGN ---
try {
    $stmt = $pdo->query("SELECT submission_id, submission_date, role, college, respondent_name, department, overall_rating FROM survey_submission ORDER BY submission_date DESC, submission_id DESC");
    $allSubmissions = $stmt->fetchAll();
} catch (Exception $e) {
    $allSubmissions = [];
}
-------------------------------------- */

// --- PLACEHOLDER DATA FOR UI DESIGN ---
// Remove this block and uncomment the block above when you are done designing.
$allSubmissions = [
    [
        'submission_id' => 1,
        'submission_date' => date('Y-m-d', strtotime('-1 days')),
        'role' => 'Student',
        'college' => 'CCS',
        'respondent_name' => 'Juan Dela Cruz',
        'department' => 'Circulation Section',
        'overall_rating' => 'Excellent',
        'likert_scores' => [5, 5, 4, 5],
        'recommendations' => 'Please add more comfortable chairs in the reading area.',
        'comments' => 'The library staff were very accommodating today. Thank you!'
    ],
    [
        'submission_id' => 2,
        'submission_date' => date('Y-m-d', strtotime('-3 days')),
        'role' => 'Faculty',
        'college' => 'CAS',
        'respondent_name' => 'Maria Clara',
        'department' => 'General Reference Section',
        'overall_rating' => 'Very Good',
        'likert_scores' => [4, 4, 5, 4],
        'recommendations' => '',
        'comments' => 'Great collection of newly acquired books.'
    ],
    [
        'submission_id' => 3,
        'submission_date' => date('Y-m-d', strtotime('-1 week')),
        'role' => 'Alumni',
        'college' => 'CBA',
        'respondent_name' => 'Jose Rizal',
        'department' => 'Filipiniana Section',
        'overall_rating' => 'Good',
        'likert_scores' => [3, 3, 3, 3],
        'recommendations' => 'Extend library hours during weekends.',
        'comments' => ''
    ],
    [
        'submission_id' => 4,
        'submission_date' => date('Y-m-d', strtotime('-2 weeks')),
        'role' => 'Student',
        'college' => 'CAMP',
        'respondent_name' => 'Andres Bonifacio',
        'department' => 'Health Sciences Library',
        'overall_rating' => 'Fair',
        'likert_scores' => [2, 3, 2, 2],
        'recommendations' => 'Internet connection is quite slow in this section.',
        'comments' => 'I had trouble accessing some of the online journals.'
    ],
    [
        'submission_id' => 5,
        'submission_date' => date('Y-m-d', strtotime('-1 month')),
        'role' => 'NTP',
        'college' => 'CEA',
        'respondent_name' => 'Emilio Aguinaldo',
        'department' => 'Computer and Multimedia Services (CMS)',
        'overall_rating' => 'Needs Improvement',
        'likert_scores' => [1, 2, 1, 1],
        'recommendations' => 'Computers need to be upgraded.',
        'comments' => 'Several PCs were out of order. Staff was busy and could not assist.'
    ]
];
// --------------------------------------

// 2. Fetch Admin Details to personalize the UI
$username = $_SESSION['username'] ?? 'Admin';
$is_superadmin = $_SESSION['is_superadmin'] ?? 0;
$role_display = $is_superadmin ? 'Super Administrator' : 'Branch Administrator';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AUF Library Evaluation Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen">

        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Library Evaluation Dashboard</h1>
                <p class="text-xs text-gray-500">Welcome,
                    <?php echo htmlspecialchars($username); ?> &mdash;
                    <?php echo $role_display; ?>
                </p>
            </div>


        </header>

        <main class="p-8 space-y-6">
            <!-- Filter Toolbar -->
            <div class="flex flex-col md:flex-row gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex-grow relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="advancedSearch" class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-10 pr-4 py-3 text-sm outline-none focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-medium" placeholder="Try 'college:ccs student' or 'smith'...">
                </div>
                <div class="flex gap-2">
                    <select id="filterCollege" class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 shadow-sm outline-none focus:border-blue-500 transition-colors">
                        <option value="All Colleges">All Colleges</option>
                        <?php
                        $colleges = ["CAMP", "CAS", "CBA", "CCS", "CCJE", "CEA", "CED", "CON", "SOL", "SOM", "GS", "IS", "N/A"];
                        foreach ($colleges as $c) echo "<option value=\"$c\">$c</option>";
                        ?>
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
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="expandedSubmissionsTable">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
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
                                    <?php
                                    $name = $sub['respondent_name'];
                                    if (empty($name)) {
                                        $filipinoNames = ["Juan Dela Cruz", "Maria Clara", "Jose Rizal", "Andres Bonifacio", "Emilio Aguinaldo", "Apolinario Mabini", "Marcelo H. del Pilar", "Sultan Kudarat", "Lapu-Lapu", "Gabriela Silang"];
                                        $name = $filipinoNames[array_rand($filipinoNames)] . " (Random)";
                                        $sub['respondent_name'] = $name; // Ensure JS gets the generated name
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="py-4 px-6 text-gray-500 font-medium"><?php echo htmlspecialchars(date('M d, Y', strtotime($sub['submission_date']))); ?></td>
                                        <td class="py-4 px-6 font-bold text-gray-800"><?php echo htmlspecialchars($name); ?></td>
                                        <td class="py-4 px-6 font-bold text-blue-600"><?php echo htmlspecialchars($sub['college'] ?: 'N/A'); ?></td>
                                        <td class="py-4 px-6 font-medium text-gray-500"><?php echo htmlspecialchars($sub['role'] ?: 'N/A'); ?></td>
                                        <td class="py-4 px-6 font-medium text-gray-500"><?php echo htmlspecialchars($sub['department'] ?: 'N/A'); ?></td>
                                        <td class="py-4 px-6 text-right">
                                            <button onclick="openModal(this)" data-respondent="<?php echo htmlspecialchars(json_encode($sub), ENT_QUOTES, 'UTF-8'); ?>" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-wider transition-colors">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-gray-400 font-medium italic">No evaluation records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal UI Component -->
    <div id="viewModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" onclick="closeModal()"></div>
        
        <!-- Modal Content -->
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl transform scale-95 transition-transform duration-300 relative z-10 overflow-hidden mx-4 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center shrink-0">
                <h2 class="text-white font-bold text-lg">Respondent Evaluation Details</h2>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-5 space-y-4 overflow-y-auto">
                <!-- Respondent Info -->
                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Name</span>
                        <span id="modalName" class="font-bold text-gray-800 text-sm"></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Role & College</span>
                        <span id="modalRoleCollege" class="font-bold text-gray-800 text-sm"></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Date</span>
                        <span id="modalDate" class="font-bold text-gray-800 text-sm"></span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Library Department</span>
                        <span id="modalDepartment" class="font-bold text-gray-800 text-sm"></span>
                    </div>
                </div>

                <!-- Likert Scales -->
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 px-1">Evaluation Indicators</h3>
                    <div class="space-y-2">
                        <!-- Q1 -->
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-100 shadow-sm bg-white">
                            <span class="text-sm font-medium text-gray-700 leading-snug pr-4">Sufficient resources for research and information needs</span>
                            <div id="modalQ1" class="flex-shrink-0 px-3 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase text-center min-w-[130px]"></div>
                        </div>
                        <!-- Q2 -->
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-100 shadow-sm bg-white">
                            <span class="text-sm font-medium text-gray-700 leading-snug pr-4">Staff provided assistance in a timely and helpful manner</span>
                            <div id="modalQ2" class="flex-shrink-0 px-3 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase text-center min-w-[130px]"></div>
                        </div>
                        <!-- Q3 -->
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-100 shadow-sm bg-white">
                            <span class="text-sm font-medium text-gray-700 leading-snug pr-4">Process of borrowing, returning and renewal is convenient</span>
                            <div id="modalQ3" class="flex-shrink-0 px-3 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase text-center min-w-[130px]"></div>
                        </div>
                        <!-- Q4 -->
                        <div class="flex items-center justify-between px-4 py-2.5 rounded-xl border border-gray-100 shadow-sm bg-white">
                            <span class="text-sm font-medium text-gray-700 leading-snug pr-4">Information/procedure provided were easy to understand</span>
                            <div id="modalQ4" class="flex-shrink-0 px-3 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase text-center min-w-[130px]"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200 shrink-0">
                <button onclick="closeModal()" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">Close</button>
            </div>
        </div>
    </div>

    <script>
        const likertLabels = {
            5: "Strongly Agree",
            4: "Agree",
            3: "Neutral",
            2: "Disagree",
            1: "Strongly Disagree"
        };
        const likertColors = {
            5: "bg-emerald-100 text-emerald-700",
            4: "bg-teal-100 text-teal-700",
            3: "bg-slate-100 text-slate-700",
            2: "bg-orange-100 text-orange-700",
            1: "bg-rose-100 text-rose-700"
        };

        function openModal(btn) {
            const data = JSON.parse(btn.getAttribute('data-respondent'));
            
            // Populate text
            const name = data.respondent_name || "Anonymous";
            document.getElementById('modalName').textContent = name;
            
            const roleCollege = (data.role || "N/A") + (data.college && data.college !== 'N/A' ? " • " + data.college : "");
            document.getElementById('modalRoleCollege').textContent = roleCollege;
            
            const dateObj = new Date(data.submission_date);
            document.getElementById('modalDate').textContent = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            document.getElementById('modalDepartment').textContent = data.department || "N/A";
            
            // Populate Likerts
            if(data.likert_scores && data.likert_scores.length === 4) {
                for(let i = 1; i <= 4; i++) {
                    const score = data.likert_scores[i-1];
                    const el = document.getElementById('modalQ' + i);
                    el.textContent = likertLabels[score] || "N/A";
                    el.className = "flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold tracking-wider uppercase text-center min-w-[140px] " + (likertColors[score] || "bg-gray-100 text-gray-700");
                }
            } else {
                 for(let i = 1; i <= 4; i++) {
                     const el = document.getElementById('modalQ' + i);
                     el.textContent = "N/A";
                     el.className = "flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold tracking-wider uppercase text-center min-w-[140px] bg-gray-100 text-gray-700";
                 }
            }
            
            // Show modal
            const modal = document.getElementById('viewModal');
            const modalContent = modal.querySelector('.transform');
            
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }

        function closeModal() {
            const modal = document.getElementById('viewModal');
            const modalContent = modal.querySelector('.transform');
            
            modal.classList.add('opacity-0', 'pointer-events-none');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
        }

        const collegeAliases = {
            "camp": "college of allied medical professions",
            "cas": "college of arts and sciences",
            "cba": "college of business and accountancy",
            "ccs": "college of computer studies",
            "ccje": "college of criminal justice education",
            "cea": "college of engineering",
            "ced": "college of education",
            "con": "college of nursing",
            "sol": "school of law",
            "som": "school of medicine",
            "gs": "graduate school",
            "is": "integrated school"
        };

        function parseSearchQuery(input) {
            const tokens = input.trim().split(/\s+/);
            const filters = { tokens: {}, freeText: [] };
            
            tokens.forEach(token => {
                if (token.includes(':') && !token.startsWith(':') && !token.endsWith(':')) {
                    const parts = token.split(':');
                    filters.tokens[parts[0].toLowerCase()] = parts[1].toLowerCase();
                } else {
                    if (token) filters.freeText.push(token.toLowerCase());
                }
            });
            return filters;
        }

        function checkMatch(text, term) {
            if (text.includes(term)) return true;
            if (collegeAliases[term] && text.includes(collegeAliases[term])) return true;
            
            for (const key in collegeAliases) {
                if (collegeAliases[key].includes(term) && text.includes(key)) {
                    return true;
                }
            }
            return false;
        }

        const advancedSearch = document.getElementById('advancedSearch');
        const filterCollege = document.getElementById('filterCollege');
        const filterUserType = document.getElementById('filterUserType');

        function applyFilters() {
            const query = advancedSearch.value.toLowerCase();
            const collegeVal = filterCollege.value;
            const userTypeVal = filterUserType.value;
            const parsedQuery = parseSearchQuery(query);
            
            const rows = document.querySelectorAll('#expandedSubmissionsTable tbody tr');
            rows.forEach(row => {
                if(row.cells.length < 6) return;
                
                let isMatch = true;
                
                // Indices: Date=0, Name=1, College=2, User Type=3, Library Dept=4, Action=5
                const rowCollege = row.cells[2].innerText.toLowerCase();
                const rowType = row.cells[3].innerText.toLowerCase();
                const rowDept = row.cells[4].innerText.toLowerCase();
                const rowText = row.innerText.toLowerCase();

                // 1. Dropdown Checks
                if (collegeVal !== 'All Colleges' && rowCollege !== collegeVal.toLowerCase()) {
                    isMatch = false;
                }
                if (isMatch && userTypeVal !== 'All User Types' && rowType !== userTypeVal.toLowerCase()) {
                    isMatch = false;
                }

                // 2. Tokenized Checks
                if (isMatch) {
                    for (const [key, val] of Object.entries(parsedQuery.tokens)) {
                        if (key === 'college' && !checkMatch(rowCollege, val)) isMatch = false;
                        if (key === 'type' && !checkMatch(rowType, val)) isMatch = false;
                        if (key === 'dept' && !checkMatch(rowDept, val)) isMatch = false;
                    }
                }

                // 3. Free Text Check
                if (isMatch && parsedQuery.freeText.length > 0) {
                    const matchesFreeText = parsedQuery.freeText.every(term => checkMatch(rowText, term));
                    if (!matchesFreeText) isMatch = false;
                }
                
                row.style.display = isMatch ? '' : 'none';
            });
        }

        advancedSearch.addEventListener('input', applyFilters);
        filterCollege.addEventListener('change', applyFilters);
        filterUserType.addEventListener('change', applyFilters);


    </script>
</body>

</html>