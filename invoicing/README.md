# Masomotele Invoicing System

A complete invoicing system — `public.html` frontend + PHP SQLite backend.

## File Structure

```
invoicing/
├── public.html          ← Main frontend (open this in browser)
├── api/
│   └── index.php        ← PHP backend API
├── data/
│   └── invoicing.db     ← SQLite database (auto-created)
├── uploads/             ← Uploaded logos (auto-created)
└── README.md
```

## Requirements
- PHP 7.4+ with PDO SQLite extension (standard on most hosts including Truehost)
- Web server (Apache / Nginx) or `php -S localhost:8000`

## Quick Deploy on Truehost (cPanel)

1. Upload the entire `invoicing/` folder to `public_html/invoicing/`
2. Visit `https://yourdomain.co.ke/invoicing/public.html`
3. That's it — SQLite DB and uploads folder are created automatically.

**Protect the data folder** — add this `.htaccess` in the `data/` folder:
```
Deny from all
```

## Local Testing (PHP built-in server)
```bash
cd invoicing
php -S localhost:8000
# Open: http://localhost:8000/public.html
```

## Features
- ✅ Invoice, Quotation, Receipt, Delivery Note generation
- ✅ Auto-incrementing document numbers (server-side)
- ✅ SQLite database — no MySQL needed
- ✅ Logo upload (stored on server)
- ✅ Edit & delete records
- ✅ Search & filter records
- ✅ Export CSV / JSON
- ✅ Import from JSON backup
- ✅ Quotation → Invoice/Receipt/Delivery conversion
- ✅ VAT settings
- ✅ Offline fallback (localStorage)
- ✅ MTTI / Masomotele green branding (#1a472a / #3D6318)
- ✅ Print-friendly document output
- ✅ KSh currency

## API Endpoints

| Method | Action | Description |
|--------|--------|-------------|
| GET | `?action=settings` | Load company settings |
| POST | `?action=settings` | Save company settings |
| GET | `?action=records` | List records (`?type=invoice&search=`) |
| POST | `?action=save_record` | Save new document |
| POST | `?action=update_record` | Update existing record |
| POST | `?action=delete_record` | Delete record |
| GET | `?action=stats` | Dashboard statistics |
| POST | `?action=upload_logo` | Upload company logo |
| GET | `?action=next_number&type=X` | Get next document number |

## Security Notes
- Add authentication (htpasswd or PHP session) if this is public-facing
- Protect `data/` and `uploads/` from direct listing with `.htaccess`
- The API has no auth built-in by design (internal use)
