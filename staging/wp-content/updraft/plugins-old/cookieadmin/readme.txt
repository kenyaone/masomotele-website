=== CookieAdmin - Cookie Consent Banner ===
Contributors: softaculous
Tags: cookie, notice, banner, consent, gdpr
Requires at least: 4.4
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.1.0
License: LGPLv2.1
License URI: http://www.gnu.org/licenses/lgpl-2.1.html

CookieAdmin provides easy to configure cookie consent banner with GDPR and CCPA law support.

== Description ==

CookieAdmin is a Lightweight, Bloat-free & easy to use Cookie consent banner plugin which allows you to display a personalized banner on your website for your visitors to choose which cookies they would like to use. 

Admins can customize the UI of the consent banner for the frontend to match with their website / brand identity. 

CookieAdmin supports categorization of cookies and scanning of cookies used on their website. Users can choose to Accept All, Reject All or choose cookies categories to be loaded. 

Free Features :

* Customizable Consent Form
* Preview Consent Form from admin panel
* Anonymized User IP
* Basic Cookie Scanning
* Categorized cookies list for users
* GDPR & US State Laws support
* ADA, EAA & WCAG Compliant

Pro Features :

* Advanced Cookie Scanning
* Google Consent Mode v2
* User Country Detection
* Consent Logs
* Export Consent Logs

== Installation ==

Upload the CookieAdmin plugin to your site, Activate it.
That's it. You're done!

== Screenshots ==

== Changelog ==

= 1.1.0 =
* [Pro Feature] Added advanced cookie scanner to detect cookies set by javascript on the site
* [Bug Fix] In some rare cases, "hide powered by link" setting when enabled was conflicting with some other plugins. This has been fixed. 

= 1.0.9 =
* [Pro Feature] Added option to hide Powered by CookieAdmin link
* [Pro Feature] Added option to hide Re-consent icon
* [Task] Replaced hardcoded English strings with __() function for better translation support
* [Bug Fix] Fixed a typo

= 1.0.8 =
* [Pro Feature] Added option to customize the color for links and On/Off switch for the cookie consent preference modal
* [Task] Consent Banner will not be rendered while editing pages
* [Bug Fix] Consent Banner caused conflict with some forms on the page due to missing prefixes in some css classes. All css classes now have a prefix to avoid any conflict.
* [Bug Fix] Consent preference was not saved on sites running without SSL certificate. This is fixed now.

= 1.0.7 =
* [Bug Fix] Consent saved by a visitor on a page other than home page was saved incorrectly causing the consent banner to appear again on other pages. This is fixed now. 

= 1.0.6 =
* [Feature] Added Google Consent Mode v2
* [Bug Fix] Fixes for ADA, EAA & WCAG compliance
* [Bug Fix] UI fixes for certain themes

= 1.0.5 =
* [Feature] CookieAdmin Consent Banner is now compliant with Americans with Disabilities Act (ADA), The European Accessibility Act (EAA) & Web Content Accessibility Guidelines (WCAG).

= 1.0.4 =
* [Bug Fix] The icons in Cookie Preferences modal were not visible on mobile devices. This has been fixed. 

= 1.0.3 =
* [Bug Fix] In some cases, the consent banner was displayed even after accepting the cookies. This is fixed. 
* [Bug Fix] Minor UI fixes

= 1.0.2 =
* Initial Release