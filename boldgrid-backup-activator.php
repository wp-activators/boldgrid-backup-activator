<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Total Upkeep Activator
 * Plugin URI:        https://github.com/wp-activators/boldgrid-backup-activator
 * Description:       Total Upkeep Plugin Activator
 * Version:           1.2.0
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/

defined( 'ABSPATH' ) || exit;
$TOTAL_UPKEEP_ACTIVATOR_NAME   = 'Total Upkeep Activator';
$TOTAL_UPKEEP_ACTIVATOR_DOMAIN = 'boldgrid-backup-activator';
$functions                     = require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
extract( $functions );
if (
	$activator_admin_notice_ignored()
	|| $activator_admin_notice_plugin_install( 'boldgrid-backup/boldgrid-backup.php', 'boldgrid-backup', 'Total Upkeep', $TOTAL_UPKEEP_ACTIVATOR_NAME, $TOTAL_UPKEEP_ACTIVATOR_DOMAIN )
	|| $activator_admin_notice_plugin_activate( 'boldgrid-backup/boldgrid-backup.php', $TOTAL_UPKEEP_ACTIVATOR_NAME, $TOTAL_UPKEEP_ACTIVATOR_DOMAIN )
) {
	return;
}

use Boldgrid\Library\Library\Configs;

add_filter( 'Boldgrid\Library\Library\Notice\ClaimPremiumKey_enable', '__return_true', 20 );
add_filter( 'pre_http_request', function ( $pre, $parsed_args, $url ) use ( $activator_json_response ) {
	if ( ! class_exists( Configs::class ) ) {
		return $pre;
	}
	$data               = new stdClass;
	$data->result       = new stdClass;
	$data->result->data = new stdClass;
	switch ( $url ) {
		case Configs::get( 'api' ) . '/api/plugin/checkVersion':
			$data->result->data->site_hash = $parsed_args['body']['key'] ?? md5( 'free4all' );
			$data->result->data->asset_id  = 'free4all';

			return $activator_json_response( $data );
		case Configs::get( 'api' ) . '/api/plugin/getLicense?v=' . 2:
			$data->result->data = boldgrid_backup_activator_license();

			return $activator_json_response( $data );
	}

	return $pre;
}, 99, 3 );
add_filter( 'option_active_plugins', function ( $plugins, $option_name ) {
	$plugins[] = 'boldgrid-backup-premium/boldgrid-backup-premium.php';

	return $plugins;
}, 99, 2 );

$file = ( $directory = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'boldgrid-backup-premium' ) . DIRECTORY_SEPARATOR . 'boldgrid-backup-premium.php';
if ( ! is_dir( $directory ) ) {
	mkdir( $directory, 0777, true );
}
$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/boldgrid-backup/boldgrid-backup.php' );
if ( ! file_exists( $file ) ) {
	touch( $file );
	goto make_fake_plugin;
}
premium_data:
$premium_data = get_plugin_data( WP_PLUGIN_DIR . '/boldgrid-backup-premium/boldgrid-backup-premium.php' );
if ( empty( $premium_data['Name'] ) ) {
	goto make_fake_plugin;
}
if ( version_compare( $premium_data['Version'], $plugin_data['Version'], '<' ) && ( $premium_data['Name'] == 'Total Upkeep Premium Faked' ) ) {
	make_fake_plugin:
	$php = '<?php';
	file_put_contents( $file,
		<<<EOF
{$php}
/**
 * @wordpress-plugin
 * Plugin Name:       Total Upkeep Premium Faked
 * Plugin URI:        https://wordpress.org/plugins/boldgrid-backup/
 * Description:       Fake plugin for Total Upkeep Premium
 * Version:           {$plugin_data['Version']}
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/
EOF
	);
	goto premium_data;
}

function boldgrid_backup_activator_license(): stdClass {
	$plugin              = 'boldgrid-backup';
	$inspirations        = 'boldgrid-inspirations';
	$method              = 'AES-128-CBC';
	$key                 = 'boldgrid-backup-activator';
	$iv                  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( $method ) );
	$data                = new stdClass;
	$data->$plugin       = true;
	$data->$inspirations = true;
	$data->refreshBy     = strtotime( '+1000 year' );
	$encryptedData       = openssl_encrypt(
		json_encode( $data ),
		$method,
		$key,
		OPENSSL_CIPHER_RC2_40,
		$iv
	);
	$data                = new stdClass;
	$data->data          = $encryptedData;
	$data->cipher        = $method;
	$data->key           = $key;
	$data->iv            = urlencode( $iv );
	$data->version       = 2;

	return $data;
}
