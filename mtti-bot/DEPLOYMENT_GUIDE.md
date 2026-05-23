# M.T.T.I WhatsApp Chatbot — Deployment Guide
## Step-by-Step Setup Instructions

---

## WHAT YOU'RE DEPLOYING

A WhatsApp chatbot that:
- Answers student inquiries 24/7 in English, Swahili, and Sheng
- Captures leads (name, phone, course interest) automatically
- Sends you WhatsApp alerts when a hot lead comes in
- Has a dashboard to view and manage all leads
- Costs approximately KES 5,000-8,000/month to run

## FILES IN THIS PACKAGE

```
mtti-bot/
├── webhook.php          ← Main bot (receives WhatsApp messages, calls Claude, replies)
├── dashboard.php        ← Leads dashboard (view, manage, export leads)
├── system_prompt.txt    ← M.T.T.I knowledge base (edit this with your real info)
├── test.php             ← Browser test page (DELETE before going live)
├── test_api.php         ← Test API handler (DELETE before going live)
└── data/
    ├── .htaccess        ← Protects data from public access
    ├── leads.json       ← Auto-created: captured leads
    ├── conversations.json ← Auto-created: chat histories
    └── bot.log          ← Auto-created: activity log
```

---

## DEPLOYMENT STEPS

### STEP 1: Update Your Course Information (30 minutes)
Open `system_prompt.txt` and update:
- [ ] All course fees with your ACTUAL current prices
- [ ] Next intake date
- [ ] M-Pesa Paybill number
- [ ] Any courses to add or remove
- [ ] Scholarship details
- [ ] Opening hours if different

This is the most important step. The bot will only be as good as the information you give it.

### STEP 2: Get Your Anthropic API Key (5 minutes)
- You already have this from GrantPilot
- Go to: https://console.anthropic.com/
- Copy your API key
- Add credit: Start with $10 (about KES 1,300) — this handles roughly 1,000-2,000 conversations

### STEP 3: Set Up Africa's Talking Account (1-2 days)
This is the service that connects your bot to WhatsApp.

1. Go to: https://africastalking.com/
2. Create an account (free)
3. Go to "WhatsApp" product in their dashboard
4. Apply for WhatsApp Business API access:
   - You need a Facebook Business account (create at business.facebook.com if you do not have one)
   - You need a phone number that is NOT already registered on WhatsApp (buy a new SIM for KES 50-100)
   - Africa's Talking will guide you through Meta verification (takes 1-7 days)
5. Once approved, note down:
   - API Key
   - Username
   - WhatsApp Product ID

**Alternative if Africa's Talking takes too long:** 
Use 360dialog (https://www.360dialog.com/) — they also provide WhatsApp API access and are popular in Kenya. The webhook.php file can be adapted with minimal changes.

### STEP 4: Upload to Your Hosting (15 minutes)
1. Using cPanel File Manager or FTP, upload the entire `mtti-bot` folder to your website
   - Location: `public_html/mtti-bot/` (or wherever your site lives)
2. Make the `data/` directory writable:
   ```
   chmod 755 mtti-bot/data/
   ```
3. Verify file permissions:
   - webhook.php: 644
   - dashboard.php: 644
   - data/: 755
   - data/.htaccess: 644

### STEP 5: Configure API Keys (5 minutes)
Open `webhook.php` and update these lines at the top:

```php
define('ANTHROPIC_API_KEY', 'sk-ant-xxxxx');      // Your Anthropic key
define('AT_API_KEY', 'your-at-key');               // Africa's Talking API key
define('AT_USERNAME', 'your-username');             // Africa's Talking username  
define('AT_WHATSAPP_PRODUCT_ID', 'your-product');  // From AT WhatsApp dashboard
define('BOT_PHONE', '+254712345678');              // Your WhatsApp Business number
define('ADMIN_PHONE', '+254712345678');            // YOUR number (for lead alerts)
```

Also update the API key in `test_api.php` for testing.

### STEP 6: Set Webhook URL in Africa's Talking (5 minutes)
1. Go to Africa's Talking dashboard → WhatsApp → Settings
2. Set the Callback/Webhook URL to:
   ```
   https://yoursite.com/mtti-bot/webhook.php
   ```
3. Save

This tells WhatsApp to forward all incoming messages to your bot.

### STEP 7: Test (30 minutes)
**Browser test first:**
1. Open: https://yoursite.com/mtti-bot/test.php
2. Type test messages like:
   - "Hi"
   - "What courses do you have?"
   - "How much is web development?"
   - "Niaje nataka kujua kama mna graphic design"
   - "Can I pay in installments?"
   - "My name is John and I want to do cybersecurity"
3. Verify responses are accurate and natural

**WhatsApp test:**
1. Send a message to your WhatsApp Business number from a different phone
2. You should get a reply within 3-5 seconds
3. Test various questions and languages

### STEP 8: Go Live Checklist
- [ ] DELETE test.php and test_api.php (security risk!)
- [ ] Change dashboard password in dashboard.php (line: $PASSWORD = 'mtti2026')
- [ ] Verify lead alerts arrive on your phone
- [ ] Test the dashboard at: https://yoursite.com/mtti-bot/dashboard.php
- [ ] Add the WhatsApp number to all marketing materials
- [ ] Update your Google Business profile with the WhatsApp number

---

## DAILY OPERATIONS

### Morning Routine (5 minutes)
1. Open dashboard: https://yoursite.com/mtti-bot/dashboard.php
2. Check new leads from overnight
3. Call any "New" leads — mark as "Contacted"
4. Follow up on yesterday's "Contacted" leads

### Weekly Routine (15 minutes)
1. Export leads as CSV (download button on dashboard)
2. Review which courses have most interest — adjust marketing
3. Check API usage — are you within budget?
4. Read the bot log for any errors
5. Update system_prompt.txt if anything changed (fees, dates, new courses)

### Monthly
1. Check Anthropic API billing — top up if needed
2. Check Africa's Talking billing — top up if needed  
3. Review conversion rate: leads → enrolled students
4. Update next intake dates in system_prompt.txt

---

## COSTS BREAKDOWN (MONTHLY)

| Item | Cost | Notes |
|------|------|-------|
| Anthropic API | KES 1,300-3,900 ($10-30) | ~600 conversations at KES 2-5 each |
| Africa's Talking WhatsApp | KES 1,800-4,800 | Per-conversation pricing |
| Hosting | KES 0 (existing) | Uses your current hosting |
| **TOTAL** | **KES 3,100-8,700** | |

**Break-even:** 1 extra student enrollment per month covers the cost.

---

## TROUBLESHOOTING

**Bot not replying:**
- Check webhook.php has correct API keys
- Check Africa's Talking webhook URL is correct
- Check bot.log for error messages
- Verify Anthropic API has credit balance

**Wrong information in replies:**
- Edit system_prompt.txt with correct information
- The bot only knows what is in that file

**Too many API calls (high cost):**
- Reduce DAILY_BUDGET_LIMIT in webhook.php
- When limit is hit, bot sends a static fallback message

**Dashboard not loading:**
- Check file permissions (644 for PHP files, 755 for data directory)
- Check .htaccess is not blocking dashboard.php

**Leads not saving:**
- Check data/ directory is writable (chmod 755)
- Check bot.log for file permission errors

---

## UPDATING COURSE INFORMATION

When fees, dates, or courses change:
1. Edit `system_prompt.txt` on your server
2. That is it — changes take effect immediately on the next message
3. No code changes needed

---

## SECURITY NOTES

- NEVER share your API keys publicly
- ALWAYS delete test.php and test_api.php after testing
- Change the dashboard password from the default
- The data/ directory is protected by .htaccess but consider additional server-level protection
- Regularly backup leads.json (your lead data)
