<?php
/**
 * Template Name: MTTI Application Form
 * Description: Online application form for course enrollment
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for MTTI Courses - Masomotele Technical Training Institute</title>
    <meta name="description" content="Online application form for MTTI courses. Apply for computer training, web development, cybersecurity and more from the comfort of your home.">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .form-container {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h1 {
            color: #1b5e20;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1b5e20;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        input[type="date"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group,
        .radio-group {
            margin-bottom: 15px;
        }

        .checkbox-item,
        .radio-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        input[type="checkbox"],
        input[type="radio"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }

        .checkbox-item label,
        .radio-item label {
            margin-bottom: 0;
            cursor: pointer;
            font-weight: normal;
        }

        .required {
            color: #e74c3c;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .success-message {
            display: none;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-message {
            display: none;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .admission-link {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            display: none;
        }

        .admission-link p {
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }

        .admission-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
            word-break: break-all;
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>MTTI Online Application Form</h1>
            <p>Apply for our TVETA-accredited courses from the comfort of your home</p>
        </div>

        <div class="success-message" id="successMessage">
            ✅ Application submitted successfully! Check your WhatsApp for your admission details.
        </div>

        <div class="error-message" id="errorMessage"></div>

        <form id="applicationForm" method="POST">
            <!-- Personal Information -->
            <div class="form-section">
                <div class="form-section-title">Personal Information</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">WhatsApp Number <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" placeholder="+254712345678" required>
                </div>
            </div>

            <!-- Course Selection -->
            <div class="form-section">
                <div class="form-section-title">Course Selection</div>

                <div class="form-group">
                    <label>Which course(s) are you interested in? <span class="required">*</span></label>
                    <div class="checkbox-group">
                        <p style="font-weight: bold; color: #1b5e20; margin-bottom: 10px;">SHORT-TERM COURSES (1-2 WEEKS/MONTHS)</p>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Computer and Online Essentials (2 weeks - KES 1,500)">
                            <label>Computer and Online Essentials (2 weeks - KES 1,500)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Computer Repair and Maintenance (2 months - KES 15,000)">
                            <label>Computer Repair and Maintenance (2 months - KES 15,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Mobile Phone Repair (2 months - KES 15,000)">
                            <label>Mobile Phone Repair (2 months - KES 15,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Computer Applications (2 months - KES 4,000)">
                            <label>Computer Applications (2 months - KES 4,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Social Media and Digital Marketing (2 months - KES 12,000)">
                            <label>Social Media and Digital Marketing (2 months - KES 12,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Artificial Intelligence (1 month - KES 10,000)">
                            <label>Artificial Intelligence (1 month - KES 10,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Web Design and Development (2 months - KES 12,000)">
                            <label>Web Design and Development (2 months - KES 12,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Video Editing (2 months - KES 15,000)">
                            <label>Video Editing (2 months - KES 15,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Graphic Design (2 months - KES 15,000)">
                            <label>Graphic Design (2 months - KES 15,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Programming/Coding Principles (2 months - KES 10,000)">
                            <label>Programming/Coding Principles (2 months - KES 10,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="CCTV Installation (2 months - KES 18,000)">
                            <label>CCTV Installation (2 months - KES 18,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Computer Networking (2 months - KES 15,000)">
                            <label>Computer Networking (2 months - KES 15,000)</label>
                        </div>

                        <p style="font-weight: bold; color: #1b5e20; margin-top: 15px; margin-bottom: 10px;">MEDIUM-TERM COURSES (3-6 MONTHS)</p>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Certificate in Cyber Security & Ethical Hacking (3 months - KES 30,000)">
                            <label>Certificate in Cyber Security & Ethical Hacking (3 months - KES 30,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Health Support Assistant/CNA (3-6 months - KES 29,500/term) + Free German Language">
                            <label>Health Support Assistant/CNA (3-6 months - KES 29,500/term) <strong style="color:#1b5e20;">+ Free German Language</strong></label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="Care Giver with Free Computer Essentials (3 months - KES 30,000)">
                            <label>Care Giver with Free Computer Essentials (3 months - KES 30,000)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="courses" value="German Language & Ausbildung (2 months/module - KES 24,000/module)">
                            <label>German Language & Ausbildung (2 months/module - KES 24,000/module)</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="startDate">Preferred Start Date <span class="required">*</span></label>
                    <input type="date" id="startDate" name="startDate" required>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="form-section">
                <div class="form-section-title">Payment & Registration</div>

                <div class="form-group">
                    <label for="paymentMethod">Preferred Payment Method <span class="required">*</span></label>
                    <select id="paymentMethod" name="paymentMethod" required>
                        <option value="">-- Select Payment Method --</option>
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Cash Payment">Cash Payment at MTTI Office</option>
                        <option value="Installments">Installment Plan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="terms" name="terms" required>
                        I agree to MTTI's terms and conditions <span class="required">*</span>
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="privacy" name="privacy" required>
                        I consent to receive updates via WhatsApp <span class="required">*</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn">📝 Submit Application</button>

            <div class="admission-link" id="admissionLink">
                <p><strong>Your Admission Letter Link:</strong></p>
                <p><a href="" id="admissionLinkUrl" target="_blank">Click here to view/download your admission letter</a></p>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">✅ Check your WhatsApp for updates</p>
            </div>
        </form>
    </div>

    <script>
        console.log('Form script loading...');

        const form = document.getElementById('applicationForm');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');
        const admissionLink = document.getElementById('admissionLink');

        // Debug: Check if form exists
        console.log('Form element found:', form ? 'YES' : 'NO');
        if (!form) {
            console.error('ERROR: Form not found! ID: applicationForm');
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('Form submitted!');

            // Use FormData to get all form data - this will auto-include checked boxes
            const formData = new FormData(form);

            // Get courses from FormData (FormData includes checked checkboxes automatically)
            const courses = formData.getAll('courses');
            console.log('Courses in FormData:', courses);
            console.log('Number of courses:', courses.length);

            // Validate
            if (!courses || courses.length === 0) {
                showError('❌ Please select at least one course to continue');
                errorMessage.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            console.log('Course validation passed! Selected courses:', courses);

            // Set courses as JSON and add action
            formData.set('courses', JSON.stringify(courses));
            formData.append('action', 'process_mtti_application');

            try {
                const response = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';

                    // Show admission link
                    document.getElementById('admissionLinkUrl').href = result.admissionUrl;
                    admissionLink.style.display = 'block';

                    // Scroll to success message
                    successMessage.scrollIntoView({ behavior: 'smooth' });

                    // Reset form
                    form.reset();
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 5000);
                } else {
                    showError(result.message || 'An error occurred. Please try again.');
                }
            } catch (error) {
                showError('Network error. Please check your connection and try again.');
                console.error('Error:', error);
            }
        });

        function showError(message) {
            errorMessage.textContent = '❌ ' + message;
            errorMessage.style.display = 'block';
            errorMessage.scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
