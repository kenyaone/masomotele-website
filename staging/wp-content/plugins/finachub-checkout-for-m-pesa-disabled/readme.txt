=== Finachub Lipa na Mpesa Checkout for WooCommerce ===
Contributors: Finacc, bnyamesa
Tags: mpesa, woocommerce, payments, lipa na mpesa, mobile money
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.3.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 4.0
WC tested up to: 8.8

Accept M-Pesa STK Push payments in WooCommerce. A simple and reliable way to integrate Kenya's most popular payment method.

**[Upgrade to Pro](https://finachub.com/product-category/plugins/mpesa/)** | **[View Live Demo](https://finachub.com/live-demo/)**

== Description ==

Finachub Lipa na Mpesa Checkout for WooCommerce provides the most straightforward way to integrate M-Pesa into your online store. This plugin allows your customers to pay using Safaricom's STK Push prompt directly on their phones, creating a smooth and familiar checkout experience.

Our plugin is designed for store owners who value simplicity and reliability. With a clean, modern admin interface and a focus on the core payment functionality, getting started is effortless.

**Why Choose This Plugin?**

*   **Seamless Integration:** Adds M-Pesa as a payment option directly into the WooCommerce checkout flow.
*   **User-Friendly:** Customers are prompted on their phones to complete the payment, a process they already know and trust.
*   **Modern & Clean:** From the admin panels to the customer-facing waiting page, the interface is designed to be intuitive and professional.

**Ready for Automation? Upgrade to Pro!**

The free version is perfect for getting started, but it requires you to manually verify payments. When you're ready to save time and automate your workflow, **[M-Pesa Pro is ready for you](https://finachub.com/product-category/plugins/mpesa/)**.

**[View the Live Demo](https://finachub.com/live-demo/)** to see the Pro version in action.

== Features ==

**Free Version Features:**

*   **Direct STK Push:** Initiates a payment prompt on the customer's phone.
*   **Sandbox & Live Modes:** Easily switch between testing and live environments.
*   **Secure & Reliable:** Built with security best practices and compatibility in mind.
*   **Modern Admin UI:** A clean and intuitive interface for managing settings.
*   **Comprehensive Guides:** Detailed setup and help pages right in your dashboard.

**Unlock Powerful Automation with the Pro Version:**

*   **Automatic Order Completion:** This is the biggest time-saver! The plugin automatically processes Safaricom's callback to update order statuses from "Pending" to "Processing" or "Completed" the moment a payment is made. **No more manual verification!**
*   **Detailed Transaction Dashboard:** A searchable, filterable dashboard in your WordPress admin showing all M-Pesa transactions and their statuses (Completed, Failed, etc.).
*   **CSV Data Export:** Easily export transaction data for accounting and reporting.
*   **Priority Email Support:** Get faster, dedicated assistance from our support team.
*   **[Learn More About Pro Features](https://finachub.com/product-category/plugins/mpesa/)**

== Installation ==

**Minimum Requirements:**

*   WordPress 5.0 or higher
*   WooCommerce 4.0 or higher
*   PHP 7.2 or higher
*   A valid SSL Certificate (HTTPS)

**Setup Instructions:**

1.  Upload the `finachub-checkout-for-m-pesa` folder to your `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the new **M-Pesa > Settings Guide** page in your admin menu.
4.  Follow the comprehensive, step-by-step instructions to configure the gateway.

== Frequently Asked Questions ==

= Does this plugin automatically update the order status after payment? =

The **Free version does not**. It initiates the payment, but you must manually verify the payment has been received and then update the order status in WooCommerce. **Automatic order completion is the core feature of the [Pro version](https://finachub.com/product-category/plugins/mpesa/)**.

= Where do I get my API credentials? =

You can get your Consumer Key, Consumer Secret, and Passkey from the **[Safaricom Developer Portal](https://developer.safaricom.co.ke/user/me/apps)** in the "My Apps" section after you log in.

= Do I need an SSL certificate (HTTPS)? =

**Yes, absolutely.** Safaricom's API requires a secure connection to work. Your site must be served over HTTPS.

= What happens if a customer enters the wrong phone number? =

The STK push will fail, and the customer will not be able to complete the payment. They can try again with the correct number.

= Where can I get support? =

For technical issues with the free plugin, please use the [WordPress.org support forum](https://wordpress.org/support/plugin/finachub-checkout-for-m-pesa/). For more detailed documentation or information about the Pro version, please visit the [Finachub website](https://finachub.com/mpesa-checkout-docs/).

== Screenshots ==

1.  The M-Pesa Transaction Dashboard (Available in Pro).
2.  The expanded transaction view for a single payment (Pro version).
3.  The customizable waiting page for a professional customer experience (Pro version).
4.  The M-Pesa payment option on the checkout page.

== Changelog ==

= 1.3.2 =
*   TWEAK: Shortened the plugin description to meet wordpress.org requirements.
*   TWEAK: Corrected the number of tags to 5.
*   TWEAK: Moved the "Upgrade to Pro" and "View Live Demo" buttons to a more prominent position in the readme.
*   FIX: Resolved an issue where the dashboard heading was not centered.
*   FIX: Reduced the spacing above the dashboard cards for a cleaner look.

= 1.3.0 =
*   **MAJOR:** Complete redesign of all admin pages (Dashboard, Settings Guide, Help & Upgrade) for a more modern, minimalist, and user-friendly experience.
*   **IMPROVEMENT:** The Settings Guide is now more comprehensive, with clearer steps, better explanations of Sandbox vs. Live environments, and direct links to the Safaricom portal.
*   **IMPROVEMENT:** The Help & Upgrade page is now more detailed, with an expanded troubleshooting checklist and a clearer presentation of Pro features.
*   **TWEAK:** Refined the layout and styling of all admin cards, buttons, and typography for a consistent and professional look.
*   **TWEAK:** Updated and simplified the content in this readme.txt file for clarity.

= 1.2.0 =
*   Major Admin UI Overhaul: Implemented a modern, stylish, and user-friendly interface for all admin pages (Dashboard, Settings Guide, Help).
*   Improved Admin Navigation: Consolidated plugin pages under a single "M-Pesa" top-level menu.

= 1.1.3 =
*   Feature: Added a notice to the Admin Dashboard displaying the count of 'On Hold' M-Pesa orders needing manual verification.
*   Feature: Added a clear, greyed-out section to the WooCommerce M-Pesa Settings page showcasing key Pro features.

= 1.1.2 =
*   Security: Added nonce verification to the waiting page URL generation and access check.

= 1.1.1 =
*   Improved styling for the waiting page.

= 1.1.0 =
*   Initial release.

== Upgrade Notice ==

= Stop Manually Verifying Payments! =
Upgrade to **[M-Pesa Pro](https://finachub.com/product-category/plugins/mpesa/)** to automatically update order statuses, view a full transaction dashboard, and save countless hours of manual work. It's the one feature that will truly streamline your business.
