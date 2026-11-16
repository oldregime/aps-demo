<?php
/** Creates the empty page shells that seed-home.php / seed-pages.php then fill. Idempotent. (Playground demo; locally these were created once via WP-CLI.) */
foreach ( [ 'home' => 'Home', 'about' => 'About', 'manufacturing-research' => 'Manufacturing and Research', 'sustainability' => 'Sustainability', 'innovation' => 'Innovation', 'dealer-onboarding' => 'Dealer Onboarding', 'contact' => 'Contact' ] as $slug => $title ) {
	if ( ! get_page_by_path( $slug ) ) {
		wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => '' ] );
	}
}
