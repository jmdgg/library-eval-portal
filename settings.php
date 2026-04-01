<?php
// settings.php
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
                <!-- General Preferences -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-acad-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        General Preferences
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100">
                            <div>
                                <p class="font-medium text-gray-700">Dark Mode</p>
                                <p class="text-sm text-gray-500">Enable dark theme across the portal.</p>
                            </div>
                            <!-- Placeholder toggle component -->
                            <button
                                class="bg-gray-200 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-acad-blue focus:ring-offset-2"
                                role="switch" aria-checked="false">
                                <span aria-hidden="true"
                                    class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Data Management -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-red-200">
                    <h2 class="text-xl font-semibold text-red-700 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Data Management
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-2">
                            <div class="pr-4">
                                <p class="font-medium text-gray-700">Clear History Ledger</p>
                                <p class="text-sm text-gray-500">Permanently delete all saved evaluation records. This
                                    action cannot be undone.</p>
                            </div>
                            <button
                                class="bg-white text-red-700 hover:bg-red-50 border border-red-200 font-semibold py-2 px-4 rounded transition duration-200 shadow-sm whitespace-nowrap">
                                Clear Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

</body>

</html>