<?php
// history.php
require_once 'db_connect.php';

$reportsByYear = [];

try {
    $stmt = $pdo->query("SELECT p.eval_month, p.eval_year, r.report_id, r.file_name, r.download_url, r.dashboard_data, r.generation_date FROM EVALUATION_PERIOD p JOIN GENERATED_REPORT r ON p.period_id = r.period_id ORDER BY r.generation_date DESC");
    $data = $stmt->fetchAll();

    if ($data) {
        foreach ($data as $entry) {
            $year = isset($entry['eval_year']) ? $entry['eval_year'] : 'Unknown Year';
            if (!isset($reportsByYear[$year])) {
                $reportsByYear[$year] = [];
            }
            $reportsByYear[$year][] = $entry;
        }
    }

    // Sort years descending
    krsort($reportsByYear);
} catch (PDOException $e) {
    // Fallback on error
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Locker - Library Service Evaluation Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'acad-blue': '#1e3a8a',
                        'acad-gold': '#d97706',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans antialiased text-gray-800">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto bg-gray-50 p-8 w-full">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">History Locker</h1>
            <?php if (empty($reportsByYear)): ?>
                <div class="bg-white p-8 rounded-lg shadow-sm border border-gray-200 text-center max-w-lg mx-auto mt-10">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <h2 class="text-xl font-medium text-gray-700 mb-2">No Reports Found</h2>
                    <p class="text-gray-500">There are currently no saved reports in the history locker.</p>
                </div>
            <?php else: ?>
                <div class="space-y-10">
                    <?php foreach ($reportsByYear as $year => $reports): ?>
                        <section>
                            <h2
                                class="text-2xl font-bold text-acad-blue mb-4 border-b-2 border-acad-gold pb-2 inline-block shadow-sm-bottom">
                                <?php echo htmlspecialchars($year); ?> Reports
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($reports as $report): ?>
                                    <?php
                                    $reportId = htmlspecialchars($report['report_id'] ?? '');
                                    $reportDateStr = ($report['eval_month'] ?? '') . ' ' . ($report['eval_year'] ?? '');
                                    $reportDate = htmlspecialchars(trim($reportDateStr) ?: 'Unknown Date');
                                    $downloadUrl = htmlspecialchars($report['download_url'] ?? '#');
                                    // The database stores JSON string in dashboard_data, so no need to json_encode it again
                                    $reportDataJson = htmlspecialchars($report['dashboard_data'] ?? '[]', ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div
                                        class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-200 flex flex-col pt-5">
                                        <div class="px-5 mb-4 flex-grow flex items-center justify-between">
                                            <h3 class="text-lg font-semibold text-gray-800"><?php echo $reportDate; ?></h3>
                                            <svg class="w-6 h-6 text-acad-blue opacity-50" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                </path>
                                            </svg>
                                        </div>
                                        <div class="bg-gray-50 p-4 border-t border-gray-100 flex gap-2">
                                            <button onclick="openDashboard(this)" data-report="<?php echo $reportDataJson; ?>" data-url="<?php echo $downloadUrl; ?>" data-date="<?php echo $reportDate; ?>"
                                                class="flex-1 bg-acad-blue hover:bg-blue-900 text-white text-sm font-medium py-2 px-2 rounded text-center transition duration-150 shadow-sm whitespace-nowrap">
                                                Dashboard
                                            </button>
                                            <button onclick="openFolder('<?php echo $downloadUrl; ?>')"
                                                class="flex-1 bg-white hover:bg-gray-100 text-gray-700 text-sm font-medium py-2 px-2 rounded border border-gray-300 text-center transition duration-150 shadow-sm flex items-center justify-center whitespace-nowrap">
                                                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                                </svg>
                                                Open Folder
                                            </button>
                                            <button onclick="confirmDelete('<?php echo $reportId; ?>')"
                                                class="flex-shrink-0 bg-white hover:bg-red-50 text-red-600 hover:text-red-800 border border-red-200 text-sm font-medium py-2 px-3 rounded transition duration-150 shadow-sm flex items-center justify-center"
                                                aria-label="Delete Record" title="Delete Record">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="delete-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                    onclick="closeDeleteModal()"></div>

                <!-- Modal Panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Report
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to permanently delete this
                                        report and its Excel file?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirm-delete-btn"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors">Delete</button>
                        <button type="button" onclick="closeDeleteModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        let recordToDeleteId = null;

        function confirmDelete(id) {
            recordToDeleteId = id;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            recordToDeleteId = null;
            document.getElementById('delete-modal').classList.add('hidden');
        }

        document.getElementById('confirm-delete-btn').addEventListener('click', async function () {
            if (!recordToDeleteId) return;

            const btn = this;
            const originalText = btn.innerText;
            btn.innerText = 'Deleting...';
            btn.disabled = true;

            try {
                const response = await fetch('delete_record.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: recordToDeleteId })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    // Reload page to reflect changes
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Could not delete record.'));
                    btn.innerText = originalText;
                    btn.disabled = false;
                    closeDeleteModal();
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred during deletion.');
                btn.innerText = originalText;
                btn.disabled = false;
                closeDeleteModal();
            }
        });

        function openDashboard(buttonElement) {
            try {
                // Get the raw data array from the data-report attribute
                const rawData = buttonElement.getAttribute('data-report');
                const url = buttonElement.getAttribute('data-url');
                const dateText = buttonElement.getAttribute('data-date');

                // Save it to sessionStorage
                sessionStorage.setItem('analyzedSummaryData', rawData);
                if(url) sessionStorage.setItem('downloadUrl', url);
                if(dateText) sessionStorage.setItem('reportDate', dateText);
                
                // Redirect back to preview.php as dashboard
                window.location.href = 'preview.php';
            } catch (e) {
                console.error("Error saving report data to sessionStorage:", e);
                alert("There was an error loading the dashboard data. Please try again.");
            }
        }

        function openFolder(url) {
            fetch('open_folder.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filepath: url })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') {
                    console.error('Failed to open folder:', data.message);
                }
            })
            .catch(err => console.error('Fetch error:', err));
        }
    </script>
</body>

</html>