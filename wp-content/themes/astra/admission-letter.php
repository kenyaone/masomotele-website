<?php
/**
 * Template Name: MTTI Admission Letter
 * Description: Display and manage admission letters
 */

// Get application ID from clean URL (/admission/ID/)
$appId = get_query_var('mtti_admission_id', '');

if (empty($appId)) {
    // Fallback to query parameter for backward compatibility
    $appId = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
}

$applicationData = null;
$admissionHtml = null;

if (!empty($appId)) {
    // Load application data
    $appDir = dirname(__FILE__) . '/../../mtti-applications';
    $jsonFile = $appDir . '/' . $appId . '.json';
    $htmlFile = $appDir . '/' . $appId . '_admission.html';

    if (file_exists($jsonFile)) {
        $applicationData = json_decode(file_get_contents($jsonFile), true);
    }

    if (file_exists($htmlFile)) {
        $admissionHtml = file_get_contents($htmlFile);
    }
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTTI Admission Letter<?php echo !empty($appId) ? ' - ' . $appId : ''; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #1b5e20;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            margin: 5px 0;
        }

        .letter-container {
            background: white;
            padding: 40px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .actions {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #764ba2;
        }

        .btn-primary {
            background: #1b5e20;
        }

        .btn-primary:hover {
            background: #145a1f;
        }

        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .letter-container {
                padding: 20px;
            }

            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }

        @page {
            margin: 15mm 20mm;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .header,
            .actions {
                display: none;
            }

            .letter-container {
                box-shadow: none;
                padding: 20px 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>MTTI Admission Letter Portal</h1>
            <p>View, download, and manage your admission letter</p>
        </div>

        <?php if (empty($appId)): ?>
            <div class="error">
                ❌ No admission ID provided. Please use the link from your email or WhatsApp message to view your admission letter.
            </div>
        <?php elseif (empty($applicationData)): ?>
            <div class="error">
                ❌ Admission letter not found. Please verify your admission ID and try again.
            </div>
        <?php else: ?>
            <div class="success">
                ✅ Admission letter for <strong><?php echo $applicationData['firstName'] . ' ' . $applicationData['lastName']; ?></strong> (ID: <?php echo $appId; ?>)
            </div>

            <div class="letter-container">
                <?php
                if (!empty($admissionHtml)) {
                    // Output the generated HTML letter
                    echo $admissionHtml;
                } else {
                    // Fallback - generate letter on the fly
                    echo generateAdmissionHTML($applicationData);
                }
                ?>
            </div>

            <div class="actions">
                <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Save as PDF</button>
                <button class="btn" onclick="shareViaWhatsApp()">💬 Share via WhatsApp</button>
                <button class="btn" onclick="downloadAsText()">📥 Download</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function shareViaWhatsApp() {
            const appId = '<?php echo $appId; ?>';
            const url = window.location.href;
            const text = "Check out my MTTI Admission Letter: " + url;

            const phone = '254712464936'; // MTTI contact
            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(text)}`);
        }

        function downloadAsText() {
            const appId = '<?php echo $appId; ?>';
            const element = document.querySelector('.letter-container');
            const text = element.innerText;

            const blob = new Blob([text], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = appId + '_admission.txt';
            link.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

<?php

function generateAdmissionHTML($data) {
    $coursesText = implode(', ', $data['courses']);
    $admissionDate = date('d/m/Y', strtotime($data['submittedAt']));
    $logoUrl = site_url() . '/mtti-logo.jpeg';

    $hasHealthCare = false;
    foreach ($data['courses'] as $course) {
        if (stripos($course, 'Health') !== false || stripos($course, 'HCS') !== false || stripos($course, 'CNA') !== false || stripos($course, 'Care Giver') !== false || stripos($course, 'Caregiver') !== false) {
            $hasHealthCare = true;
            break;
        }
    }
    $germanNote = $hasHealthCare
        ? '<div style="background: #fff8e1; border-left: 4px solid #f9a825; padding: 12px 15px; margin-top: 12px; border-radius: 3px;">
            <p style="margin: 0; font-size: 14px;">&#127467;&#127479; <strong>BONUS: Free German Language Classes Included</strong></p>
            <p style="margin: 6px 0 0 0; font-size: 13px; color: #555;">As part of your Health Care Support Services programme, you will receive <strong>FREE German Language classes</strong> running concurrently throughout the 24-week course at no extra cost. This prepares you for health care opportunities in German-speaking countries.</p>
           </div>'
        : '';

    return <<<HTML
    <div class="admission-letter">
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1b5e20; padding-bottom: 20px;">
            <img src="{$logoUrl}" alt="MTTI Logo" style="max-height: 80px; margin-bottom: 15px;">
            <h1 style="color: #1b5e20; margin: 10px 0;">MTTI Eldoret</h1>
            <p style="color: #666; margin: 5px 0;"><strong>Masomotele Technical Training Institute</strong></p>
            <p style="color: #666; font-size: 14px;">📍 Sagaas Center, 4th Floor, Eldoret, Kenya</p>
            <p style="color: #666; font-size: 13px;">🏢 TVETA-Accredited Technical Training Center</p>
        </div>

        <h2 style="color: #1b5e20; text-align: center;">ADMISSION LETTER</h2>

        <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #1b5e20; margin: 20px 0; font-weight: bold;">
            📋 Admission ID: <strong>{$data['applicationId']}</strong>
            <br>Date Issued: <strong>{$admissionDate}</strong>
        </div>

        <p>Dear <strong>{$data['firstName']} {$data['lastName']}</strong>,</p>

        <p>Congratulations! We are pleased to inform you that your application to Masomotele Technical Training Institute (MTTI) has been <strong>RECEIVED AND REGISTERED</strong>.</p>

        <h3 style="color: #1b5e20; margin-top: 30px;">Your Information:</h3>
        <table style="width: 100%; margin: 15px 0;">
            <tr>
                <td style="padding: 8px; font-weight: bold; color: #1b5e20; width: 200px;">Full Name:</td>
                <td style="padding: 8px;">{$data['firstName']} {$data['lastName']}</td>
            </tr>
            <tr style="background: #f5f5f5;">
                <td style="padding: 8px; font-weight: bold; color: #1b5e20;">Email:</td>
                <td style="padding: 8px;">{$data['email']}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold; color: #1b5e20;">WhatsApp:</td>
                <td style="padding: 8px;">{$data['phone']}</td>
            </tr>
            <tr style="background: #f5f5f5;">
                <td style="padding: 8px; font-weight: bold; color: #1b5e20;">Date of Birth:</td>
                <td style="padding: 8px;">{$data['dob']}</td>
            </tr>
            <tr>
                <td style="padding: 8px; font-weight: bold; color: #1b5e20;">Education Level:</td>
                <td style="padding: 8px;">{$data['qualification']}</td>
            </tr>
        </table>

        <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="color: #1b5e20; margin-top: 0;">Selected Courses:</h3>
            <p style="margin: 0; font-weight: bold;">{$coursesText}</p>
            {$germanNote}
            <p style="font-size: 13px; margin: 10px 0 0 0; color: #666;">
                <strong>Preferred Start Date:</strong> {$data['startDate']}
            </p>
        </div>

        <div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;">
            <h3 style="color: #667eea; margin-top: 0;">📌 Next Steps:</h3>
            <ol style="margin-left: 20px;">
                <li><strong>Confirm Your Enrollment:</strong> Reply to the WhatsApp message we sent you to confirm your attendance</li>
                <li><strong>Complete Payment:</strong> Pay the course fees via {$data['paymentMethod']}</li>
                <li><strong>Bring Required Documents:</strong>
                    <ul>
                        <li>Original ID or Passport</li>
                        <li>Birth Certificate (photocopy)</li>
                        <li>Proof of latest education qualification</li>
                        <li>Two passport-size photos</li>
                    </ul>
                </li>
                <li><strong>Attend Orientation:</strong> Report on your start date for course orientation</li>
            </ol>
        </div>

        <div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center;">
            <h3 style="color: #1b5e20; margin-top: 0;">📞 Need Help? Contact Us:</h3>
            <p style="margin: 10px 0;">
                <strong>WhatsApp:</strong> <a href="https://wa.me/254712464936" style="color: #667eea; text-decoration: none;">+254 712 464 936</a><br>
                <strong>Email:</strong> <a href="mailto:info@masomotele.ac.ke" style="color: #667eea; text-decoration: none;">info@masomotele.ac.ke</a><br>
                <strong>Location:</strong> Sagaas Center, 4th Floor, Eldoret, Kenya
            </p>
        </div>

        <p style="margin-top: 30px; color: #666; text-align: center;">
            <em>This is an automated admission letter issued upon successful application submission.
            For course updates and confirmation, you will receive WhatsApp messages from our team shortly.</em>
        </p>

        <div style="margin-top: 50px; padding-top: 30px; border-top: 1px solid #ddd; display: flex; justify-content: space-between;">
            <div style="width: 45%;">
                <p style="margin-bottom: 50px;"><strong>_______________________</strong></p>
                <p style="margin: 0;"><strong>Authorized Signatory</strong></p>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">Masomotele Technical Training Institute</p>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;">Date: {$admissionDate}</p>
            </div>
            <div style="width: 45%;">
                <p style="margin-bottom: 10px; color: #666; font-size: 12px;"><em>Official Stamp</em></p>
                <p style="margin: 40px 0; height: 50px; border: 1px dashed #ccc;"></p>
            </div>
        </div>

        <p style="margin-top: 40px; text-align: center; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px;">
            MTTI Eldoret - Real Skills, Real Jobs | www.masomoteletraining.co.ke<br>
            ✉️ info@masomotele.ac.ke | 📱 WhatsApp: +254 712 464 936<br>
            © 2026 Masomotele Technical Training Institute. All Rights Reserved.
        </p>
    </div>
    HTML;
}
?>
