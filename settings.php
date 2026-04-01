<?php
// settings.php
$ledgerPath = __DIR__ . '/history_log.json';
$totalRecords = 0;
if (file_exists($ledgerPath)) {
    $content = file_get_contents($ledgerPath);
    $data = json_decode($content, true);
    if (is_array($data)) {
        $totalRecords = count($data);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Library Service Evaluation Portal</title>
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

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans antialiased">

    <?php include 'sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto bg-gray-50 p-8 w-full flex flex-col items-center">
        <div class="w-full max-w-4xl">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">System Settings</h1>

            <div class="space-y-6">
                <!-- Card 1: System Overview -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-acad-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        System Overview
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100">
                            <div>
                                <p class="font-medium text-gray-700">Database Stat</p>
                                <p class="text-sm text-gray-500">Current volume of parsed reports in the system.</p>
                            </div>
                            <div class="text-right">
                                <span class="block text-2xl font-bold text-acad-blue"><?php echo $totalRecords; ?></span>
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Historical Records</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: The Danger Zone -->
                <div class="bg-red-50 p-6 rounded-lg shadow-sm border border-red-200 mt-6">
                    <h2 class="text-xl font-semibold text-red-700 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Factory Reset / Wipe All Data
                    </h2>
                    <p class="text-red-700 text-sm mb-6">
                        Warning: Proceeding with this action will permanently delete all historical JSON records from the ledger and physically remove every generated Excel (.xlsx) file stored on the server. This cannot be undone.
                    </p>
                    <button onclick="openWipeModal()"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-6 rounded transition duration-200 shadow shadow-red-200/50">
                        Wipe System Data
                    </button>
                </div>
            </div>

        </div>
    </main>

    <!-- Wipe Confirmation Modal -->
    <div id="wipe-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeWipeModal()"></div>
            <!-- Modal Panel -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Confirm System Wipe</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you absolutely sure you want to wipe all system data? This will zero out the records ledger and delete all exported Excel files.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-wipe-btn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        Yes, Delete Everything
                    </button>
                    <button type="button" onclick="closeWipeModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-acad-blue sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openWipeModal() {
            document.getElementById('wipe-modal').classList.remove('hidden');
        }

        function closeWipeModal() {
            document.getElementById('wipe-modal').classList.add('hidden');
        }

        document.getElementById('confirm-wipe-btn').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerText;
            btn.innerText = 'Wiping...';
            btn.disabled = true;

            try {
                const response = await fetch('api_wipe_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ confirm: true })
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    alert('System Data has been completely wiped.');
                    window.location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Could not wipe system.'));
                    btn.innerText = originalText;
                    btn.disabled = false;
                    closeWipeModal();
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred during system wipe.');
                btn.innerText = originalText;
                btn.disabled = false;
                closeWipeModal();
            }
        });
    </script>
</body>

</html>