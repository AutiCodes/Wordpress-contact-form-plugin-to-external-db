<?php
/*
Plugin Name: T.R.M.C. formulier aanmelden leden naar ext. database
Plugin URI: https://github.com/AutiCodes/Wordpress-contact-form-plugin-to-external-db
Description: Deze plugin is om nieuwe aanmeldingen in de database van de club manager te zetten (en een mail notificatie te sturen)
Version: 1.1
Author: Kelvin de Reus AKA AutiCodes
Author URI: https://auticodes.nl
*/

/**
 * It's me, AutiCodes.
 * Yes, i didn't follow coding standard from WordPress. 
 * Fluff them!
 *
 * Maybe i gonna hate feature me, maybe not. Feel free to fix it if you want.
 */

/**
 * Plugin settings
 *
 * @author AutiCodes
 */
function trmcCfSettingsInit()
{
	register_setting('general', 'new_register_contact_email');

	add_settings_section(
		'new_register_contact_email',
		'T.R.M.C aanmeld formulier instellingen', 'trmcCfSettingsCallback',
		'general'
	);

	add_settings_field(
		'trmc_cf_settings_field',
		'Email adres voor notificaties voor nieuwe leden aanmeldingen', 'trmcCfSettingsFieldCallback',
		'general',
		'new_register_contact_email'
	);
}

add_action('admin_init', 'trmcCfSettingsInit');

// section content cb
function trmcCfSettingsCallback() {
	echo '<p>De instellingen voor de custom made aanmeld formulier plugin</p>';
}

// field content cb
function trmcCfSettingsFieldCallback() {
	$setting = get_option('new_register_contact_email');
	?>
	<input type="text" name="new_register_contact_email" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
    <?php
}

/**
 * The HTML of the form
 *
 * @author AutiCodes
 * @return void
 */
function htmlFormCode(): void
{
	// Shows form HTML from an external file
	include('html/form_html.php');
}

/**
 * Handles the form submission 
 *
 * @author AutiCodes
 * @return void
 */
function handleNewFormSubmission(): void
{
    // If it is an post req
	if (isset($_POST['cf-submitted'])) {
        // Make new DB instance with second DB creds
        $cmDB = new WPDB(DB2_USER, DB2_PASSWORD, DB2_NAME, DB2_HOST);

		if (sanitize_text_field($_POST['anti_bot']) != 6) {
			echo 'Er ging iets mis! Bot vraag is verkeerd!';
			return;
		}

		// Insert new request into Members table in the club manager DB
        $query = $cmDB->insert(
            'Members',
            array(
                'name' => sanitize_text_field($_POST['firstname']) . ' ' . sanitize_text_field($_POST['lastname']),
				'first_name' => sanitize_text_field($_POST['firstname']),
				'address' => sanitize_text_field($_POST['address']),
				'city' => sanitize_text_field($_POST['city']),
				'postcode' => sanitize_text_field($_POST['postal_code']),
				'email' => sanitize_text_field($_POST['email']),
				'phone' => sanitize_text_field($_POST['phone']),
				'birthdate' => date('Y-m-d', strtotime(sanitize_text_field($_POST['birthdate']))), // To DB DATE format
				'nationality' => sanitize_text_field($_POST['nationality']),
				'has_glider_brevet' => sanitize_text_field($_POST['glider_brevet']) ?? 0,
				'has_plane_brevet' => sanitize_text_field($_POST['motor_brevet']) ?? 0,
				'has_helicopter_brevet' => sanitize_text_field($_POST['heli_brevet']) ?? 0,
				'has_drone_a1' => sanitize_text_field($_POST['drone_brevet_a1']) ?? 0,
				'has_drone_a2' => sanitize_text_field($_POST['drone_brevet_a2']) ?? 0,
				'has_drone_a3' => sanitize_text_field($_POST['drone_brevet_a3']) ?? 0,
				'rdw_number' => sanitize_text_field($_POST['rdw_number']) ?? 0,
				'is_member_of_other_club' => sanitize_text_field($_POST['other_club_text']) ?? 0,
				'KNVvl' => sanitize_text_field($_POST['knvvl_text']),
				'wanna_be_member_at' => date('Y-m-d', strtotime(sanitize_text_field($_POST['wanna_be_member_at']))), // To DB DATE format
				'is_from_wp' => 1,
				'club_status' => 8,
            )
        );

		// If query didnt succeed
		if ($query != 1) {
			echo 'Er ging iets mis! Contacteer Kelvin op het email adres: webmaster@trmc.nl';
			return;
		} else {
			// Send mail
			wp_mail(
				get_option('new_register_contact_email'), // Gets email from the Settings -> general -> new register settings menu
				'Nieuwe T.R.M.C. aanmelding', // Subject
				'Er is een nieuwe club aanmelding. Bezoek https://club.trmc.nl en zoek op lid: ' . sanitize_text_field($_POST['firstname']) . ' ' . sanitize_text_field($_POST['lastname']), // Content
				array('Content-Type: text/html; charset=UTF-8'),
			);
        	// Show succes html text from external file
			include('html/succes_form_html.php');
		}
    } 
}


/**
 * Function to register the shortcode
 *
 * @author AutiCodes
 * @return object ob_get_clean()
 */
function regShortcode() {
	ob_start();
	handleNewFormSubmission();
	htmlFormCode();

	return ob_get_clean();
}


// Registers the shortcode
add_shortcode('trmc_new_member_cf', 'regShortcode');

?>