<?php
/**
 * Creates the two Fluent Forms (Dealer onboarding, Contact/enquiry) and stores their IDs in options
 * aps_form_dealer / aps_form_contact. Re-runnable (updates by title). Leads are stored in the
 * dashboard under Fluent Forms → Entries and emailed to the address in APS Settings.
 */
function ff_el( &$i, $el, $name, $label, $required = false, $extra_attrs = [], $extra_settings = [], $tpl = 'inputText', $title = 'Text Input' ) {
	$i++;
	return [
		'index' => $i, 'element' => $el, 'uniqElKey' => "el_$i",
		'attributes' => array_merge( [ 'type' => 'text', 'name' => $name, 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '' ], $extra_attrs ),
		'settings' => array_merge( [
			'container_class' => '', 'label' => $label, 'label_placement' => '', 'admin_field_label' => $label, 'help_message' => '',
			'validation_rules' => $required ? [ 'required' => [ 'value' => true, 'message' => 'This field is required' ] ] : [ 'required' => [ 'value' => false, 'message' => 'This field is required' ] ],
			'conditional_logics' => [],
		], $extra_settings ),
		'editor_options' => [ 'title' => $title, 'icon_class' => 'ff-edit-text', 'template' => $tpl ],
	];
}
function ff_select( &$i, $name, $label, $options, $required = false ) {
	$opts = []; foreach ( $options as $o ) $opts[] = [ 'label' => $o, 'value' => $o, 'calc_value' => '', 'image' => '' ];
	$f = ff_el( $i, 'select', $name, $label, $required, [ 'placeholder' => '- Select -' ], [ 'advanced_options' => $opts, 'enable_image_input' => false, 'calc_value_status' => false ], 'selectField', 'Dropdown' );
	unset( $f['attributes']['type'] ); return $f;
}
function ff_area( &$i, $name, $label, $ph = '' ) {
	$f = ff_el( $i, 'textarea', $name, $label, false, [ 'placeholder' => $ph, 'rows' => 4, 'cols' => 2, 'maxlength' => '' ], [], 'inputTextarea', 'Text Area' );
	unset( $f['attributes']['type'] ); return $f;
}

function ff_create( $title, $fields, $thanks, $btn ) {
	$form_fields = wp_json_encode( [ 'fields' => $fields, 'submitButton' => [
		'uniqElKey' => 'submit_btn', 'element' => 'button', 'attributes' => [ 'type' => 'submit', 'class' => '' ],
		'settings' => [ 'align' => 'left', 'button_style' => '', 'container_class' => '', 'help_message' => '', 'background_color' => '#F0D586', 'button_size' => 'md', 'color' => '#0D291D', 'button_ui' => [ 'type' => 'default', 'text' => $btn, 'img_url' => '' ], 'normal_styles' => [], 'hover_styles' => [], 'current_state' => 'normal_styles' ],
		'editor_options' => [ 'title' => 'Submit Button' ],
	] ] );
	$id = wpFluent()->table( 'fluentform_forms' )->where( 'title', $title )->value( 'id' );
	$row = [ 'title' => $title, 'status' => 'published', 'appearance_settings' => '', 'form_fields' => $form_fields, 'has_payment' => 0, 'type' => 'form', 'conditions' => '', 'updated_at' => current_time( 'mysql' ) ];
	if ( $id ) wpFluent()->table( 'fluentform_forms' )->where( 'id', $id )->update( $row );
	else { $row['created_by'] = 1; $row['created_at'] = current_time( 'mysql' ); $id = wpFluent()->table( 'fluentform_forms' )->insertGetId( $row ); }

	$settings = [ 'confirmation' => [ 'redirectTo' => 'samePage', 'messageToShow' => $thanks, 'customPage' => null, 'samePageFormBehavior' => 'hide_form', 'customUrl' => null ],
		'restrictions' => [ 'denyEmptySubmission' => [ 'enabled' => false ] ], 'layout' => [ 'labelPlacement' => 'top', 'helpMessagePlacement' => 'with_label', 'errorMessagePlacement' => 'inline', 'cssClassName' => 'aps-form' ] ];
	$to = aps_setting( 'email', get_option( 'admin_email' ) );
	$notif = [ 'name' => 'Admin notification', 'sendTo' => [ 'type' => 'email', 'email' => $to, 'field' => '', 'routing' => [] ], 'subject' => "New enquiry: $title", 'message' => '{all_data}', 'fromName' => get_bloginfo( 'name' ), 'fromEmail' => '', 'replyTo' => '', 'bcc' => '', 'enabled' => true, 'conditionals' => [ 'status' => false, 'type' => 'all', 'conditions' => [] ] ];
	foreach ( [ 'formSettings' => $settings, 'notifications' => $notif ] as $k => $v ) {
		$exists = wpFluent()->table( 'fluentform_form_meta' )->where( [ 'form_id' => $id, 'meta_key' => $k ] )->value( 'id' );
		$data = [ 'form_id' => $id, 'meta_key' => $k, 'value' => wp_json_encode( $v ) ];
		if ( $exists ) wpFluent()->table( 'fluentform_form_meta' )->where( 'id', $exists )->update( $data ); else wpFluent()->table( 'fluentform_form_meta' )->insert( $data );
	}
	return (int) $id;
}

/* ---- Dealer / distributor onboarding (from B2BModal + proposal: GSTIN, licence, territory) ---- */
$i = 0;
$dealer = [
	ff_el( $i, 'input_text', 'full_name', 'Your full name', true, [ 'placeholder' => 'e.g. Ramesh Kumar' ] ),
	ff_el( $i, 'input_text', 'organization', 'Organisation / firm name', true, [ 'placeholder' => 'e.g. Deccan Agro FPO' ] ),
	ff_el( $i, 'input_text', 'phone', 'Phone / WhatsApp', true, [ 'type' => 'tel', 'placeholder' => '+91 …' ] ),
	ff_el( $i, 'input_email', 'email', 'Email address', false, [ 'type' => 'email', 'placeholder' => 'name@company.com' ], [], 'inputText', 'Email' ),
	ff_select( $i, 'buyer_category', 'Buyer category', [ 'Agro-Input Distributor', 'Farmer Producer Organization (FPO)', 'Commercial Estate / Plantation', 'Agricultural Exporter', 'Government Body / Tender', 'OEM / Private Label Inquiry' ], true ),
	ff_el( $i, 'input_text', 'gstin', 'GSTIN (if registered)', false, [ 'placeholder' => '22AAAAA0000A1Z5' ] ),
	ff_el( $i, 'input_text', 'territory', 'Territory (district & state)', true, [ 'placeholder' => 'e.g. Guntur, Andhra Pradesh' ] ),
	ff_select( $i, 'land_area', 'Target land area covered', [ '10 to 50 Acres', '50 to 200 Acres', '200 to 1,000 Acres', '1,000+ Acres / District level' ] ),
	ff_area( $i, 'requirements', 'Crops and expected quantity', 'Specify crops and the traps / lures you need…' ),
];
$thanks = '<p><strong>Thank you.</strong> Our team will contact you within one working day.</p>';
$id1 = ff_create( 'APS – Dealer & Distributor Onboarding', $dealer, $thanks, 'Submit application' );
update_option( 'aps_form_dealer', $id1 );

/* ---- Contact / product enquiry ---- */
$i = 0;
$contact = [
	ff_el( $i, 'input_text', 'full_name', 'Your name', true ),
	ff_el( $i, 'input_text', 'phone', 'Phone / WhatsApp', true, [ 'type' => 'tel' ] ),
	ff_el( $i, 'input_email', 'email', 'Email', false, [ 'type' => 'email' ], [], 'inputText', 'Email' ),
	ff_select( $i, 'topic', 'I am enquiring about', [ 'Products & pricing', 'Technical / pest advice', 'Dealership', 'Export / OEM', 'Other' ] ),
	ff_area( $i, 'message', 'Message', 'How can we help?' ),
];
$id2 = ff_create( 'APS – Contact & Enquiry', $contact, '<p><strong>Thank you.</strong> We have received your message and will reply shortly.</p>', 'Send message' );
update_option( 'aps_form_contact', $id2 );
WP_CLI::success( "forms: dealer=$id1 contact=$id2" );
