# MTTI Online Application Form - Setup Guide

## 📋 Overview

Complete online application system for MTTI with:
- ✅ Mobile-friendly application form
- ✅ Auto-generated PDF admission letters
- ✅ WhatsApp notifications
- ✅ Admin dashboard to manage applications
- ✅ Shareable admission letter links

---

## 🚀 Quick Start

### Step 1: Create Application Page in WordPress

1. Go to **WordPress Admin** → **Pages** → **Add New**
2. Set title: **"Apply to MTTI"**
3. In the template dropdown, select: **"MTTI Application Form"**
4. Set slug: **`apply`** (optional but recommended)
5. Click **Publish**

Your application form will be live at: `https://masomoteletraining.co.ke/apply/`

### Step 2: Create Admission Letter Display Page

1. Go to **WordPress Admin** → **Pages** → **Add New**
2. Set title: **"My Admission Letter"**
3. In the template dropdown, select: **"MTTI Admission Letter"**
4. Set slug: **`mtti-admission`**
5. Click **Publish**

Admission letters will be accessible at: `https://masomoteletraining.co.ke/mtti-admission/[APPLICATION-ID]`

### Step 3: Configure WhatsApp Integration

Two options:

#### Option A: Automatic WhatsApp Web (Current Setup)
- Applicants' WhatsApp number is used to generate a WhatsApp Web link
- They'll see a pre-filled message ready to send
- **Recommended for small volume**

#### Option B: API Integration (For Higher Volume)
Use Twilio, WhatsApp Business API, or similar:

```
1. Sign up for Twilio (https://www.twilio.com)
2. Get your WhatsApp Business number
3. Update process-application.php with API credentials
```

### Step 4: Access Admin Dashboard

View all applications at:
```
https://masomoteletraining.co.ke/mtti-admin-applications.php?key=MTTI2026Admin123
```

⚠️ **Change the authentication key!** Edit `mtti-admin-applications.php` and replace `MTTI2026Admin123` with a secure password.

---

## 📁 Files Created

```
/wp-content/themes/
├── application-form.php          # Application form template
├── process-application.php        # Form processor & PDF generator
└── admission-letter.php           # Admission letter viewer

/mtti-applications/               # Stores application data
├── MTTI20260515123456_001.json   # Application data
├── MTTI20260515123456_001_admission.html  # Generated admission letter
└── whatsapp-logs.txt             # WhatsApp notification log

/mtti-admin-applications.php      # Admin dashboard
```

---

## 🔧 Form Features

### Sections Collected:
1. **Personal Information**
   - First/Last Name
   - Email Address
   - WhatsApp Number
   - Date of Birth
   - ID/Passport Number

2. **Education Background**
   - Highest Qualification
   - Work/IT Experience

3. **Course Selection**
   - Multiple course checkboxes
   - 8 courses available (Computer Applications, Web Dev, Cybersecurity, Nursing, Python, Phone Repair, Digital Marketing, Graphic Design)
   - Preferred start date

4. **Payment & Registration**
   - Payment method (M-Pesa, Bank Transfer, Cash, Installments)
   - Terms & conditions acceptance
   - WhatsApp consent

### Form Validation:
- Required fields enforced
- Email format validation
- Phone number format validation
- At least one course must be selected
- Date format validation

---

## 📧 Email System

### Confirmation Email Sent To Applicant:
- ✅ Admission ID
- ✅ Link to view admission letter
- ✅ Course details
- ✅ Next steps
- ✅ Contact information

### Email Settings:
Edit the sender email in `process-application.php`:
```php
"From: MTTI Eldoret <info@masomotele.ac.ke>\r\n"
```

---

## 💬 WhatsApp Integration

### How It Works:

1. **Applicant Submits Form** → System generates message content
2. **WhatsApp Link Created** → Pre-filled message with admission details
3. **Applicant Opens Link** → WhatsApp Web opens with message ready
4. **Confirmation Sent** → Sent to applicant's WhatsApp

### Message Content:
```
Hi [Name]! 👋

Your application to MTTI Eldoret has been received! ✅

Admission ID: MTTI20260515123456

📋 View your admission letter: [LINK]

📞 Next steps:
1. Reply 'YES' to confirm enrollment
2. Complete payment
3. Bring required documents

Questions? Just reply to this message or call us.

Real Skills, Real Jobs - MTTI Eldoret 💼
```

### WhatsApp Logs:
All WhatsApp notifications are logged in:
```
/mtti-applications/whatsapp-logs.txt
```

---

## 📄 Admission Letters

### Auto-Generated PDF Contains:
- Applicant information
- Selected courses
- Admission ID
- Admission date
- Next steps
- Required documents list
- Contact information

### Admission Letter Access:
```
Direct URL: https://masomoteletraining.co.ke/mtti-admission/[ADMISSION-ID]

Example:
https://masomoteletraining.co.ke/mtti-admission/MTTI20260515123456_001
```

### Applicant Can:
- ✅ View in browser
- ✅ Print to PDF
- ✅ Download as text file
- ✅ Share via WhatsApp
- ✅ Share via Email

---

## 📊 Admin Dashboard

### Access:
```
https://masomoteletraining.co.ke/mtti-admin-applications.php?key=MTTI2026Admin123
```

### Features:
- **View all applications** - See submitted forms at a glance
- **Statistics** - Total applications, pending review count
- **Search** - Filter by name or email
- **Quick actions**:
  - View full details in modal
  - Open admission letter
  - Contact applicant via WhatsApp

### Data Stored:
All application data stored as JSON files in `/mtti-applications/` directory

---

## 🔒 Security

### Current Security:
- Email validation
- Phone format validation
- HTML escaping to prevent XSS
- Basic admin authentication key

### Recommended Enhancements:
1. **Change admin authentication key** (see below)
2. **Add WordPress user authentication** to admin dashboard
3. **Enable SSL** (already configured)
4. **Regular backups** of `/mtti-applications/` folder
5. **Log access** to admin dashboard

### Change Admin Key:

1. Edit `/mtti-admin-applications.php`
2. Find line with: `if (!isset($_GET['key']) || $_GET['key'] !== 'MTTI2026Admin123')`
3. Replace `MTTI2026Admin123` with a strong password
4. Save file

Example: `if (!isset($_GET['key']) || $_GET['key'] !== 'MySecureAdminKey2026')`

---

## 📱 Mobile Optimization

The form is fully mobile-responsive:
- ✅ Touch-friendly input fields
- ✅ Large clickable buttons
- ✅ Single-column layout on mobile
- ✅ Optimized for slower connections
- ✅ Works on all phones

---

## 🎨 Customization

### Change Course List:
Edit `/wp-content/themes/application-form.php`, find the courses section:

```html
<div class="checkbox-item">
    <input type="checkbox" id="courseID" name="courses" value="Course Name (KES 15,000)">
    <label for="courseID">Course Name (KES 15,000)</label>
</div>
```

### Change Form Colors:
Edit the `<style>` section in `application-form.php`:
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
/* Change these hex colors to your brand colors */
```

### Add/Remove Form Fields:
Add new input groups in the relevant section and update `process-application.php` to handle them.

---

## 🐛 Troubleshooting

### Form Not Submitting?
1. Check browser console (F12 → Console tab)
2. Verify `/wp-content/themes/process-application.php` exists
3. Check file permissions: should be readable

### Emails Not Sending?
1. Check server mail configuration
2. Verify sender email address is valid
3. Check spam folder
4. Test with: `mail('your@email.com', 'Test', 'Test message');`

### WhatsApp Links Not Working?
1. Applicant must have WhatsApp installed
2. Phone number format must be correct (try +254712464936)
3. Check WhatsApp logs for details

### Admin Dashboard Not Loading?
1. Verify correct authentication key
2. Check URL syntax: `...?key=YourKey`
3. Check `/mtti-applications/` folder exists and has files

---

## 📈 Next Steps

### To Increase Adoption:
1. **Add application form link** to your homepage
2. **Create landing page** highlighting benefits of online application
3. **Share on social media** - "Apply from your phone!"
4. **Email to existing contacts** with application link
5. **Update WhatsApp status** with application link

### Sample Promotion Text:
```
📱 Apply to MTTI Online Now!

✅ Complete application from your phone
✅ Get instant admission letter
✅ Start your course journey today

👉 Apply here: [LINK TO /apply/]

Questions? WhatsApp: +254712464936
```

---

## 📞 Support

**If applications aren't being saved:**
- Check `/mtti-applications/` folder exists (create if needed)
- Verify folder permissions: `chmod 755 mtti-applications`

**To view stored applications:**
- Access admin dashboard: `/mtti-admin-applications.php?key=YourKey`
- Or check `/mtti-applications/` folder directly via file manager

**WhatsApp integration help:**
- Review WhatsApp logs: `/mtti-applications/whatsapp-logs.txt`
- All sent messages logged with phone numbers and timestamps

---

## 🎯 Summary

Your MTTI application system is now ready! Learners can:
- 📱 Apply from phones and computers
- 📄 Get instant admission letters
- 💬 Receive WhatsApp updates
- 🔗 Share their admission letter link

Admin can:
- 📊 View all applications
- 🔍 Search and filter
- 📋 Manage admissions
- 📧 Contact applicants

---

**Created:** May 15, 2026  
**System:** MTTI Online Application Form v1.0
