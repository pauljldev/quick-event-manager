<?php

function qem_process_payment_form( $values, &$val = array() )
{
    global  $post ;
    global  $qem_fs ;
    $payments = qem_get_stored_payment();
    $register = get_custom_registration_form( $post->ID );
    $ic = qem_get_incontext();
    
    if ( isset( $_REQUEST['action'] ) && "qem_validate_form" == $_REQUEST['action'] ) {
        $page_url = $_SERVER["HTTP_REFERER"];
    } else {
        $page_url = qem_current_page_url();
    }
    
    $reference = $post->post_title;
    $paypalurl = 'https://www.paypal.com/cgi-bin/webscr';
    if ( $payments['sandbox'] ) {
        $paypalurl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    $cost = get_post_meta( $post->ID, 'event_cost', true );
    $register['event_donation'] = get_post_meta( $post->ID, 'event_donation', true );
    $quantity = ( $values['yourplaces'] < 1 ? 1 : strip_tags( $values['yourplaces'] ) );
    $useproducts = get_post_meta( $post->ID, 'event_products', true );
    
    if ( $useproducts ) {
        $product = get_post_meta( $post->ID, 'event_productlist', true );
        $products = explode( ',', $product );
        $quantity = 1;
        $cost = $values['qtyproduct0'] * (double) preg_replace( '/[^.0-9]/', '', $products[2] ) + $values['qtyproduct1'] * (double) preg_replace( '/[^.0-9]/', '', $products[4] ) + $values['qtyproduct2'] * (double) preg_replace( '/[^.0-9]/', '', $products[6] ) + $values['qtyproduct3'] * (double) preg_replace( '/[^.0-9]/', '', $products[8] );
    }
    
    $deposit = get_post_meta( $post->ID, 'event_deposit', true );
    $deposittype = get_post_meta( $post->ID, 'event_deposittype', true );
    if ( $deposit ) {
        $cost = $deposit;
    }
    if ( $deposittype == 'perevent' ) {
        $quantity = 1;
    }
    $cost = preg_replace( '/[^.0-9]/', '', $cost );
    $redirect = qem_get_redirect( $post->ID, $register, $page_url );
    
    if ( qem_get_element( $payments, 'useprocess', false ) && qem_get_element( $payments, 'processpercent', false ) ) {
        $percent = preg_replace( '/[^.,0-9]/', '', $payments['processpercent'] ) / 100;
        $percentprocess = $cost * $quantity * $percent;
    }
    
    if ( qem_get_element( $payments, 'useprocess', false ) && qem_get_element( $payments, 'processfixed', false ) ) {
        $fixedprocess = preg_replace( '/[^.,0-9]/', '', $payments['processfixed'] );
    }
    $handling = $percentprocess + $fixedprocess;
    if ( !$cost ) {
        return;
    }
    $cost = round( $cost, 2 );
    $handling = round( $handling, 2 );
    // $val array is passed by reference - ugh - warning
    $val['name'] = $reference;
    $val['return'] = $redirect;
    $val['cancel'] = $page_url;
    $val['currency_code'] = strtoupper( $payments['currency'] );
    $val['item_number'] = $values['yourname'];
    $val['quantity'] = $quantity;
    $val['amount'] = $cost;
    $val['custom'] = $values['ipn'];
    $val['handling'] = $handling;
    $event_date = get_post_meta( $post->ID, 'event_date', true );
    $event_start = get_post_meta( $post->ID, 'event_start', true );
    $event_date = date_i18n( get_option( 'date_format' ), $event_date );
    $event_date = $event_date . ' ' . $event_start;
    $privacy = '';
    if ( isset( $register['useoptin'] ) && 'checked' == $register['useoptin'] ) {
        if ( isset( $values['youroptin'] ) && 'checked' == $values['youroptin'] ) {
            $privacy = ' - ' . $register['optinblurb'];
        }
    }
    // build paypal form
    $content = '<h2 id="qem_reload">' . $payments['waiting'] . '</h2>
    <form action="' . $paypalurl . '" method="post" name="qempay" id="qempay">
    <input type="hidden" name="cmd" value="_xclick">
    <input type="hidden" name="item_name" value="' . esc_html__( 'Event', 'quick-event-manager' ) . ': ' . $reference . ' [ ' . strip_tags( $values['yourname'] ) . $privacy . ' ]"/>
    <input type="hidden" name="business" value="' . $payments['paypalemail'] . '">
    <input type="hidden" name="bn" value="quickplugins_SP">
    <input type="hidden" name="return" value="' . $redirect . '">
    <input type="hidden" name="cancel_return" value="' . $page_url . '">
    <input type="hidden" name="currency_code" value="' . strtoupper( $payments['currency'] ) . '">
    <input type="hidden" name="item_number" value="' . $event_date . '">
    <input type="hidden" name="quantity" value="' . $quantity . '">
    <input type="hidden" name="amount" value="' . $cost . '">
    <input type="hidden" name="custom" value="' . $values['ipn'] . '">';
    $globalredirect = $register['redirectionurl'];
    $eventredirect = get_post_meta( $post->ID, 'event_redirect', true );
    $redirect = ( $eventredirect ? $eventredirect : $globalredirect );
    
    if ( $redirect ) {
        $redirect_id = get_post_meta( $post->ID, 'event_redirect_id', true );
        
        if ( $redirect_id ) {
            if ( substr( $redirect, -1 ) != '/' ) {
                $redirect = $redirect . '/';
            }
            $id = get_the_ID();
            $redirect = $redirect . "?event=" . $id;
        }
    
    }
    
    if ( $redirect ) {
        $content .= '<input type="hidden" name="return" value="' . $redirect . '">';
    }
    if ( $payments['useprocess'] ) {
        $content .= '<input type="hidden" name="handling" value="' . $handling . '">';
    }
    $content .= '</form>
    <script language="JavaScript">document.getElementById("qempay").submit();</script>';
    return $content;
}

function qem_get_redirect( $id, $register, $page_url )
{
    $redirect = get_post_meta( $id, 'event_redirect', true );
    if ( !$redirect && $register['redirectionurl'] ) {
        $redirect = $register['redirectionurl'];
    }
    $redirect = ( $redirect ? $redirect : $page_url );
    return $redirect;
}

function qem_current_page_url()
{
    $pageURL = 'http';
    if ( isset( $_SERVER["HTTPS"] ) ) {
        if ( $_SERVER["HTTPS"] == "on" ) {
            $pageURL .= "s";
        }
    }
    $pageURL .= "://";
    
    if ( $_SERVER["SERVER_PORT"] != "80" ) {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    
    return $pageURL;
}
