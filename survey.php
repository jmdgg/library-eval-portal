<?php
$form_data = [
    'roles' => ['Student', 'Faculty', 'NTP', 'Alumni', 'Other Researcher', 'Other'],
    'services' => [
        'borrowing' => 'Borrowing / Renewal / Returning library material',
        'document_delivery' => 'Document Delivery (Scanned Documents)',
        'reference' => 'Reference Service (includes request for booklist, resources relative to a query/topic, etc)',
        'tutorial' => 'One-on-one Library Online Tutorial Service',
        'instruction' => 'Library Instruction Service (Class / Embedded Session)',
        'clearance' => 'Clearance Request',
        'turnitin' => 'Similarity Scanning Service (Turnitin)',
        'credentials' => 'Login Credentials (user name and password)',
        'recommendation' => 'Book Recommendation for Purchase',
        'others' => 'Others'
    ],
    'feedback_statements' => [
        'resources' => 'The library has sufficient resources for my research and information needs',
        'staff_assistance' => 'Library staff provided assistance in a timely and helpful manner',
        'process' => 'The process of borrowing, returning and renewal of library resources is convenient',
        'procedures' => 'The information/procedure provided by the library staff were easy to understand'
    ],
    'likert_options' => [
        '5' => 'Strongly Agree',
        '4' => 'Agree',
        '3' => 'Neutral',
        '2' => 'Disagree',
        '1' => 'Strongly Disagree'
    ],
    'libraries' => [
        '1' => 'Circulation Section',
        '2' => 'General Reference Section',
        '3' => 'Computer and Multimedia Services (CMS)',
        '4' => 'Health Sciences Library',
        '5' => 'Filipiniana Section',
        '6' => 'College of Business and Accountancy Library',
        '7' => 'PS Library'
    ],
    'satisfaction_options' => ['Yes', 'No'],
    'rating_options' => ['Excellent', 'Very Good', 'Good', 'Fair', 'Needs Improvement']
];
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
            background-color: #f3f4f6; /* bg-gray-100 */
        }
    </style>
</head>
<body class="text-gray-800 antialiased py-6 sm:py-10 px-4 sm:px-6">

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col items-center text-center mb-10">
        <!-- Logo placeholder could go here if needed -->
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
    <input type="email" name="email" id="email" required placeholder="e.g., x@auf.edu.ph" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-blue-500">
</div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200" placeholder="e.g., Juan Dela Cruz">
                </div>
                <div>
                    <label for="date" class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                    <input type="date" id="date" name="date" required class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200">
                </div>
                <div>
                    <label for="college" class="block text-sm font-semibold text-gray-700 mb-2">College</label>
                    <select id="college" name="college" required class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200">
                        <option value="" disabled selected>Select a College</option>
                        <option value="Integrated School (IS)">Integrated School (IS)</option>
                        <option value="College of Allied Medical Professions (CAMP)">College of Allied Medical Professions (CAMP)</option>
                        <option value="College of Arts and Sciences (CAS)">College of Arts and Sciences (CAS)</option>
                        <option value="College of Business and Accountancy (CBA)">College of Business and Accountancy (CBA)</option>
                        <option value="College of Computer Studies (CCS)">College of Computer Studies (CCS)</option>
                        <option value="College of Criminal Justice Education (CCJE)">College of Criminal Justice Education (CCJE)</option>
                        <option value="College of Engineering and Architecture (CEA)">College of Engineering and Architecture (CEA)</option>
                        <option value="College of Education (CED)">College of Education (CED)</option>
                        <option value="College of Nursing (CON)">College of Nursing (CON)</option>
                        <option value="School of Law (SOL)">School of Law (SOL)</option>
                        <option value="School of Medicine (SOM)">School of Medicine (SOM)</option>
                        <option value="Graduate School (GS)">Graduate School (GS)</option>
                    </select>
                </div>
                <div>
                    <label for="department" class="block text-sm font-semibold text-gray-700 mb-2">Department</label>
                    <select id="department" name="department" required disabled class="w-full rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200">
                        <option value="" disabled selected>Select a Department</option>
                    </select>
                    <input type="text" id="department_other" name="department_other" class="w-full mt-3 rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200" placeholder="Please specify your program..." style="display: none;">
                </div>
            </div>

            <div class="pt-2">
                <label class="block text-sm font-semibold text-gray-700 mb-4">Please select the category that best describe your role in Angeles University Foundation.</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($form_data['roles'] as $role): ?>
                    <label class="block cursor-pointer relative h-full">
                        <div class="w-full h-full flex items-center justify-center text-center px-4 py-3.5 rounded-xl border-2 border-gray-100 bg-gray-50 text-gray-600 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:text-blue-700 hover:border-blue-300 hover:bg-blue-50/50 transition-all font-medium text-sm shadow-sm element-press">
                            <input type="radio" name="role" value="<?php echo htmlspecialchars($role); ?>" class="sr-only" required>
                            <?php echo htmlspecialchars($role); ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <!-- Dynamic text input for 'Other' -->
                <div class="mt-4 hidden" id="role_other_container">
                    <input type="text" id="role_other" name="role_other" placeholder="Please specify your role" class="w-full sm:w-1/2 rounded-xl border-gray-200 border bg-gray-50 p-3.5 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition duration-200 text-sm">
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
                <label for="department_id" class="block text-sm font-semibold text-gray-700 mb-4">Which Library accommodated your request?</label>
                <div class="relative">
                    <select id="department_id" name="department_id" required class="w-full appearance-none rounded-xl border-2 border-gray-100 bg-gray-50 px-5 py-4 text-gray-700 font-medium focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-600 outline-none transition-all cursor-pointer shadow-sm">
                        <option value="" disabled selected>Select a Library Department</option>
                        <?php foreach ($form_data['libraries'] as $id => $name): ?>
                        <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($name); ?></option>
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
                    <?php foreach ($form_data['services'] as $key => $service): ?>
                    <label class="block cursor-pointer relative h-full group">
                        <div class="h-full flex items-start p-4 rounded-xl border-2 border-gray-100 bg-gray-50 text-gray-600 has-[:checked]:bg-blue-50 has-[:checked]:border-blue-600 has-[:checked]:text-blue-800 hover:border-blue-300 hover:bg-blue-50/50 transition-all shadow-sm">
                            <input type="checkbox" name="services[]" value="<?php echo htmlspecialchars($key); ?>" class="sr-only service-checkbox">
                            <div class="flex-shrink-0 mt-0.5 mr-3 w-5 h-5 rounded border-2 border-gray-300 group-has-[:checked]:bg-blue-600 group-has-[:checked]:border-blue-600 flex items-center justify-center transition-colors">
                                <svg class="w-3.5 h-3.5 text-white opacity-0 group-has-[:checked]:opacity-100 scale-50 group-has-[:checked]:scale-100 transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="font-medium text-sm leading-snug"><?php echo htmlspecialchars($service); ?></span>
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
            
            <?php
            $likert_colors = [
                '5' => 'border-emerald-200 bg-emerald-50/50 text-emerald-700 has-[:checked]:bg-emerald-600 has-[:checked]:border-emerald-600 has-[:checked]:text-white hover:bg-emerald-100 hover:border-emerald-300',
                '4' => 'border-teal-200 bg-teal-50/50 text-teal-700 has-[:checked]:bg-teal-500 has-[:checked]:border-teal-500 has-[:checked]:text-white hover:bg-teal-100 hover:border-teal-300',
                '3' => 'border-slate-200 bg-slate-50/50 text-slate-600 has-[:checked]:bg-slate-500 has-[:checked]:border-slate-500 has-[:checked]:text-white hover:bg-slate-100 hover:border-slate-300',
                '2' => 'border-orange-200 bg-orange-50/50 text-orange-700 has-[:checked]:bg-orange-500 has-[:checked]:border-orange-500 has-[:checked]:text-white hover:bg-orange-100 hover:border-orange-300',
                '1' => 'border-rose-200 bg-rose-50/50 text-rose-700 has-[:checked]:bg-rose-600 has-[:checked]:border-rose-600 has-[:checked]:text-white hover:bg-rose-100 hover:border-rose-300'
            ];
            ?>

            <div class="space-y-8">
                <?php foreach ($form_data['feedback_statements'] as $key => $statement): ?>
                <fieldset class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 sm:p-6 relative group overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-200 group-hover:bg-blue-400 transition-colors"></div>
                    <legend class="text-base font-semibold text-gray-800 mb-5 pl-2 leading-relaxed"><?php echo htmlspecialchars($statement); ?></legend>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <?php foreach ($form_data['likert_options'] as $value => $label): ?>
                        <label class="block cursor-pointer relative h-full">
                            <div class="flex flex-col items-center justify-center py-3 px-2 rounded-xl border transition-all text-center h-full shadow-sm element-press <?php echo isset($likert_colors[$value]) ? $likert_colors[$value] : ''; ?>">
                                <input type="radio" name="feedback[<?php echo htmlspecialchars($key); ?>]" value="<?php echo htmlspecialchars($value); ?>" class="sr-only" required>
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
                    <?php foreach ($form_data['satisfaction_options'] as $option): ?>
                    <label class="block cursor-pointer relative h-full">
                        <div class="flex items-center justify-center px-6 py-4 rounded-xl border-2 border-gray-200 bg-white text-gray-600 has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white hover:border-gray-300 hover:bg-gray-50 transition-all font-bold text-lg shadow-sm element-press">
                            <input type="radio" name="satisfied" value="<?php echo htmlspecialchars($option); ?>" class="sr-only" required>
                            <?php if($option == 'Yes'): ?>
                                <svg class="w-5 h-5 mr-2 opacity-70" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 mr-2 opacity-70" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($option); ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            $rating_colors = [
                'Excellent' => 'border-emerald-200 bg-emerald-50/50 text-emerald-700 has-[:checked]:bg-emerald-600 has-[:checked]:border-emerald-600 has-[:checked]:text-white hover:bg-emerald-100 hover:border-emerald-300',
                'Very Good' => 'border-teal-200 bg-teal-50/50 text-teal-700 has-[:checked]:bg-teal-500 has-[:checked]:border-teal-500 has-[:checked]:text-white hover:bg-teal-100 hover:border-teal-300',
                'Good' => 'border-slate-200 bg-slate-50/50 text-slate-600 has-[:checked]:bg-slate-500 has-[:checked]:border-slate-500 has-[:checked]:text-white hover:bg-slate-100 hover:border-slate-300',
                'Fair' => 'border-orange-200 bg-orange-50/50 text-orange-700 has-[:checked]:bg-orange-500 has-[:checked]:border-orange-500 has-[:checked]:text-white hover:bg-orange-100 hover:border-orange-300',
                'Needs Improvement' => 'border-rose-200 bg-rose-50/50 text-rose-700 has-[:checked]:bg-rose-600 has-[:checked]:border-rose-600 has-[:checked]:text-white hover:bg-rose-100 hover:border-rose-300'
            ];
            ?>
            <div>
                <label class="block text-base font-bold text-gray-800 mb-4 px-1">Overall, how would you rate the library service/s we provide?</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <?php foreach ($form_data['rating_options'] as $option): ?>
                    <label class="block cursor-pointer relative h-full">
                        <div class="h-full flex items-center justify-center text-center px-2 py-4 rounded-xl border-2 transition-all font-semibold text-sm shadow-sm element-press <?php echo isset($rating_colors[$option]) ? $rating_colors[$option] : 'border-gray-100 bg-gray-50 text-gray-700 has-[:checked]:bg-blue-600 has-[:checked]:border-blue-600 has-[:checked]:text-white hover:border-gray-300'; ?>">
                            <input type="radio" name="overall_rating" value="<?php echo htmlspecialchars($option); ?>" class="sr-only" required>
                            <?php echo htmlspecialchars($option); ?>
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
    /* Custom utility for active press state on labels */
    .element-press:active > div {
        transform: scale(0.98);
    }
    
    /* Make the svg checkmark match the color of the text for un-checked state */
    input.peer:not(:checked) ~ div .opacity-0 {
        /* hide it entirely, managed by tailwind classes */
    }
    
    /* Shimmer animation keyframes for the button */
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle "Other" input field for role
        const roleRadios = document.querySelectorAll('input[name="role"]');
        const roleOtherContainer = document.getElementById('role_other_container');
        const roleOtherInput = document.getElementById('role_other');
        
        roleRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                if(e.target.value === 'Other') {
                    roleOtherContainer.classList.remove('hidden');
                    roleOtherInput.setAttribute('required', 'required');
                    // Add slight delay to focus for smooth transition
                    setTimeout(() => roleOtherInput.focus(), 50);
                } else {
                    roleOtherContainer.classList.add('hidden');
                    roleOtherInput.removeAttribute('required');
                }
            });
        });

        // Checkbox validation - at least one service must be selected
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input.service-checkbox');
            let isChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            if(!isChecked) {
                e.preventDefault();
                alert('Please select at least one service you availed from the Library.');
                
                // Scroll to the services section so user can see what they missed
                document.getElementById('services_container').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                
                // Add a temporary highlight effect
                const container = document.getElementById('services_container');
                container.classList.add('ring-2', 'ring-red-500', 'ring-offset-4', 'rounded-xl');
                setTimeout(() => {
                    container.classList.remove('ring-2', 'ring-red-500', 'ring-offset-4', 'rounded-xl');
                }, 2000);
            }
        });

        // Set default date to today for convenience, while allowing override
        const dateInput = document.getElementById('date');
        if(!dateInput.value) {
            dateInput.valueAsDate = new Date();
        }

        // Cascading dropdown logic for College and Department
        const collegeData = {
            "Integrated School (IS)": ["Pre-Kinder", "Kindergarten", "Grade School", "Junior High School", "Senior High School"],
            "College of Allied Medical Professions (CAMP)": ["BS Medical Technology", "BS Occupational Therapy", "BS Pharmacy", "BS Clinical Pharmacy", "BS Radiologic Technology", "BS Physical Therapy", "BS Physical Therapy Professional Enhancement Program"],
            "College of Arts and Sciences (CAS)": ["AB Communication", "BS Biology", "BS Biology Three-Year Accelerated Program", "BS Psychology", "AB Psychology", "BS in Human Biology", "Straight AB Psychology - MA Psychology Program"],
            "College of Business and Accountancy (CBA)": ["BS Accountancy", "BS Management Accounting", "BS Business Administration", "BS Hospitality Management", "BS Tourism Management"],
            "College of Computer Studies (CCS)": ["Bachelor of Multimedia Arts", "BS Computer Science", "BS Information Technology"],
            "College of Criminal Justice Education (CCJE)": ["BS Criminology"],
            "College of Engineering and Architecture (CEA)": ["BS Architecture", "BS Civil Engineering", "BS Computer Engineering", "BS Electronics Engineering"],
            "College of Education (CED)": ["Bachelor of Elementary Education", "Bachelor of Secondary Education", "Bachelor of Early Childhood Education", "Bachelor of Special Needs Education", "Professional Certificate Course in Teaching"],
            "College of Nursing (CON)": ["BS Nursing"],
            "School of Law (SOL)": ["Juris Doctor", "Other"],
            "School of Medicine (SOM)": ["Doctor of Medicine", "Other"],
            "Graduate School (GS)": ["Education Programs", "Psychology Program", "Business Programs", "Information Technology Programs", "Public Health Programs", "Medical Laboratory Science Programs", "Nursing Programs", "Criminal Justice Program", "Other"]
        };

        const collegeSelect = document.getElementById('college');
        const departmentSelect = document.getElementById('department');
        const departmentOtherInput = document.getElementById('department_other');

        collegeSelect.addEventListener('change', function() {
            const selectedCollege = this.value;
            
            departmentSelect.innerHTML = '<option value="" disabled selected>Select a Department</option>';
            
            departmentOtherInput.style.display = 'none';
            departmentOtherInput.value = '';
            departmentOtherInput.removeAttribute('required');

            if (selectedCollege && collegeData[selectedCollege]) {
                departmentSelect.removeAttribute('disabled');
                
                collegeData[selectedCollege].forEach(function(dept) {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    departmentSelect.appendChild(option);
                });
            } else {
                departmentSelect.setAttribute('disabled', 'disabled');
            }
        });

        departmentSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                departmentOtherInput.style.display = 'block';
                departmentOtherInput.setAttribute('required', 'required');
            } else {
                departmentOtherInput.style.display = 'none';
                departmentOtherInput.value = '';
                departmentOtherInput.removeAttribute('required');
            }
        });
    });
</script>

</body>
</html>
