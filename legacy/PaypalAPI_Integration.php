<?php
/*
	PaypalAPI Integration Pack
*/
function qem_get_incontext() {
	$setup = qem_get_stored_payment();

	$mode = ( ( isset( $setup['sandbox'] ) && $setup['sandbox'] == 'checked' ) ? 'SANDBOX' : 'PRODUCTION' );

	if ( $mode == 'SANDBOX' ) {
		$incontext = qem_get_stored_sandbox();
	} else {
		$incontext = qem_get_stored_incontext();
	}

	$incontext['api_mode'] = $mode;

	return $incontext;
}

function qem_start_transaction( $paypal, $values ) {
	$o = $paypal->NewOrder();
	/*
		IPN Code
	*/
	$o->setAttribute( 'CUSTOM', $values['custom'] );
	$o->setAttribute( 'CURRENCYCODE', $values['currency_code'] );
	$o->setAttribute( 'HANDLINGAMT', $values['handling'] );
	$i = $o->NewItem( $values['amount'], $values['quantity'] );

	$i->setAttribute( 'NAME', $values['name'] );
	$i->setAttribute( 'NUMBER', $values['item_number'] );
	$i->setAttribute( 'DESC', "Payment for attendence to " . $values['name'] );
}

function qem_display_deferred() {
	$register = qem_get_stored_register();
	$display  = "<h2>" . $register['replytitle'] . "</h2>";
	$display  .= "<p>" . $register['replydeferred'] . "</p>";

	return $display;
}

function qem_display_success( $return, $return_url, $api ) {
	$payment = qem_get_stored_payment();

	if ( isset( $return['PAYMENTINFO_0_TRANSACTIONID'] ) ) {
		$tid = $return['PAYMENTINFO_0_TRANSACTIONID'];
	} else {
		$tid = $return['TRANSACTIONID'];
	}

	$amt = $return['PAYMENTINFO_0_AMT'];

	$display = "<h2>{$api['confirmationtitle']}</h2>
        <p>{$api['confirmationblurb']}</p>
        <p><b>{$api['confirmationreference']}</b> {$tid}</p>
        <p><b>{$api['confirmationamount']}</b> {$return['AMT']}&nbsp;{$payment['currency']}</p>
        <p><a href=\"{$return_url}\">{$api['confirmationanchor']}</a></p>";

	return $display;
}

function qem_display_pending( $api ) {
	$display = '<h2>' . $api['pendingtitle'] . '</h2>
        <p>' . $api['pendingblurb'] . '</p>';

	return $display;
}

function qem_display_failure( $result, $return_url, $api ) {
	$display = '<h2>' . $api['failuretitle'] . '</h2>
        <p>' . $api['failureblurb'] . '</p>
        <p><a href=' . $return_url . '>' . $api['failureanchor'] . '</a></p>';

	return $display;
}

function qem_mark_paid( $return ) {
	$id      = get_the_ID();
	$message = get_option( 'qem_messages_' . $id );
	$custom  = $return['CUSTOM'];

	if ( $message ) {
		$count = count( $message );
		for ( $i = 0; $i <= $count; $i ++ ) {
			if ( $message[ $i ]['ipn'] == $custom ) {
				$message[ $i ]['ipn'] = 'Paid';
				$auto                 = qem_get_stored_autoresponder( $item );
				if ( $auto['enable'] && $message[ $i ]['youremail'] && $auto['whenconfirm'] == 'afterpayment' ) {
					$register = get_custom_registration_form( $id );
					$values   = array(
						'yourname'      => $message[ $i ]['yourname'],
						'youremail'     => $message[ $i ]['youremail'],
						'yourtelephone' => $message[ $i ]['yourtelephone'],
						'yourmessage'   => $message[ $i ]['yourmessage'],
						'yourplaces'    => $message[ $i ]['yourplaces'],
						'yourblank1'    => $message[ $i ]['yourblank1'],
						'yourdropdown'  => $message[ $i ]['yourdropdown'],
						'yourselector'  => $message[ $i ]['yourselector'],
						'yournumber1'   => $message[ $i ]['yournumber1'],
						'morenames'     => $message[ $i ]['morenames'],
						'ignore'        => $message[ $i ]['ignore'],
					);
					$content  = qem_build_event_message( $values, $register );
					qem_send_confirmation( $auto, $values, $content, $register, $id );
				}
				update_option( 'qem_messages_' . $id, $message );
			}
		}
	}
}

function qem_remove_registration( $return ) {
	$id      = get_the_ID();
	$message = get_option( 'qem_messages_' . $id );
	$custom  = $return['CUSTOM'];

	if ( $message ) {
		$messages = array();
		$count    = count( $message );
		for ( $i = 0; $i < $count; $i ++ ) {
			if ( $message[ $i ]['ipn'] != $custom ) {
				$messages[] = $message[ $i ];
			}
		}

		update_option( 'qem_messages_' . $id, $messages );
	}
}

?>
