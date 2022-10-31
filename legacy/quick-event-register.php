<?php

/*
	Add: wordpress hooks for ajax
*/
use  Quick_Event_Manager\Plugin\Control\Admin_Template_Loader ;
add_action( 'wp_ajax_qem_validate_form', 'qem_ajax_validation' );
add_action( 'wp_ajax_nopriv_qem_validate_form', 'qem_ajax_validation' );
function qem_registration_fields()
{
    // Only apply defaults to fields that are updatable from the edit form
    return array(
        'yourname'        => array(
        'form_field'  => 1,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'youremail'       => array(
        'form_field'  => 2,
        'sanitize_cb' => 'sanitize_email',
        'default'     => '',
    ),
        'yourplaces'      => array(
        'form_field'  => 5,
        'sanitize_cb' => 'absint',
        'default'     => '1',
    ),
        'yourtelephone'   => array(
        'form_field'  => 4,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'notattend'       => array(
        'form_field'  => 3,
        'sanitize_cb' => 'sanitize_text_field',
    ),
        'morenames'       => array(
        'sanitize_cb' => 'sanitize_textarea_field',
        'default'     => '',
    ),
        'yourmessage'     => array(
        'form_field'  => 6,
        'sanitize_cb' => 'sanitize_textarea_field',
        'default'     => '',
    ),
        'youranswer'      => array(
        'form_field'  => 7,
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'answer'          => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'thesum'          => array(
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'qem-copy'        => array(
        'form_field'  => 8,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'yourblank1'      => array(
        'form_field'  => 9,
        'sanitize_cb' => 'sanitize_textarea_field',
        'default'     => '',
    ),
        'yourblank2'      => array(
        'form_field'  => 10,
        'sanitize_cb' => 'sanitize_textarea_field',
        'default'     => '',
    ),
        'yourdropdown'    => array(
        'form_field'  => 11,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'yournumber1'     => array(
        'form_field'  => 12,
        'sanitize_cb' => 'qem_sanitize_number',
        'default'     => '',
    ),
        'yourselector'    => array(
        'form_field'  => 14,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'youroptin'       => array(
        'form_field'  => 15,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'yourcheckslist'  => array(
        'form_field'  => 16,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'donation_amount' => array(
        'form_field'  => 17,
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'ipn'             => array(
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'validator'       => array(
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'action'          => array(
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'terms'           => array(
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
        'qtyproduct0'     => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'qtyproduct1'     => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'qtyproduct2'     => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'qtyproduct3'     => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'qtyproduct4'     => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'id'              => array(
        'sanitize_cb' => 'absint',
        'default'     => '',
    ),
        'ignore'          => array(
        'sanitize_cb' => 'sanitize_text_field',
        'default'     => '',
    ),
    );
}

function qem_sanitize_forms_values( $input )
{
    $fields = qem_registration_fields();
    foreach ( $input as $key => $value ) {
        
        if ( isset( $fields[$key] ) ) {
            $cb = qem_get_element( $fields, $key )['sanitize_cb'];
            $output[$key] = $cb( qem_get_element( $input, $key ) );
        } else {
            if ( preg_match( '/checks_\\d*/', $key ) ) {
                // hard code this as it is only one
                $output[$key] = sanitize_text_field( qem_get_element( $input, $key ) );
            }
            if ( preg_match( '/name\\d*/', $key ) ) {
                // hard code this as it is only one
                $output[$key] = sanitize_text_field( qem_get_element( $input, $key ) );
            }
            if ( preg_match( '/email\\d*/', $key ) ) {
                // hard code this as it is only one
                $output[$key] = sanitize_email( qem_get_element( $input, $key ) );
            }
        }
    
    }
    return $output;
}

function qem_sanitize_number( $in )
{
    return preg_replace( "/[^0-9.]/", '', $in );
}

/*
	Add: qem_ajax_validation
*/
/**
 * @throws \Stripe\Exception\ApiErrorException
 */
function qem_ajax_validation()
{
    header( "Content-Type: application/json", true );
    global  $post ;
    global  $qem_fs ;
    $event = $_POST['id'];
    $args = array(
        'p'         => $event,
        'post_type' => 'any',
    );
    $json = array(
        'success' => false,
        'errors'  => array(),
    );
    // Start "The Loop"
    $query = new WP_Query( $args );
    $formvalues = qem_sanitize_forms_values( $_POST );
    $formerrors = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $register = get_custom_registration_form( $event );
            $verify = qem_verify_form( $formvalues, $formerrors, true );
            /*
            	Build required objects
            */
            $payment = qem_get_stored_payment();
            $event = event_get_stored_options();
            $id = ( isset( $post->ID ) ? get_the_ID() : null );
            //  if there is a vlue use counter
            $usecounter = get_post_meta( $id, 'event_number', true );
            // the number
            $number = (int) get_post_meta( $id, 'event_number', true );
            $json['coming'] = qem_places(
                $register,
                $payment,
                $id,
                $usecounter,
                null
            );
            $num = qem_number_places_available( $id );
            // places seems not used here
            $places = (int) $number - (int) $num;
            $json['places'] = '';
            $json['ignore'] = false;
            if ( qem_get_element( $register, 'placesavailable' ) && $number ) {
                $json['places'] .= '<p id="whoscoming">' . qem_get_element( $register, 'placesbefore' ) . ' ' . $num . ' ' . qem_get_element( $register, 'placesafter' ) . '<p>';
            }
            
            if ( !$verify ) {
                
                if ( isset( $formerrors['alreadyregistered'] ) ) {
                    
                    if ( $formerrors['alreadyregistered'] == 'checked' ) {
                        $json['title'] = qem_get_element( $register, 'alreadyregistered' );
                    } else {
                        $json['title'] = qem_get_element( $register, 'nameremoved' );
                    }
                    
                    if ( qem_get_element( $register, 'useread_more' ) ) {
                        $json['form'] = '<p><a href="' . get_permalink() . '">' . qem_get_element( $register, 'read_more' ) . '</a></p>';
                    }
                } else {
                    $json['title'] = qem_get_element( $register, 'error' );
                }
                
                /*
                	Format error array
                */
                $errors = array();
                foreach ( $formerrors as $k => $v ) {
                    array_push( $errors, array(
                        'name'  => $k,
                        'error' => $v,
                    ) );
                }
                $json['errors'] = $errors;
            } else {
                $id = ( isset( $post->ID ) ? get_the_ID() : null );
                $cost = get_post_meta( $id, 'event_cost', true );
                $useproducts = get_post_meta( $id, 'event_products', true );
                if ( $useproducts ) {
                    $cost = $useproducts;
                }
                $number = get_post_meta( $id, 'event_number', true );
                $paypal = get_post_meta( $id, 'event_paypal', true );
                $json['success'] = true;
                
                if ( isset( $formvalues['ignore'] ) && $formvalues['ignore'] == 'checked' && qem_get_element( $register, 'ignorepayment' ) == 'checked' ) {
                    qem_process_form( $formvalues, true );
                    $json['ignore'] = true;
                } else {
                    // qem_ajax_submit($formvalues);
                    qem_process_form( $formvalues, true );
                    if ( $paypal && $cost ) {
                        $payment['paypal'] = 'checked';
                    }
                    $payment = qem_get_stored_payment();
                    $ic = qem_get_incontext();
                    $usecounter = get_post_meta( $id, 'event_number', true );
                    $json['coming'] = qem_places(
                        $register,
                        $payment,
                        $id,
                        $usecounter,
                        null
                    );
                    $num = qem_number_places_available( $id );
                    $json['places'] = '';
                    $json['form'] = '';
                    $json['form'] .= '<a id="qem_reload"></a>';
                    $values = array();
                    
                    if ( $paypal && $cost && !qem_get_element( $formvalues, 'ignore' ) ) {
                        $json['form'] .= qem_process_payment_form( $formvalues, $values );
                    } elseif ( qem_get_element( $register, 'useread_more' ) ) {
                        $json['form'] .= '<p><a href="' . get_permalink() . '">' . qem_get_element( $register, 'read_more' ) . '</a></p>';
                    }
                    
                    
                    if ( empty($formerrors) ) {
                        $values['amount'] = ( isset( $values['amount'] ) ? $values['amount'] : 0 );
                        $values['quantity'] = ( isset( $values['quantity'] ) ? $values['quantity'] : 0 );
                        $values['handling'] = ( isset( $values['handling'] ) ? $values['handling'] : 0 );
                        $total = $values['amount'] * $values['quantity'] + $values['handling'];
                        $json['ic'] = array(
                            'use' => false,
                        );
                        $json['title'] = qem_get_element( $register, 'replytitle' );
                        if ( !$num && qem_get_element( $event['active_buttons'], 'field5' ) && $number && qem_get_element( $register, 'waitinglist' ) ) {
                            $register['replyblurb'] = qem_get_element( $register, 'waitinglistreply' );
                        }
                        if ( qem_get_element( $register, 'moderate' ) ) {
                            $register['replyblurb'] = qem_get_element( $register, 'moderatereply' );
                        }
                        $json['blurb'] = qem_get_element( $register, 'replyblurb' );
                        /*
                        	Add total to the messages table
                        */
                        $messages = get_option( 'qem_messages_' . $id );
                        for ( $i = 0 ;  $i < count( $messages ) ;  $i++ ) {
                            
                            if ( isset( $values['custom'] ) && $values['custom'] == $messages[$i]['ipn'] ) {
                                $messages[$i]['total'] = $total;
                                $messages[$i]['custom'] = qem_get_element( $values, 'custom' );
                            }
                        
                        }
                        update_option( 'qem_messages_' . $id, $messages );
                    }
                
                }
                
                $globalredirect = qem_get_element( $register, 'redirectionurl' );
                $eventredirect = get_post_meta( $post->ID, 'event_redirect', true );
                $redirect = ( $eventredirect ? $eventredirect : $globalredirect );
                $redirect_id = get_post_meta( $id, 'event_redirect_id', true );
                $redirecting = false;
                
                if ( !empty($redirect) && empty($paypal) ) {
                    
                    if ( $redirect_id ) {
                        if ( substr( $redirect, -1 ) != '/' ) {
                            $redirect = $redirect . '/';
                        }
                        $id = get_the_ID();
                        $redirect = $redirect . "?event=" . $id;
                    }
                    
                    $redirecting = true;
                }
                
                $json['redirect'] = array(
                    'redirect' => $redirecting,
                    'url'      => $redirect,
                );
            }
        
        }
    }
    echo  json_encode( $json ) ;
    exit;
}

/*
	@Change
	@Changed from "Echo" to "Return"
*/
/**
 * @return false|string
 */
function qem_loop()
{
    $ic = qem_get_incontext();
    global  $post ;
    
    if ( null !== $post ) {
        $pw = get_post_meta( $post->ID, 'event_password_register', true );
        if ( post_password_required( $post ) && $pw ) {
            return get_the_password_form();
        }
    }
    
    $id = get_the_ID();
    $link = get_permalink( $id );
    $payments = qem_get_stored_payment();
    $register = qem_get_stored_register();
    $api = qem_get_stored_api();
    if ( !qem_get_element( $api, 'useincontext', false ) ) {
        $api['useapi'] = 'paypal';
    }
    $uic = qem_get_element( $ic, 'useincontext' );
    
    if ( isset( $_POST['module'] ) && $_POST['module'] == 'deferred' && qem_get_element( $register, 'ignorepayment' ) == 'checked' ) {
        return qem_display_deferred();
    } else {
        
        if ( qem_get_element( $ic, 'useapi' ) == 'paypal' && qem_get_element( $ic, 'useincontext' ) && (isset( $_GET['token'] ) && isset( $_GET['PayerID'] )) ) {
            // Success (allegedly)
            $mode = qem_get_element( $ic, 'api_mode' );
            $paypal = new PaypalAPI(
                qem_get_element( $ic, 'api_username' ),
                qem_get_element( $ic, 'api_password' ),
                qem_get_element( $ic, 'api_key' ),
                $mode
            );
            $paypal->setMethod( 'GetExpressCheckoutDetails' );
            $paypal->setAttribute( 'TOKEN', sanitize_text_field( qem_get_element( $_GET, 'token' ) ) );
            $return = $paypal->execute();
            
            if ( qem_get_element( $return, 'ACK' ) == 'Success' ) {
                switch ( qem_get_element( $return, 'CHECKOUTSTATUS' ) ) {
                    case 'PaymentActionNotInitiated':
                        //its waiting for us to process it!
                        $paypal->reloadFromResponse( 'DoExpressCheckoutPayment' );
                        $r = $paypal->execute();
                        
                        if ( qem_get_element( $r, 'PAYMENTINFO_0_ACK' ) == 'Success' ) {
                            /*
                            	Reload Express Checkout Details
                            */
                            $paypal = new PaypalAPI(
                                qem_get_element( $ic, 'api_username' ),
                                qem_get_element( $ic, 'api_password' ),
                                qem_get_element( $ic, 'api_key' ),
                                $mode
                            );
                            $paypal->setMethod( 'GetExpressCheckoutDetails' );
                            $paypal->setAttribute( 'TOKEN', sanitize_text_field( qem_get_element( $_GET, 'token' ) ) );
                            $return = $paypal->execute();
                            qem_mark_paid( $return );
                            return qem_display_success( $return, $link, $api );
                        } else {
                            qem_remove_registration( $return );
                            return qem_display_failure( $return, $link, $api );
                        }
                        
                        break;
                    case 'PaymentActionFailed':
                        //payment failed
                        qem_remove_registration( $return );
                        return qem_display_failure( $return, $link, $api );
                        break;
                    case 'PaymentActionInProgress':
                        //processing/pending
                        return qem_display_pending( $api );
                        break;
                    case 'PaymentActionCompleted':
                        //100% Success
                        return qem_display_success( $return, $link, $api );
                        break;
                }
            } else {
                qem_remove_registration( $return );
                return qem_display_failure( $return, $link, $api );
            }
        
        } elseif ( qem_get_element( $ic, 'useapi' ) == 'paypal' && qem_get_element( $ic, 'useincontext' ) == 'checked' && isset( $_GET['token'] ) ) {
            // Failure
            $mode = qem_get_element( $ic, 'api_mode' );
            $paypal = new PaypalAPI(
                qem_get_element( $ic, 'api_username' ),
                qem_get_element( $ic, 'api_password' ),
                qem_get_element( $ic, 'api_key' ),
                $mode
            );
            $paypal->setMethod( 'GetExpressCheckoutDetails' );
            $paypal->setAttribute( 'TOKEN', sanitize_text_field( qem_get_element( $_GET, 'token' ) ) );
            $return = $paypal->execute();
            qem_remove_registration( $return );
            return qem_display_failure( $return, $link, $api );
        }
    
    }
    
    
    if ( isset( $_POST['qemregister' . $id] ) && !empty($_POST['qemregister' . $id]) ) {
        $formvalues = $_POST;
        $formerrors = array();
        
        if ( !qem_verify_form( $formvalues, $formerrors ) ) {
            return qem_display_form( $formvalues, $formerrors, null );
        } else {
            qem_process_form( $formvalues );
            return qem_display_form( $formvalues, array(), 'checked' );
        }
    
    } else {
        $values = get_custom_registration_form( $id );
        $payment = qem_get_stored_payment();
        
        if ( is_user_logged_in() && qem_get_element( $values, 'showuser', false ) ) {
            $current_user = wp_get_current_user();
            $values['yourname'] = $current_user->display_name;
            $values['youremail'] = $current_user->user_email;
        }
        
        $values['yourplaces'] = '1';
        $values['yournumber1'] = '';
        $values['youranswer'] = '';
        $values['yourcoupon'] = qem_get_element( $payment, 'couponcode' );
        $values['ipn'] = md5( mt_rand() );
        $digit1 = mt_rand( 1, 10 );
        $digit2 = mt_rand( 1, 10 );
        
        if ( $digit2 >= $digit1 ) {
            $values['thesum'] = "{$digit1} + {$digit2}";
            $values['answer'] = $digit1 + $digit2;
        } else {
            $values['thesum'] = "{$digit1} - {$digit2}";
            $values['answer'] = $digit1 - $digit2;
        }
        
        if ( is_user_logged_in() && qem_get_element( $values, 'registeredusers', false ) || !qem_get_element( $values, 'registeredusers', false ) ) {
            return qem_display_form( $values, array(), null );
        }
    }

}

/**
 * @param false $id
 *
 * @return array
 */
function get_custom_registration_form( $id )
{
    global  $qem_fs ;
    $register = qem_get_stored_register();
    $usecustomform = get_post_meta( $id, 'usecustomform', true );
    
    if ( $usecustomform ) {
        $arr = array(
            'usename',
            'usemail',
            'usetelephone',
            'useplaces',
            'usedonation',
            'maxplaces',
            'usemessage',
            'useattend',
            'useblank1',
            'useblank2',
            'usedropdown',
            'useselector',
            'usenumber1',
            'usechecks',
            'useaddinfo',
            'addinfo',
            'usemorenames',
            'moreemail',
            'useterms',
            'usecaptcha',
            'usecoupon'
        );
        foreach ( $arr as $item ) {
            $register[$item] = get_post_meta( $id, $item, true );
        }
    }
    
    return $register;
}

function qem_display_form( $values, $errors, $registered )
{
    // was added to enable form password protection but not implemented
    return qem_display_form_unprotected( $values, $errors, $registered );
}

/**
 * @param $values
 * @param $errors
 * @param $registered
 *
 * @return false|string
 */
function qem_display_form_unprotected( $values, $errors, $registered )
{
    global  $qem_fs ;
    global  $post ;
    $id = ( isset( $post->ID ) ? get_the_ID() : null );
    $qem_number_places_available = qem_number_places_available( $id );
    $event_number_max = get_post_meta( $id, 'event_number', true );
    $cutoff = '';
    $notopen = '';
    $content = '';
    $cutoff_display_date = '';
    $cutoffdate = get_post_meta( $id, 'event_cutoff_date', true );
    
    if ( !empty($cutoffdate) && $cutoffdate <= time() ) {
        $cutoff = 'checked';
        $utc_timestamp_converted = date( 'Y-m-d H:i:s', $cutoffdate );
        $cutoff_display_date = get_date_from_gmt( $utc_timestamp_converted, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
    }
    
    $style = qem_get_register_style();
    $payment = qem_get_stored_payment();
    $api = qem_get_stored_api();
    if ( !qem_get_element( $api, 'useincontext', false ) ) {
        $api['useapi'] = 'paypal';
    }
    $placesleft = '';
    $register = get_custom_registration_form( $id );
    $useproducts = get_post_meta( $id, 'event_products', true );
    $cost = get_post_meta( $id, 'event_cost', true );
    $paypal = get_post_meta( $id, 'event_paypal', true );
    $cutoffmessage = ( get_post_meta( $id, 'event_show_cutoff', true ) ? get_post_meta( $id, 'event_show_cutoff_blurb', true ) : null );
    if ( $paypal && $cost ) {
        $payment['paypal'] = 'checked';
    }
    $usecustomform = get_post_meta( $id, 'usecustomform', true );
    $usecounter = get_post_meta( $id, 'event_number', true );
    $register['event_maxplaces'] = get_post_meta( $id, 'event_maxplaces', true );
    $register['event_requiredplaces'] = get_post_meta( $id, 'event_requiredplaces', true );
    $register['event_getemails'] = get_post_meta( $id, 'event_getemails', true );
    $register['event_getnames'] = get_post_meta( $id, 'event_getnames', true );
    $register['event_donation'] = get_post_meta( $id, 'event_donation', true );
    $content = "<script type='text/javascript'>ajaxurl = '" . admin_url( 'admin-ajax.php' ) . "';</script>";
    if ( function_exists( 'qem_whosnotcoming' ) ) {
        $content .= qem_whosnotcoming();
    }
    
    if ( qem_get_element( $errors, 'spam', false ) ) {
        $errors['alreadyregistered'] = 'checked';
        $register['alreadyregistered'] = qem_get_element( $register, 'spam' );
    } elseif ( $registered ) {
        if ( !empty(qem_get_element( $register, 'replytitle' )) ) {
            $register['replytitle'] = '<h2>' . qem_get_element( $register, 'replytitle' ) . '</h2>';
        }
        if ( $qem_number_places_available == 0 && qem_get_element( $register, 'placesavailable' ) && $event_number_max && qem_get_element( $register, 'waitinglist' ) ) {
            $register['replyblurb'] = qem_get_element( $register, 'waitinglistreply' );
        }
        if ( qem_get_element( $register, 'moderate' ) ) {
            $register['replyblurb'] = qem_get_element( $register, 'moderatereply' );
        }
        if ( !empty(qem_get_element( $register, 'replyblurb' )) ) {
            $register['replyblurb'] = '<p>' . qem_get_element( $register, 'replyblurb' ) . '</p>';
        }
        $content .= qem_get_element( $register, 'replytitle' ) . qem_get_element( $register, 'replyblurb' );
        
        if ( $paypal && $cost && !qem_get_element( $values, 'ignore', false ) ) {
            $content .= '<a id="qem_reload"></a>';
            $content .= '<script type="text/javascript" language="javascript">
        document.querySelector("#qem_reload").scrollIntoView();
        </script>';
            $content .= qem_process_payment_form( $values );
        } elseif ( qem_get_element( $register, 'useread_more' ) ) {
            $content .= '<p><a href="' . get_permalink() . '">' . qem_get_element( $register, 'read_more' ) . '</a></p>';
        }
        
        $content .= '<a id="qem_reload"></a>';
    } elseif ( 'checked' == $cutoff ) {
        
        if ( get_post_meta( $id, 'qem_reg_closed_date_time_show_msg', true ) ) {
            $template_loader = new \Quick_Event_Manager\Plugin\Control\User_Template_Loader();
            $template_loader->set_template_data( array(
                'message'         => get_post_meta( $id, 'qem_reg_closed_date_time_msg', true ),
                'template_loader' => $template_loader,
                'freemius'        => $qem_fs,
            ) );
            $template_loader->get_template_part( 'registration_closed' );
            $content = $template_loader->get_output();
        }
        
        $qem_number_places_available = '';
    } elseif ( 'checked' == $notopen ) {
    } elseif ( $event_number_max !== '' && $event_number_max >= 0 && $qem_number_places_available == 0 && !qem_get_element( $register, 'waitinglist' ) ) {
        $content .= '';
        $qem_number_places_available = '';
    } elseif ( qem_get_element( $errors, 'alreadyregistered', false ) == 'checked' ) {
        $content .= "<div class='places'>" . $placesleft . '</div><h2>' . qem_get_element( $register, 'alreadyregistered' ) . '</h2>';
        if ( qem_get_element( $register, 'useread_more' ) ) {
            $content .= '<p><a href="' . get_permalink() . '">' . qem_get_element( $register, 'read_more' ) . '</a></p>';
        }
        $content .= '<a id="qem_reload"></a>';
    } elseif ( qem_get_element( $errors, 'alreadyregistered', false ) == 'removed' ) {
        $content .= "<div class='places'>" . $placesleft . '</div><h2>' . qem_get_element( $register, 'nameremoved' ) . '</h2>';
        if ( qem_get_element( $register, 'useread_more' ) ) {
            $content .= '<p><a href="' . get_permalink() . '">' . qem_get_element( $register, 'read_more' ) . '</a></p>';
        }
        $content .= '<a id="qem_reload"></a>';
    } else {
        if ( !empty(qem_get_element( $register, 'title' )) ) {
            $register['thetitle'] = '<h2>' . qem_get_element( $register, 'title' ) . '</h2>';
        }
        if ( !empty(qem_get_element( $register, 'blurb' )) ) {
            $register['blurb'] = '<p>' . qem_get_element( $register, 'blurb' ) . '</p>';
        }
        $content .= '<div class="qem-register">';
        if ( qem_get_element( $register, 'hideform', false ) && count( $errors ) == 0 ) {
            $content .= '<div class="toggle-qem"><a href="#">' . qem_get_element( $register, 'title' ) . '</a></div>
            <div class="apply" style="display: none;">';
        }
        $content .= '<div id="' . qem_get_element( $style, 'border' ) . '">';
        
        if ( count( $errors ) > 0 ) {
            $content .= "<h2 class='qem-error-header'>" . qem_get_element( $register, 'error' ) . "</h2>\r\t";
            $arr = array(
                'yourname',
                'youremail',
                'yourtelephone',
                'yourplaces',
                'yourmessage',
                'youranswer',
                'yourblank1',
                'yourblank2',
                'yourdropdown',
                'yourcoupon'
            );
            foreach ( $arr as $item ) {
                if ( qem_get_element( $errors, $item ) == 'error' ) {
                    $errors[$item] = ' class="qem-error"';
                }
            }
            if ( qem_get_element( $errors, 'yourcoupon' ) ) {
                $register['blurb'] = '<p>Invalid Coupon Code</p>';
            }
            if ( qem_get_element( $errors, 'yourplaces' ) ) {
                $errors['yourplaces'] = 'border:1px solid red;';
            }
            if ( qem_get_element( $errors, 'yournumber1' ) ) {
                $errors['yournumber1'] = 'border:1px solid red;';
            }
            if ( qem_get_element( $errors, 'youranswer' ) ) {
                $errors['youranswer'] = 'border:1px solid red;';
            }
        } else {
            
            if ( !qem_get_element( $register, 'hideform', false ) || count( $errors ) != 0 ) {
                $content .= qem_get_element( $register, 'thetitle' );
            } else {
                $content .= '<h2></h2>';
            }
            
            if ( !$registered ) {
                $content .= qem_get_element( $register, 'blurb' );
            }
        }
        
        if ( $cutoffmessage && 'checked' === $cutoff ) {
            $content .= '<p class="qem-cutoff-message"><strong>' . $cutoffmessage . ' ' . $cutoff_display_date . '</strong></p>';
        }
        $content .= "<div class='places'>" . $placesleft . "</div>";
        $content .= '<div class="qem-form"><form action="" method="POST" enctype="multipart/form-data" id="' . $id . '">';
        $content .= '<input type="hidden" name="id" value="' . $id . '" />';
        foreach ( explode( ',', qem_get_element( $register, 'sort' ) ) as $name ) {
            $required = '';
            switch ( $name ) {
                case 'field1':
                    
                    if ( qem_get_element( $register, 'usename' ) ) {
                        $required = ( qem_get_element( $register, 'reqname' ) ? 'class="required"' : '' );
                        
                        if ( qem_get_element( $register, 'event_maxplaces' ) > 1 && 'checked' !== qem_get_element( $register, 'event_getnames' ) ) {
                            $qem_number_places_available = qem_get_element( $register, 'event_maxplaces' );
                            $content .= '<table width="100%">
                        <tr><th>' . __( 'Names', 'quick-event-manager' ) . '</th>';
                            if ( qem_get_element( $register, 'event_getemails' ) ) {
                                $content .= '<th>' . __( 'Email', 'quick-event-manager' ) . '</th>';
                            }
                            $content .= '</tr>';
                            $content .= qem_get_element( $errors, 'name' . $i );
                            for ( $i = 1 ;  $i <= $qem_number_places_available ;  $i++ ) {
                                $content .= '<tr><td><input type="text" name="name' . $i . '" ' . $required . ' ' . qem_get_element( $errors, 'name' . $i ) . ' value="' . qem_get_element( $values, 'name' . $i ) . '"></td>';
                                if ( qem_get_element( $register, 'event_getemails' ) ) {
                                    $content .= '<td><input type="text" name="email' . $i . '" ' . $required . ' ' . qem_get_element( $errors, 'email' . $i ) . ' value="' . qem_get_element( $values, 'email' . $i ) . '"></td>';
                                }
                                $content .= '</tr>';
                            }
                            $content .= '</table>';
                        } else {
                            $content .= '<input id="yourname" name="yourname" ' . $required . ' ' . qem_get_element( $errors, 'yourname' ) . ' type="text" value="' . qem_get_element( $values, 'yourname' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourname' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourname' ) . '\') {this.value = \'\';}" />' . "\n";
                        }
                    
                    }
                    
                    break;
                case 'field2':
                    
                    if ( qem_get_element( $register, 'usemail' ) && (qem_get_element( $register, 'event_maxplaces' ) < 2 || 'checked' === qem_get_element( $register, 'event_getnames' )) ) {
                        $required = ( qem_get_element( $register, 'reqmail' ) ? 'class="required"' : '' );
                        $content .= '<input id="email" name="youremail" ' . $required . ' ' . qem_get_element( $errors, 'youremail' ) . ' type="text" value="' . qem_get_element( $values, 'youremail' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'youremail' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'youremail' ) . '\') {this.value = \'\';}" />';
                    }
                    
                    break;
                case 'field3':
                    if ( qem_get_element( $register, 'useattend', false ) ) {
                        $content .= '<p><input type="checkbox" name="notattend" value="checked" ' . qem_get_element( $values, 'notattend' ) . ' /> ' . qem_get_element( $register, 'yourattend' ) . '</p>';
                    }
                    break;
                case 'field4':
                    
                    if ( qem_get_element( $register, 'usetelephone', false ) ) {
                        $required = ( qem_get_element( $register, 'reqtelephone' ) ? 'class="required"' : '' );
                        $content .= '<input id="email" name="yourtelephone" ' . $required . ' ' . qem_get_element( $errors, 'yourtelephone' ) . ' type="text" value="' . qem_get_element( $values, 'yourtelephone' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourtelephone' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourtelephone' ) . '\') {this.value = \'\';}" />';
                    }
                    
                    break;
                case 'field5':
                    
                    if ( $useproducts ) {
                        $product = get_post_meta( $id, 'event_productlist', true );
                        $products = explode( ',', trim( $product, ',' ) );
                        $products = array_chunk( $products, 2 );
                        for ( $i = 0 ;  $i < count( $products ) ;  $i++ ) {
                            list( $Mlabel, $Mcost ) = qem_get_element( $products, $i );
                            $products[$i] = array(
                                'label' => (string) $Mlabel,
                                'cost'  => (double) $Mcost,
                            );
                        }
                        $content .= '<script type="text/javascript"> qem_multi_' . $id . ' = ' . json_encode( $products ) . '; </script>';
                        if ( qem_get_element( $payment, 'attendeelabel' ) ) {
                            $content .= '<p><b>' . qem_get_element( $payment, 'attendeelabel' ) . '</b></p>';
                        }
                        $content .= '<div class="qem_multi_holder" id="qem_multi_' . $id . '">';
                        for ( $i = 0 ;  $i < count( $products ) ;  $i++ ) {
                            $label = qem_get_element( $payment, 'itemlabel' );
                            $label = str_replace( '[label]', $products[$i]['label'], $label );
                            $label = str_replace( '[currency]', qem_get_element( $payment, 'currencysymbol' ), $label );
                            $label = str_replace( '[cost]', $products[$i]['cost'], $label );
                            $content .= '<div style="clear:both;"><b><span style="float:left">' . $label . '</span><span style="float:right;width:3em;"><input type="text" style="text-align:right;" class="qem-multi-product" name="qtyproduct' . $i . '" id="qtyproduct' . $i . '" value="" /></span></b></div>';
                        }
                        $content .= '<div style="clear:both;"></div>
                    <p style="clear:both"><span style="float:left">' . $payment['totallabel'] . '</span><span style="float:right;width:5em;text-align:right;" id="total_price">' . qem_get_element( $payment, 'currencysymbol' ) . '<span class="qem_output">0.00</span></span></p>
                    <div style="clear:both;"></div>';
                        $content .= '</div>';
                    } elseif ( qem_get_element( $register, 'useplaces', false ) ) {
                        $content .= '<p>';
                        if ( $register['placesposition'] == 'right' ) {
                            $content .= qem_get_element( $register, 'yourplaces' ) . ' ';
                        }
                        $content .= '<input id="yourplaces" name="yourplaces" min="1" type="number"' . qem_get_element( $errors, 'yourplaces' ) . ' style="width:3em;margin-right:5px" value="' . qem_get_element( $values, 'yourplaces' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourplaces' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourplaces' ) . '\') {this.value = \'\';}" />';
                        if ( qem_get_element( $register, 'placesposition' ) != 'right' ) {
                            $content .= ' ' . qem_get_element( $register, 'yourplaces' );
                        }
                        $content .= '</p>';
                    } else {
                        $content .= '<input type="hidden" name="yourplaces" value="1">';
                    }
                    
                    if ( qem_get_element( $register, 'usemorenames', false ) && !qem_get_element( $register, 'maxplaces', false ) ) {
                        $content .= '<div id="morenames" hidden="hidden"><p>' . qem_get_element( $register, 'morenames' ) . '</p>
                        <textarea rows="4" label="message" name="morenames"></textarea>
                        </div>';
                    }
                    break;
                case 'field6':
                    
                    if ( qem_get_element( $register, 'usemessage', false ) ) {
                        $required = ( qem_get_element( $register, 'reqmessage' ) ? 'class="required"' : '' );
                        $content .= '<textarea rows="4" label="message" name="yourmessage" ' . $required . ' ' . qem_get_element( $errors, 'yourmessage' ) . ' onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourmessage' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourmessage' ) . '\') {this.value = \'\';}" />' . stripslashes( qem_get_element( $values, 'yourmessage' ) ) . '</textarea>';
                    }
                    
                    break;
                case 'field7':
                    if ( qem_get_element( $register, 'usecaptcha', false ) ) {
                        $content .= '<p>' . qem_get_element( $register, 'captchalabel' ) . ' ' . $values['thesum'] . ' = <input id="youranswer" name="youranswer" class="required" type="text"' . qem_get_element( $errors, 'youranswer' ) . ' style="width:3em;"  value="' . qem_get_element( $values, 'youranswer' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'youranswer' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'youranswer' ) . '\') {this.value = \'\';}" /><input type="hidden" name="answer" value="' . strip_tags( qem_get_element( $values, 'answer' ) ) . '" />
<input type="hidden" name="thesum" value="' . strip_tags( qem_get_element( $values, 'thesum' ) ) . '" /></p>';
                    }
                    break;
                case 'field8':
                    
                    if ( qem_get_element( $register, 'usecopy', false ) ) {
                        if ( qem_get_element( $register, 'copychecked' ) ) {
                            $copychecked = 'checked';
                        }
                        $content .= '<p><input type="checkbox" name="qem-copy" value="checked" ' . qem_get_element( $values, 'qem-copy' ) . ' ' . $copychecked . ' /> ' . qem_get_element( $register, 'copyblurb' ) . '</p>';
                    }
                    
                    break;
                case 'field9':
                    
                    if ( qem_get_element( $register, 'useblank1', false ) ) {
                        $required = ( qem_get_element( $register, 'reqblank1' ) ? 'class="required"' : '' );
                        
                        if ( qem_get_element( $register, 'yourblank1textarea' ) ) {
                            $content .= '<textarea rows="4" label="blank1" name="yourblank1" ' . $required . ' ' . qem_get_element( $errors, 'yourblank1' ) . ' onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourblank1' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourblank1' ) . '\') {this.value = \'\';}" />' . stripslashes( qem_get_element( $values, 'yourblank1' ) ) . '</textarea>';
                        } else {
                            $content .= '<input id="yourblank1" name="yourblank1" ' . $required . ' ' . qem_get_element( $errors, 'yourblank1' ) . ' type="text" value="' . qem_get_element( $values, 'yourblank1' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourblank1' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourblank1' ) . '\') {this.value = \'\';}" />';
                        }
                    
                    }
                    
                    break;
                case 'field10':
                    
                    if ( qem_get_element( $register, 'useblank2', false ) ) {
                        $required = ( qem_get_element( $register, 'reqblank2' ) ? 'class="required"' : '' );
                        
                        if ( qem_get_element( $register, 'yourblank2textarea' ) ) {
                            $content .= '<textarea rows="4" label="blank2" name="yourblank2" ' . $required . ' ' . qem_get_element( $errors, 'yourblank2' ) . ' onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourblank2' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourblank2' ) . '\') {this.value = \'\';}" />' . stripslashes( qem_get_element( $values, 'yourblank2' ) ) . '</textarea>';
                        } else {
                            $content .= '<input id="yourblank2" name="yourblank2" ' . $required . ' ' . qem_get_element( $errors, 'yourblank2' ) . ' type="text" value="' . qem_get_element( $values, 'yourblank2' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yourblank2' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yourblank2' ) . '\') {this.value = \'\';}" />';
                        }
                    
                    }
                    
                    break;
                case 'field11':
                    
                    if ( qem_get_element( $register, 'usedropdown', false ) ) {
                        $content .= '<select' . qem_get_element( $errors, 'yourdropdown' ) . ' name="yourdropdown">';
                        $arr = explode( ",", qem_get_element( $register, 'yourdropdown' ) );
                        foreach ( $arr as $item ) {
                            $selected = '';
                            if ( qem_get_element( $values, 'yourdropdown' ) == $item ) {
                                $selected = 'selected';
                            }
                            $content .= '<option value="' . $item . '" ' . $selected . '>' . $item . '</option>';
                        }
                        $content .= '</select>';
                    }
                    
                    break;
                case 'field12':
                    
                    if ( qem_get_element( $register, 'usenumber1', false ) ) {
                        $required = ( qem_get_element( $register, 'reqnumber1' ) ? 'class="required"' : '' );
                        $content .= '<p>' . qem_get_element( $register, 'yournumber1' ) . '&nbsp;<input id="yournumber1" name="yournumber1" ' . $required . ' ' . qem_get_element( $errors, 'yournumber1' ) . ' type="text" style="' . qem_get_element( $errors, 'yournumber1' ) . 'width:3em;margin-right:5px" value="' . qem_get_element( $values, 'yournumber1' ) . '" value="' . qem_get_element( $values, 'yournumber1' ) . '" onblur="if (this.value == \'\') {this.value = \'' . qem_get_element( $values, 'yournumber1' ) . '\';}" onfocus="if (this.value == \'' . qem_get_element( $values, 'yournumber1' ) . '\') {this.value = \'\';}" /></p>';
                    }
                    
                    break;
                case 'field13':
                    if ( qem_get_element( $register, 'useaddinfo', false ) && ($paypal && qem_get_element( $register, 'paypaladdinfo', false ) || !$paypal && !qem_get_element( $register, 'paypaladdinfo', false )) ) {
                        $content .= '<p>' . qem_get_element( $register, 'addinfo' ) . '</p>';
                    }
                    break;
                case 'field14':
                    
                    if ( qem_get_element( $register, 'useselector', false ) ) {
                        $content .= '<select ' . qem_get_element( $errors, 'useselector' ) . ' name="yourselector">';
                        $arr = explode( ",", qem_get_element( $register, 'yourselector' ) );
                        foreach ( $arr as $item ) {
                            $selected = '';
                            if ( qem_get_element( $values, 'yourselector' ) == $item ) {
                                $selected = 'selected';
                            }
                            $content .= '<option value="' . $item . '" ' . $selected . '>' . $item . '</option>';
                        }
                        $content .= '</select>';
                    }
                    
                    break;
                case 'field15':
                    if ( qem_get_element( $register, 'useoptin', false ) ) {
                        $content .= '<p><input type="checkbox" name="youroptin" value="checked" ' . qem_get_element( $values, 'youroptin' ) . ' /> ' . qem_get_element( $register, 'optinblurb' ) . '</p>';
                    }
                    break;
                case 'field16':
                    
                    if ( qem_get_element( $register, 'usechecks', false ) ) {
                        $content .= '<p>' . qem_get_element( $register, 'checkslabel' ) . '</p>';
                        $content .= '<p>';
                        $arr = explode( ",", qem_get_element( $register, 'checkslist' ) );
                        $i = 0;
                        $type = 'checkbox';
                        if ( 'checked' === qem_get_element( $register, 'usechecksradio', false ) ) {
                            $type = 'radio';
                        }
                        foreach ( $arr as $item ) {
                            $i++;
                            if ( 'radio' === $type ) {
                                // force fixed name for radio as only 1 can be selected
                                $i = 1;
                            }
                            $content .= '<label><input type="' . esc_attr( $type ) . '" style="margin:0; padding: 0; border: none" name="' . 'checks_' . $i . '" value="' . $item . '" ' . checked( qem_get_element( $values, $item ), $item, false ) . '> ' . $item . '</label><br>';
                        }
                        $content .= '</p>';
                    }
                    
                    break;
                case 'field17':
                    break;
            }
        }
        
        if ( qem_get_element( $register, 'useattachment', false ) ) {
            $content .= '<div>';
            $qfc_file_info = (object) array(
                'types'    => explode( ',', qem_get_element( $register, 'attachmenttypes' ) ),
                'max_size' => (int) qem_get_element( $register, 'attachmentsize' ),
                'error'    => qem_get_element( $register, 'attachmenterror' ),
            );
            $content .= '<script type="text/javascript"> qfc_file_info = ' . json_encode( $qfc_file_info ) . ';</script>';
            
            if ( qem_get_element( $errors, 'attach' ) ) {
                $content .= qem_get_element( $errors, 'attach' );
            } else {
                $content .= '<p class="input">' . qem_get_element( $register, 'attachmentlabel' ) . '</p>' . "\r\t" . '<p>';
            }
            
            $content .= '<div name="attach"><input type="file" name="filename"/></p>
		</div></div>';
        }
        
        
        if ( qem_get_element( $register, 'useterms', false ) ) {
            $termstyle = '';
            $termslink = '';
            
            if ( qem_get_element( $errors, 'terms', false ) ) {
                $termstyle = ' style="border:1px solid red;"';
                $termslink = ' style="color:red;"';
            }
            
            $target = '';
            if ( qem_get_element( $register, 'termstarget' ) ) {
                $target = ' target="_blank"';
            }
            $content .= '<p><input type="checkbox" name="terms" value="checked" ' . $termstyle . qem_get_element( $values, 'terms' ) . ' /> <a href="' . qem_get_element( $register, 'termsurl' ) . '"' . $target . $termslink . '>' . qem_get_element( $register, 'termslabel' ) . '</a></p>';
        }
        
        if ( qem_get_element( $register, 'ignorepayment', false ) && ($paypal && $cost) ) {
            $content .= '<p><input type="checkbox" id="paylater" name="ignore" value="checked" ' . esc_attr( qem_get_element( $values, 'ignore' ) ) . ' />' . esc_html( qem_get_element( $register, 'ignorepaymentlabel' ) ) . '</p>';
        }
        
        if ( $paypal && $cost ) {
            $button_value = qem_get_element( $payment, 'qempaypalsubmit' );
            if ( qem_get_element( $payment, 'usecoupon' ) || qem_get_element( $register, 'usecoupon' ) ) {
                $content .= '<input name="yourcoupon" type="text"' . qem_get_element( $errors, 'yourcoupon' ) . ' value="' . esc_attr( qem_get_element( $values, 'yourcoupon' ) ) . '" onblur="if (this.value == \'\') {this.value = \'' . esc_attr( qem_get_element( $values, 'yourcoupon' ) ) . '\';}" onfocus="if (this.value == \'' . esc_attr( qem_get_element( $values, 'yourcoupon' ) ) . '\') {this.value = \'\';}" />';
            }
            $content .= "<script type='text/javascript'>qem_ignore_ic = false;</script>";
        } else {
            $button_value = qem_get_element( $register, 'qemsubmit' );
            $content .= "<script type='text/javascript'>qem_ignore_ic = true;</script>";
        }
        
        /* --------------------*/
        $content .= '<div class="validator">Enter the word YES in the box: <input type="text" style="width:3em" name="validator" value=""></div>
        <input type="hidden" name="ipn" value="' . qem_get_element( $values, 'ipn' ) . '">
        <input type="submit" value="' . $button_value . '" alt="' . qem_get_element( $register, 'qemsubmit' ) . '" id="submit" name="qemregister' . $id . '" />
        </form></div>
        <div class="qem_validating_form" data-form-id="' . $id . '"><span class="qem-spinner is-active"></span></div>
		<div id="qem_validating">' . qem_get_element( $api, 'validating' ) . '<span class="qem-spinner is-active"></span></div>
		<div id="qem_processing">' . qem_get_element( $api, 'waiting' ) . '<span class="qem-spinner is-active"></span></div>
        <div style="clear:both;"></div></div>';
        if ( qem_get_element( $register, 'hideform', false ) && count( $errors ) == 0 ) {
            $content .= '</div>';
        }
        $content .= '</div>';
        /*
        	Remove This since this throws an error since it doesn't exist at that moment
        
        $content .= '<script type="text/javascript" language="javascript">
        	document.querySelector("#qem_reload").scrollIntoView();
        	</script>';
        */
    }
    
    return $content;
}

/**
 * @param $needle
 * @param $haystack
 *
 * @return bool|string
 */
function qem_search_array( $needle, $haystack )
{
    if ( in_array( $needle, $haystack ) ) {
        return true;
    }
    foreach ( $haystack as $element ) {
        if ( is_array( $element ) && qem_search_array( $needle, $element ) ) {
            return 'error';
        }
    }
}

/**
 * @param $values
 * @param $errors
 * @param false $ajax
 *
 * @return bool
 * @throws exception
 */
function qem_verify_form( &$values, &$errors, $ajax = false )
{
    global  $qem_fs ;
    $errors = array();
    $id = get_the_ID();
    $whoscoming = get_option( 'qem_messages_' . $id );
    if ( !$whoscoming ) {
        $whoscoming = array();
    }
    $register = get_custom_registration_form( $id );
    $payment = qem_get_stored_payment();
    $apikey = get_option( 'qem-akismet' );
    $event_maxplaces = get_post_meta( $id, 'event_maxplaces', true );
    $event_getemails = get_post_meta( $id, 'event_getemails', true );
    $event_getnames = get_post_meta( $id, 'event_getnames', true );
    $paypal = get_post_meta( $id, 'event_paypal', true );
    $cost = get_post_meta( $id, 'event_cost', true );
    $payment = qem_get_stored_payment();
    $ic = qem_get_incontext();
    // determine if payment complete checking is enabled
    $payment_checking = false;
    if ( 'checked' == qem_get_element( $payment, 'ipn', false ) ) {
        $payment_checking = true;
    }
    
    if ( $apikey ) {
        $blogurl = get_site_url();
        $akismet = new qem_akismet( $blogurl, $apikey );
        $akismet->setCommentAuthor( qem_get_element( $values, 'yourname' ) );
        $akismet->setCommentAuthorEmail( qem_get_element( $values, 'youremail' ) );
        $akismet->setCommentContent( qem_get_element( $values, 'yourmessage' ) );
        if ( $akismet->isCommentSpam() ) {
            $errors['spam'] = qem_get_element( $register, 'spam' );
        }
    }
    
    $errors = apply_filters(
        'quick_entry_is_spam',
        $errors,
        qem_get_element( $values, 'yourname' ),
        qem_get_element( $values, 'youremail' ),
        qem_get_element( $values, 'yourmessage' ),
        $values,
        qem_get_element( $register, 'spam' )
    );
    // Checks against CSV
    $alreadyregistered = false;
    
    if ( !qem_get_element( $register, 'usemail' ) && qem_get_element( $register, 'usename' ) && !qem_get_element( $register, 'allowmultiple' ) && qem_get_element( $values, 'yourname' ) ) {
        $alreadyregistered = qem_search_array( qem_get_element( $values, 'yourname' ), $whoscoming );
    } elseif ( qem_get_element( $register, 'usemail' ) && qem_get_element( $values, 'youremail' ) ) {
        foreach ( $whoscoming as $key => $line ) {
            
            if ( $values['youremail'] == qem_get_element( $line, 'youremail' ) ) {
                //  payment gateway
                // so check if the payment is pending  - if so allow reregistrations only when payment processing ( $paypal ) and payment checking in place
                
                if ( $paypal && $payment_checking && 'Paid' != qem_get_element( $line, 'ipn', false ) ) {
                    //remove all pendings for this email
                    $message = get_option( 'qem_messages_' . $id );
                    for ( $i = 0 ;  $i <= count( $message ) ;  $i++ ) {
                        if ( $message[$i]['youremail'] == qem_get_element( $values, 'youremail' ) ) {
                            unset( $message[$i] );
                        }
                    }
                    $message = array_values( $message );
                    update_option( 'qem_messages_' . $id, $message );
                } else {
                    $alreadyregistered = true;
                }
                
                break;
            }
        
        }
    }
    
    if ( $alreadyregistered ) {
        // see if we want to remove
        
        if ( qem_get_element( $register, 'checkremoval' ) && qem_get_element( $values, 'notattend' ) && qem_get_element( $values, 'youremail' ) && qem_get_element( $register, 'usemail' ) ) {
            $message = get_option( 'qem_messages_' . $id );
            $emails = array_column( $message, 'youremail' );
            $your_email = qem_get_element( $values, 'youremail' );
            
            if ( in_array( $your_email, $emails ) ) {
                $errors['alreadyregistered'] = 'removed';
                $message = array_filter( $message, function ( $msg ) use( $your_email ) {
                    return qem_get_element( $msg, 'youremail' ) != $your_email;
                } );
            }
            
            update_option( 'qem_messages_' . $id, $message );
            if ( !qem_get_element( $register, 'nonotifications' ) ) {
                qem_sendremovalemail( $register, $values );
            }
        }
    
    }
    // @since 2.2.3  rearrange error messages
    if ( qem_get_element( $values, 'notattend', false ) ) {
        if ( !$alreadyregistered ) {
            $errors['notattend'] = esc_html__( 'Your are not already attending', 'quick-event-manager' );
        }
    }
    if ( $alreadyregistered && !qem_get_element( $register, 'allowmultiple' ) && !qem_get_element( $values, 'notattend', false ) ) {
        $errors['alreadyregistered'] = 'checked';
    }
    
    if ( 'checked' !== $event_getnames && $event_maxplaces > 1 && get_post_meta( $id, 'event_requiredplaces', true ) ) {
        for ( $i = 1 ;  $i <= $event_maxplaces ;  $i++ ) {
            $values['name' . $i] = filter_var( qem_get_element( $values, 'name' . $i ), FILTER_SANITIZE_STRING );
            if ( empty(qem_get_element( $values, 'name' . $i )) ) {
                $errors['name' . $i] = __( 'Name required', 'quick-event-manager' );
            }
        }
    } elseif ( 'checked' !== $event_getnames && $event_maxplaces > 1 ) {
        $values['name1'] = filter_var( qem_get_element( $values, 'name1' ), FILTER_SANITIZE_STRING );
        if ( empty(qem_get_element( $values, 'name1' )) ) {
            $errors['name1'] = __( 'Name required', 'quick-event-manager' );
        }
    } else {
        $values['yourname'] = filter_var( qem_get_element( $values, 'yourname' ), FILTER_SANITIZE_STRING );
        if ( qem_get_element( $register, 'usename' ) && qem_get_element( $register, 'reqname' ) && (empty(qem_get_element( $values, 'yourname' )) || qem_get_element( $values, 'yourname' ) == qem_get_element( $register, 'yourname' )) ) {
            $errors['yourname'] = __( 'Name required', 'quick-event-manager' );
        }
    }
    
    
    if ( 'checked' !== $event_getnames && $event_maxplaces > 1 && get_post_meta( $id, 'event_requiredplaces', true ) && $event_getemails ) {
        for ( $i = 1 ;  $i <= $event_maxplaces ;  $i++ ) {
            $values['email' . $i] = strtolower( filter_var( qem_get_element( $values, 'email' . $i ), FILTER_SANITIZE_STRING ) );
            if ( empty(qem_get_element( $values, 'email' . $i )) ) {
                $errors['email' . $i] = __( 'Required input', 'quick-event-manager' );
            }
        }
    } elseif ( 'checked' !== $event_getnames && $event_maxplaces > 1 && $event_getemails ) {
        $values['email1'] = strtolower( filter_var( qem_get_element( $values, 'email1' ), FILTER_VALIDATE_EMAIL ) );
        if ( empty(qem_get_element( $values, 'email1' )) ) {
            $errors['email1'] = __( 'Required input', 'quick-event-manager' );
        }
    } else {
        if ( qem_get_element( $register, 'usemail' ) && qem_get_element( $register, 'reqmail' ) && !filter_var( qem_get_element( $values, 'youremail' ), FILTER_VALIDATE_EMAIL ) ) {
            $errors['youremail'] = __( 'Valid email required', 'quick-event-manager' );
        }
        $values['youremail'] = strtolower( filter_var( qem_get_element( $values, 'youremail' ), FILTER_SANITIZE_STRING ) );
        if ( qem_get_element( $register, 'usemail' ) && qem_get_element( $register, 'reqmail' ) && (empty(qem_get_element( $values, 'youremail' )) || qem_get_element( $values, 'youremail' ) == qem_get_element( $register, 'youremail' )) ) {
            $errors['youremail'] = __( 'Valid email required', 'quick-event-manager' );
        }
    }
    
    $values['yourtelephone'] = filter_var( qem_get_element( $values, 'yourtelephone' ), FILTER_SANITIZE_STRING );
    if ( qem_get_element( $register, 'usetelephone' ) && qem_get_element( $register, 'reqtelephone' ) && (empty(qem_get_element( $values, 'yourtelephone' )) || qem_get_element( $values, 'yourtelephone' ) == qem_get_element( $register, 'yourtelephone' )) ) {
        $errors['yourtelephone'] = __( 'Valid phone number required', 'quick-event-manager' );
    }
    $values['yourplaces'] = preg_replace( '/[^0-9]/', '', qem_get_element( $values, 'yourplaces' ) );
    /*
     * check the max places need to consider variable tickets or simple places
     *
     */
    if ( $event_maxplaces > 0 ) {
        
        if ( !empty(qem_get_element( $values, 'yourplaces' )) ) {
            if ( qem_get_element( $values, 'yourplaces' ) > $event_maxplaces ) {
                $errors['yourplaces'] = sprintf( esc_html__( 'Maximum places (%1$s) per registration exceeded', 'quick-event-manager' ), $event_maxplaces );
            }
        } else {
            
            if ( qem_get_variable_prices_attending( $values ) > $event_maxplaces ) {
                $msg = sprintf( esc_html__( 'Total Maximum places (%1$s) per registration exceeded', 'quick-event-manager' ), $event_maxplaces );
                $errors = qem_set_variable_prices_attending_errors(
                    $values,
                    $errors,
                    $event_maxplaces,
                    $msg
                );
            } else {
                
                if ( 0 === qem_get_variable_prices_attending( $values ) ) {
                    $msg = esc_html__( 'No places selected', 'quick-event-manager' );
                    $errors = qem_set_variable_prices_attending_errors(
                        $values,
                        $errors,
                        $event_maxplaces,
                        $msg
                    );
                }
            
            }
        
        }
    
    }
    if ( qem_get_element( $register, 'useplaces' ) && empty(qem_get_element( $values, 'yourplaces' )) ) {
        $values['yourplaces'] = '1';
    }
    $values['morenames'] = filter_var( qem_get_element( $values, 'morenames' ), FILTER_SANITIZE_STRING );
    $values['yourmessage'] = filter_var( qem_get_element( $values, 'yourmessage' ), FILTER_SANITIZE_STRING );
    if ( qem_get_element( $register, 'usemessage' ) && qem_get_element( $register, 'reqmessage' ) && (empty(qem_get_element( $values, 'yourmessage' )) || qem_get_element( $values, 'yourmessage' ) == qem_get_element( $register, 'yourmessage' )) ) {
        $errors['yourmessage'] = __( 'Required input', 'quick-event-manager' );
    }
    if ( $values['yourmessage'] == qem_get_element( $register, 'yourmessage' ) ) {
        $values['yourmessage'] = '';
    }
    $values['yourblank1'] = filter_var( qem_get_element( $values, 'yourblank1' ), FILTER_SANITIZE_STRING );
    if ( qem_get_element( $register, 'useblank1' ) && qem_get_element( $register, 'reqblank1' ) && (empty(qem_get_element( $values, 'yourblank1' )) || qem_get_element( $values, 'yourblank1' ) == qem_get_element( $register, 'yourblank1' )) ) {
        $errors['yourblank1'] = __( 'Required input', 'quick-event-manager' );
    }
    if ( qem_get_element( $values, 'yourblank1' ) == qem_get_element( $register, 'yourblank1' ) ) {
        $values['yourblank1'] = '';
    }
    $values['yourblank2'] = filter_var( qem_get_element( $values, 'yourblank2' ), FILTER_SANITIZE_STRING );
    if ( qem_get_element( $register, 'useblank2' ) && qem_get_element( $register, 'reqblank2' ) && (empty(qem_get_element( $values, 'yourblank2' )) || qem_get_element( $values, 'yourblank2' ) == qem_get_element( $register, 'yourblank2' )) ) {
        $errors['yourblank2'] = __( 'Required input', 'quick-event-manager' );
    }
    if ( qem_get_element( $values, 'yourblank2' ) == qem_get_element( $register, 'yourblank2' ) ) {
        $values['yourblank2'] = '';
    }
    $values['yourdropdown'] = filter_var( qem_get_element( $values, 'yourdropdown' ), FILTER_SANITIZE_STRING );
    $values['yourselector'] = filter_var( qem_get_element( $values, 'yourselector' ), FILTER_SANITIZE_STRING );
    $values['yournumber1'] = filter_var( qem_get_element( $values, 'yournumber1' ), FILTER_SANITIZE_STRING );
    if ( qem_get_element( $register, 'usenumber1' ) && qem_get_element( $register, 'reqnumber1' ) && (empty(qem_get_element( $values, 'yournumber1' )) || qem_get_element( $values, 'yournumber1' ) == qem_get_element( $register, 'yournumber1' )) ) {
        $errors['yournumber1'] = __( 'Required input', 'quick-event-manager' );
    }
    if ( qem_get_element( $register, 'useterms' ) && empty(qem_get_element( $values, 'terms' )) ) {
        $errors['terms'] = __( 'Required input', 'quick-event-manager' );
    }
    if ( qem_get_element( $register, 'usecaptcha' ) && (empty(qem_get_element( $values, 'youranswer' )) || qem_get_element( $values, 'youranswer' ) != qem_get_element( $values, 'answer' )) ) {
        $errors['youranswer'] = __( 'Incorrect value try again', 'quick-event-manager' );
    }
    $values['youranswer'] = filter_var( qem_get_element( $values, 'youranswer' ), FILTER_SANITIZE_STRING );
    $useproducts = get_post_meta( $id, 'event_products', true );
    if ( $useproducts ) {
        if ( 0 == qem_get_variable_prices_attending( $values ) ) {
            $errors['qtyproduct0'] = esc_html__( 'You have selected zero tickets', 'quick-event-manager' );
        }
    }
    // @since 9.2.2 places and tickets should be mutually exclusive
    
    if ( get_post_meta( $id, 'event_number', true ) && !qem_get_element( $register, 'waitinglist' ) ) {
        // limits and no waiting.
        $attending = qem_get_the_numbers( $id, $payment );
        $places = get_post_meta( $id, 'event_number', true );
        
        if ( !$useproducts ) {
            
            if ( qem_get_element( $register, 'useplaces' ) ) {
                $number = $attending + qem_get_element( $values, 'yourplaces', 0 );
                if ( $places < $number ) {
                    $errors['yourplaces'] = esc_html__( 'Not enough places left', 'quick-event-manager' );
                }
            }
        
        } else {
            
            if ( qem_get_variable_prices_attending( $values ) > 0 ) {
                $number = $attending + qem_get_variable_prices_attending( $values );
                if ( $places < $number ) {
                    $errors['qtyproduct0'] = esc_html__( 'Not enough places left', 'quick-event-manager' );
                }
            } else {
                $errors['qtyproduct0'] = esc_html__( 'You have selected zero tickets', 'quick-event-manager' );
            }
        
        }
    
    }
    
    if ( qem_get_element( $values, 'validator' ) ) {
        die;
    }
    return count( $errors ) == 0;
}

/**
 * set errors when total tickets exceeds maximum
 *
 * @param $values
 * @param $errors
 * @param $event_maxplaces
 *
 * @return mixed
 * @since 9.1.1
 *
 */
function qem_set_variable_prices_attending_errors(
    $values,
    $errors,
    $event_maxplaces,
    $msg
)
{
    for ( $i = 0 ;  $i <= 4 ;  $i++ ) {
        if ( isset( $values["qtyproduct{$i}"] ) ) {
            $errors["qtyproduct{$i}"] = $msg;
        }
    }
    return $errors;
}

/**
 * Calculate the number of variable tickets purchased
 *
 * @param $values
 *
 * @return int|mixed
 * @since 9.1.1
 *
 */
function qem_get_variable_prices_attending( $values )
{
    $tickets = 0;
    for ( $i = 0 ;  $i <= 4 ;  $i++ ) {
        if ( isset( $values["qtyproduct{$i}"] ) ) {
            $tickets += (int) $values["qtyproduct{$i}"];
        }
    }
    return $tickets;
}

/**
 * @param $values
 * @param false $ajax
 *
 * @return string
 */
function qem_process_form( $values, $ajax = false )
{
    global  $post ;
    global  $qem_fs ;
    $id = get_the_ID();
    $date = get_post_meta( $post->ID, 'event_date', true );
    $enddate = get_post_meta( $post->ID, 'event_end_date', true );
    $content = '';
    $places = get_post_meta( $post->ID, 'event_number', true );
    $maxplaces = get_post_meta( $post->ID, 'event_maxplaces', true );
    $required_places = get_post_meta( $post->ID, 'event_requiredplaces', true );
    $event_getnames = get_post_meta( $post->ID, 'event_getnames', true );
    $paypal = 'checked' == get_post_meta( $post->ID, 'event_paypal', true );
    $cost = get_post_meta( $id, 'event_cost', true );
    $date = date_i18n( "d M Y", $date );
    $enddate = date_i18n( "d M Y", $enddate );
    $start = get_post_meta( $post->ID, 'event_start', true );
    $finish = get_post_meta( $post->ID, 'event_finish', true );
    $register = get_custom_registration_form( $id );
    $auto = qem_get_stored_autoresponder();
    $addons = qem_get_addons();
    $payment = qem_get_stored_payment();
    $qem_messages = get_option( 'qem_messages_' . $id );
    if ( !is_array( $qem_messages ) ) {
        $qem_messages = array();
    }
    $sentdate = date_i18n( 'd M Y' );
    $useproducts = get_post_meta( $id, 'event_products', true );
    $checks = explode( ",", qem_get_element( $register, 'checkslist' ) );
    $checkslist = '';
    $i = 0;
    foreach ( $checks as $item ) {
        $i++;
        $item = 'checks_' . $i;
        if ( qem_get_element( $values, $item ) ) {
            $checkslist .= ', ' . qem_get_element( $values, $item );
        }
    }
    $values['checkslist'] = substr( $checkslist, 2 );
    
    if ( $useproducts ) {
        $product = get_post_meta( $id, 'event_productlist', true );
        $products = explode( ',', trim( $product, ',' ) );
        $values['products'] = ' (';
        for ( $i = 0 ;  $i < 4 ;  $i++ ) {
            if ( qem_get_element( $products, $i * 2 ) ) {
                $values['products'] .= qem_get_element( $products, $i * 2 ) . ' x ' . qem_get_element( $values, 'qtyproduct' . $i ) . ' ';
            }
        }
        $values['products'] .= ')';
        $values['yourplaces'] = qem_get_element( $values, 'qtyproduct0', 0 ) + qem_get_element( $values, 'qtyproduct1', 0 ) + qem_get_element( $values, 'qtyproduct2', 0 ) + qem_get_element( $values, 'qtyproduct3', 0 );
    }
    
    
    if ( 'checked' !== $event_getnames && $maxplaces > 1 ) {
        $values['yourplaces'] = $maxplaces;
        $values['team'] = qem_build_team( $values, $maxplaces );
        $multi = $values;
        $multi['yourplaces'] = 1;
        for ( $i = 1 ;  $i <= $maxplaces ;  $i++ ) {
            $multi['yourname'] = qem_get_element( $multi, 'name' . $i );
            $multi['youremail'] = qem_get_element( $multi, 'email' . $i );
            if ( qem_get_element( $multi, 'yourname' ) ) {
                $qem_messages[] = qem_add_attendee( $multi );
            }
            if ( (qem_get_element( $auto, 'enable' ) || qem_get_element( $multi, 'qem-copy' )) && qem_get_element( $multi, 'youremail' ) && !qem_get_element( $register, 'moderate' ) && qem_get_element( $auto, 'whenconfirm' ) == 'aftersubmission' ) {
                qem_send_confirmation(
                    $auto,
                    $multi,
                    $content,
                    $register,
                    $id
                );
            }
        }
        $auto['enable'] = false;
        $values['qem-copy'] = false;
        $values['yourname'] = qem_get_element( $multi, 'name1' );
        $values['youremail'] = qem_get_element( $multi, 'email1' );
    } else {
        $qem_messages[] = qem_add_attendee( $values );
    }
    
    
    if ( qem_get_element( $values, 'notattend' ) ) {
        $qem_removal = get_option( 'qem_removal' );
        $newmessage['title'] = get_the_title();
        $newmessage['date'] = $date;
        $qem_removal[] = $newmessage;
        update_option( 'qem_removal', $qem_removal );
    }
    
    update_option( 'qem_messages_' . $id, $qem_messages );
    if ( function_exists( 'qem_update_csv' ) ) {
        qem_update_csv( $values );
    }
    if ( apply_filters( 'qem_registration_always_confirm_admin', false ) || ('aftersubmission' == qem_get_element( $auto, 'whenconfirm' ) || empty($cost) || false == $paypal) ) {
        qem_admin_notification(
            $id,
            $register,
            $addons,
            $values,
            $auto,
            $enddate,
            $date,
            $start,
            $finish,
            $payment
        );
    }
    
    if ( apply_filters( 'qem_registration_always_confirm', false ) || (qem_get_element( $auto, 'enable' ) || qem_get_element( $values, 'qem-copy' )) && !qem_get_element( $register, 'moderate' ) && ('aftersubmission' == qem_get_element( $auto, 'whenconfirm' ) || empty($cost) || false == $paypal) ) {
        qem_send_confirmation(
            $auto,
            $values,
            $content,
            $register,
            $id
        );
        // user notifications
    }
    
    if ( $paypal && !qem_get_element( $values, 'ignore', false ) ) {
        return 'checked';
    }
    $globalredirect = qem_get_element( $register, 'redirectionurl' );
    $eventredirect = get_post_meta( $post->ID, 'event_redirect', true );
    $redirect = ( $eventredirect ? $eventredirect : $globalredirect );
    $redirect_id = get_post_meta( $post->ID, 'event_redirect_id', true );
    
    if ( $redirect && !$ajax ) {
        
        if ( $redirect_id ) {
            if ( substr( $redirect, -1 ) != '/' ) {
                $redirect = $redirect . '/';
            }
            $id = get_the_ID();
            $redirect = $redirect . "?event=" . $id;
        }
        
        echo  "<meta http-equiv='refresh' content='0;url={$redirect}' />" ;
        exit;
    }

}

/**
 * @param $id
 * @param $register
 * @param $addons
 * @param $values
 * @param $auto
 * @param $enddate
 * @param $date
 * @param $start
 * @param $finish
 * @param $payment
 */
function qem_admin_notification(
    $id,
    $register,
    $addons,
    $values,
    $auto,
    $enddate,
    $date,
    $start,
    $finish,
    $payment
)
{
    global  $qem_fs ;
    
    if ( empty(qem_get_element( $register, 'sendemail' )) ) {
        $sys_email = get_bloginfo( 'admin_email' );
    } else {
        $emails = explode( ',', qem_get_element( $register, 'sendemail' ) );
        $sys_email = $emails[0];
    }
    
    $qem_email = $sys_email;
    $notificationsubject = esc_html__( 'New Registration for ', 'quick-event-manager' ) . get_the_title() . __( ' on ', 'quick-event-manager' ) . $date;
    $content = qem_build_event_message( $values, $register );
    $details = '';
    
    if ( qem_get_element( $auto, 'useeventdetails' ) ) {
        if ( qem_get_element( $auto, 'eventdetailsblurb' ) ) {
            $details .= '<h2>' . qem_get_element( $auto, 'eventdetailsblurb' ) . '</h2>';
        }
        $details .= '<p>' . get_the_title( $id ) . '</p><p>' . $date;
        
        if ( $enddate ) {
            $enddate = date_i18n( "d M Y", $enddate );
            $details .= ' - ' . $enddate;
        }
        
        $details .= '</p>';
        if ( $start ) {
            $details .= '<p>' . $start;
        }
        if ( $finish ) {
            $details .= ' - ' . $finish;
        }
        $details .= '</p>';
    }
    
    // Add permalink
    $close = '';
    if ( qem_get_element( $auto, 'permalink' ) ) {
        $close .= '<p><a href="' . get_permalink( $id ) . '">' . get_permalink( $id ) . '</a></p>';
    }
    $headers = "From: " . qem_get_element( $values, 'yourname' ) . " <" . $sys_email . ">\r\n";
    $headers .= "Reply-To: " . qem_get_element( $values, 'yourname' ) . " <" . qem_get_element( $values, 'youremail' ) . ">\r\n";
    $headers .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
    
    if ( !qem_get_element( $register, 'nonotifications' ) ) {
        $message = '<html>' . $content . $details . $close . '</html>';
        $message = apply_filters(
            'qem_registration_email_message',
            $message,
            $content,
            $details,
            $close,
            $id,
            $payment
        );
        qem_wp_mail(
            'Admin Registration Email',
            $qem_email,
            $notificationsubject,
            $message,
            $headers
        );
    }

}

/**
 * @param $values
 *
 * @return array
 */
function qem_add_attendee( $values )
{
    $newmessage = array();
    $sentdate = date_i18n( 'd M Y' );
    $arr = array(
        'yourname',
        'youremail',
        'yourtelephone',
        'yourmessage',
        'yourplaces',
        'yourblank1',
        'yourblank2',
        'yourdropdown',
        'yourselector',
        'yournumber1',
        'donation_amount',
        'morenames',
        'ignore',
        'youroptin',
        'products',
        'checkslist'
    );
    // @TODO  what is this section seems to do nothing with an undefined variable
    foreach ( $arr as $item ) {
        if ( $values[$item] != $register[$item] ) {
            $newmessage[$item] = $values[$item];
        }
    }
    $newmessage['notattend'] = qem_get_element( $values, 'notattend' );
    if ( qem_get_element( $values, 'notattend' ) ) {
        $values['yourplaces'] = '';
    }
    $newmessage['sentdate'] = $sentdate;
    $newmessage['datetime_added'] = time();
    $newmessage['ipn'] = qem_get_element( $values, 'ipn' );
    $newmessage['custom'] = qem_get_element( $values, 'ipn' );
    return $newmessage;
}

/**
 * @TODO  whhat is this doing
 *
 * @param $values
 * @param $addons
 */
function qem_mailchimp( $values, $addons )
{
    $content = '<form action="http://mailchimp.us8.list-manage.com/subscribe/post" method="POST" id="mailchimpsubmit">
    <input type="hidden" name="u" value="' . qem_get_element( $addons, 'mailchimpuser' ) . '">
    <input type="hidden" name="id" value="' . qem_get_element( $addons, 'mailchimpid' ) . '">
    <input type="hidden" name="MERGE0" id="MERGE0" value=' . qem_get_element( $values, 'email' ) . '>
    <input type="hidden" name="FNAME" id="FNAME" value=' . qem_get_element( $values, 'firstname' ) . '>
    <input type="hidden" name="LNAME" id="LNAME" value=' . qem_get_element( $values, 'lastname' ) . '>
    </form>
    <script language="JavaScript">document.getElementById("mailchimpsubmit").submit();</script>';
    echo  $content ;
}

/**
 * @param $register
 * @param $values
 *
 * @throws \PHPMailer\PHPMailer\Exception
 */
function qem_sendremovalemail( $register, $values )
{
    global  $post ;
    
    if ( empty($register['sendemail']) ) {
        $qem_email = get_bloginfo( 'admin_email' );
    } else {
        $emails = explode( ',', $register['sendemail'] );
        $qem_email = $emails[0];
    }
    
    $date = get_post_meta( $post->ID, 'event_date', true );
    $date = date_i18n( "d M Y", $date );
    $subject = 'Registration Removal for ' . get_the_title() . ' on ' . $date;
    $headers = "From: " . $values['yourname'] . " <" . $qem_email . ">\r\n";
    $headers .= "Reply-to: " . $values['yourname'] . " <" . $values['youremail'] . ">\r\n" . "Content-Type: text/html; charset=\"utf-8\"\r\n";
    $content = $values['yourname'] . ' (' . $values['youremail'] . ') is no longer attending ' . get_the_title() . ' on ' . $date;
    $message = '<html>' . $content . '</html>';
    qem_wp_mail(
        'Removal Email',
        $qem_email,
        $subject,
        $message,
        $headers
    );
    $qem_removal = get_option( 'qem_removals' );
    if ( !is_array( $qem_removal ) ) {
        $qem_removal = array();
    }
    $sentdate = date_i18n( 'd M Y' );
    $newmessage = array();
    $arr = array(
        'yourname',
        'youremail',
        'notattend',
        'yourtelephone',
        'yourmessage',
        'yourplaces',
        'yourblank1',
        'yourblank2',
        'yourdropdown',
        'yourselector',
        'yournumber1',
        'morenames',
        'ignore'
    );
    foreach ( $arr as $item ) {
        if ( $values[$item] != $register[$item] ) {
            $newmessage[$item] = $values[$item];
        }
    }
    $newmessage['sentdate'] = $sentdate;
    $newmessage['datetime_added'] = time();
    $newmessage['title'] = get_the_title();
    $newmessage['date'] = $date;
    $qem_removal[] = $newmessage;
    update_option( 'qem_removal', $qem_removal );
}

/**
 * @param $values
 * @param $maxplaces
 *
 * @return string
 */
function qem_build_team( $values, $maxplaces )
{
    $team = '<table>';
    for ( $i = 1 ;  $i <= $maxplaces ;  $i++ ) {
        $team .= '<tr><td>' . $values['name' . $i] . '</td><td>' . $values['email' . $i] . '</td></tr>';
    }
    $team .= '</table>';
    return $team;
}

/**
 * @param $auto
 * @param $values
 * @param $content
 * @param $register
 * @param $id
 *
 * @throws \PHPMailer\PHPMailer\Exception
 */
function qem_send_confirmation(
    $auto,
    $values,
    $content,
    $register,
    $id
)
{
    $event = event_get_stored_options();
    $payment = qem_get_stored_payment();
    $rcm = get_post_meta( $id, 'event_registration_message', true );
    $date = get_post_meta( $id, 'event_date', true );
    $enddate = get_post_meta( $id, 'event_end_date', true );
    $start = get_post_meta( $id, 'event_start', true );
    $finish = get_post_meta( $id, 'event_finish', true );
    $location = get_post_meta( $id, 'event_location', true );
    $paymentlink = get_post_meta( $id, 'event_paypal', true );
    $date = date_i18n( "d M Y", $date );
    $subject = $auto['subject'];
    if ( $auto['subjecttitle'] ) {
        $subject = $subject . ' ' . get_the_title( $id );
    }
    if ( $auto['subjectdate'] ) {
        $subject = $subject . ' ' . $date;
    }
    if ( empty($subject) ) {
        $subject = 'Event Registration';
    }
    if ( !$auto['fromemail'] ) {
        $auto['fromemail'] = get_bloginfo( 'admin_email' );
    }
    if ( !$auto['fromname'] ) {
        $auto['fromname'] = get_bloginfo( 'name' );
    }
    if ( $paymentlink && $payment['message'] ) {
        $auto['message'] = $payment['message'];
    }
    // Build the autoresponder message
    $msg = ( $rcm ? $rcm : $auto['message'] );
    $msg = str_replace( '[name]', $values['yourname'], $msg );
    $msg = str_replace( '[places]', $values['yourplaces'], $msg );
    $msg = str_replace( '[event]', get_the_title( $id ), $msg );
    $msg = str_replace( '[date]', $date, $msg );
    $msg = str_replace( '[enddate]', $enddate, $msg );
    $msg = str_replace( '[start]', $start, $msg );
    $msg = str_replace( '[finish]', $finish, $msg );
    $msg = str_replace( '[location]', $location, $msg );
    $msg = str_replace( '[team]', $values['team'], $msg );
    $msg = apply_filters(
        'qem_autoresponder-message-content',
        $msg,
        $id,
        $payment
    );
    $copy = '<html>' . $msg;
    // Add registration details
    if ( $auto['useregistrationdetails'] || $values['qem-copy'] ) {
        
        if ( $auto['registrationdetailsblurb'] ) {
            $copy .= '<h2>' . $auto['registrationdetailsblurb'] . '</h2>';
            $copy .= qem_build_event_message( $values, $register );
        }
    
    }
    $details = '';
    // Add event details
    
    if ( $auto['useeventdetails'] ) {
        if ( $auto['eventdetailsblurb'] ) {
            $details .= '<h2>' . $auto['eventdetailsblurb'] . '</h2>';
        }
        $details .= '<p>' . get_the_title( $id ) . '</p><p>' . $date;
        
        if ( $enddate ) {
            $enddate = date_i18n( "d M Y", $enddate );
            $details .= ' - ' . $enddate;
        }
        
        $details .= '</p>';
        if ( $start ) {
            $details .= '<p>' . $start;
        }
        if ( $finish ) {
            $details .= ' - ' . $finish;
        }
        $details .= '</p>';
    }
    
    // Add permalink
    if ( $auto['permalink'] ) {
        $close = '<p><a href="' . get_permalink( $id ) . '">' . get_permalink( $id ) . '</a></p>';
    }
    $message = $copy . $details . $close . '</html>';
    $headers = "From: " . $auto['fromname'] . " <{$auto['fromemail']}>\r\n" . "Content-Type: text/html; charset=\"utf-8\"\r\n";
    qem_wp_mail(
        'Email to registrant',
        $values['youremail'],
        $subject,
        $message,
        $headers
    );
}

/**
 * @param $values
 * @param $register
 *
 * @return string
 */
function qem_build_event_message( $values, $register )
{
    global  $post ;
    global  $qem_fs ;
    $id = get_the_ID();
    $sort = explode( ',', $register['sort'] );
    $content = '';
    foreach ( $sort as $name ) {
        switch ( $name ) {
            case 'field1':
                
                if ( $values['team'] ) {
                    $content .= $values['team'];
                } else {
                    $content .= '<p><b>' . $register['yourname'] . ': </b>' . strip_tags( stripslashes( $values['yourname'] ) ) . '</p>';
                }
                
                break;
            case 'field2':
                if ( !$values['team'] ) {
                    $content .= '<p><b>' . $register['youremail'] . ': </b>' . strip_tags( stripslashes( $values['youremail'] ) ) . '</p>';
                }
                break;
            case 'field3':
                if ( $register['useattend'] && $values['notattend'] ) {
                    $content .= '<p><b>' . $register['yourattend'] . ': </b></p>';
                }
                break;
            case 'field4':
                if ( $register['usetelephone'] ) {
                    $content .= '<p><b>' . $register['yourtelephone'] . ': </b>' . strip_tags( stripslashes( $values['yourtelephone'] ) ) . '</p>';
                }
                break;
            case 'field5':
                $useproducts = get_post_meta( $id, 'event_products', true );
                
                if ( $useproducts ) {
                    $product = get_post_meta( $id, 'event_productlist', true );
                    $products = explode( ',', trim( $product, ',' ) );
                    $content .= '<p>';
                    for ( $i = 0 ;  $i < 4 ;  $i++ ) {
                        if ( $products[$i * 2] ) {
                            $content .= $products[$i * 2] . ' x ' . $values['qtyproduct' . $i] . '<br>';
                        }
                    }
                    $content .= '</p>';
                } elseif ( $register['useplaces'] && !$values['notattend'] ) {
                    $content .= '<p><b>' . $register['yourplaces'] . ': </b>' . strip_tags( stripslashes( $values['yourplaces'] ) ) . '</p>';
                } elseif ( !$register['useplaces'] && !$values['notattend'] ) {
                    $values['yourplaces'] = '1';
                } else {
                    $values['yourplaces'] = '';
                }
                
                if ( $register['usemorenames'] && $values['yourplaces'] > 1 ) {
                    $content .= '<p><b>' . $register['morenames'] . ': </b>' . strip_tags( stripslashes( $values['morenames'] ) ) . '</p>';
                }
                break;
            case 'field6':
                if ( $register['usemessage'] && $register['yourmessage'] != $values['yourmessage'] ) {
                    $content .= '<p><b>' . $register['yourmessage'] . ': </b>' . strip_tags( stripslashes( $values['yourmessage'] ) ) . '</p>';
                }
                break;
            case 'field9':
                if ( $register['useblank1'] && $register['yourblank1'] != $values['yourblank1'] ) {
                    $content .= '<p><b>' . $register['yourblank1'] . ': </b>' . strip_tags( stripslashes( $values['yourblank1'] ) ) . '</p>';
                }
                break;
            case 'field10':
                if ( $register['useblank2'] && $register['yourblank2'] != $values['yourblank2'] ) {
                    $content .= '<p><b>' . $register['yourblank2'] . ': </b>' . strip_tags( stripslashes( $values['yourblank2'] ) ) . '</p>';
                }
                break;
            case 'field11':
                
                if ( $register['usedropdown'] ) {
                    $arr = explode( ",", $register['yourdropdown'] );
                    $content .= '<p><b>' . $arr[0] . ': </b>' . strip_tags( stripslashes( $values['yourdropdown'] ) ) . '</p>';
                }
                
                break;
            case 'field14':
                
                if ( $register['useselector'] ) {
                    $arr = explode( ",", $register['yourselector'] );
                    $content .= '<p><b>' . $arr[0] . ': </b>' . strip_tags( stripslashes( $values['yourselector'] ) ) . '</p>';
                }
                
                break;
            case 'field15':
                if ( $register['useoptin'] ) {
                    $content .= '<p><b>' . $register['optinblurb'] . ': </b>' . strip_tags( stripslashes( $values['youroptin'] ) ) . '</p>';
                }
                break;
            case 'field16':
                if ( $register['usechecks'] ) {
                    $content .= '<p><b>' . $register['checkslabel'] . ': </b>' . strip_tags( stripslashes( $values['checkslist'] ) ) . '</p>';
                }
                break;
            case 'field12':
                if ( $register['usenumber1'] && $values['yournumber1'] ) {
                    $content .= '<p><b>' . $register['yournumber1'] . ': </b>' . strip_tags( stripslashes( $values['yournumber1'] ) ) . '</p>';
                }
                break;
            case 'field17':
                break;
        }
    }
    if ( $register['ignorepayment'] ) {
        $content .= '<p><b>' . $register['ignorepaymentlabel'] . ': </b>' . strip_tags( stripslashes( $values['ignore'] ) ) . '</p>';
    }
    return $content;
}

/**
 * @param $register
 * @param $message
 * @param $report
 * @param $pid
 * @param $qem_edit
 * @param $selected
 *
 * @return string
 */
function qem_build_registration_table(
    $register,
    $message,
    $report,
    $pid,
    $qem_edit,
    $selected,
    $extra_args = array()
)
{
    global  $qem_fs ;
    $payment = qem_get_stored_payment();
    $event = event_get_stored_options();
    $ic = qem_get_incontext();
    $number = get_post_meta( $pid, 'event_number', true );
    $span = $charles = $content = '';
    if ( isset( $extra_args['show_events_with_no_attendees'] ) && true === $extra_args['show_events_with_no_attendees'] ) {
        $charles = 'message';
    }
    $delete = array();
    $i_array = 0;
    $sort = explode( ',', $register['sort'] );
    if ( isset( $extra_args['fields'] ) && !empty($extra_args['fields']) ) {
        $sort = explode( ',', $extra_args['fields'] );
    }
    
    if ( isset( $extra_args['no_payment_info'] ) && !empty($extra_args['no_payment_info']) ) {
        $register['ipn'] = '';
        $register['ignorepayment'] = '';
        $ic['useincontext'] = '';
    }
    
    $dashboard = '<table cellspacing="0">
    <tr>';
    foreach ( $sort as $name ) {
        switch ( $name ) {
            case 'field1':
                if ( $register['usename'] ) {
                    $dashboard .= '<th class="yourname">' . $register['yourname'] . '</th>';
                }
                break;
            case 'field2':
                if ( $register['usemail'] ) {
                    $dashboard .= '<th class="youremail">' . $register['youremail'] . '</th>';
                }
                break;
            case 'field3':
                if ( isset( $register['useattend'] ) && $register['useattend'] ) {
                    $dashboard .= '<th class="yourattend">' . $register['yourattend'] . '</th>';
                }
                break;
            case 'field4':
                if ( isset( $register['usetelephone'] ) && $register['usetelephone'] ) {
                    $dashboard .= '<th class="yourtelephone">' . $register['yourtelephone'] . '</th>';
                }
                break;
            case 'field5':
                if ( isset( $register['useplaces'] ) && $register['useplaces'] ) {
                    $dashboard .= '<th class="yourplaces">' . $register['yourplaces'] . '</th>';
                }
                if ( $register['usemorenames'] ) {
                    $dashboard .= '<th class="morenames">' . $register['morenames'] . '</th>';
                }
                break;
            case 'field6':
                if ( isset( $register['usemessage'] ) && $register['usemessage'] ) {
                    $dashboard .= '<th class="yourmessage">' . $register['yourmessage'] . '</th>';
                }
                break;
            case 'field9':
                if ( $register['useblank1'] ) {
                    $dashboard .= '<th class="yourblank1">' . $register['yourblank1'] . '</th>';
                }
                break;
            case 'field10':
                if ( $register['useblank2'] ) {
                    $dashboard .= '<th class="yourblank2">' . $register['yourblank2'] . '</th>';
                }
                break;
            case 'field11':
                
                if ( $register['usedropdown'] ) {
                    $arr = explode( ",", $register['yourdropdown'] );
                    $dashboard .= '<th class="yourdropdown">' . $arr[0] . '</th>';
                }
                
                break;
            case 'field14':
                
                if ( isset( $register['useselector'] ) && $register['useselector'] ) {
                    $arr = explode( ",", $register['yourselector'] );
                    $dashboard .= '<th class="yourselector">' . $arr[0] . '</th>';
                }
                
                break;
            case 'field15':
                if ( $register['useoptin'] ) {
                    $dashboard .= '<th class="optinblurb">' . $register['optinblurb'] . '</th>';
                }
                break;
            case 'field16':
                if ( $register['usechecks'] ) {
                    $dashboard .= '<th class="checkslist">' . $register['checkslabel'] . '</th>';
                }
                break;
            case 'field12':
                if ( $register['usenumber1'] ) {
                    $dashboard .= '<th class="yournumber1">' . $register['yournumber1'] . '</th>';
                }
                break;
            case 'field17':
                break;
        }
    }
    $dashboard .= '<th class="datesent">Date Sent</th>';
    if ( $payment['ipn'] || $ic['useincontext'] || $register['ignorepayment'] ) {
        $dashboard .= '<th class="payment">' . $payment['title'] . '</th>';
    }
    if ( !$report ) {
        $dashboard .= '<th class="checkbox">Select</th>';
    }
    if ( $report == 'edit' ) {
        $dashboard .= '<th></th>';
    }
    $dashboard .= '</tr>';
    $num = 0;
    $sort_message = qem_add_message_key( $message );
    foreach ( $sort_message as $value ) {
        $num += (int) $value['yourplaces'];
        $span = '';
        if ( $number && $num > $number ) {
            $span = 'color:#CCC;';
        }
        if ( !qem_get_element( $value, 'approved', false ) && $register['moderate'] ) {
            $span = $span . 'font-style:italic;';
        }
        if ( $span ) {
            $span = ' style="' . $span . '" ';
        }
        $content .= '<tr' . $span . '>';
        foreach ( $sort as $name ) {
            switch ( $name ) {
                case 'field1':
                    
                    if ( qem_get_element( $register, 'usename' ) ) {
                        $content .= '<td class="yourname">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" required value="' . qem_get_element( $value, 'yourname' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourname]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourname' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field2':
                    
                    if ( qem_get_element( $register, 'usemail' ) ) {
                        $content .= '<td class="youremail">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="email" value="' . qem_get_element( $value, 'youremail' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][youremail]">';
                        } else {
                            $content .= qem_get_element( $value, 'youremail' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field3':
                    
                    if ( qem_get_element( $register, 'useattend' ) ) {
                        $content .= '<td class="yourattend">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'notattend' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][notattend]">';
                        } else {
                            $content .= qem_get_element( $value, 'notattend' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field4':
                    
                    if ( qem_get_element( $register, 'usetelephone' ) ) {
                        $content .= '<td class="yourtelephone">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'yourtelephone' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourtelephone]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourtelephone' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field5':
                    
                    if ( qem_get_element( $register, 'useplaces' ) && empty(qem_get_element( $value, 'notattend' )) ) {
                        $content .= '<td class="yourplaces">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="number" required min="1" value="' . qem_get_element( $value, 'yourplaces' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourplaces]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourplaces' ) . qem_get_element( $value, 'products' );
                        }
                        
                        $content .= '</td>';
                    } elseif ( qem_get_element( $register, 'useplaces' ) ) {
                        $content .= '<td></td>';
                    }
                    
                    
                    if ( qem_get_element( $register, 'usemorenames' ) ) {
                        $content .= '<td class="morenames">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'morenames' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][morenames]">';
                        } else {
                            $content .= qem_get_element( $value, 'morenames' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field6':
                    if ( qem_get_element( $register, 'usemessage', false ) ) {
                        $content .= '<td class="yourmessage">' . qem_get_element( $value, 'yourmessage' ) . '</td>';
                    }
                    break;
                case 'field9':
                    
                    if ( qem_get_element( $register, 'useblank1' ) ) {
                        $content .= '<td class="yourblank1">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'yourblank1' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourblank1]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourblank1' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field10':
                    
                    if ( qem_get_element( $register, 'useblank2' ) ) {
                        $content .= '<td class="yourblank2">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'yourblank2' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourblank2]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourblank2' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field11':
                    
                    if ( qem_get_element( $register, 'usedropdown' ) ) {
                        $content .= '<td class="yourdropdown">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'yourdropdown' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourdropdown]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourdropdown' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field12':
                    
                    if ( qem_get_element( $register, 'usenumber1' ) ) {
                        $content .= '<td class="yournumber1">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'yournumber1' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yournumber1]">';
                        } else {
                            $content .= qem_get_element( $value, 'yournumber1' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field14':
                    
                    if ( qem_get_element( $register, 'useselector' ) ) {
                        $content .= '<td class="yourselector">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'yourselector' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][yourselector]">';
                        } else {
                            $content .= qem_get_element( $value, 'yourselector' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field15':
                    
                    if ( qem_get_element( $register, 'useoptin', false ) ) {
                        $content .= '<td class="youroptin">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'youroptin' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][youroptin]">';
                        } else {
                            $content .= qem_get_element( $value, 'youroptin' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field16':
                    
                    if ( qem_get_element( $register, 'usechecks', false ) ) {
                        $content .= '<td class="checkslist">';
                        
                        if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                            $content .= '<input style="width:100%" type="text" value="' . qem_get_element( $value, 'checkslist' ) . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][checkslist]">';
                        } else {
                            $content .= qem_get_element( $value, 'checkslist' );
                        }
                        
                        $content .= '</td>';
                    }
                    
                    break;
                case 'field17':
                    break;
            }
        }
        if ( qem_get_element( $value, 'yourname' ) || qem_get_element( $value, 'youremail' ) ) {
            $charles = 'messages';
        }
        $content .= '<td class="sentdate">' . qem_get_element( $value, 'sentdate' ) . '</td>';
        
        if ( qem_get_element( $payment, 'ipn' ) || qem_get_element( $ic, 'useincontext' ) || qem_get_element( $register, 'ignorepayment' ) ) {
            $paid_sel = '';
            $pending_sel = '';
            
            if ( 'Paid' == qem_get_element( $value, 'ipn' ) ) {
                $paid_sel = ' selected ';
            } else {
                $pending_sel = ' selected ';
            }
            
            $ipn = ( qem_get_element( $payment, 'sandbox' ) ? qem_get_element( $value, 'ipn' ) : esc_html__( 'Pending', 'quick-event-manager' ) );
            if ( isset( $register['ignorepayment'] ) && $register['ignorepayment'] && isset( $value['ignore'] ) && 'checked' === $value['ignore'] ) {
                $ipn = qem_get_element( $register, 'ignorepaymentlabel' );
            }
            $value['ipn'] = ( qem_get_element( $value, 'ipn' ) == "Paid" ? qem_get_element( $payment, 'paid' ) : $ipn );
            $content .= '<td class="payment">';
            
            if ( $qem_edit == 'selected' && qem_get_element( $selected, $i_array ) || $qem_edit == 'all' ) {
                //		$content .= '<input style="width:100%" type="text" value="' . $value['ipn'] . '" name="message[' . $i . '][ipn]">';
                $content .= '<select style="width:100%"' . '" name="message[' . qem_get_element( $value, 'orig_key' ) . '][ipn]">';
                $content .= '<option value="Paid"' . $paid_sel . '>' . qem_get_element( $payment, 'paid' ) . '</option>';
                $content .= '<option value="' . qem_get_element( $value, 'custom' ) . '"' . $pending_sel . '>' . __( 'Pending', 'quick-event-manager' ) . '</option>';
                $content .= '</select>';
            } else {
                $content .= qem_get_element( $value, 'ipn' );
            }
            
            $content .= '</td>';
        }
        
        if ( !$report || $report == 'edit' ) {
            // has name of sorted position and value of table position
            $content .= '<td class="checkbox"><input type="checkbox" name="' . $i_array . '" value="' . qem_get_element( $value, 'orig_key' ) . '" /></td>';
        }
        $content .= '</tr>';
        $i_array++;
    }
    $dashboard .= $content . '</table>';
    $str = qem_get_the_numbers( $pid, $payment );
    if ( $number && $str > $number ) {
        $str = $number;
    }
    if ( $str ) {
        $dashboard .= qem_get_element( $event, 'numberattendingbefore' ) . ' ' . $str . ' ' . qem_get_element( $event, 'numberattendingafter' );
    }
    $usecounter = get_post_meta( $pid, 'event_number', true );
    $output = '<p class="placesavailable">' . qem_places(
        $register,
        $pid,
        $usecounter,
        $event
    ) . '</p>';
    if ( $output ) {
        $dashboard .= $output;
    }
    if ( $charles ) {
        return $dashboard;
    }
}

function qem_add_message_key( $messages )
{
    foreach ( $messages as $key => &$row ) {
        $row['orig_key'] = $key;
    }
    return $messages;
}

function qem_sort_surname( $messages )
{
    usort( $messages, function ( $a, $b ) {
        $a_name = qem_split_surname( qem_get_element( $a, 'yourname' ) );
        $b_name = qem_split_surname( qem_get_element( $b, 'yourname' ) );
        return strcmp( strtolower( $a_name[1] ), strtolower( $b_name[1] ) );
    } );
    return $messages;
}

function qem_sort_email( $messages )
{
    usort( $messages, function ( $a, $b ) {
        return strcmp( strtolower( $a['youremail'] ), strtolower( $b['youremail'] ) );
    } );
    return $messages;
}

function qem_sort_date_desc( $messages )
{
    usort( $messages, function ( $a, $b ) {
        return strcmp( strtolower( $b['datetime_added'] ), strtolower( $a['datetime_added'] ) );
    } );
    return $messages;
}

function qem_split_surname( $full_name )
{
    $parts = explode( " ", $full_name );
    
    if ( count( $parts ) > 2 ) {
        $last = array_pop( $parts );
        return [ implode( " ", $parts ), $last ];
    }
    
    return [ $parts[0], $parts[1] ];
}

/**
 * @throws \PHPMailer\PHPMailer\Exception
 */
function qem_messages()
{
    global  $qem_fs ;
    $event = ( isset( $_GET["event"] ) ? (int) $_GET["event"] : null );
    $title = ( isset( $_GET["title"] ) ? sanitize_text_field( $_GET["title"] ) : null );
    $unixtime = get_post_meta( $event, 'event_date', true );
    $date = date_i18n( "d M Y", $unixtime );
    $noregistration = '<p>' . esc_html__( 'No event selected', 'quick-event-manager' ) . '</p>';
    $extra_args = array();
    $category = 'All Categories';
    
    if ( isset( $_POST['qem_reset_message'] ) ) {
        $event = (int) $_POST['qem_download_form'];
        $title = get_the_title( $event );
        delete_option( 'qem_messages_' . $event );
        delete_option( $event );
        qem_admin_notice( 'Registrants for ' . $title . ' have been deleted.' );
        $eventnumber = get_post_meta( $event, 'event_number', true );
        update_option( $event . 'places', $eventnumber );
    }
    
    if ( isset( $_POST['category'] ) ) {
        $category = sanitize_text_field( $_POST["category"] );
    }
    
    if ( isset( $_POST['select_event'] ) || isset( $_POST['eventid'] ) ) {
        $event = (int) $_POST["eventid"];
        
        if ( $event ) {
            $unixtime = get_post_meta( $event, 'event_date', true );
            $date = date_i18n( "d M Y", $unixtime );
            $title = get_the_title( $event );
            $noregistration = '<h2>' . $title . ' | ' . $date . '</h2><p>' . esc_html__( 'Nobody has registered for', 'quick-event-manager' ) . '  ' . $title . ' ' . esc_html__( 'yet', 'quick-event-manager' ) . '</p>';
        } else {
            $noregistration = '<p>' . esc_html__( 'No event selected', 'quick-event-manager' ) . '</p>';
        }
    
    }
    
    
    if ( isset( $_POST['changeoptions'] ) ) {
        $options = array( 'showevents', 'category' );
        foreach ( $options as $item ) {
            $messageoptions[$item] = stripslashes( $_POST[$item] );
        }
        $category = qem_get_element( $messageoptions, 'category' );
        update_option( 'qem_messageoptions', $messageoptions );
    }
    
    
    if ( isset( $_POST['qem_delete_selected'] ) ) {
        $event = (int) $_POST["qem_download_form"];
        $message = get_option( 'qem_messages_' . $event );
        $len = count( $message );
        for ( $i = 0 ;  $i < $len ;  $i++ ) {
            
            if ( isset( $_POST[$i] ) ) {
                $num = ( $message[$_POST[$i]]['yourplaces'] ? $message[$_POST[$i]]['yourplaces'] : 1 );
                unset( $message[(int) $_POST[$i]] );
            }
        
        }
        $message = array_values( $message );
        update_option( 'qem_messages_' . $event, $message );
        qem_admin_notice( esc_html__( 'Selected registrations have been deleted.', 'quick-event-manager' ) );
    }
    
    $new_row = false;
    
    if ( isset( $_POST['qem_add_row'] ) ) {
        $message = get_option( 'qem_messages_' . $event );
        $message[] = apply_filters( 'qem_new_attendee_defaults', array() );
        update_option( 'qem_messages_' . $event, $message );
        $new_row = count( $message ) - 1;
        qem_admin_notice( esc_html__( 'New attendee added.', 'quick-event-manager' ) );
    }
    
    
    if ( isset( $_POST['qem_delete_blanks'] ) ) {
        $event = (int) $_POST["qem_download_form"];
        $message = get_option( 'qem_messages_' . $event );
        for ( $i = 0 ;  $i <= 200 ;  $i++ ) {
            if ( !$message[$i]['yourname'] ) {
                unset( $message[$i] );
            }
        }
        $message = array_values( $message );
        update_option( 'qem_messages_' . $event, $message );
        qem_admin_notice( esc_html__( 'Blanks registrations have been deleted.', 'quick-event-manager' ) );
    }
    
    
    if ( isset( $_POST['qem_approve_selected'] ) ) {
        $event = (int) $_POST["qem_download_form"];
        $message = get_option( 'qem_messages_' . $event );
        $len = count( $message );
        $auto = qem_get_stored_autoresponder();
        for ( $i = 0 ;  $i < $len ;  $i++ ) {
            
            if ( isset( $_POST[$i] ) ) {
                $num = ( $message[$_POST[$i]]['yourplaces'] ? $message[$i]['yourplaces'] : 1 );
                $message[$_POST[$i]]['approved'] = 'checked';
                // @TODO  investigate what register is needed for
                qem_send_confirmation(
                    $auto,
                    $message[$_POST[$i]],
                    null,
                    $register,
                    $event
                );
            }
        
        }
        $message = array_values( $message );
        update_option( 'qem_messages_' . $event, $message );
        qem_admin_notice( esc_html__( 'Selected registrations have been approved.', 'quick-event-manager' ) );
    }
    
    // Update edited applications
    
    if ( isset( $_POST['qem_update'] ) ) {
        $event = (int) $_POST["qem_download_form"];
        $unixtime = get_post_meta( $event, 'event_date', true );
        $date = date_i18n( "d M Y", $unixtime );
        $title = get_the_title( $event );
        $arr = array(
            'yourname',
            'youremail',
            'yourtelephone',
            'yourmessage',
            'yourplaces',
            'yourblank1',
            'yourblank2',
            'yourdropdown',
            'yourselector',
            'yournumber1',
            'morenames',
            'ignore',
            'youroptin',
            'products',
            'ipn'
        );
        $message = get_option( 'qem_messages_' . $event );
        // sanitize $_POST['message'] array
        $post_message = array_map( function ( $row ) {
            return array_map( function ( $field ) {
                return sanitize_text_field( $field );
            }, $row );
        }, ( isset( $_POST['message'] ) ? $_POST['message'] : array() ) );
        foreach ( $post_message as $registration => $row ) {
            // Loop through the row thats contained in the message array entry
            foreach ( $row as $k => $v ) {
                // Do the same value assignment you make in your code
                if ( 'ipn' == $k && 'pending' == $v ) {
                    continue;
                }
                
                if ( 'notattend' == $k && empty($v) ) {
                    continue;
                    // skip to avoid setting not attend flag
                }
                
                $message[$registration][$k] = $v;
            }
        }
        update_option( 'qem_messages_' . $event, $message );
        qem_admin_notice( esc_html__( 'Attendees for', 'quick-event-manager' ) . ' ' . get_the_title( $event ) . ' ' . esc_html__( 'have been updated', 'quick-event-manager' ) );
    }
    
    $qem_edit = '';
    $selected = '';
    // Edit all applications
    
    if ( isset( $_POST['qem_edit'] ) ) {
        $event = (int) $_POST["qem_download_form"];
        $unixtime = get_post_meta( $event, 'event_date', true );
        $date = date_i18n( "d M Y", $unixtime );
        $title = get_the_title( $event );
        $qem_edit = 'all';
    }
    
    // Edit selected applications
    
    if ( isset( $_POST['qem_edit_selected'] ) || false !== $new_row ) {
        $event = (int) $_POST["qem_download_form"];
        $unixtime = get_post_meta( $event, 'event_date', true );
        $date = date_i18n( "d M Y", $unixtime );
        $title = get_the_title( $event );
        $qem_edit = 'selected';
        
        if ( false !== $new_row ) {
            $selected = array(
                $new_row => $new_row,
            );
            $extra_args = array(
                'do_not_sort' => true,
            );
        } else {
            $selected = array_map( function ( $row ) {
                return sanitize_text_field( $row );
            }, $_POST );
        }
    
    }
    
    
    if ( isset( $_POST['qem_emaillist'] ) ) {
        
        if ( empty($register['sendemail']) ) {
            $sys_email = get_bloginfo( 'admin_email' );
        } else {
            $emails = explode( ',', $register['sendemail'] );
            $sys_email = $emails[0];
        }
        
        $event = (int) $_POST["qem_download_form"];
        $title = sanitize_text_field( $_POST["qem_download_title"] );
        $message = get_option( 'qem_messages_' . $event );
        $register = get_custom_registration_form( $event );
        $number = get_post_meta( $event, 'event_number', true );
        $content = qem_build_registration_table(
            $register,
            $message,
            '',
            '',
            '',
            '',
            array(
            'sort_surname' => true,
        )
        );
        global  $current_user ;
        wp_get_current_user();
        $qem_email = $current_user->user_email;
        $values = array(
            'youremail' => $qem_email,
        );
        $headers = "From: " . $sys_email . "\r\n";
        $headers = "Reply-to: {$qem_email}\r\n" . "Content-Type: text/html; charset=\"utf-8\"\r\n";
        qem_wp_mail(
            'Registration list',
            $qem_email,
            $title,
            $content,
            $headers
        );
        qem_admin_notice( esc_html__( 'Registration list has been sent to', 'quick-event-manager' ) . ' ' . $qem_email . '.' );
    }
    
    qem_generate_csv();
    $content = $current = $all = '';
    $messageoptions = qem_get_stored_msg();
    $register = get_custom_registration_form( $event );
    ${$messageoptions['showevents']} = "checked";
    $message = get_option( 'qem_messages_' . $event );
    $places = get_option( $event . 'places' );
    if ( !is_array( $message ) ) {
        $message = array();
    }
    $dashboard = '<div class="wrap">
    <h1>' . esc_html__( 'Event Registration Report', 'quick-event-manager' ) . '</h1>
    <p><form class="select-form-control" method="post" action="">' . qem_message_categories( $category ) . '
        &nbsp;&nbsp;' . qem_get_eventlist(
        $event,
        $register,
        $messageoptions,
        $category
    ) . '
        &nbsp;&nbsp;<b>Show:</b> <input style="margin:0; padding:0; border:none;" type="radio" name="showevents" value="all" ' . $all . ' /> ' . esc_html__( 'All Events', 'quick-event-manager' ) . ' <input style="margin:0; padding:0; border:none;" type="radio" name="showevents" value="current" ' . $current . ' /> Current Events&nbsp;&nbsp;<input type="submit" name="changeoptions" class="button-secondary" value="Update options" />
        </form>
        </p>
        <div id="qem-widget">
        <form method="post" id="qem_download_form" action="">';
    $content = qem_build_registration_table(
        $register,
        $message,
        '',
        $event,
        $qem_edit,
        $selected,
        $extra_args
    );
    
    if ( $content ) {
        $dashboard .= '<h2>' . $title . ' | ' . $date . '</h2>';
        $dashboard .= '<p>Event ID: ' . $event . '</p>';
        $dashboard .= $content;
        $dashboard .= '<div class="bottom-actions"><input type="hidden" name="qem_download_form" value = "' . $event . '" />
        <input type="hidden" name="qem_download_title" value = "' . $title . '" />
        <input type="submit" name="qem_download_csv" class="button-primary" value="' . esc_html__( 'Export to CSV', 'quick-event-manager' ) . '" />
        <input type="submit" name="qem_emaillist" class="button-primary" value="' . esc_html__( 'Email List', 'quick-event-manager' ) . '" />
        <input type="submit" name="qem_reset_message" class="button-secondary" value="' . esc_html__( 'Delete All Attendees', 'quick-event-manager' ) . '" onclick="return window.confirm( \'' . sprintf( esc_html__( 'Are you sure you want to delete all the attendees for %s?', 'quick-event-manager' ), $title ) . '\' );"/>
        <input type="submit" name="qem_delete_selected" class="button-secondary" value="' . esc_html__( 'Delete Selected', 'quick-event-manager' ) . '" onclick="return window.confirm( \'' . esc_html__( 'Are you sure you want to delete the selected attendees?', 'quick-event-manager' ) . '\' );"/>
        <input type="submit" name="qem_delete_blanks" class="button-secondary" value="' . esc_html__( 'Delete Blanks', 'quick-event-manager' ) . '" onclick="return window.confirm( \'' . esc_html__( 'Are you sure you want to delete the blanks?', 'quick-event-manager' ) . '\' );"/>
		<input type="submit" name="qem_add_row" class="button-secondary" value="' . esc_html__( 'Add Attendee', 'quick-event-manager' ) . '" onclick="return window.confirm( \'' . esc_html__( 'Are you sure you want to manually add an attendee? The number of free places will not be checked here. They WILL NOT get an auto email so you may need to email them details manually', 'quick-event-manager' ) . '\' );"/>';
        
        if ( $qem_edit ) {
            $dashboard .= ' <input type="submit" name="qem_update" class="button-primary" value="' . __( 'Update Attendees', 'quick-event-manager' ) . '" /> ';
        } else {
            $dashboard .= ' <input type="submit" name="qem_edit" class="button-secondary" value="' . __( 'Edit Attendees', 'quick-event-manager' ) . '" /> <input type="submit" name="qem_edit_selected" class="button-secondary" value="' . __( 'Edit Selected', 'quick-event-manager' ) . '" /> ';
        }
        
        if ( qem_get_element( $register, 'moderate' ) ) {
            $dashboard .= ' <input type="submit" name="qem_approve_selected" class="button-secondary" value="Approve Selected" onclick="return window.confirm( \'Are you sure you want to approve the selected attendees?\' );"/>';
        }
        $dashboard .= '</div></form>';
        
        if ( !$qem_fs->can_use_premium_code() ) {
            $template_loader = new Admin_Template_Loader();
            $template_loader->set_template_data( array(
                'template_loader' => $template_loader,
                'freemius'        => $qem_fs,
            ) );
            $template_loader->get_template_part( 'upgrade_cta' );
            $dashboard .= $template_loader->get_output();
        }
    
    } else {
        $dashboard .= $noregistration;
    }
    
    $dashboard .= '</div></div>';
    echo  $dashboard ;
}

/**
 * @param $event
 * @param $register
 * @param $messageoptions
 * @param $thecat
 *
 * @return string
 */
function qem_get_eventlist(
    $event,
    $register,
    $messageoptions,
    $thecat
)
{
    global  $post ;
    $arr = get_categories();
    $content = $slug = '';
    foreach ( $arr as $option ) {
        if ( $thecat == $option->slug ) {
            $slug = $option->slug;
        }
    }
    $content .= '<select name="eventid" onchange="this.form.submit()"><option value="">Select an Event</option>' . "\r\t";
    $args = array(
        'post_type'      => 'event',
        'meta_key'       => 'event_date',
        'orderby'        => array(
        'meta_value_num' => 'ASC',
        'title'          => 'DESC',
    ),
        'posts_per_page' => -1,
        'category_name'  => $slug,
    );
    $today = strtotime( date( 'Y-m-d' ) );
    $event_posts = new WP_Query( $args );
    
    if ( $event_posts->have_posts() ) {
        while ( $event_posts->have_posts() ) {
            $event_posts->the_post();
            $title = get_the_title();
            $id = get_the_id();
            $a = get_post_meta( $post->ID );
            $unixtime = get_post_meta( $post->ID, 'event_date', true );
            $date = date_i18n( "d M Y", $unixtime );
            if ( qem_get_element( $register, 'useform' ) || qem_get_event_field( "event_register" ) && (qem_get_element( $messageoptions, 'showevents' ) == 'all' || $unixtime >= $today) ) {
                $content .= '<option value="' . $id . '">' . $title . ' | ' . $date . '</option>';
            }
        }
        wp_reset_postdata();
        $content .= '</select>
        <noscript><input type="submit" name="select_event" class="button-primary" value="Select Event" /></noscript>';
    }
    
    return $content;
}

/**
 * @param $thecat
 *
 * @return string
 */
function qem_message_categories( $thecat )
{
    $arr = get_categories();
    $content = '<select name="category" onchange="this.form.submit()">
<option value="">All Categories</option>';
    foreach ( $arr as $option ) {
        
        if ( $thecat == $option->slug ) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        
        $content .= '<option value="' . $option->slug . '" ' . $selected . '>' . $option->name . '</option>';
    }
    $content .= '</select>';
    return $content;
}

/**
 * @return array
 */
function qem_get_stored_msg()
{
    $messageoptions = get_option( 'qem_messageoptions' );
    if ( !is_array( $messageoptions ) ) {
        $messageoptions = array();
    }
    $default = array(
        'showevents'   => 'current',
        'messageorder' => 'newest',
    );
    $messageoptions = array_merge( $default, $messageoptions );
    return $messageoptions;
}
