<?php
/** Builds the inner pages from the React content. Re-runnable (overwrites those pages' content). */
require_once __DIR__ . '/lib-blocks.php';
$d = json_decode( file_get_contents( __DIR__ . '/seed-data.json' ), true );
$dealer_id = (int) get_option( 'aps_form_dealer' ); $contact_id = (int) get_option( 'aps_form_contact' );

function page_set( $slug, $content ) {
	$p = get_page_by_path( $slug );
	if ( ! $p ) { WP_CLI::warning( "missing page $slug" ); return; }
	wp_update_post( [ 'ID' => $p->ID, 'post_content' => $content ] );
	if ( function_exists( 'pll_set_post_language' ) ) pll_set_post_language( $p->ID, 'en' );
	WP_CLI::log( "page: $slug" );
}
function intro( $eyebrow, $title, $lead ) { return section( b_eyebrow( $eyebrow ) . b_h( 1, $title ) . b_p( esc_html( $lead ), '"fontSize":"lead"', 'has-lead-font-size' ), 'canvas-muted', '', '40' ); }
function short( $code ) { return "<!-- wp:shortcode -->$code<!-- /wp:shortcode -->\n"; }

$pillar = fn( $id ) => array_values( array_filter( $d['pillars'], fn( $p ) => $p['id'] === $id ) )[0];

/* About */
page_set( 'about', intro( 'ABOUT US', 'Agri Phero Solutionz', 'An Indian agricultural biotechnology company making pheromone lures and IPM traps in Cherlapally, Hyderabad.' ) .
	section( b_h( 2, 'Who we are' ) .
		b_p( 'Agri Phero Solutionz (APS) designs, synthesises and manufactures species-specific pheromone lures and trap hardware that help growers monitor and manage pests with fewer chemical sprays.' ) .
		b_h( 3, 'Quality and compliance' ) . b_list( array_map( fn( $c ) => $c['title'] . ' — ' . $c['desc'], $d['company']['certifications'] ) ) .
		b_h( 3, 'Visit or contact us' ) . b_p( esc_html( $d['company']['headquarters']['address'] ) ) .
		b_btns( b_btn( 'Contact the plant', '/contact/' ) . b_btn( 'Become a dealer', '/dealer-onboarding/', true ) ), 'canvas' ) );

/* Manufacturing and Research / Sustainability */
foreach ( [ [ 'manufacturing-research', 'manufacturing', 'MANUFACTURING & RESEARCH' ], [ 'sustainability', 'sustainability', 'SUSTAINABILITY' ] ] as [ $slug, $pid, $eb ] ) {
	$p = $pillar( $pid ); $sci = $pillar( 'science' );
	$extra = $slug === 'manufacturing-research' ? b_h( 2, esc_html( $sci['title'] ) ) . b_p( esc_html( $sci['description'] ) ) . b_list( $sci['bulletPoints'] ) : '';
	page_set( $slug, intro( $eb, $p['title'], $p['tagline'] ) . section( b_p( esc_html( $p['description'] ) ) . b_list( $p['bulletPoints'] ) .
		b_p( '<strong>' . esc_html( $p['stat']['number'] ) . '</strong> ' . esc_html( $p['stat']['label'] ) ) . $extra .
		b_btns( b_btn( 'Request a quote', '/contact/' ) ), 'canvas' ) );
}

/* Innovation: how it works + comparison table */
$rows = [
	[ 'Target specificity', '100% species-specific — attracts only the target insect', 'Broad-spectrum — affects non-target insects' ],
	[ 'Honeybees & earthworms', 'Designed not to harm pollinators or soil life', 'Can harm bees and beneficial predators' ],
	[ 'Resistance build-up', 'Mating-scent approach — resistance is not expected to build up the way it does with chemicals', 'Resistance can develop over repeated sprays' ],
	[ 'Harvest residue (MRL)', 'No pesticide residue from the trap itself — supports export MRL compliance', 'Residues and pre-harvest waiting periods' ],
	[ 'Operator safety', 'No respirators or hazmat gear needed for trap handling', 'Inhalation and skin exposure risk' ],
];
$tbl = "<!-- wp:table {\"className\":\"aps-compare\"} -->\n<figure class=\"wp-block-table aps-compare\"><table><thead><tr><th>Feature</th><th>APS pheromone system</th><th>Conventional chemical spray</th></tr></thead><tbody>";
foreach ( $rows as $r ) $tbl .= '<tr><td><strong>' . esc_html( $r[0] ) . '</strong></td><td>' . esc_html( $r[1] ) . '</td><td>' . esc_html( $r[2] ) . '</td></tr>';
$tbl .= "</tbody></table></figure>\n<!-- /wp:table -->\n";
$steps = ''; foreach ( $d['steps'] as $s ) $steps .= b_h( 3, esc_html( $s['step'] . ' — ' . $s['title'] ) ) . b_p( esc_html( $s['desc'] ) );
page_set( 'innovation', intro( 'INNOVATION', 'How pheromone biotechnology works', 'By decoding natural insect communication, APS lures intercept pests before mating — halting reproduction without chemical toxicity.' ) .
	section( $steps . b_h( 2, 'APS pheromone system vs. chemical spraying' ) . $tbl .
		b_h( 2, 'What we are building next' ) . b_list( [ 'Computer-vision trap counting from a smartphone photo', 'Connected solar smart traps', 'Sprayable micro-encapsulated lures' ] ) .
		b_p( '<em>Roadmap items are plans, not available products.</em>' ), 'canvas' ) );

/* Dealer onboarding + Contact */
page_set( 'dealer-onboarding', intro( 'PARTNER WITH APS', 'Dealer & distributor onboarding', 'Apply to distribute APS products in your territory. We will review your details and contact you within one working day.' ) .
	section( short( "[fluentform id=\"$dealer_id\"]" ), 'canvas' ) );
$wa = preg_replace( '/\D/', '', aps_setting( 'whatsapp' ) );
page_set( 'contact', intro( 'CONTACT', 'Talk to the APS plant', 'Product enquiries, technical advice, dealership, export and OEM — we reply quickly.' ) .
	section( b_cols( [
		short( "[fluentform id=\"$contact_id\"]" ),
		b_h( 3, 'Reach us directly' ) . b_p( '[aps field="address"]<br>Phone: [aps field="phone"]<br>Email: [aps field="email"]' ) .
		b_btns( b_btn( 'Chat on WhatsApp', "https://wa.me/$wa" ) ) . short( '[aps_map]' ),
	] ), 'canvas' ) );
WP_CLI::success( 'pages built' );
