<?php
/**
 * Seeds products, knowledge articles and the Home page from seed-data.json
 * (exported from the approved React site). Run:  ./scripts/wp eval-file /scripts/seed-home.php
 * Safe to re-run: existing items (matched by slug) are updated, not duplicated.
 */
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$d = json_decode( file_get_contents( __DIR__ . '/seed-data.json' ), true );

function sd_attach( $url, $desc ) {
	$existing = get_posts( [ 'post_type' => 'attachment', 'meta_key' => '_aps_source', 'meta_value' => $url, 'posts_per_page' => 1, 'fields' => 'ids' ] );
	if ( $existing ) return (int) $existing[0];
	$tmp = download_url( $url, 60 );
	if ( is_wp_error( $tmp ) ) { WP_CLI::warning( "image failed: $url — " . $tmp->get_error_message() ); return 0; }
	$name = sanitize_file_name( preg_match( '/\.(jpe?g|png|webp|avif)(\?|$)/i', $url ) ? basename( strtok( $url, '?' ) ) : sanitize_title( $desc ) . '.jpg' );
	$id = media_handle_sideload( [ 'name' => $name, 'tmp_name' => $tmp ], 0, $desc );
	if ( is_wp_error( $id ) ) { WP_CLI::warning( "image failed: $url — " . $id->get_error_message() ); return 0; }
	update_post_meta( $id, '_aps_source', $url );
	update_post_meta( $id, '_wp_attachment_image_alt', $desc );
	return (int) $id;
}

/* ---------- settings from the React company data ---------- */
$c = $d['company']['headquarters'];
update_option( 'aps_settings', [
	'company_name' => $d['company']['legalEntity'], 'tagline' => 'Protecting Crop • Creating Life',
	'phone' => $c['phone'], 'whatsapp' => $c['whatsappNumber'], 'email' => $c['email'], 'address' => $c['address'],
	'shop_url' => $d['company']['ecommerceUrl'],
] );
update_option( 'blogname', $d['company']['name'] );
update_option( 'blogdescription', 'Protecting Crop • Creating Life' );

/* ---------- products ---------- */
$cat_ids = [];
foreach ( array_values( $d['products'] ) as $i => $p ) {
	$term = term_exists( $p['category'], 'aps_product_cat' ) ?: wp_insert_term( $p['category'], 'aps_product_cat' );
	$tid = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
	$existing = get_page_by_path( $p['id'], OBJECT, 'aps_product' );
	$body = "<!-- wp:paragraph --><p>" . esc_html( $p['tagline'] ) . "</p><!-- /wp:paragraph -->\n<!-- wp:heading {\"level\":3} --><h3>Key features</h3><!-- /wp:heading -->\n<!-- wp:list --><ul>";
	foreach ( $p['features'] as $f ) $body .= '<!-- wp:list-item --><li>' . esc_html( $f ) . '</li><!-- /wp:list-item -->';
	$body .= "</ul><!-- /wp:list -->";
	$specs = ''; foreach ( $p['specs'] as $k => $v ) $specs .= "$k | $v\n";
	$arr = [
		'post_type' => 'aps_product', 'post_status' => 'publish', 'post_title' => $p['name'], 'post_name' => $p['id'],
		'post_excerpt' => $p['tagline'], 'post_content' => $body, 'menu_order' => $i,
		'meta_input' => [
			'aps_target_pest' => $p['targetPest'], 'aps_crops' => implode( ', ', $p['targetCrops'] ),
			'aps_dosage' => $p['trapsPerAcre'], 'aps_lure_life' => $p['lureLongevity'], 'aps_specs' => trim( $specs ),
			'aps_shop_url' => $p['storeUrl'], 'aps_enquiry_label' => 'Enquire on WhatsApp',
		],
	];
	if ( $existing ) { $arr['ID'] = $existing->ID; $pid = wp_update_post( $arr ); } else { $pid = wp_insert_post( $arr ); }
	wp_set_object_terms( $pid, [ $tid ], 'aps_product_cat' );
	if ( ! has_post_thumbnail( $pid ) && ( $img = sd_attach( $p['image'], $p['name'] ) ) ) set_post_thumbnail( $pid, $img );
	if ( function_exists( 'pll_set_post_language' ) ) pll_set_post_language( $pid, 'en' );
	WP_CLI::log( "product: {$p['name']} (#$pid)" );
}

/* ---------- Agri Jnanam articles ---------- */
foreach ( $d['jnanam'] as $a ) {
	$slug = sanitize_title( $a['title'] );
	$existing = get_page_by_path( $slug, OBJECT, 'aps_knowledge' );
	$arr = [ 'post_type' => 'aps_knowledge', 'post_status' => 'publish', 'post_title' => $a['title'], 'post_name' => $slug,
		'post_excerpt' => $a['snippet'], 'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $a['snippet'] ) . '</p><!-- /wp:paragraph -->' ];
	$pid = $existing ? wp_update_post( $arr + [ 'ID' => $existing->ID ] ) : wp_insert_post( $arr );
	if ( function_exists( 'pll_set_post_language' ) ) pll_set_post_language( $pid, 'en' );
}

/* ---------- media for Home ---------- */
$hero_poster = sd_attach( 'https://images.unsplash.com/photo-1595974482597-4b8da8879bc5?auto=format&fit=crop&w=2400&q=85', 'Green crop field' );
$hero_video  = '';
$vid = get_posts( [ 'post_type' => 'attachment', 'meta_key' => '_aps_source', 'meta_value' => 'hero-video', 'posts_per_page' => 1, 'fields' => 'ids' ] );
if ( $vid ) { $hero_video = wp_get_attachment_url( $vid[0] ); }
else {
	$tmp = download_url( 'https://cdn.coverr.co/videos/coverr-green-crop-field-under-the-sun-5582/1080p.mp4', 120 );
	if ( ! is_wp_error( $tmp ) ) {
		$vid_id = media_handle_sideload( [ 'name' => 'aps-hero-field.mp4', 'tmp_name' => $tmp ], 0, 'Hero field footage' );
		if ( ! is_wp_error( $vid_id ) ) { update_post_meta( $vid_id, '_aps_source', 'hero-video' ); $hero_video = wp_get_attachment_url( $vid_id ); }
	} else WP_CLI::warning( 'hero video download failed: ' . $tmp->get_error_message() );
}
$poster_url = $hero_poster ? wp_get_attachment_url( $hero_poster ) : '';

/* ---------- block builders ---------- */
require_once __DIR__ . '/lib-blocks.php';

/* ---------- HERO ---------- */
$video_tag = $hero_video ? "<video class=\"wp-block-cover__video-background intrinsic-ignore\" autoplay muted loop playsinline poster=\"" . esc_url( $poster_url ) . "\" src=\"" . esc_url( $hero_video ) . "\" data-object-fit=\"cover\"></video>" : '';
$cover_attrs = [ 'url' => $hero_video ?: $poster_url, 'dimRatio' => 65, 'overlayColor' => 'forest-dark', 'minHeight' => 86, 'minHeightUnit' => 'vh', 'className' => 'aps-hero', 'contentPosition' => 'center left', 'align' => 'full', 'isDark' => true ];
if ( $hero_video ) $cover_attrs['backgroundType'] = 'video';
$hero_inner =
	b_eyebrow( 'SCIENCE-LED PEST MANAGEMENT • EST. 2014' ) .
	b_h( 1, 'A clearer signal for<br><em>healthier crops.</em>', '"textColor":"canvas"' ) .
	b_p( 'Pest monitoring solutions that help growers act with greater confidence—and help nature work a little more in balance.', '"textColor":"canvas","fontSize":"lead"', 'has-canvas-color has-text-color has-lead-font-size' ) .
	b_btns( b_btn( 'Explore our solutions', '#products' ) . b_btn( 'Partner with APS', '/dealer-onboarding/', true ) ) .
	b_p( 'Made in India • Manufacturer &amp; exporter • Built around Integrated Pest Management (IPM) • Cherlapally, Hyderabad • ISO 9001:2015', '"textColor":"canvas","fontSize":"small"', 'has-canvas-color has-text-color has-small-font-size' );
$bg_img = ( ! $hero_video && $poster_url ) ? "<img class=\"wp-block-cover__image-background\" alt=\"\" loading=\"eager\" fetchpriority=\"high\" src=\"" . esc_url( $poster_url ) . "\" data-object-fit=\"cover\"/>" : '';
$hero = "<!-- wp:cover " . wp_json_encode( $cover_attrs, JSON_UNESCAPED_SLASHES ) . " -->\n<div class=\"wp-block-cover alignfull is-light has-custom-content-position is-position-center-left aps-hero\" style=\"min-height:86vh\"><span aria-hidden=\"true\" class=\"wp-block-cover__background has-forest-dark-background-color has-background-dim-60 has-background-dim\"></span>$bg_img$video_tag<div class=\"wp-block-cover__inner-container\">$hero_inner</div></div>\n<!-- /wp:cover -->\n";

/* ---------- METRICS ---------- */
$cards = [];
foreach ( $d['metrics'] as $m ) {
	$cards[] = b_group(
		b_p( esc_html( strtoupper( $m['suffix'] === '+' ? 'Field impact' : $m['suffix'] ) ), '"className":"aps-eyebrow"', 'aps-eyebrow' ) .
		b_p( esc_html( $m['value'] . ( $m['suffix'] === '+' ? '+' : '' ) ), '"fontFamily":"serif","fontSize":"title","textColor":"forest"', 'has-forest-color has-text-color has-serif-font-family has-title-font-size' ) .
		b_h( 4, esc_html( $m['label'] ) ) . b_p( esc_html( $m['subtext'] ), '"fontSize":"small"', 'has-small-font-size' ),
		[ 'className' => 'aps-card', 'style' => [ 'spacing' => [ 'padding' => [ 'all' => '1.5rem' ] ] ] ], 'aps-card' );
}
$stake = [
	[ 'Growers — Commercial & progressive farmers', 'Save ₹4,500 – ₹8,000 / acre in chemical spray labour and pesticide costs', [ 'Eliminates 6–10 hazardous spray operations per harvest cycle', 'Protects spray operators and families from chemical exposure', 'Boosts Grade-A blemish-free fruit and boll turnout' ] ],
	[ 'Exporters — Agricultural exporters & processors', 'Supports compliance at European, US & Japanese border MRL checks', [ 'Supports compliance with Maximum Residue Limits (MRL)', 'Avoids reliance on banned organophosphates and chlorpyrifos', 'Helps secure premium export value for grapes, mangoes and spices' ] ],
	[ 'Dealers — Agro-dealers & FPO collectives', 'Strong inventory turnover with a long ambient shelf life', [ 'Factory-backed batch traceability from Cherlapally, Hyderabad', 'Repeat farmer retention driven by visible trap-catch proof', 'No special hazardous-storage requirements' ] ],
];
$details = '';
foreach ( $stake as $s ) $details .= "<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>" . esc_html( $s[0] ) . "</summary>" . b_p( '<strong>' . esc_html( $s[1] ) . '</strong>' ) . b_list( $s[2] ) . "</details>\n<!-- /wp:details -->\n";
$metrics = section(
	b_eyebrow( 'MEASURABLE FIELD IMPACT' ) . b_h( 2, 'Ecological balance backed by <em>field arithmetic.</em>' ) .
	b_cols( array_map( fn( $c ) => $c, $cards ) ) .
	b_h( 3, 'How APS technology transforms agricultural economics' ) . $details, 'canvas', 'impact' );

/* ---------- PRODUCTS ---------- */
$loop = "<!-- wp:query {\"queryId\":11,\"query\":{\"perPage\":4,\"pages\":0,\"offset\":0,\"postType\":\"aps_product\",\"order\":\"asc\",\"orderBy\":\"menu_order\",\"inherit\":false},\"align\":\"wide\"} -->\n<div class=\"wp-block-query alignwide\"><!-- wp:post-template {\"layout\":{\"type\":\"grid\",\"columnCount\":4}} -->\n"
	. "<!-- wp:group {\"className\":\"aps-card\",\"style\":{\"spacing\":{\"padding\":{\"all\":\"1rem\"}}}} -->\n<div class=\"wp-block-group aps-card\">"
	. "<!-- wp:post-featured-image {\"isLink\":true,\"aspectRatio\":\"4/3\",\"style\":{\"border\":{\"radius\":\"12px\"}}} /-->\n"
	. "<!-- wp:post-title {\"level\":3,\"isLink\":true,\"fontSize\":\"subtitle\"} /-->\n"
	. "<!-- wp:post-excerpt {\"excerptLength\":22,\"fontSize\":\"small\"} /-->\n"
	. "<!-- wp:shortcode -->[aps_meta key=\"crops\"]<!-- /wp:shortcode -->\n"
	. "<!-- wp:read-more {\"content\":\"View specifications\",\"className\":\"aps-readmore\"} /-->\n"
	. "</div>\n<!-- /wp:group -->\n<!-- /wp:post-template --></div>\n<!-- /wp:query -->\n";
$products = section( b_eyebrow( 'FLAGSHIP SOLUTIONS' ) . b_h( 2, 'Four systems. <em>One IPM philosophy.</em>' ) . $loop .
	b_btns( b_btn( 'View all products', '/products/', true ) ), 'sand', 'products' );

/* ---------- PILLARS ---------- */
$cols = [];
foreach ( $d['pillars'] as $p ) {
	$img = sd_attach( $p['image'], $p['title'] );
	$img_block = $img ? "<!-- wp:image {\"id\":$img,\"sizeSlug\":\"large\",\"linkDestination\":\"none\",\"style\":{\"border\":{\"radius\":\"16px\"}}} -->\n<figure class=\"wp-block-image size-large has-custom-border\"><img src=\"" . esc_url( wp_get_attachment_image_url( $img, 'large' ) ) . "\" alt=\"" . esc_attr( $p['title'] ) . "\" class=\"wp-image-$img\" style=\"border-radius:16px\"/></figure>\n<!-- /wp:image -->\n" : '';
	$cols[] = $img_block . b_eyebrow( $p['badge'] ) . b_h( 3, esc_html( $p['title'] ) ) . b_p( '<em>' . esc_html( $p['tagline'] ) . '</em>' ) . b_p( esc_html( $p['description'] ) ) . b_list( $p['bulletPoints'] ) .
		b_p( '<strong>' . esc_html( $p['stat']['number'] ) . '</strong> ' . esc_html( $p['stat']['label'] ) );
}
$pillars = section( b_eyebrow( 'CORE CAPABILITIES' ) . b_h( 2, 'Science, manufacturing and <em>responsible stewardship.</em>' ) .
	b_p( 'Bridging organic molecular chemistry with high-capacity manufacturing in Cherlapally, Hyderabad to provide Indian and global agriculture with sustainable pest defence.' ) . b_cols( $cols ), 'canvas-muted', 'science' );

/* ---------- EXPLAINER ---------- */
$steps = [];
foreach ( $d['steps'] as $s ) $steps[] = b_p( esc_html( $s['step'] ), '"fontFamily":"serif","fontSize":"title","textColor":"buttercup"', 'has-buttercup-color has-text-color has-serif-font-family has-title-font-size' ) . b_h( 4, esc_html( $s['title'] ), '"textColor":"canvas"' ) . b_p( esc_html( $s['desc'] ), '"textColor":"canvas","fontSize":"small"', 'has-canvas-color has-text-color has-small-font-size' );
$explainer = section( b_eyebrow( 'HOW PHEROMONE TECHNOLOGY WORKS' ) . b_h( 2, 'From scent signal to <em>residue-free harvest.</em>', '"textColor":"canvas"' ) . b_cols( $steps ), 'forest-dark', 'how-it-works' );

/* ---------- AGRI JNANAM + CALCULATOR ---------- */
$kloop = "<!-- wp:query {\"queryId\":12,\"query\":{\"perPage\":3,\"pages\":0,\"offset\":0,\"postType\":\"aps_knowledge\",\"order\":\"desc\",\"orderBy\":\"date\",\"inherit\":false},\"align\":\"wide\"} -->\n<div class=\"wp-block-query alignwide\"><!-- wp:post-template {\"layout\":{\"type\":\"grid\",\"columnCount\":3}} -->\n"
	. "<!-- wp:group {\"className\":\"aps-card\",\"style\":{\"spacing\":{\"padding\":{\"all\":\"1.25rem\"}}}} -->\n<div class=\"wp-block-group aps-card\"><!-- wp:post-title {\"level\":4,\"isLink\":true} /-->\n<!-- wp:post-excerpt {\"excerptLength\":28,\"fontSize\":\"small\"} /--></div>\n<!-- /wp:group -->\n<!-- /wp:post-template --></div>\n<!-- /wp:query -->\n";
$jnanam = section( b_eyebrow( 'APS AGRI JNANAM' ) . b_h( 2, 'Practical knowledge for <em>every farmer.</em>' ) . $kloop .
	b_h( 3, 'Calculate how many traps you need' ) . "<!-- wp:shortcode -->[aps_trap_calculator]<!-- /wp:shortcode -->\n", 'canvas', 'agri-jnanam' );

/* ---------- B2B CTA ---------- */
$cta = section( b_eyebrow( 'INSTITUTIONAL PARTNERSHIPS' ) . b_h( 2, 'Distribute, private-label or export with <em>APS.</em>', '"textColor":"canvas"' ) .
	b_p( 'Custom isomer synthesis, private-label packaging, phytosanitary export documentation, and air/sea container dispatch from our Cherlapally plant.', '"textColor":"canvas"', 'has-canvas-color has-text-color' ) .
	b_btns( b_btn( 'Become a dealer', '/dealer-onboarding/' ) . b_btn( 'Contact the plant', '/contact/', true ) ), 'forest', 'partner' );

$content = $hero . $metrics . $products . $pillars . $explainer . $jnanam . $cta;
$home = get_page_by_path( 'home' );
wp_update_post( [ 'ID' => $home->ID, 'post_content' => $content ] );
if ( function_exists( 'pll_set_post_language' ) ) pll_set_post_language( $home->ID, 'en' );
WP_CLI::success( 'Home page built (' . strlen( $content ) . ' bytes), id ' . $home->ID );
