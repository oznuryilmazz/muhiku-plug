<?php
/**
 * Privacy/GDPR related functionality which ties into WordPress functionality.
 *
 * @package MuhikuPlug\Classes
 * @version 1.3.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Privacy Class.
 */
class MHK_Privacy {

	/**
	 * Init - hook into events.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_privacy_message' ) );
	}

	/**
	 * Adds the privacy message on MHK privacy page.
	 */
	public function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = $this->get_privacy_message();

			if ( $content ) {
				wp_add_privacy_policy_content( __( 'Muhiku Plug', 'muhiku-plug' ), $this->get_privacy_message() );
			}
		}
	}

	/**
	 * Add privacy policy content for the privacy policy page.
	 *
	 * @since 1.3.1
	 */
	public function get_privacy_message() {
		$content = '
			<div class="wp-suggested-text">' .
				'<p class="privacy-policy-tutorial">' .
					__( 'This sample policy includes the basics around what personal data you may be collecting, storing and sharing, as well as who may have access to that data. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your form will vary. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.', 'muhiku-plug' ) .
				'</p>' .
				'<p>' . __( 'We collect information about you during the form submission process on our site.', 'muhiku-plug' ) . '</p>' .
				'<h2>' . __( 'What we collect and store', 'muhiku-plug' ) . '</h2>' .
				'<p>' . __( 'While you visit our site, we’ll track:', 'muhiku-plug' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'Form Fields Data: Forms Fields data includes the available field types when creating a form. We’ll use this to, for example, collect informations like Name, Email and other available fields.', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Location, IP address and browser type: we’ll use this for purposes like geolocating users and reducing fraudulent activities.', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Transaction Details: we’ll ask you to enter this so we can, for instance, provide subscription packs, and keep track of your payment details for subscription packs!', 'muhiku-plug' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'We’ll also use cookies to keep track of form elements while you’re browsing our site.', 'muhiku-plug' ) . '</p>' .
				'<p class="privacy-policy-tutorial">' . __( 'Note: you may want to further detail your cookie policy, and link to that section from here.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'When you fill up a form, we’ll ask you to provide information including your name, address, email, phone number, credit card/payment details and optional account information like username and password and any other form fields available in the form builder. We’ll use this information for purposes, such as, to:', 'muhiku-plug' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'Send you information about your account and order', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Respond to your requests, including transaction details and complaints', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Process payments and prevent fraud', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Set up your account for our site', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Comply with any legal obligations we have, such as calculating taxes', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Improve our form offerings', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Send you marketing messages, if you choose to receive them', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Or any other service the built form was created to comply with and it’s necessary information', 'muhiku-plug' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'If you create an account, we will store your name, address, email and phone number, which will be used to populate the form fields for future submissions.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'We generally store information about you for as long as we need the information for the purposes for which we collect and use it, and we are not legally required to continue to keep it. For example, we will store form submission information for XXX years for geolocating and marketting purposes. This includes your name, address, email, phone number.', 'muhiku-plug' ) . '</p>' .
				'<h2>' . __( 'Who on our team has access', 'muhiku-plug' ) . '</h2>' .
				'<p>' . __( 'Members of our team have access to the information you provide us. For example, both Administrators and Editors can access:', 'muhiku-plug' ) . '</p>' .
				'<ul>' .
					'<li>' . __( 'Form submission information and other details related to it', 'muhiku-plug' ) . '</li>' .
					'<li>' . __( 'Customer information like your name, email and address information.', 'muhiku-plug' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'Our team members have access to this information to help fulfill entries and support you.', 'muhiku-plug' ) . '</p>' .
				'<h2>' . __( 'What we share with others', 'muhiku-plug' ) . '</h2>' .
				'<p class="privacy-policy-tutorial">' . __( 'In this section you should list who you’re sharing data with, and for what purpose. This could include, but may not be limited to, analytics, marketing, payment gateways, shipping providers, and third party embeds.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'We share information with third parties who help us provide our orders and store services to you; for example --', 'muhiku-plug' ) . '</p>' .
				'<h3>' . __( 'Payments', 'muhiku-plug' ) . '</h3>' .
				'<p class="privacy-policy-tutorial">' . __( 'In this subsection you should list which third party payment processors you’re using to take payments on your site since these may handle customer data. We’ve included PayPal as an example, but you should remove this if you’re not using PayPal.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'We accept payments through PayPal. When processing payments, some of your data will be passed to PayPal, including information required to process or support the payment, such as the purchase total and billing information.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'Please see the <a href="https://www.paypal.com/us/webapps/mpp/ua/privacy-full">PayPal Privacy Policy</a> for more details.', 'muhiku-plug' ) . '</p>' .
				'<h3>' . __( 'Available Modules', 'muhiku-plug' ) . '</h3>' .
				'<p class="privacy-policy-tutorial">' . __( 'In this subsection you should list which third party modules you’re using to increase functionality on your site since these may handle customer data. We’ve included MailChimp as an example, but you should remove this if you’re not using MailChimp.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'We send beautiful email through MailChimp. When processing emails, some of your data will be passed to MailChimp, including information required to process or support the email marketing services, such as the name, email address and any other information that you intend to pass or collect including all collected information through subscription.', 'muhiku-plug' ) . '</p>' .
				'<p>' . __( 'Please see the <a href="https://mailchimp.com/legal/privacy/">MailChimp Privacy Policy</a> for more details.', 'muhiku-plug' ) . '</p>' .
				'</div>';

		return apply_filters( 'muhiku_forms_privacy_policy_content', $content );
	}
}

new MHK_Privacy();
