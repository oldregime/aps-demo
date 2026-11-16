<?php
/**
 * Final provisioning after the seed scripts: front page, logo, site icon, permalinks.
 * Used by the Playground demo. (On the local Docker stack these were set once via WP-CLI.)
 */
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$home = get_page_by_path( 'home' );
if ( $home ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home->ID );
}

// Remove WordPress' default sample content.
foreach ( [ 'hello-world' ] as $slug ) { $p = get_page_by_path( $slug, OBJECT, 'post' ); if ( $p ) wp_delete_post( $p->ID, true ); }
$s = get_page_by_path( 'sample-page' ); if ( $s ) wp_delete_post( $s->ID, true );

if ( ! get_theme_mod( 'custom_logo' ) && file_exists( __DIR__ . '/aps-logo.png' ) ) {
	$tmp = wp_tempnam( 'aps-logo.png' );
	copy( __DIR__ . '/aps-logo.png', $tmp );
	$id = media_handle_sideload( [ 'name' => 'aps-logo.png', 'tmp_name' => $tmp ], 0, 'Agri Phero Solutionz logo' );
	if ( ! is_wp_error( $id ) ) {
		set_theme_mod( 'custom_logo', $id );
		update_option( 'site_icon', $id );
	}
}
update_option( 'aps_dev_demo', 1 );
