=== Schedula Smart Appointment Booking ===
Contributors: vertim
Donate link: https://vertimcoders.com  
Tags: scheduling, booking, appointment, calendar, reservations
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Schedula is a powerful WordPress plugin designed for service-based businesses to streamline their booking
process.

== Description ==

<h4>About Schedula</h4>
<ul>
    <li>Plugin site: https://vertimcoders.com/tests-product-landing/apps/schedula-for-woocommerce/</li>
</ul>

<h4>Overview</h4>

Schedula is a comprehensive WordPress booking plugin designed for service-based businesses. It features an intuitive frontend reservation system where customers can easily book appointments and make payments online, paired with a powerful admin dashboard for managing services, staff schedules, customer data, and appointments. Perfect for hairdressers, consultants, beauty salons, garages, and other local professionals who need a reliable, responsive booking solution that handles the scheduling complexity while they focus on their business.

<h4>Features</h4>
<ul>
<li>Easy-to-use booking calendar</li>
<li>Create and manage services and categories</li>
<li>Manage employee schedules and availability</li>
<li>Customer management for quick bookings</li>
<li>Responsive and user-friendly design</li>
<li>Support for custom booking forms</li>
<li>Notifications for bookings and reminders (email)</li>
</ul>

<h4>Pro features</h4>
<ul>
    <li>Add unlimited services</li>
    <li>Add unlimited staff members</li>
    <li>Advanced form customization</li>
    <li>Advanced email design templates</li>
    <li>Multiple payment methods</li>
    <li>SMS notifications</li>
    <li>Advanced statistics and reporting</li>
    <li>AI-powered booking optimization</li>
    <li>Online payment integration</li>
    <li>Customizable notification templates</li>
</ul>

== External Services ==

This plugin connects to several third-party services to provide its full range of features. The use of these services and the data they handle are detailed below.

= Stripe =
*   **Service:** Stripe is a payment processing platform.
*   **Purpose:** This service is used to process online payments when a customer books an appointment and chooses to pay in advance.
*   **Data Sent:** To process a payment, the plugin sends customer information (such as name and email), appointment details, and payment card information to Stripe. This happens when the user submits the booking form with a payment method selected. The plugin also retrieves payment status from Stripe using a session ID.
*   **Service Policies:** [Stripe Terms of Service](https://stripe.com/legal/ssa) | [Stripe Privacy Policy](https://stripe.com/privacy)

= Google Fonts =
*   **Service:** Google Fonts is a library of open-source fonts.
*   **Purpose:** This service is used to load custom fonts to style the appearance of the booking forms and other frontend components. This allows users to customize the look and feel of the plugin to match their website's design.
*   **Data Sent:** When loading fonts, the user's browser makes a request to the Google Fonts API. This request sends the user's IP address to Google's servers. This happens on pages where the Schedula booking form is displayed.
*   **Service Policies:** [Google Fonts API Terms of Service](https://developers.google.com/fonts/terms) | [Google Privacy Policy](https://policies.google.com/privacy)

= IP2C =
*   **Service:** IP2C (ip2c.org) is a free service for IP address to country geolocation.
*   **Purpose:** This service is used to determine the country of the user based on their IP address. This information is used to set a default country code for phone numbers in the booking form, improving user experience.
*   **Data Sent:** The user's browser sends a request to the ip2c.org service, which implicitly includes the user's IP address. This happens when the booking form is loaded with the "auto default country" feature enabled.
*   **Service Policies:** The service is provided free of charge under the GNU LGPLv3 license. More information about their service and privacy can be found on their website: [https://ip2c.org/](https://ip2c.org/)

= ipapi.co =
*   **Service:** ipapi.co is a free service for IP address to location geolocation.
*   **Purpose:** This service is used as a fallback to determine the timezone of the user based on their IP address. This information is used in the plugin's admin settings to help administrators configure their business timezone.
*   **Data Sent:** The user's browser sends a request to the ipapi.co service, which implicitly includes the user's IP address. This happens in the admin settings when the browser's timezone cannot be detected automatically.
*   **Service Policies:** [ipapi.co Terms of Service](https://ipapi.co/terms) | [ipapi.co Privacy Policy](https://ipapi.co/privacy)

= Email Services =
*   **Service:** The plugin uses the default WordPress email system (wp_mail) to send emails. This may be configured to use the server's built-in email functionality or an external SMTP provider.
*   **Purpose:** This service is used to send transactional emails for booking confirmations, cancellations, and other notifications to the client, staff, and admin. It is also used to send a confirmation email upon newsletter subscription and to forward contact form submissions to the developer.
*   **Data Sent:** The plugin sends the recipient's email address, name, and the content of the email (which includes appointment details, newsletter confirmation, or contact form message) to the email service.
*   **Service Policies:** The privacy policy of the email service provider configured on the WordPress installation will apply.



== Installation ==

<div class="block-content"><h4>Minimum Requirements</h4>

<ul>
<li>WordPress 6.0 or greater</li>
<li>PHP version 7.4 or greater</li>
<li>MySQL version 5.6 or greater</li>
</ul>

<p><strong>This section describes how to install the plugin and get it working.</strong></p>

<h4>Automatic installation (easiest way)</h4>

<p>To do an automatic install of Schedula, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.</p>

<p>In the search field, type "Schedula" and click Search Plugins. Once you have found it, you can install it by simply clicking "Install Now".</p>

<h4>Manual installation</h4>

<p><strong>Uploading in WordPress Dashboard</strong></p>

<ol>
<li>Download <code>schedula.zip</code></li>
<li>Navigate to the 'Add New' in the plugins dashboard</li>
<li>Navigate to the 'Upload' area</li>
<li>Select <code>schedula.zip</code> from your computer</li>
<li>Click 'Install Now'</li>
<li>Activate the plugin in the Plugin dashboard</li>
</ol>

<p><strong>Using FTP</strong></p>

<ol>
<li>Download <code>schedula.zip</code></li>
<li>Extract the <code>schedula</code> directory to your computer</li>
<li>Upload the <code>schedula</code> directory to the <code>/wp-content/plugins/</code> directory</li>
<li>Activate the plugin in the Plugin dashboard</li>
</ol>

<p>The WordPress codex contains <a href="http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation" rel="nofollow">instructions on how to install a WordPress plugin</a>.</p>

<h4>Updating</h4>

<p>You can use automatic update to update the plugin safely.</p></div>

== Live Demo ==

<p><a href="https://demos.vertimcoders.com/schedula-live-demo/" rel="nofollow">DEMO Schedula Booking Plugin</a>.</p>

== Source Code ==

<p>The source code and unminified assets for this plugin are publicly available in our GitHub repository. Administrators and developers who need to review or audit the plugin's source files can access them at: <a href="https://github.com/vertim-coders/schedula-smart-appointment-booking" rel="nofollow">https://github.com/vertim-coders/schedula-smart-appointment-booking</a>.</p>

== Frequently Asked Questions ==

= Where can I find Schedula documentation and user guides? =
You can access our online documentation at <a href="https://docs.vertimcoders.com/docs/schedula-documentation/">here</a>.

<p>If you need help, you can open a support ticket at <a href="https://vertimcoders.com/contact-us/">here</a>.</p>

= Will Schedula work with my theme? =

Schedula is built to work with any WordPress theme, including the default themes.

== Screenshots ==

1. Main Booking Calendar
2. Add New Service
3. Employee Management Dashboard

== Changelog ==
= 1.0 2025-09-22 =

- First stable release.