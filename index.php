<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Evaluation Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
</head>

<body class="bg-gray-50 min-h-screen font-sans antialiased flex flex-col items-center justify-center p-4">

    <!-- Card Container -->
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden relative">
        
        <!-- Header -->
        <div class="bg-acad-blue text-white px-6 py-8 border-b-4 border-acad-gold text-center relative overflow-hidden">
            <h1 class="text-2xl font-bold tracking-tight relative z-10">Library Service Evaluation</h1>
            <p class="text-blue-200 text-sm mt-1 relative z-10">Data Processing Portal</p>
        </div>

        <!-- Form Section -->
        <div class="p-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-6 text-center">Upload Monthly Data</h2>
            
            <form id="upload-form" class="space-y-6">
                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">Select Google Forms CSV Dataset:</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="csv_file" id="drop-zone" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-500">.CSV files only</p>
                            </div>
                            <!-- Added onchange event to update the display text -->
                            <input type="file" name="csv_file" id="csv_file" class="hidden" accept=".csv" required onchange="updateFilename(this)">
                        </label>
                    </div>
                    <p id="file-name-display" class="mt-2 text-sm text-acad-blue text-center font-medium min-h-[20px]"></p>
                </div>
                
                <div class="flex flex-col items-center pt-2">
                    <button type="submit" id="submit-btn" class="w-full flex justify-center items-center px-4 py-2.5 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-acad-blue hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-acad-blue transition-colors">
                        Process Data
                    </button>
                    <!-- Exact element ID expected by the form submission logic -->
                    <span id="loading-msg" style="display:none;" class="mt-3 text-sm font-medium text-acad-gold animate-pulse text-center w-full">
                        Parsing file & generating report...
                    </span>
                </div>
            </form>
        </div>
        
    </div>
    
    <!-- Footer -->
    <div class="mt-8 text-center text-sm text-gray-500">
        &copy; <?php echo date('Y'); ?> Library Service Evaluation Portal. All rights reserved.
    </div>

    <script>
        // Update helper for the stylized hidden file input
        window.updateFilename = function(input) {
            const display = document.getElementById('file-name-display');
            if (input.files && input.files[0]) {
                display.textContent = 'Selected File: ' + input.files[0].name;
            } else {
                display.textContent = '';
            }
        };

        // Drag and drop mechanics
        document.addEventListener('DOMContentLoaded', () => {
            const dropZone = document.getElementById('drop-zone');
            const fileInput = document.getElementById('csv_file');

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('bg-blue-50', 'border-acad-blue');
                dropZone.classList.remove('bg-gray-50');
            });

            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                dropZone.classList.remove('bg-blue-50', 'border-acad-blue');
                dropZone.classList.add('bg-gray-50');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('bg-blue-50', 'border-acad-blue');
                dropZone.classList.add('bg-gray-50');
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    window.updateFilename(fileInput);
                }
            });
        });

        document.getElementById('upload-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = this;
            const btn = document.getElementById('submit-btn');
            const msg = document.getElementById('loading-msg');

            const formData = new FormData(form);
            btn.disabled = true;
            msg.style.display = 'inline-block';

            try {
                const response = await fetch('process.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    const result = await response.json();
                    if (result.success) {
                        // Save analyzed data to sessionStorage
                        sessionStorage.setItem('analyzedSummaryData', JSON.stringify(result.analyzedSummaryData));
                        sessionStorage.setItem('reportDate', result.reportDate || "UNKNOWN DATE");

                        // Trigger download of the generated report
                        const link = document.createElement('a');
                        link.href = 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' + result.excelBase64;
                        link.download = result.fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        // Wait a brief moment to ensure download starts before redirecting
                        setTimeout(() => {
                            window.location.href = 'preview.php';
                        }, 500);
                    } else {
                        alert("Error: " + result.error);
                        btn.disabled = false;
                        msg.style.display = 'none';
                    }
                } else {
                    const textResponse = await response.text();
                    alert("Error processing file format: " + textResponse);
                    btn.disabled = false;
                    msg.style.display = 'none';
                }
            } catch (err) {
                alert("An error occurred during submission: " + err.message);
                btn.disabled = false;
                msg.style.display = 'none';
            }
        });
    </script>

</body>

</html>