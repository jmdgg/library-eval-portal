<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php?error=unauthorized");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Export - AUF Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50 flex">

    <?php require_once 'sidebar.php'; ?>

    <div class="flex-1 ml-64 [.collapsed-sidebar_&]:ml-20 transition-all duration-300 min-h-screen">

        <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center px-8">
            <h1 class="text-xl font-bold text-gray-800">Analytics & Reports</h1>
        </header>

        <main class="p-8 space-y-6">

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">

                <div id="export-overlay"
                    class="hidden absolute inset-0 bg-white/90 backdrop-blur-sm z-10 flex flex-col items-center justify-center transition-all duration-300">
                    <div class="w-10 h-10 border-4 border-gray-200 border-t-green-600 rounded-full animate-spin mb-3">
                    </div>
                    <p class="text-sm font-bold text-gray-800">Generating Master Template...</p>
                    <p class="text-xs text-gray-500 mt-1">Crunching database records, please wait.</p>
                </div>

                <h2 class="text-lg font-bold text-gray-800 mb-2">Export to Master Template</h2>
                <p class="text-sm text-gray-500 mb-4">Select a date range to compile evaluation data and inject it
                    directly into the library's master .xlsx reporting template.</p>

                <form id="export-form" action="generate_excel.php" method="GET"
                    class="flex flex-wrap items-center gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200 relative z-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-600">From:</span>
                        <select name="start_month"
                            class="text-sm border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:ring-blue-500">
                            <option value="JANUARY">January</option>
                            <option value="FEBRUARY">February</option>
                            <option value="MARCH">March</option>
                            <option value="APRIL" selected>April</option>
                            <option value="MAY">May</option>
                            <option value="JUNE">June</option>
                            <option value="JULY">July</option>
                            <option value="AUGUST">August</option>
                            <option value="SEPTEMBER">September</option>
                            <option value="OCTOBER">October</option>
                            <option value="NOVEMBER">November</option>
                            <option value="DECEMBER">December</option>
                        </select>
                        <input type="number" name="start_year" value="<?php echo date('Y'); ?>"
                            class="text-sm w-24 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:ring-blue-500"
                            required>
                    </div>

                    <span class="text-gray-400 font-bold px-2">TO</span>

                    <div class="flex items-center gap-2">
                        <select name="end_month"
                            class="text-sm border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:ring-blue-500">
                            <option value="JANUARY">January</option>
                            <option value="FEBRUARY">February</option>
                            <option value="MARCH">March</option>
                            <option value="APRIL" selected>April</option>
                            <option value="MAY">May</option>
                            <option value="JUNE">June</option>
                            <option value="JULY">July</option>
                            <option value="AUGUST">August</option>
                            <option value="SEPTEMBER">September</option>
                            <option value="OCTOBER">October</option>
                            <option value="NOVEMBER">November</option>
                            <option value="DECEMBER">December</option>
                        </select>
                        <input type="number" name="end_year" value="<?php echo date('Y'); ?>"
                            class="text-sm w-24 border-gray-300 rounded-lg shadow-sm py-2 px-3 focus:ring-blue-500"
                            required>
                    </div>

                    <button type="submit" id="export-btn"
                        class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition shadow-sm ml-auto">
                        Download Master Report (.xlsx)
                    </button>
                </form>
            </div>

            <script>
                document.getElementById('export-form').addEventListener('submit', async function (e) {
                    // 1. Stop the browser from navigating away
                    e.preventDefault();

                    const form = this;
                    const overlay = document.getElementById('export-overlay');
                    const submitBtn = document.getElementById('export-btn');

                    // 2. Lock the UI and show the loading spinner
                    overlay.classList.remove('hidden');
                    submitBtn.disabled = true;

                    try {
                        // 3. Gather form data and create the URL query string
                        const formData = new FormData(form);
                        const queryString = new URLSearchParams(formData).toString();

                        // 4. Call generate_excel.php in the background
                        const response = await fetch(`generate_excel.php?${queryString}`, {
                            method: 'GET'
                        });

                        if (!response.ok) {
                            throw new Error('Server returned an error. Please check your data.');
                        }

                        // 5. Extract the actual filename PHP generated from the headers
                        let filename = 'EVAL_REPORT.xlsx'; // Fallback
                        const disposition = response.headers.get('Content-Disposition');
                        if (disposition && disposition.indexOf('filename=') !== -1) {
                            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                            if (matches != null && matches[1]) {
                                filename = matches[1].replace(/['"]/g, '');
                            }
                        }

                        // 6. Convert the binary response into a downloadable Blob
                        const blob = await response.blob();
                        const downloadUrl = window.URL.createObjectURL(blob);

                        // 7. Create a temporary invisible link, click it, and destroy it
                        const a = document.createElement('a');
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();

                        // Clean up
                        window.URL.revokeObjectURL(downloadUrl);
                        a.remove();

                    } catch (error) {
                        alert('Download Failed: ' + error.message);
                    } finally {
                        // 8. Always hide the loading UI and unlock the button, even if it fails
                        overlay.classList.add('hidden');
                        submitBtn.disabled = false;
                    }
                });
            </script>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 min-h-[400px]">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Interactive Performance Analytics</h2>

                <div id="chart-container"
                    class="w-full h-80 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                    <p class="text-gray-500 font-medium">Classmate's Chart.js components will render here.</p>
                </div>
            </div>

        </main>
    </div>

</body>

</html>