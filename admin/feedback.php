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

// --- PLACEHOLDER DATA FOR UI DESIGN ---
$allFeedback = [
    [
        'id' => 1,
        'submission_date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'respondent_name' => 'Juan Dela Cruz',
        'email' => 'jdelacruz@auf.edu.ph',
        'role' => 'Student',
        'college' => 'CCS',
        'department' => 'Circulation Section',
        'recommendations' => 'Please add more comfortable chairs in the reading area.',
        'comments' => 'The library staff were very accommodating today. Thank you!',
        'is_read' => false
    ],
    [
        'id' => 2,
        'submission_date' => date('Y-m-d H:i:s', strtotime('-1 days')),
        'respondent_name' => 'Maria Clara',
        'email' => 'mclara@auf.edu.ph',
        'role' => 'Faculty',
        'college' => 'CAS',
        'department' => 'General Reference Section',
        'recommendations' => '',
        'comments' => 'Great collection of newly acquired books. However, it can get noisy near the entrance.',
        'is_read' => false
    ],
    [
        'id' => 3,
        'submission_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'respondent_name' => 'Jose Rizal',
        'email' => 'jrizal@auf.edu.ph',
        'role' => 'Alumni',
        'college' => 'CBA',
        'department' => 'Filipiniana Section',
        'recommendations' => 'Extend library hours during weekends.',
        'comments' => '',
        'is_read' => true
    ],
    [
        'id' => 4,
        'submission_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'respondent_name' => 'Andres Bonifacio',
        'email' => 'abonifacio@auf.edu.ph',
        'role' => 'Student',
        'college' => 'CAMP',
        'department' => 'Health Sciences Library',
        'recommendations' => 'Internet connection is quite slow in this section. Sometimes the AC is too cold.',
        'comments' => 'I had trouble accessing some of the online journals using my institutional account.',
        'is_read' => true
    ]
];
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
            safelist: [
                'bg-blue-50', 'border-blue-500', 'border-transparent',
                'text-gray-900', 'text-gray-600', 'text-blue-600',
                'text-gray-400', 'text-gray-800', 'text-gray-500'
            ]
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 flex flex-col h-screen">

        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-8 flex-shrink-0 z-10">
            <div>
                <h1 class="text-xl font-bold text-gray-800">Feedback Inbox</h1>
                <p class="text-xs text-gray-500">Manage and review qualitative responses.</p>
            </div>
        </header>

        <!-- Inbox Layout -->
        <main class="flex-1 flex overflow-hidden">
            
            <!-- Left Pane: List of Feedback -->
            <div class="w-1/3 min-w-[320px] bg-white border-r border-gray-200 flex flex-col z-0">
                <div class="p-4 border-b border-gray-100 bg-gray-50/50 space-y-3">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" id="feedbackSearch" placeholder="Search feedback..." class="w-full bg-white border border-gray-200 rounded-lg pl-9 pr-4 py-2 text-sm font-medium outline-none focus:border-blue-500 transition-colors shadow-sm">
                    </div>
                    <select id="feedbackFilter" class="bg-white border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-600 shadow-sm outline-none focus:border-blue-500 transition-colors w-full cursor-pointer">
                        <option value="all">All Feedback</option>
                        <option value="unread">Unread Only</option>
                        <option value="read">Read Only</option>
                        <option value="comments_only">Comments Only</option>
                        <option value="recommendations_only">Recommendations Only</option>
                        <option value="both">Both Comments & Recommendations</option>
                    </select>
                </div>
                
                <div class="flex-1 overflow-y-auto custom-scrollbar divide-y divide-gray-100" id="feedbackList">
                    <?php foreach($allFeedback as $fb): ?>
                        <?php 
                            $snippet = '';
                            if(!empty($fb['recommendations'])) $snippet = $fb['recommendations'];
                            else if(!empty($fb['comments'])) $snippet = $fb['comments'];
                            
                            $isUnread = !$fb['is_read'];
                        ?>
                        <button onclick="viewFeedback(this)" data-feedback="<?php echo htmlspecialchars(json_encode($fb), ENT_QUOTES, 'UTF-8'); ?>" class="feedback-item w-full text-left p-5 hover:bg-blue-50/50 transition-colors relative group focus:outline-none" style="border-left: 4px solid transparent;">
                            <?php if($isUnread): ?>
                                <span class="absolute top-6 left-3 w-2.5 h-2.5 bg-blue-600 rounded-full unread-dot shadow-sm shadow-blue-500/50"></span>
                            <?php endif; ?>
                            
                            <div class="ml-4">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold <?php echo $isUnread ? 'text-gray-900' : 'text-gray-600'; ?> truncate pr-2 tracking-tight"><?php echo htmlspecialchars($fb['respondent_name']); ?></span>
                                    <div class="flex items-center flex-shrink-0">
                                        <div class="active-indicator hidden flex items-center space-x-1.5 bg-blue-500 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm shadow-blue-500/30 uppercase tracking-widest">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-100 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-white"></span>
                                            </span>
                                            <span>Viewing</span>
                                        </div>
                                        <span class="date-display text-[11px] font-bold <?php echo $isUnread ? 'text-blue-600' : 'text-gray-400'; ?> uppercase tracking-wider"><?php echo date('M d', strtotime($fb['submission_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="text-[11px] font-bold text-gray-400 mb-2 truncate uppercase tracking-widest"><?php echo htmlspecialchars($fb['role'] . ' • ' . $fb['college']); ?></div>
                                <p class="text-sm <?php echo $isUnread ? 'font-medium text-gray-800' : 'text-gray-500'; ?> truncate leading-snug"><?php echo htmlspecialchars($snippet); ?></p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Pane: Reading Area -->
            <div class="flex-1 bg-gray-50/50 flex flex-col relative">
                <!-- Empty State -->
                <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 bg-gray-50/50 z-10">
                    <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <p class="text-lg font-medium">Select a feedback to read</p>
                </div>

                <!-- Content State -->
                <div id="contentState" class="flex-1 overflow-y-auto custom-scrollbar hidden">
                    <div class="max-w-3xl mx-auto p-8 lg:p-12 space-y-8">
                        
                        <!-- Meta Info -->
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div>
                                <h2 id="readName" class="text-2xl font-bold text-gray-800 tracking-tight"></h2>
                                <p id="readEmail" class="text-sm font-medium text-gray-500 mb-2 mt-0.5 flex items-center hidden"><svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v10a2 2 0 002 2z"></path></svg> <span></span></p>
                                <p id="readRoleCol" class="text-sm font-bold text-blue-600 uppercase tracking-widest"></p>
                                <p id="readDept" class="text-sm font-medium text-gray-500 mt-2 flex items-center">
                                    <svg class="w-4 h-4 mr-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    <span></span>
                                </p>
                            </div>
                            <div class="sm:text-right">
                                <p id="readDate" class="text-sm font-bold text-gray-700"></p>
                                <p id="readTime" class="text-xs font-bold text-gray-400 mt-1 uppercase tracking-wider"></p>
                            </div>
                        </div>

                        <!-- Recommendations -->
                        <div id="readRecContainer" class="hidden space-y-3">
                            <h3 class="text-xs font-bold text-blue-500 uppercase tracking-widest flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Recommendations
                            </h3>
                            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-6 sm:p-8 shadow-sm">
                                <p id="readRec" class="text-[15px] text-blue-900 leading-relaxed whitespace-pre-wrap font-medium"></p>
                            </div>
                        </div>

                        <!-- Comments -->
                        <div id="readComContainer" class="hidden space-y-3">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                General Comments
                            </h3>
                            <div class="bg-white border border-gray-200 rounded-2xl p-6 sm:p-8 shadow-sm">
                                <p id="readCom" class="text-[15px] text-gray-700 leading-relaxed whitespace-pre-wrap font-medium"></p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        let currentlyActiveBtn = null;

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
            btn.style.borderLeft = "4px solid #3b82f6"; // Tailwind blue-500
            btn.style.backgroundColor = "#eff6ff"; // Tailwind blue-50

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
            if(data.email && data.email.trim() !== '') {
                emailEl.querySelector('span').textContent = data.email;
                emailEl.classList.remove('hidden');
            } else {
                emailEl.classList.add('hidden');
            }

            document.getElementById('readRoleCol').textContent = (data.role || "N/A") + (data.college && data.college !== 'N/A' ? " • " + data.college : "");
            document.getElementById('readDept').querySelector('span').textContent = data.department || "N/A";
            
            const dateObj = new Date(data.submission_date);
            document.getElementById('readDate').textContent = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            document.getElementById('readTime').textContent = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute:'2-digit' });

            // Populate Feedback Content
            const recCont = document.getElementById('readRecContainer');
            const recText = document.getElementById('readRec');
            if(data.recommendations && data.recommendations.trim()) {
                recText.textContent = data.recommendations.trim();
                recCont.classList.remove('hidden');
            } else {
                recCont.classList.add('hidden');
            }

            const comCont = document.getElementById('readComContainer');
            const comText = document.getElementById('readCom');
            if(data.comments && data.comments.trim()) {
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
                if(searchTerm.trim() !== '') {
                    const searchableText = `
                        ${data.respondent_name || ''} 
                        ${data.role || ''} 
                        ${data.college || ''} 
                        ${data.department || ''} 
                        ${data.recommendations || ''} 
                        ${data.comments || ''}
                    `.toLowerCase();
                    
                    if(!searchableText.includes(searchTerm)) {
                        matchesSearch = false;
                    }
                }

                // 2. Dropdown Filter Check
                let matchesFilter = true;
                // Check if the item currently has the unread dot
                const isUnread = item.querySelector('.unread-dot') !== null;
                const hasComments = data.comments && data.comments.trim() !== '';
                const hasRecs = data.recommendations && data.recommendations.trim() !== '';

                if (filterValue === 'unread' && !isUnread) matchesFilter = false;
                if (filterValue === 'read' && isUnread) matchesFilter = false;
                if (filterValue === 'comments_only' && (!hasComments || hasRecs)) matchesFilter = false;
                if (filterValue === 'recommendations_only' && (!hasRecs || hasComments)) matchesFilter = false;
                if (filterValue === 'both' && (!hasComments || !hasRecs)) matchesFilter = false;

                // Apply visibility
                if(matchesSearch && matchesFilter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', applyFilters);
        filterSelect.addEventListener('change', applyFilters);

        // --- Persistence Logic ---
        function getReadIds() {
            const stored = localStorage.getItem('readFeedbackIds');
            return stored ? JSON.parse(stored) : [];
        }

        function saveReadId(id) {
            const ids = getReadIds();
            if(!ids.includes(id)) {
                ids.push(id);
                localStorage.setItem('readFeedbackIds', JSON.stringify(ids));
            }
        }

        function markItemAsReadVisually(btn) {
            const dot = btn.querySelector('.unread-dot');
            if(dot) dot.remove();
            
            const nameEl = btn.querySelector('.text-gray-900');
            if(nameEl) {
                nameEl.classList.remove('text-gray-900');
                nameEl.classList.add('text-gray-600');
            }
            
            const dateEl = btn.querySelector('.date-display');
            if(dateEl) { 
                dateEl.classList.remove('text-blue-600');
                dateEl.classList.add('text-gray-400');
            }

            const snippetEl = btn.querySelector('p.font-medium.text-gray-800');
            if(snippetEl) {
                snippetEl.classList.remove('font-medium', 'text-gray-800');
                snippetEl.classList.add('text-gray-500');
            }
        }

        function initializeReadStates() {
            const readIds = getReadIds();
            feedbackItems.forEach(item => {
                const data = JSON.parse(item.getAttribute('data-feedback'));
                if(data.is_read || readIds.includes(data.id)) {
                    markItemAsReadVisually(item);
                }
            });
            // Re-apply filters after initializing states in case the default filter isn't "All"
            applyFilters();
        }

        // Run on load
        initializeReadStates();
    </script>
</body>
</html>
