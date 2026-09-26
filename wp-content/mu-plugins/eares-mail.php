<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Mail
 * Description: Sends all site email through the SMTP server configured in the environment (Mailpit locally).
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'phpmailer_init',
	function ( $phpmailer ) {
		$host = getenv( 'EARES_SMTP_HOST' );
		if ( ! $host ) {
			return;
		}
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer's API.
		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = (int) ( getenv( 'EARES_SMTP_PORT' ) ?: 25 );
		$phpmailer->SMTPSecure = (string) getenv( 'EARES_SMTP_SECURE' );
		// SMTPAutoTLS stays on: with SMTP_SECURE empty, PHPMailer still upgrades
		// to TLS whenever the server offers STARTTLS (Mailpit does not).
		$user = getenv( 'EARES_SMTP_USER' );
		if ( $user ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $user;
			$phpmailer->Password = (string) getenv( 'EARES_SMTP_PASSWORD' );
		}
		// phpcs:enable
	}
);

add_filter( 'wp_mail_from', fn( $from ) => getenv( 'EARES_MAIL_FROM' ) ?: $from );
add_filter( 'wp_mail_from_name', fn( $name ) => getenv( 'EARES_MAIL_FROM_NAME' ) ?: $name );
