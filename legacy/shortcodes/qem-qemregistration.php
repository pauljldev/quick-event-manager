<?php
function qem_loop_esc() {
	global $post;
	if ( null !== $post ) {
		$pw = get_post_meta( $post->ID, 'event_password_register', true );
		if ( post_password_required( $post ) && $pw ) {
			return get_the_password_form();
		}
	}
	$id       = get_the_ID();

		$values  = get_custom_registration_form( $id );
		$payment = qem_get_stored_payment();
		if ( is_user_logged_in() && qem_get_element( $values, 'showuser', false ) ) {
			$current_user        = wp_get_current_user();
			$values['yourname']  = $current_user->display_name;
			$values['youremail'] = $current_user->user_email;
		}
		$values['yourplaces']  = '1';
		$values['yournumber1'] = '';
		$values['youranswer']  = '';
		$values['yourcoupon']  = qem_get_element( $payment, 'couponcode' );
		$values['ipn']         = md5( wp_rand() );
		$digit1                = wp_rand( 1, 10 );
		$digit2                = wp_rand( 1, 10 );
		if ( $digit2 >= $digit1 ) {
			$values['thesum'] = "$digit1 + $digit2";
			$values['answer'] = $digit1 + $digit2;
		} else {
			$values['thesum'] = "$digit1 - $digit2";
			$values['answer'] = $digit1 - $digit2;
		}
		if ( ( is_user_logged_in() && qem_get_element( $values, 'registeredusers', false ) ) || ! qem_get_element( $values, 'registeredusers', false ) ) {
			return qem_display_form_esc( $values, array(), null );
		}
}
