<?php
require_once 'db_connect.php';

// Fetch options from the new 3NF lookup tables
$colleges = $pdo->query("SELECT * FROM college ORDER BY college_name")->fetchAll();
$patron_types = $pdo->query("SELECT * FROM patron_type ORDER BY patron_type_id")->fetchAll();
$library_departments = $pdo->query("SELECT * FROM library_department ORDER BY dept_name")->fetchAll();
$library_services = $pdo->query("SELECT * FROM library_service ORDER BY service_id")->fetchAll();
$questions = $pdo->query("SELECT * FROM question_metric ORDER BY question_id")->fetchAll();
$academic_departments = $pdo->query("SELECT * FROM academic_department ORDER BY dept_name")->fetchAll();

// Group departments by college for JS
$dept_mapping = [];
foreach ($academic_departments as $ad) {
    $dept_mapping[$ad['college_id']][] = [
        'id' => $ad['acad_dept_id'],
        'name' => $ad['dept_name']
    ];
}
$dept_json = json_encode($dept_mapping);

$likert_options = [
    '5' => 'Strongly Agree',
    '4' => 'Agree',
    '3' => 'Neutral',
    '2' => 'Disagree',
    '1' => 'Strongly Disagree'
];
$satisfaction_options = ['Yes' => 1, 'No' => 0]; // For DB mapping
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Service Evaluation Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="text-gray-800 antialiased py-6 sm:py-10 px-4 sm:px-6">

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col items-center text-center mb-10">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 tracking-tight">UNIVERSITY LIBRARY</h1>
        <h2 class="text-lg sm:text-xl font-semibold text-blue-700">Library Service Evaluation Form</h2>
        <p class="text-sm sm:text-base text-gray-500 mt-2 font-medium">for Higher Education Including Senior High School</p>
    </div>

    <!-- Form Container -->
    <form action="survey_submit.php" method="POST" class="bg-white shadow-xl rounded-2xl p-5 sm:p-10 space-y-12 border border-gray-100">
        
        <div class="bg-blue-50/50 border border-blue-100 text-blue-800 p-5 rounded-xl text-sm leading-relaxed shadow-sm">
            <p class="font-bold mb-2 text-blue-900 text-base">Your feedback is important to us.</p>
            <p>Please take time to fill out this evaluation form. Rest assured that the personal information you shared will be treated with strict confidentiality. It will only be used to improve our library services. Thank you for your interest and support.</p>
        </div>

        <!-- Section 1: Demographics -->
        <section class="space-y-6">
            <div class="flex items-center space-x-3 border-b border-gray-100 pb-3 mb-6">
                <span class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-sm">1</span>
                <h3 class="text-xl font-bold text-gray-800">Demographics</h3>
            </div>

            <div class="space-y-2">
                <label for="email" class="block text-sm font-semibold text-gray-700">Email Address <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" required placeholder="e.g., x@auf.edu.ph" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 outline-none transition duration-200">
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-sm font-normal text-gray-400 ml-1">(Optional)</span></label>
                    <input type="text" id="full_name" name="full_name" class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200" placeholder="e.g., Juan Dela Cruz">
                </div>
                <div>
                    <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                    <input type="date" id="date" name="date" required class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200">
                </div>
                <div class="sm:col-span-2">
                    <label for="college_id" class="block text-sm font-semibold text-gray-700 mb-2">College</label>
                    <select id="college_id" name="college_id" class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200">
                        <option value="" selected>Select a College (Optional for NTP)</option>
                        <?php foreach ($colleges as $c): ?>
                            <option value="<?php echo $c['college_id']; ?>"><?php echo htmlspecialchars($c['college_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2" id="department_container" style="display: none;">
                    <label for="acad_dept_id" class="block text-sm font-semibold text-gray-700 mb-2">Academic Department</label>
                    <select id="acad_dept_id" name="acad_dept_id" class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200">
                        <option value="" disabled selected>Select a Department</option>
                    </select>
                </div>
            </div>

            <div class="pt-2">
                <label class="block text-sm font-semibold text-gray-700 mb-4">Please select the category that best describe your role in Angeles University Foundation.</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($patron_types as $pt): ?>
                    <label class="block cursor-pointer relative h-full">
                        <div class="w-full h-full flex items-center justify-center text-center px-4 py-3.5 rounded-xl border-2 border-gray-100 bg-gray-50 text-gray-600 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:text-blue-700 hover:border-blue-300 hover:bg-blue-50/50 transition-all font-medium text-sm shadow-sm element-press">
                            <input type="radio" name="patron_type_id" value="<?php echo $pt['patron_type_id']; ?>" class="sr-only" required>
                            <?php echo htmlspecialchars($pt['type_name']); ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Section 2: Library & Services -->
        <section class="space-y-6">
            <div class="flex items-center space-x-3 border-b border-gray-100 pb-3 mb-6">
                <span class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-sm">2</span>
                <h3 class="text-xl font-bold text-gray-800">Library & Services</h3>
            </div>
            
            <div>
                <label for="lib_dept_id" class="block text-sm font-semibold text-gray-700 mb-4">Which Library accommodated your request?</label>
                <div class="relative">
                    <select id="lib_dept_id" name="lib_dept_id" required class="w-full appearance-none rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-600 outline-none transition-all cursor-pointer shadow-sm">
                        <option value="" disabled selected>Select a Library Department</option>
                        <?php foreach ($library_departments as $ld): ?>
                        <option value="<?php echo $ld['lib_dept_id']; ?>"><?php echo htmlspecialchars($ld['dept_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-blue-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
            </div>

            <div class="pt-2 relative">
                <div class="flex items-center space-x-1 mb-4">
                    <label class="block text-sm font-semibold text-gray-700">Which of the services did you avail from the Library?</label>
                    <span class="text-xs text-gray-400 font-normal ml-2">(Select all that apply)</span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="services_container">
                    <?php foreach ($library_services as $ls): ?>
                    <label class="block cursor-pointer relative h-full group">
                        <div class="h-full flex items-start p-4 rounded-xl border-2 border-gray-100 bg-gray-50 text-gray-600 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:text-blue-800 hover:border-blue-300 hover:bg-blue-50/50 transition-all shadow-sm">
                            <input type="checkbox" name="services[]" value="<?php echo $ls['service_id']; ?>" class="sr-only service-checkbox">
                            <div class="flex-shrink-0 mt-0.5 mr-3 w-5 h-5 rounded border-2 border-gray-300 group-has-[:checked]:bg-blue-600 group-has-[:checked]:border-blue-600 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5 text-white opacity-0 group-has-[:checked]:opacity-100 scale-50 group-has-[:checked]:scale-100 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="font-medium text-sm leading-snug"><?php echo htmlspecialchars($ls['service_name']); ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Section 3: Feedback Indicators -->
        <section class="space-y-6">
            <div class="flex items-center space-x-3 border-b border-gray-100 pb-3 mb-6">
                <span class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-sm">3</span>
                <h3 class="text-xl font-bold text-gray-800">Feedback Indicators</h3>
            </div>
            
            <div class="space-y-8">
                <?php foreach ($questions as $q): ?>
                <fieldset class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 sm:p-6 relative group overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-200 group-hover:bg-blue-400 transition-colors"></div>
                    <legend class="text-base font-semibold text-gray-800 mb-5 pl-2 leading-relaxed"><?php echo htmlspecialchars($q['question_text']); ?></legend>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <?php foreach ($likert_options as $value => $label): ?>
                        <label class="block cursor-pointer relative h-full">
                            <div class="flex flex-col items-center justify-center py-3 px-2 rounded-xl border border-gray-200 bg-gray-50/50 text-gray-500 has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white hover:bg-gray-100 hover:border-gray-300 transition-all text-center h-full shadow-sm element-press">
                                <input type="radio" name="feedback[<?php echo $q['question_id']; ?>]" value="<?php echo htmlspecialchars($value); ?>" class="sr-only" required>
                                <span class="font-black text-xl mb-1"><?php echo htmlspecialchars($value); ?></span>
                                <span class="text-[0.65rem] sm:text-xs font-semibold uppercase tracking-wider leading-tight text-center px-1"><?php echo htmlspecialchars($label); ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Section 4: Overall Satisfaction -->
        <section class="space-y-8">
            <div class="flex items-center space-x-3 border-b border-gray-100 pb-3 mb-6">
                <span class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-sm">4</span>
                <h3 class="text-xl font-bold text-gray-800">Overall Satisfaction</h3>
            </div>
            
            <div class="bg-gray-50/50 rounded-2xl p-6 border border-gray-100">
                <label class="block text-base font-bold text-gray-800 mb-4 px-1">Are you satisfied with the library service you have received?</label>
                <div class="grid grid-cols-2 gap-4 max-w-md mx-auto sm:mx-0">
                    <?php foreach ($satisfaction_options as $label => $val): ?>
                    <label class="block cursor-pointer relative h-full">
                        <div class="flex items-center justify-center px-6 py-4 rounded-xl border-2 border-gray-200 bg-white text-gray-600 has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white hover:border-gray-300 hover:bg-gray-50 transition-all font-bold text-lg shadow-sm element-press">
                            <input type="radio" name="is_satisfied" value="<?php echo htmlspecialchars($val); ?>" class="sr-only" required>
                            <?php if($label == 'Yes'): ?>
                                <svg class="w-5 h-5 mr-2 opacity-70" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 mr-2 opacity-70" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($label); ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Section 5: Open Ended -->
        <section class="space-y-6">
            <div class="flex items-center space-x-3 border-b border-gray-100 pb-3 mb-6">
                <span class="bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm shadow-sm">5</span>
                <h3 class="text-xl font-bold text-gray-800">Recommendations & Comments <span class="text-sm font-normal text-gray-400 ml-2">(Optional)</span></h3>
            </div>
            
            <div>
                <label for="recommendations" class="block text-sm font-semibold text-gray-700 mb-2">Are there any library services that you would like to recommend?</label>
                <textarea id="recommendations" name="recommendations" rows="3" class="w-full rounded-xl border-gray-200 border bg-gray-50 p-4 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 resize-y text-gray-800 shadow-sm" placeholder="Tell us your recommendations..."></textarea>
            </div>

            <div>
                <label for="comments" class="block text-sm font-semibold text-gray-700 mb-2">Your suggestions/comments are valuable to us in improving our services. Please feel free to write your comments below:</label>
                <textarea id="comments" name="comments" rows="4" class="w-full rounded-xl border-gray-200 border bg-gray-50 p-4 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 resize-y text-gray-800 shadow-sm" placeholder="Leave your comments here..."></textarea>
            </div>
        </section>

        <!-- Submit Button -->
        <div class="pt-6 pb-2">
            <button type="submit" class="w-full relative overflow-hidden group bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-4 px-8 rounded-xl shadow-lg transition-all focus:outline-none focus:ring-4 focus:ring-blue-500/50 text-lg uppercase tracking-wide">
                <span class="relative z-10 flex items-center justify-center">
                    Submit Evaluation
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </span>
                <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-[100%] group-hover:animate-[shimmer_1.5s_infinite]"></div>
            </button>
        </div>
    </form>
    
    <!-- Footer Watermark -->
    <div class="mt-12 mb-8 text-center">
        <p class="font-mono text-xs text-gray-400/80 uppercase tracking-widest select-none">
            AUF-Form-UL-64, August 22, 2022 - Rev. 00
        </p>
    </div>
</div>

<style>
    .element-press:active > div { transform: scale(0.98); }
    input.peer:not(:checked) ~ div .opacity-0 { }
    @keyframes shimmer { 100% { transform: translateX(100%); } }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input.service-checkbox');
            let isChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            if(!isChecked) {
                e.preventDefault();
                alert('Please select at least one service you availed from the Library.');
                document.getElementById('services_container').scrollIntoView({ behavior: 'smooth', block: 'center' });
                const container = document.getElementById('services_container');
                container.classList.add('ring-2', 'ring-red-500', 'ring-offset-4', 'rounded-xl');
                setTimeout(() => container.classList.remove('ring-2', 'ring-red-500', 'ring-offset-4', 'rounded-xl'), 2000);
            }
        });

        const dateInput = document.getElementById('date');
        if(!dateInput.value) { dateInput.valueAsDate = new Date(); }

        // Cascading dropdown logic for College and Department
        const deptMapping = <?php echo $dept_json; ?>;

        const collegeSelect = document.getElementById('college_id');
        const departmentContainer = document.getElementById('department_container');
        const departmentSelect = document.getElementById('acad_dept_id');

        collegeSelect.addEventListener('change', function() {
            const collegeId = this.value;
            
            departmentSelect.innerHTML = '<option value="" disabled selected>Select a Department</option>';

            if (collegeId && deptMapping[collegeId]) {
                departmentContainer.style.display = 'block';
                departmentSelect.setAttribute('required', 'required');
                
                deptMapping[collegeId].forEach(function(dept) {
                    const option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    departmentSelect.appendChild(option);
                });
            } else {
                departmentContainer.style.display = 'none';
                departmentSelect.removeAttribute('required');
            }
        });
    });
</script>

</body>
</html>
