<?php
/** Shared block-markup builders used by the seed scripts. */
function b_h( $lvl, $html, $extra = '' ) {
	$cls = 'wp-block-heading';
	if ( preg_match( '/"textColor":"([a-z-]+)"/', $extra, $m ) ) $cls .= " has-{$m[1]}-color has-text-color";
	return "<!-- wp:heading {\"level\":$lvl" . ( $extra ? ",$extra" : '' ) . "} --><h$lvl class=\"$cls\">$html</h$lvl><!-- /wp:heading -->\n";
}
function b_p( $html, $attrs = '', $class = '' ) { return "<!-- wp:paragraph" . ( $attrs ? " {{$attrs}}" : '' ) . " --><p" . ( $class ? " class=\"$class\"" : '' ) . ">$html</p><!-- /wp:paragraph -->\n"; }
function b_eyebrow( $t ) { return b_p( esc_html( $t ), '"className":"aps-eyebrow"', 'aps-eyebrow' ); }
function b_btn( $text, $href, $outline = false ) {
	$cls = $outline ? 'is-style-outline' : '';
	return "<!-- wp:button" . ( $outline ? ' {"className":"is-style-outline"}' : '' ) . " --><div class=\"wp-block-button $cls\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_url( $href ) . "\">" . esc_html( $text ) . "</a></div><!-- /wp:button -->\n";
}
function b_btns( $inner ) { return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">$inner</div>\n<!-- /wp:buttons -->\n"; }
function b_group( $inner, $attrs = [], $classes = '' ) {
	$a = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES );
	$cls = 'wp-block-group' . ( $classes ? " $classes" : '' );
	return "<!-- wp:group $a -->\n<div class=\"$cls\">$inner</div>\n<!-- /wp:group -->\n";
}
function b_cols( $cols, $attrs = '' ) {
	$out = "<!-- wp:columns" . ( $attrs ? " {{$attrs}}" : '' ) . " -->\n<div class=\"wp-block-columns\">";
	foreach ( $cols as $c ) $out .= "<!-- wp:column -->\n<div class=\"wp-block-column\">$c</div>\n<!-- /wp:column -->";
	return $out . "</div>\n<!-- /wp:columns -->\n";
}
function b_list( $items ) { $o = "<!-- wp:list --><ul class=\"wp-block-list\">"; foreach ( $items as $i ) $o .= "<!-- wp:list-item --><li>" . esc_html( $i ) . "</li><!-- /wp:list-item -->"; return $o . "</ul><!-- /wp:list -->\n"; }
function section( $inner, $bg, $id = '', $pad = '50' ) {
	$attrs = [ 'align' => 'full', 'className' => 'aps-section has-global-padding', 'backgroundColor' => $bg, 'style' => [ 'spacing' => [ 'padding' => [ 'top' => "var:preset|spacing|$pad", 'bottom' => "var:preset|spacing|$pad" ] ] ], 'layout' => [ 'type' => 'constrained' ] ];
	if ( $id ) $attrs['anchor'] = $id;
	$idattr = $id ? " id=\"$id\"" : '';
	return "<!-- wp:group " . wp_json_encode( $attrs ) . " -->\n<div$idattr class=\"wp-block-group alignfull aps-section has-global-padding has-$bg-background-color has-background\" style=\"padding-top:var(--wp--preset--spacing--$pad);padding-bottom:var(--wp--preset--spacing--$pad)\">$inner</div>\n<!-- /wp:group -->\n";
}
