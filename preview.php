<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Dashboard Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'acad-blue': '#1e3a8a', // deep blue 900
                        'acad-gold': '#d97706', // amber 600
                        'acad-gold-light': '#fef3c7', // amber 50
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar for table container if needed */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans antialiased text-gray-900">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto bg-gray-50 p-8 w-full flex flex-col">
        <div class="max-w-7xl mx-auto w-full">

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">Evaluation Dashboard</h1>
                    <p class="text-sm text-gray-500 mt-1">Analytical Preview</p>
                </div>
                <button id="btn-export-excel"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-acad-gold hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-acad-gold transition-colors shadow-sm hidden">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    Open in File Explorer
                </button>
            </div>

            <!-- Error State (Hidden by default) -->
            <div id="error-state"
                class="hidden flex flex-col items-center justify-center py-20 bg-white rounded-lg shadow-sm border border-gray-200">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">No analyzed data found.</h2>
                <p class="text-gray-500 mb-6 text-center max-w-md">Please return to the upload page and process a file.
                </p>
                <a href="index.php"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-acad-blue hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-acad-blue transition-colors">
                    Return to Upload Page
                </a>
            </div>

            <!-- Control Panel -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8 w-full max-w-7xl">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Control Panel</h3>
                <div class="flex flex-col sm:flex-row gap-6">
                    <!-- Year Filter -->
                    <div class="flex-1">
                        <label for="filter-year" class="block text-sm font-medium text-gray-700 mb-1">1. Filter by
                            Year</label>
                        <div class="relative">
                            <select id="filter-year"
                                class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-acad-blue focus:border-acad-blue sm:text-sm rounded-md border appearance-none text-gray-900 leading-5">
                                <option value="" disabled selected>Select Year</option>
                                <option value="2026">2026</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- Month Filter -->
                    <div class="flex-1">
                        <label for="filter-month" class="block text-sm font-medium text-gray-700 mb-1">2. Filter by
                            Month</label>
                        <div class="relative">
                            <select id="filter-month" disabled
                                class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-acad-blue focus:border-acad-blue sm:text-sm rounded-md border appearance-none text-gray-900 leading-5 bg-gray-100 disabled:opacity-50">
                                <option value="" disabled selected>Select Month</option>
                                <option value="01">Jan</option>
                                <option value="02">Feb</option>
                                <option value="03">Mar</option>
                                <option value="04">Apr</option>
                                <option value="05">May</option>
                                <option value="06">Jun</option>
                                <option value="07">Jul</option>
                                <option value="08">Aug</option>
                                <option value="09">Sep</option>
                                <option value="10">Oct</option>
                                <option value="11">Nov</option>
                                <option value="12">Dec</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- Branch Filter -->
                    <div class="flex-1">
                        <label for="filter-branch" class="block text-sm font-medium text-gray-700 mb-1">3. Filter by
                            Branch</label>
                        <div class="relative">
                            <select id="filter-branch" disabled
                                class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-acad-blue focus:border-acad-blue sm:text-sm rounded-md border appearance-none text-gray-900 leading-5 bg-gray-100 disabled:opacity-50">
                                <option value="" disabled selected>Select Branch</option>
                                <option value="OVERALL">Overall Summary</option>
                                <option value="CIRC">CIRC</option>
                                <option value="GEN REF">GEN REF</option>
                                <option value="CMS">CMS</option>
                                <option value="HSL">HSL</option>
                                <option value="FILNA">FILNA</option>
                                <option value="CBA">CBA</option>
                                <option value="PS">PS</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State Status Message -->
            <div id="status-message"
                class="bg-gray-100 text-gray-700 text-center py-10 px-6 rounded-lg border border-gray-300 shadow-sm w-full max-w-7xl mb-8 flex flex-col items-center justify-center">
                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg font-medium">Please select a Year and Month to view the evaluation preview.</p>
            </div>

            <!-- Dashboard Content -->
            <div id="dashboard-content"
                class="hidden space-y-8 bg-white p-8 rounded-lg shadow-sm border border-gray-200 text-black w-full max-w-7xl">

                <div class="text-center mb-8">
                    <h2 class="text-xl font-bold uppercase tracking-wide text-gray-900">SUMMARY OF LIBRARY SERVICE
                        EVALUATION</h2>
                    <h3 id="report-date-display" class="text-md font-bold italic uppercase mt-1 text-gray-800">DECEMBER
                        2022
                    </h3>
                </div>

                <!-- Dashboard Visuals -->
                <div id="dashboard-visuals" class="mb-10 grid grid-cols-1 lg:grid-cols-3 gap-6 hidden">
                    <div class="space-y-6 lg:col-span-1">
                        <div
                            class="bg-blue-50 p-6 rounded-lg border border-blue-200 shadow-sm flex flex-col items-center justify-center h-32">
                            <p class="text-sm font-semibold text-blue-800 uppercase text-center mb-2">Total Respondents
                            </p>
                            <p id="kpi-respondents" class="text-3xl font-bold text-blue-900">-</p>
                        </div>
                        <div id="card-part2"
                            class="p-6 rounded-lg border border-gray-200 border-t-4 shadow-sm flex flex-col items-center justify-center h-32 bg-gray-50">
                            <p class="text-sm font-semibold text-gray-800 uppercase text-center mb-2">Overall
                                Satisfaction
                            </p>
                            <div class="flex items-center space-x-2 mt-1">
                                <p id="score-part2" class="text-3xl font-bold text-gray-900">-</p>
                                <span id="label-part2"
                                    class="px-2 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-gray-100 text-gray-700 hidden"></span>
                            </div>
                        </div>
                        <div id="card-part3"
                            class="p-6 rounded-lg border border-gray-200 border-t-4 shadow-sm flex flex-col items-center justify-center h-32 bg-gray-50">
                            <p class="text-sm font-semibold text-gray-800 uppercase text-center mb-2">Overall Rating</p>
                            <div class="flex items-center space-x-2 mt-1">
                                <p id="score-part3" class="text-3xl font-bold text-gray-900">-</p>
                                <span id="label-part3"
                                    class="px-2 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-gray-100 text-gray-700 hidden"></span>
                            </div>
                        </div>
                    </div>
                    <div
                        class="lg:col-span-2 bg-white p-4 rounded-lg border border-gray-200 shadow-sm min-h-[300px] flex flex-col relative w-full">
                        <div class="flex justify-end mb-2">
                            <select id="chart-metric-select"
                                class="block pl-3 pr-10 py-1.5 text-sm border-gray-300 focus:outline-none focus:ring-acad-blue focus:border-acad-blue rounded-md border text-gray-700 bg-gray-50 cursor-pointer">
                                <option value="part1">Part I (Mean Rating)</option>
                                <option value="part2">Part II (Overall Satisfaction)</option>
                                <option value="part3" selected>Part III (Overall Rating)</option>
                            </select>
                        </div>
                        <div class="flex-grow w-full relative min-h-[250px]">
                            <canvas id="reporting-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Automated Service Analysis -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-10 w-full relative hidden"
                    id="automated-analysis-container">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 text-acad-gold mr-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Automated Service Analysis
                        </h3>
                        <button onclick="copyAnalysisText(this)"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-acad-blue transition-colors">
                            <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                </path>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <p id="analysis-text" class="text-gray-700 text-lg leading-relaxed">
                        Data loaded successfully. The system detects responses across evaluated branches. Please
                        implement
                        an LLM analysis generation endpoint here to populate detailed programmatic service insights
                        based on
                        the selected filters.
                    </p>
                </div>

                <!-- PART I -->
                <div>
                    <h4 class="font-bold text-gray-900 mb-2">PART I</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse border border-black text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-32">
                                        Sections / Branch<br>Library</th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-24">
                                        Respondents</th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-40">
                                        The library has<br>sufficient resources<br>for my research and<br>information
                                        needs
                                    </th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-40">
                                        Library staff provided<br>assistance in a timely and<br>helpful manner</th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-40">
                                        The process of<br>borrowing, returning<br>and renewal of library<br>resources is
                                    </th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-40">
                                        The information/procedure<br>provided by the library<br>staff were easy
                                        to<br>understand</th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-bold">
                                        MEAN RATING</th>
                                </tr>
                            </thead>
                            <tbody id="part1-table-body">
                                <!-- JS injected -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PART II -->
                <div class="mt-8">
                    <h4 class="font-bold text-gray-900 mb-2">PART II</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full max-w-2xl border-collapse border border-black text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-48">
                                        Sections / Branch<br>Library</th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal">
                                        Are you satisfied with the library<br>service you have received?</th>
                                </tr>
                            </thead>
                            <tbody id="part2-table-body">
                                <!-- JS injected -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PART III -->
                <div class="mt-8">
                    <h4 class="font-bold text-gray-900 mb-2">PART III</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full max-w-2xl border-collapse border border-black text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal w-48">
                                        Sections / Branch<br>Library</th>
                                    <th
                                        class="border border-black bg-[#fff2cc] px-2 py-3 text-center align-middle font-normal">
                                        Overall, how would you rate the library<br>service/s we provide?</th>
                                </tr>
                            </thead>
                            <tbody id="part3-table-body">
                                <!-- JS injected -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RATING LEGEND -->
                <div class="mt-12">
                    <div class="flex">
                        <table class="border-collapse text-sm ml-32 text-gray-900">
                            <tbody>
                                <tr>
                                    <td class="font-bold text-right pr-4 pb-1">Rating</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="text-right pr-4">Poor</td>
                                    <td>1.00-1.80</td>
                                </tr>
                                <tr>
                                    <td class="text-right pr-4">Fair</td>
                                    <td>1.90-2.60</td>
                                </tr>
                                <tr>
                                    <td class="text-right pr-4">Good</td>
                                    <td>2.70-3.40</td>
                                </tr>
                                <tr>
                                    <td class="text-right pr-4">Very Good</td>
                                    <td>3.50-4.20</td>
                                </tr>
                                <tr>
                                    <td class="text-right pr-4">Excellent</td>
                                    <td>4.30-5.0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ANALYSIS -->
                <div class="mt-8">
                    <h4 class="font-bold text-gray-900 mb-2">ANALYSIS</h4>
                    <div class="min-h-[100px] text-gray-800 text-sm">
                        [Please type your manual analysis here]
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <footer class="mt-auto pt-8 pb-4">
            <p class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> Library Service Evaluation Portal. All rights reserved.
            </p>
        </footer>
    </main>

    <script>
        function getRatingColorStyle(score) {
            const numScore = parseFloat(score);
            if (isNaN(numScore) || numScore === 0) {
                return { textClass: 'text-gray-500', bgClass: 'bg-gray-100', borderClass: 'border-gray-300', label: 'N/A' };
            }
            if (numScore >= 4.30) {
                return { textClass: 'text-green-700', bgClass: 'bg-green-100', borderClass: 'border-green-500', label: 'Excellent' };
            } else if (numScore >= 3.50) {
                return { textClass: 'text-blue-700', bgClass: 'bg-blue-100', borderClass: 'border-blue-500', label: 'Very Good' };
            } else if (numScore >= 2.70) {
                return { textClass: 'text-amber-600', bgClass: 'bg-amber-100', borderClass: 'border-amber-500', label: 'Good' };
            } else if (numScore >= 1.90) {
                return { textClass: 'text-orange-600', bgClass: 'bg-orange-100', borderClass: 'border-orange-500', label: 'Fair' };
            } else if (numScore >= 1.00) {
                return { textClass: 'text-red-700', bgClass: 'bg-red-100', borderClass: 'border-red-500', label: 'Poor' };
            } else {
                return { textClass: 'text-gray-500', bgClass: 'bg-gray-100', borderClass: 'border-gray-300', label: 'N/A' };
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const errorState = document.getElementById('error-state');
            const dataState = document.getElementById('dashboard-content');

            const part1Body = document.getElementById('part1-table-body');
            const part2Body = document.getElementById('part2-table-body');
            const part3Body = document.getElementById('part3-table-body');
            const dateDisplay = document.getElementById('report-date-display');

            // Filters logic
            const yearFilter = document.getElementById('filter-year');
            const monthFilter = document.getElementById('filter-month');
            const branchFilter = document.getElementById('filter-branch');

            const branches = ['CIRC', 'GEN REF', 'CMS', 'HSL', 'FILNA', 'CBA', 'PS'];

            const rawData = sessionStorage.getItem('analyzedSummaryData');
            // Check for the download URL and wire up the button
            const downloadUrl = sessionStorage.getItem('downloadUrl');
            const exportBtn = document.getElementById('btn-export-excel');

            if (downloadUrl) {
                exportBtn.classList.remove('hidden');
                exportBtn.addEventListener('click', () => {
                    fetch('open_folder.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ filepath: downloadUrl })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status !== 'success') {
                            alert("Could not open folder: " + data.message);
                            console.error('Failed to open folder:', data.message);
                        }
                    })
                    .catch(err => {
                        alert("Fetch execution error: " + err);
                        console.error('Fetch error:', err);
                    });
                });
            }



            const reportDate = sessionStorage.getItem('reportDate');

            if (reportDate) {
                dateDisplay.textContent = reportDate;
            }

            if (!rawData) {
                errorState.classList.remove('hidden');
                return;
            }

            let masterData = [];
            try {
                const parsed = JSON.parse(rawData);
                if (!parsed || (typeof parsed !== 'object' && !Array.isArray(parsed))) {
                    errorState.classList.remove('hidden');
                    return;
                }

                // If the root dataset is not an array, convert its keys into an array, or assume it's just one object if keys are branches
                if (Array.isArray(parsed)) {
                    masterData = parsed;
                } else {
                    masterData = [];
                    Object.keys(parsed).forEach(k => {
                        masterData.push({ ...parsed[k], branch: k });
                    });
                }
                // Rely on applyFilters to reveal dashboard-content

            } catch (e) {
                console.error("Failed to parse analyzedSummaryData", e);
                errorState.classList.remove('hidden');
                return;
            }

            // Hierarchical Filter Implementations
            function updateFiltersState() {
                if (yearFilter.value) {
                    monthFilter.disabled = false;
                    monthFilter.classList.remove('bg-gray-100', 'disabled:opacity-50');
                } else {
                    monthFilter.disabled = true;
                    monthFilter.classList.add('bg-gray-100', 'disabled:opacity-50');
                    monthFilter.value = "";
                    branchFilter.disabled = true;
                    branchFilter.classList.add('bg-gray-100', 'disabled:opacity-50');
                    branchFilter.value = "";
                }

                if (monthFilter.value) {
                    branchFilter.disabled = false;
                    branchFilter.classList.remove('bg-gray-100', 'disabled:opacity-50');
                } else {
                    branchFilter.disabled = true;
                    branchFilter.classList.add('bg-gray-100', 'disabled:opacity-50');
                    branchFilter.value = "";
                }
            }

            function applyFilters() {
                const y = yearFilter.value;
                const m = monthFilter.value;
                const b = branchFilter.value;

                const contentNode = document.getElementById('dashboard-content');
                const statusNode = document.getElementById('status-message');
                const statusText = statusNode.querySelector('p');

                if (!y || !m) {
                    contentNode.classList.add('hidden');
                    statusNode.classList.remove('hidden');
                    statusText.textContent = 'Please select a Year and Month to view the evaluation preview.';
                    return;
                }

                let subset = masterData;

                if (y) subset = subset.filter(d => d.Year && d.Year === String(y));
                if (m) subset = subset.filter(d => d.Month && d.Month === String(m));
                if (b && b !== 'OVERALL') subset = subset.filter(d => d.branch && d.branch.toUpperCase() === b.toUpperCase());

                if (!subset || subset.length === 0) {
                    contentNode.classList.add('hidden');
                    statusNode.classList.remove('hidden');
                    statusText.textContent = 'No evaluation data found for this specific period and branch.';
                    return;
                }

                statusNode.classList.add('hidden');
                contentNode.classList.remove('hidden');

                renderView(subset, b);
            }

            yearFilter.addEventListener('change', () => { updateFiltersState(); applyFilters(); });
            monthFilter.addEventListener('change', () => { updateFiltersState(); applyFilters(); });
            branchFilter.addEventListener('change', () => { updateFiltersState(); applyFilters(); });

            const chartMetricSelect = document.getElementById('chart-metric-select');
            if (chartMetricSelect) {
                chartMetricSelect.addEventListener('change', () => applyFilters());
            }

            // Initialize empty dashboard with full dataset first
            let reportChart = null;

            function formatNum(val, isOverallMean) {
                if (val === null || val === undefined || val === '' || val === '-') return '-';
                const n = parseFloat(val);
                if (isNaN(n)) return val;
                if (isOverallMean && Number.isInteger(n)) return n.toFixed(2); // "5.00" style for Overall
                return Number.isInteger(n) ? n.toString() : n.toFixed(2);
            }

            function drawTableRow(bData, branch, isOverall) {
                const respondents = bData.respondents || '-';
                const p1 = formatNum(bData.partIMean1 || bData.part1_1 || bData.part1Mean1);
                const p2 = formatNum(bData.partIMean2 || bData.part1_2 || bData.part1Mean2);
                const p3 = formatNum(bData.partIMean3 || bData.part1_3 || bData.part1Mean3);
                const p4 = formatNum(bData.partIMean4 || bData.part1_4 || bData.part1Mean4);

                let meanRating = '-';
                let meanSum = 0;
                let meanCount = 0;
                [p1, p2, p3, p4].forEach(v => {
                    if (v !== '-') {
                        meanSum += parseFloat(v);
                        meanCount++;
                    }
                });
                if (meanCount > 0) {
                    meanRating = formatNum(meanSum / meanCount);
                }

                const sat = formatNum(bData.partIISatisfaction || bData.part2, isOverall);
                const over = formatNum(bData.partIIIOverallRating || bData.part3, isOverall);

                const trClass = isOverall ? 'bg-[#fff2cc] font-bold' : '';
                const firstTdClass = 'border border-black px-2 py-1 text-left';
                const centerTdClass = 'border border-black px-2 py-1 text-center';

                const renderBadge = (val, isOvr) => {
                    if (!isOvr || val === '-') return val;
                    const st = getRatingColorStyle(val);
                    return `<span class="inline-block px-3 py-1 rounded-full text-sm font-bold shadow-sm ${st.bgClass} ${st.textClass}">${val}</span>`;
                };

                // PART 1
                const tr1 = document.createElement('tr');
                if (isOverall) tr1.className = trClass;
                tr1.innerHTML = `
                    <td class="${firstTdClass}">${branch}</td>
                    <td class="${centerTdClass}">${respondents}</td>
                    <td class="${centerTdClass}">${renderBadge(p1, isOverall)}</td>
                    <td class="${centerTdClass}">${renderBadge(p2, isOverall)}</td>
                    <td class="${centerTdClass}">${renderBadge(p3, isOverall)}</td>
                    <td class="${centerTdClass}">${renderBadge(p4, isOverall)}</td>
                    <td class="${centerTdClass} ${isOverall ? '' : 'font-bold text-[#1e3a8a]'}">${renderBadge(meanRating, isOverall)}</td>
                `;
                part1Body.appendChild(tr1);

                // PART 2
                const tr2 = document.createElement('tr');
                if (isOverall) {
                    tr2.className = trClass;
                    tr2.innerHTML = `
                        <td class="${firstTdClass} text-right">Overall Rating:</td>
                        <td class="${centerTdClass}">${renderBadge(sat, isOverall)}</td>
                    `;
                } else {
                    tr2.innerHTML = `
                        <td class="${firstTdClass}">${branch}</td>
                        <td class="${centerTdClass}">${sat}</td>
                    `;
                }
                part2Body.appendChild(tr2);

                // PART 3
                const tr3 = document.createElement('tr');
                if (isOverall) {
                    tr3.className = trClass;
                    tr3.innerHTML = `
                        <td class="${firstTdClass} text-right">Overall Rating:</td>
                        <td class="${centerTdClass}">${renderBadge(over, isOverall)}</td>
                    `;
                } else {
                    tr3.innerHTML = `
                        <td class="${firstTdClass}">${branch}</td>
                        <td class="${centerTdClass}">${over}</td>
                    `;
                }
                part3Body.appendChild(tr3);
            }

            function generateAnalysisText(rowData, branchName, month, year) {
                if (!rowData || !rowData.respondents || rowData.respondents === '-' || rowData.respondents === '0' || rowData.respondents === 0 || rowData.respondents === 'N/A') {
                    return "There is no library service feedback recorded for this section during this period.";
                }

                const p1 = formatNum(rowData.partIMean1 || rowData.part1_1 || rowData.part1Mean1);
                const p2 = formatNum(rowData.partIMean2 || rowData.part1_2 || rowData.part1Mean2);
                const p3 = formatNum(rowData.partIMean3 || rowData.part1_3 || rowData.part1Mean3);
                const p4 = formatNum(rowData.partIMean4 || rowData.part1_4 || rowData.part1Mean4);
                let p1Mean = '-';
                let mSum = 0, mC = 0;
                [p1, p2, p3, p4].forEach(v => { if (v !== '-') { mSum += parseFloat(v); mC++; } });
                if (mC > 0) p1Mean = formatNum(mSum / mC);

                const part2 = formatNum(rowData.partIISatisfaction || rowData.part2, true);
                const part3 = formatNum(rowData.partIIIOverallRating || rowData.part3, true);

                return `The ${month} ${year} Library Service Evaluation for ${branchName} shows that the library continues to provide excellent service. Based on the responses of ${rowData.respondents} respondents, the service indicators in Part I received an overall mean rating of ${p1Mean}, reflecting strong satisfaction with resources, staff assistance, borrowing procedures, and clear information. In Part II, the library obtained an overall user satisfaction rating of ${part2}. Meanwhile, Part III recorded an overall rating of ${part3}, showing that users generally perceive the services to be of very high quality.`;
            }

            function renderView(subset, selectedBranch = null) {
                part1Body.innerHTML = '';
                part2Body.innerHTML = '';
                part3Body.innerHTML = '';

                const kpiR = document.getElementById('kpi-respondents');
                document.getElementById('dashboard-visuals').classList.remove('hidden');

                function updateKPICard(prefix, scoreStr) {
                    const card = document.getElementById(`card-${prefix}`);
                    const scoreEl = document.getElementById(`score-${prefix}`);
                    const labelEl = document.getElementById(`label-${prefix}`);

                    if (!card || !scoreEl || !labelEl) return;

                    const score = parseFloat(scoreStr);
                    const style = getRatingColorStyle(score);

                    const cardBaseClass = "p-6 rounded-lg border shadow-sm flex flex-col items-center justify-center h-32 border-t-4";
                    const scoreBaseClass = "text-3xl font-bold";
                    const labelBaseClass = "px-2 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase shadow-sm";

                    if (isNaN(score) || score === 0) {
                        scoreEl.innerText = '-';
                        labelEl.classList.add('hidden');
                        card.className = `${cardBaseClass} bg-gray-50 border-gray-200`;
                        scoreEl.className = `${scoreBaseClass} text-gray-900`;
                    } else {
                        scoreEl.innerText = score.toFixed(2);
                        labelEl.innerText = style.label;
                        labelEl.className = `${labelBaseClass} bg-white opacity-90 ${style.textClass}`;
                        labelEl.classList.remove('hidden');

                        card.className = `${cardBaseClass} ${style.bgClass} ${style.borderClass}`;
                        scoreEl.className = `${scoreBaseClass} ${style.textClass}`;
                    }
                }

                if (!subset || subset.length === 0) {
                    kpiR.textContent = 'N/A';
                    updateKPICard('part2', 'N/A');
                    updateKPICard('part3', 'N/A');

                    if (reportChart) reportChart.destroy();

                    branches.forEach(b => drawTableRow({}, b, false));
                    drawTableRow({}, 'OVERALL RATING', true);
                    return;
                }

                const getBranchData = (branchName) => {
                    return subset.find(d => (d.branch && d.branch.toUpperCase() === branchName.toUpperCase())) || {};
                };

                // Draw Tables
                branches.forEach(b => drawTableRow(getBranchData(b), b, false));

                // We should also look up the special OVERALL RATING row created for this subset context (if available)
                // Wait, process.php generated an OVERALL RATING row.
                drawTableRow(getBranchData('OVERALL RATING'), 'OVERALL RATING', true);

                // Update KPIs
                let kpiDataObj = (selectedBranch && selectedBranch !== "OVERALL")
                    ? getBranchData(selectedBranch)
                    : getBranchData('OVERALL RATING');

                if (!kpiDataObj || Object.keys(kpiDataObj).length === 0) {
                    kpiR.textContent = 'N/A';
                    updateKPICard('part2', 'N/A');
                    updateKPICard('part3', 'N/A');
                } else {
                    kpiR.textContent = kpiDataObj.respondents || 'N/A';
                    updateKPICard('part2', kpiDataObj.partIISatisfaction || kpiDataObj.part2);
                    updateKPICard('part3', kpiDataObj.partIIIOverallRating || kpiDataObj.part3);
                }

                // Construct Analysis Text context
                const dText = document.getElementById('report-date-display').textContent;
                let defaultM = "Month";
                let defaultY = "Year";
                if (dText) {
                    const p = dText.split(" ");
                    if (p.length >= 2) {
                        defaultM = p[0].charAt(0).toUpperCase() + p[0].slice(1).toLowerCase();
                        defaultY = p[1];
                    }
                }
                const mapM = { "01": "January", "02": "February", "03": "March", "04": "April", "05": "May", "06": "June", "07": "July", "08": "August", "09": "September", "10": "October", "11": "November", "12": "December" };
                const mDisplay = monthFilter.value ? mapM[monthFilter.value] : defaultM;
                const yDisplay = yearFilter.value ? yearFilter.value : defaultY;
                const bDisplay = (selectedBranch && selectedBranch !== "OVERALL") ? selectedBranch : "All Branches (Overall)";

                const analysisString = generateAnalysisText(kpiDataObj, bDisplay, mDisplay, yDisplay);
                document.getElementById('analysis-text').innerText = analysisString;

                // Update Chart
                updateChart(subset);

                // Show analysis container if data exists
                document.getElementById('automated-analysis-container').classList.remove('hidden');
            }

            function updateChart(subset) {
                const ctx = document.getElementById('reporting-chart').getContext('2d');
                if (reportChart) reportChart.destroy();

                const metricSelect = document.getElementById('chart-metric-select');
                const selectedMetric = metricSelect ? metricSelect.value : 'part3';

                const labels = [];
                const dataPoints = [];

                let chartLabel = '';

                branches.forEach(b => {
                    const row = subset.find(d => d.branch && d.branch.toUpperCase() === b.toUpperCase());
                    if (row) {
                        let val = null;
                        if (selectedMetric === 'part1') {
                            const p1 = parseFloat(row.partIMean1 || row.part1_1 || row.part1Mean1);
                            const p2 = parseFloat(row.partIMean2 || row.part1_2 || row.part1Mean2);
                            const p3 = parseFloat(row.partIMean3 || row.part1_3 || row.part1Mean3);
                            const p4 = parseFloat(row.partIMean4 || row.part1_4 || row.part1Mean4);
                            let sum = 0, ptCount = 0;
                            [p1, p2, p3, p4].forEach(v => { if (!isNaN(v)) { sum += v; ptCount++; } });
                            if (ptCount > 0) val = sum / ptCount;
                            chartLabel = 'Part I (Mean Rating)';
                        } else if (selectedMetric === 'part2') {
                            val = parseFloat(row.partIISatisfaction || row.part2);
                            chartLabel = 'Part II (Overall Satisfaction)';
                        } else {
                            val = parseFloat(row.partIIIOverallRating || row.part3);
                            chartLabel = 'Part III (Overall Rating)';
                        }

                        if (val !== null && !isNaN(val)) {
                            labels.push(b);
                            dataPoints.push(val);
                        }
                    }
                });

                reportChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels.length ? labels : ['No Data'],
                        datasets: [{
                            label: chartLabel,
                            data: dataPoints.length ? dataPoints : [0],
                            backgroundColor: '#1e3a8a',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, max: 5 }
                        }
                    }
                });
            }

            // Auto-select filters based on uploaded data
            if (masterData && masterData.length > 0) {
                const firstRow = masterData[0];
                if (firstRow.Year && firstRow.Month) {
                    yearFilter.value = firstRow.Year;
                    monthFilter.value = firstRow.Month;
                    branchFilter.value = 'OVERALL';

                    // Unlock the dropdowns visually and functionally
                    updateFiltersState();
                }
            }

            // Init dashboard
            applyFilters();

            // Global generic copy function
            window.copyAnalysisText = function (btn) {
                const textNode = document.getElementById('analysis-text');
                if (textNode) {
                    const text = textNode.innerText;
                    navigator.clipboard.writeText(text).then(() => {
                        const originalHTML = btn.innerHTML;
                        btn.innerHTML = `<svg class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Copied!`;
                        setTimeout(() => { btn.innerHTML = originalHTML; }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy text: ', err);
                    });
                }
            };
        });
    </script>
</body>

</html>