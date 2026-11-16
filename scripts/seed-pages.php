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
function intro( $eyebrow, $title, $lead ) {
	$inner = b_eyebrow( $eyebrow ) . b_h( 1, $title, '"textColor":"canvas"' ) . b_p( esc_html( $lead ), '"textColor":"canvas","fontSize":"lead"', 'has-canvas-color has-text-color has-lead-font-size' );
	return section( $inner, 'forest-dark', '', '40' ) ;
}
function img_src( $url, $alt, $radius = 24 ) {
	$q = get_posts( [ 'post_type' => 'attachment', 'meta_key' => '_aps_source', 'meta_value' => $url, 'posts_per_page' => 1, 'fields' => 'ids' ] );
	if ( ! $q ) return '';
	$id = (int) $q[0]; $u = wp_get_attachment_image_url( $id, 'large' );
	return "<!-- wp:image {\"id\":$id,\"sizeSlug\":\"large\",\"linkDestination\":\"none\",\"className\":\"aps-photo\"} -->\n<figure class=\"wp-block-image size-large aps-photo\"><img src=\"" . esc_url( $u ) . "\" alt=\"" . esc_attr( $alt ) . "\" class=\"wp-image-$id\"/></figure>\n<!-- /wp:image -->\n";
}
function split_cols( $left, $right, $reverse = false ) {
	$cols = $reverse ? [ $right, $left ] : [ $left, $right ];
	return b_cols( $cols, '"className":"aps-split","verticalAlignment":"center"' );
}
function panel( $inner ) { return b_group( $inner, [ 'className' => 'aps-panel', 'layout' => [ 'type' => 'default' ] ], 'aps-panel' ); }
function stat_box( $num, $label ) { return b_group( b_p( esc_html( $num ), '"fontFamily":"serif","fontSize":"title","textColor":"forest"', 'has-forest-color has-text-color has-serif-font-family has-title-font-size aps-stat__num' ) . b_p( esc_html( $label ), '"fontSize":"small"', 'has-small-font-size' ), [ 'className' => 'aps-stat', 'layout' => [ 'type' => 'default' ] ], 'aps-stat' ); }
function short( $code ) { return "<!-- wp:shortcode -->$code<!-- /wp:shortcode -->\n"; }

$pillar = fn( $id ) => array_values( array_filter( $d['pillars'], fn( $p ) => $p['id'] === $id ) )[0];

/* About */
$mfg = $pillar( 'manufacturing' ); $sci0 = $pillar( 'science' );
$certs = ''; foreach ( $d['company']['certifications'] as $c ) $certs .= b_group( b_h( 4, esc_html( $c['title'] ) ) . b_p( esc_html( $c['desc'] ), '"fontSize":"small"', 'has-small-font-size' ), [ 'className' => 'aps-card', 'style' => [ 'spacing' => [ 'padding' => [ 'all' => '1.5rem' ] ] ] ], 'aps-card' );
$cert_cols = []; foreach ( $d['company']['certifications'] as $c ) $cert_cols[] = b_h( 4, esc_html( $c['title'] ) ) . b_p( esc_html( $c['desc'] ), '"fontSize":"small"', 'has-small-font-size' );
page_set( 'about', intro( 'ABOUT US', 'Agri Phero Solutionz', 'An Indian agricultural biotechnology company making pheromone lures and IPM traps in Cherlapally, Hyderabad.' ) .
	section( split_cols(
		b_eyebrow( 'WHO WE ARE' ) . b_h( 2, 'Science-led pest management, <em>made in India.</em>' ) .
		b_p( 'Agri Phero Solutionz (APS) designs, synthesises and manufactures species-specific pheromone lures and trap hardware that help growers monitor and manage pests with fewer chemical sprays.' ) .
		b_p( 'Our work sits at the meeting point of organic chemistry and field agronomy: every lure is made in-house at our Cherlapally facility, and every trap is designed around a specific pest and crop.' ) .
		b_btns( b_btn( 'Contact the plant', '/contact/' ) . b_btn( 'Become a dealer', '/dealer-onboarding/', true ) ),
		img_src( $mfg['image'], $mfg['title'] ), true ), 'canvas' ) .
	section( b_eyebrow( 'QUALITY & COMPLIANCE' ) . b_h( 2, 'Certified, documented and <em>export-ready.</em>' ) . b_cols( $cert_cols, '"className":"aps-certs"' ), 'canvas-muted' ) .
	section( b_group( b_eyebrow( 'VISIT US' ) . b_h( 2, 'Cherlapally, Hyderabad', '"textColor":"canvas"' ) . b_p( esc_html( $d['company']['headquarters']['address'] ), '"textColor":"canvas","fontSize":"lead"', 'has-canvas-color has-text-color has-lead-font-size' ) .
		b_btns( b_btn( 'Get in touch', '/contact/' ) ), [ 'className' => 'aps-cta-panel', 'backgroundColor' => 'forest', 'layout' => [ 'type' => 'default' ] ], 'aps-cta-panel has-forest-background-color has-background' ), 'canvas', '', '40' ) );

/* Manufacturing and Research / Sustainability */
foreach ( [ [ 'manufacturing-research', 'manufacturing', 'MANUFACTURING & RESEARCH' ], [ 'sustainability', 'sustainability', 'SUSTAINABILITY' ] ] as [ $slug, $pid, $eb ] ) {
	$p = $pillar( $pid ); $sci = $pillar( 'science' );
	$main = section( split_cols(
		b_eyebrow( $p['badge'] ) . b_h( 2, esc_html( $p['title'] ) ) . b_p( esc_html( $p['description'] ) ) . b_list( $p['bulletPoints'] ) . stat_box( $p['stat']['number'], $p['stat']['label'] ) . b_btns( b_btn( 'Request a quote', '/contact/' ) ),
		img_src( $p['image'], $p['title'] ) ), 'canvas' );
	$extra = $slug === 'manufacturing-research'
		? section( split_cols( b_eyebrow( $sci['badge'] ) . b_h( 2, esc_html( $sci['title'] ) ) . b_p( esc_html( $sci['description'] ) ) . b_list( $sci['bulletPoints'] ) . stat_box( $sci['stat']['number'], $sci['stat']['label'] ), img_src( $sci['image'], $sci['title'] ), true ), 'canvas-muted' ) : '';
	page_set( $slug, intro( $eb, $p['title'], $p['tagline'] ) . $main . $extra );
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
$step_cols = []; foreach ( $d['steps'] as $st ) $step_cols[] = b_p( esc_html( $st['step'] ), '"fontFamily":"serif","fontSize":"title","textColor":"buttercup"', 'has-buttercup-color has-text-color has-serif-font-family has-title-font-size' ) . b_h( 4, esc_html( $st['title'] ), '"textColor":"canvas"' ) . b_p( esc_html( $st['desc'] ), '"textColor":"canvas","fontSize":"small"', 'has-canvas-color has-text-color has-small-font-size' );
page_set( 'innovation', intro( 'INNOVATION', 'How pheromone biotechnology works', 'By decoding natural insect communication, APS lures intercept pests before mating — halting reproduction without chemical toxicity.' ) .
	section( b_eyebrow( 'THE MECHANISM' ) . b_h( 2, 'From scent signal to <em>residue-free harvest.</em>', '"textColor":"canvas"' ) . b_cols( $step_cols, '"className":"aps-steps"' ), 'forest-dark', 'how-it-works' ) .
	section( b_eyebrow( 'THE COMPARISON' ) . b_h( 2, 'APS pheromone system vs. <em>chemical spraying.</em>' ) . $tbl, 'canvas' ) .
	section( b_eyebrow( 'ROADMAP' ) . b_h( 2, 'What we are building <em>next.</em>' ) . b_list( [ 'Computer-vision trap counting from a smartphone photo', 'Connected solar smart traps', 'Sprayable micro-encapsulated lures' ] ) .
		b_p( '<em>Roadmap items are plans, not available products.</em>', '"fontSize":"small"', 'has-small-font-size' ), 'canvas-muted' ) );

/* Dealer onboarding + Contact */
page_set( 'dealer-onboarding', intro( 'PARTNER WITH APS', 'Dealer & distributor onboarding', 'Apply to distribute APS products in your territory. We will review your details and contact you within one working day.' ) .
	section( panel( short( "[fluentform id=\"$dealer_id\"]" ) ), 'canvas-muted' ) );
$wa = preg_replace( '/\D/', '', aps_setting( 'whatsapp' ) );
page_set( 'contact', intro( 'CONTACT', 'Talk to the APS plant', 'Product enquiries, technical advice, dealership, export and OEM — we reply quickly.' ) .
	section( b_cols( [
		panel( b_h( 3, 'Send us a message' ) . short( "[fluentform id=\"$contact_id\"]" ) ),
		panel( b_h( 3, 'Reach us directly' ) . b_p( '[aps field="address"]<br>Phone: [aps field="phone"]<br>Email: [aps field="email"]' ) .
		b_btns( b_btn( 'Chat on WhatsApp', "https://wa.me/$wa" ) ) . short( '[aps_map]' ) ),
	], '"className":"aps-contact-cols"' ), 'canvas-muted' ) );
WP_CLI::success( 'pages built' );
