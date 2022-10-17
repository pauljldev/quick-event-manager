<?php

function event_get_stored_options()
{
    $event = get_option( 'event_settings' );
    if ( !is_array( $event ) ) {
        $event = array();
    }
    $default = array(
        'active_buttons'        => array(
        'field1'  => 'on',
        'field2'  => 'on',
        'field3'  => 'on',
        'field4'  => 'on',
        'field5'  => 'on',
        'field6'  => 'on',
        'field7'  => 'on',
        'field8'  => 'on',
        'field9'  => 'on',
        'field10' => 'on',
        'field11' => 'on',
        'field12' => 'on',
        'field13' => '',
        'field14' => '',
    ),
        'summary'               => array(
        'field1' => 'checked',
        'field2' => 'checked',
        'field3' => 'checked',
    ),
        'label'                 => array(
        'field1'  => __( 'Short Description', 'quick-event-manager' ),
        'field2'  => __( 'Event Time', 'quick-event-manager' ),
        'field3'  => __( 'Venue', 'quick-event-manager' ),
        'field4'  => __( 'Address', 'quick-event-manager' ),
        'field5'  => __( 'Event Website', 'quick-event-manager' ),
        'field6'  => __( 'Cost', 'quick-event-manager' ),
        'field7'  => __( 'Organiser', 'quick-event-manager' ),
        'field8'  => __( 'Full Description', 'quick-event-manager' ),
        'field9'  => __( 'Places Taken', 'quick-event-manager' ),
        'field10' => __( 'Attendees', 'quick-event-manager' ),
        'field11' => __( 'Places Available', 'quick-event-manager' ),
        'field12' => __( 'Registration Form', 'quick-event-manager' ),
        'field13' => __( 'Category', 'quick-event-manager' ),
        'field14' => __( 'Sharing', 'quick-event-manager' ),
        'field17' => __( 'Allow variable donations', 'quick-event-manager' ),
    ),
        'sort'                  => 'field1,field2,field3,field4,field5,field17,field6,field7,field8,field9,field10,field11,field12,field13,field14',
        'bold'                  => array(
        'field2'  => 'checked',
        'field10' => 'checked',
    ),
        'italic'                => array(
        'field4' => 'checked',
    ),
        'colour'                => array(
        'field2' => '#343838',
        'field6' => '#008C9E',
    ),
        'size'                  => array(
        'field1' => '110',
        'field2' => '120',
        'field6' => '120',
    ),
        'address_label'         => '',
        'url_label'             => '',
        'description_label'     => '',
        'cost_label'            => '',
        'dateformat'            => '',
        'date_background'       => '',
        'deposit_before_label'  => __( 'Deposit', 'quick-event-manager' ),
        'deposit_after_label'   => __( 'per person', 'quick-event-manager' ),
        'organiser_label'       => '',
        'category_label'        => __( 'Category', 'quick-event-manager' ),
        'facebook_label'        => __( 'Share on Facebook', 'quick-event-manager' ),
        'twitter_label'         => __( 'Share on Twitter', 'quick-event-manager' ),
        'ics_label'             => __( 'Download to Calendar', 'quick-event-manager' ),
        'start_label'           => __( 'From', 'quick-event-manager' ),
        'finish_label'          => __( 'until', 'quick-event-manager' ),
        'location_label'        => __( 'At', 'quick-event-manager' ),
        'address_style'         => 'italic',
        'website_link'          => 'checked',
        'show_telephone'        => 'checked',
        'whoscoming'            => '',
        'whoscomingmessage'     => __( 'Look who\'s coming: ', 'quick-event-manager' ),
        'placesbefore'          => __( 'There are', 'quick-event-manager' ),
        'placesafter'           => __( 'places available.', 'quick-event-manager' ),
        'numberattendingbefore' => __( 'There are', 'quick-event-manager' ),
        'numberattendingafter'  => __( 'people coming.', 'quick-event-manager' ),
        'oneplacebefore'        => __( 'There is one place available', 'quick-event-manager' ),
        'oneattendingbefore'    => __( 'There is one person coming', 'quick-event-manager' ),
        'whoscoming'            => 'checked',
        'whosavatar'            => 'checked',
        'facebook'              => 'checked',
    );
    $event = array_merge( $default, $event );
    if ( !strpos( $event['sort'], 'field14' ) ) {
        $event['sort'] = $event['sort'] . ',field14';
    }
    return $event;
}

function event_get_stored_display()
{
    $display = get_option( 'qem_display' );
    if ( !is_array( $display ) ) {
        $display = array();
    }
    $default = array(
        'read_more'             => __( 'Find out more...', 'quick-event-manager' ),
        'noevent'               => __( 'No event found', 'quick-event-manager' ),
        'event_image'           => '',
        'usefeatured'           => 'checked',
        'monthheading'          => '',
        'back_to_list_caption'  => __( 'Return to Event list', 'quick-event-manager' ),
        'image_width'           => 300,
        'event_image_width'     => 300,
        'event_archive'         => '',
        'map_width'             => 200,
        'max_width'             => 40,
        'map_height'            => 200,
        'useics'                => '',
        'uselistics'            => '',
        'useicsbutton'          => __( 'Download Event to Calendar', 'quick-event-manager' ),
        'usetimezone'           => '',
        'timezonebefore'        => __( 'Timezone:', 'quick-event-manager' ),
        'timezoneafter'         => __( 'time', 'quick-event-manager' ),
        'show_map'              => '',
        'map_and_image'         => 'checked',
        'localization'          => '',
        'monthtype'             => 'short',
        'monthheadingorder'     => 'my',
        'categorydropdown'      => false,
        'categorydropdownlabel' => 'Select a Category',
        'categorydropdownwidth' => false,
        'categorylocation'      => 'title',
        'showcategory'          => '',
        'recentposts'           => '',
        'lightboxwidth'         => 60,
        'fullpopup'             => 'checked',
        'linktocategory'        => 'checked',
        'showuncategorised'     => '',
        'keycaption'            => __( 'Event Categories:', 'quick-event-manager' ),
        'showkeyabove'          => '',
        'showkeybelow'          => '',
        'showcategory'          => '',
        'showcategorycaption'   => __( 'Current Category:', 'quick-event-manager' ),
        'cat_border'            => 'checked',
        'catallevents'          => '',
        'catalleventscaption'   => 'Show All',
    );
    $display = array_merge( $default, $display );
    return $display;
}

function qem_get_stored_style()
{
    $style = get_option( 'qem_style' );
    if ( !is_array( $style ) ) {
        $style = array();
    }
    $default = array(
        'font'                => 'theme',
        'font-family'         => 'arial, sans-serif',
        'font-size'           => '1em',
        'header-size'         => '100%',
        'width'               => 600,
        'widthtype'           => 'percent',
        'event_border'        => '',
        'event_background'    => 'bgtheme',
        'event_backgroundhex' => '#FFF',
        'date_colour'         => '#FFF',
        'month_colour'        => '#343838',
        'date_background'     => 'grey',
        'date_backgroundhex'  => '#FFF',
        'month_background'    => 'white',
        'month_backgroundhex' => '#FFF',
        'date_border_width'   => '2',
        'date_border_colour'  => '#343838',
        'date_bold'           => '',
        'date_italic'         => 'checked',
        'calender_size'       => 'medium',
        'icon_corners'        => 'rounded',
        'styles'              => '',
        'uselabels'           => '',
        'startlabel'          => __( 'Starts', 'quick-event-manager' ),
        'finishlabel'         => __( 'Ends', 'quick-event-manager' ),
        'event_margin'        => 'margin: 0 0 20px 0,',
        'line_margin'         => 'margin: 0 0 8px 0,padding: 0 0 0 0',
        'use_custom'          => '',
        'custom'              => ".qem {\r\n}\r\n.qem h2{\r\n}",
        'combined'            => 'checked',
        'iconorder'           => 'default',
        'vanillaw'            => '',
        'vanillawidget'       => '',
        'vanillamonth'        => '',
        'use_head'            => '',
        'linktocategory'      => 'checked',
        'showuncategorised'   => '',
        'linktocategories'    => '',
        'keycaption'          => __( 'Event Categories:', 'quick-event-manager' ),
        'showkeyabove'        => '',
        'showkeybelow'        => '',
        'showcategory'        => '',
        'showcategorycaption' => __( 'Current Category:', 'quick-event-manager' ),
        'dayborder'           => 'checked',
        'catallevents'        => '',
        'catalleventscaption' => 'Show All',
        'location'            => 'head',
        'cata'                => '',
        'catb'                => '',
        'catc'                => '',
        'catd'                => '',
        'cate'                => '',
        'catf'                => '',
        'catg'                => '',
        'cath'                => '',
        'cati'                => '',
        'catj'                => '',
        'background'          => 'bgwhite',
    );
    $style = array_merge( $default, $style );
    return $style;
}

function qem_get_stored_calendar()
{
    $calendar = get_option( 'qem_calendar' );
    if ( !is_array( $calendar ) ) {
        $calendar = array();
    }
    $default = array(
        'day'                 => '#EBEFC9',
        'calday'              => '#EBEFC9',
        'eventday'            => '#EED1AC',
        'oldday'              => '#CCC',
        'eventhover'          => '#F2F2E6',
        'eventdaytext'        => '#343838',
        'eventbackground'     => '#FFF',
        'eventtext'           => '#343838',
        'eventlink'           => 'linkpopup',
        'calendar_text'       => __( 'View as calendar', 'quick-event-manager' ),
        'calendar_url'        => '',
        'eventlist_text'      => __( 'View as a list of events', 'quick-event-manager' ),
        'eventlist_url'       => '',
        'eventlength'         => '20',
        'connect'             => '',
        'startday'            => 'sunday',
        'archive'             => 'checked',
        'archivelinks'        => 'checked',
        'prevmonth'           => 'Prev',
        'nextmonth'           => 'Next',
        'smallicon'           => 'arrow',
        'unicode'             => '\\263A',
        'eventtext'           => '#343838',
        'eventtextsize'       => '80%',
        'trigger'             => '480px',
        'eventbackground'     => '#FFF',
        'eventhover'          => '#EED1AC',
        'eventborder'         => '1px solid #343838',
        'keycaption'          => __( 'Event Key:', 'quick-event-manager' ),
        'navicon'             => 'arrows',
        'linktocategory'      => 'checked',
        'showuncategorised'   => '',
        'tdborder'            => '',
        'cellspacing'         => 3,
        'header'              => 'h2',
        'headerorder'         => 'my',
        'headerstyle'         => '',
        'eventimage'          => '',
        'imagewidth'          => '80',
        'usetootlip'          => '',
        'event_corner'        => 'rounded',
        'fixeventborder'      => '',
        'showmonthsabove'     => '',
        'showmonthsbelow'     => '',
        'monthscaption'       => 'Select Month:',
        'hidenavigation'      => '',
        'jumpto'              => 'checked',
        'calallevents'        => 'checked',
        'calalleventscaption' => 'Show All',
        'eventbold'           => '',
        'eventitalic'         => '',
        'eventbackground'     => '',
        'eventgridborder'     => '',
        'caldaytext'          => '',
        'attendeeflag'        => '',
        'attendeeflagcontent' => '',
    );
    $calendar = array_merge( $default, $calendar );
    return $calendar;
}

function qem_get_stored_register()
{
    $register = get_option( 'qem_register' );
    if ( !is_array( $register ) ) {
        $register = array();
    }
    $default = array(
        'sort'                  => 'field1,field2,field3,field4,field5,field17,field6,field7,field8,field9,field10,field11,field12,field13,field14,field15,field16',
        'useform'               => '',
        'formwidth'             => 280,
        'usename'               => 'checked',
        'usemail'               => 'checked',
        'useblank1'             => '',
        'useblank2'             => '',
        'usedropdown'           => '',
        'usenumber1'            => '',
        'useaddinfo'            => '',
        'useoptin'              => '',
        'usechecks'             => '',
        'usechecksradio'        => '',
        'reqname'               => 'checked',
        'reqmail'               => 'checked',
        'reqblank1'             => '',
        'reqblank2'             => '',
        'reqdropdown'           => '',
        'reqnumber1'            => '',
        'formborder'            => '',
        'ontheright'            => '',
        'notificationsubject'   => __( 'New registration for', 'quick-event-manager' ),
        'title'                 => __( 'Register for this event', 'quick-event-manager' ),
        'blurb'                 => __( 'Enter your details below', 'quick-event-manager' ),
        'replytitle'            => __( 'Thank you for registering', 'quick-event-manager' ),
        'replyblurb'            => __( 'We will be in contact soon', 'quick-event-manager' ),
        'replydeferred'         => __( 'Please ensure you bring the registration fee to the event', 'quick-event-manager' ),
        'yourname'              => __( 'Your Name', 'quick-event-manager' ),
        'youremail'             => __( 'Email Address', 'quick-event-manager' ),
        'yourtelephone'         => __( 'Telephone Number', 'quick-event-manager' ),
        'yourplaces'            => __( 'Places required', 'quick-event-manager' ),
        'donation'              => __( 'Donation Amount', 'quick-event-manager' ),
        'placesposition'        => 'left',
        'yourmessage'           => __( 'Message', 'quick-event-manager' ),
        'yourattend'            => __( 'I will not be attending this event', 'quick-event-manager' ),
        'yourblank1'            => __( 'More Information', 'quick-event-manager' ),
        'yourblank2'            => __( 'More Information', 'quick-event-manager' ),
        'yourdropdown'          => __( 'Separate,With,Commas', 'quick-event-manager' ),
        'yourselector'          => __( 'Separate,With,Commas', 'quick-event-manager' ),
        'yournumber1'           => __( 'Number', 'quick-event-manager' ),
        'addinfo'               => __( 'Fill in this field', 'quick-event-manager' ),
        'captchalabel'          => __( 'Answer the sum', 'quick-event-manager' ),
        'optinblurb'            => __( 'Sign me up for email messages', 'quick-event-manager' ),
        'checkslabel'           => __( 'Select options', 'quick-event-manager' ),
        'checkslist'            => __( 'Option 1,Option 4,Option 3', 'quick-event-manager' ),
        'usemorenames'          => '',
        'morenames'             => __( 'Enter all names:', 'quick-event-manager' ),
        'useterms'              => '',
        'termslabel'            => __( 'I agree to the Terms and Conditions', 'quick-event-manager' ),
        'termsurl'              => '',
        'termstarget'           => '',
        'useattachment'         => '',
        'attachmentlabel'       => 'Attach an image',
        'attachmenttypes'       => 'jpeg,jpg,gif,png',
        'attachmentsize'        => '200kb',
        'notattend'             => '',
        'error'                 => __( 'Please complete the form', 'quick-event-manager' ),
        'qemsubmit'             => __( 'Register', 'quick-event-manager' ),
        'whoscoming'            => '',
        'whoscomingmessage'     => __( 'Look who\'s coming: ', 'quick-event-manager' ),
        'placesbefore'          => __( 'There are', 'quick-event-manager' ),
        'placesafter'           => __( 'places available.', 'quick-event-manager' ),
        'numberattendingbefore' => __( 'There are', 'quick-event-manager' ),
        'numberattendingafter'  => __( 'people coming.', 'quick-event-manager' ),
        'eventlist'             => '',
        'eventfull'             => '',
        'eventfullmessage'      => __( 'Registration is closed', 'quick-event-manager' ),
        'waitinglist'           => '',
        'waitinglistreply'      => __( 'Your name has been added to the waiting list', 'quick-event-manager' ),
        'waitinglistmessage'    => __( 'But you can register for the waiting list', 'quick-event-manager' ),
        'moderate'              => '',
        'moderatereply'         => __( 'Your registration is awaiting approval', 'quick-event-manager' ),
        'read_more'             => __( 'Return to the event', 'quick-event-manager' ),
        'useread_more'          => '',
        'sendemail'             => get_bloginfo( 'admin_email' ),
        'qemmail'               => 'wpmail',
        'sendcopy'              => '',
        'usecopy'               => '',
        'completed'             => '',
        'copyblurb'             => __( 'Send registration details to your email address', 'quick-event-manager' ),
        'alreadyregistered'     => __( 'You are already registered for this event', 'quick-event-manager' ),
        'nameremoved'           => __( 'You have been removed from the list', 'quick-event-manager' ),
        'checkremoval'          => '',
        'spam'                  => __( 'Your Details have been flagged as spam', 'quick-event-manager' ),
        'thanksurl'             => '',
        'cancelurl'             => '',
        'allowmultiple'         => '',
        'paypal'                => '',
        'perevent'              => 'perperson',
        'couponcode'            => __( 'Coupon code', 'quick-event-manager' ),
        'ignorepayment'         => '',
        'ignorepaymentlabel'    => __( 'Pay on arrival', 'quick-event-manager' ),
        'placesavailable'       => 'checked',
        'submitbackground'      => '#343838',
        'hoversubmitbackground' => '#888888',
        'listname'              => false,
        'listblurb'             => '[name] x[places] ([telephone]) [website]',
    );
    $register = array_merge( $default, $register );
    if ( !strpos( $register['sort'], 'field15' ) ) {
        $register['sort'] = $register['sort'] . ',field15';
    }
    if ( !strpos( $register['sort'], 'field16' ) ) {
        $register['sort'] = $register['sort'] . ',field16';
    }
    return $register;
}

function qem_get_register_style()
{
    $style = get_option( 'qem_register_style' );
    $register = qem_get_stored_register();
    if ( !is_array( $style ) ) {
        $style = array();
    }
    $default = array(
        'header'                  => '',
        'header-type'             => 'h2',
        'header-size'             => '1.6em',
        'header-colour'           => '#465069',
        'text-font-family'        => 'arial, sans-serif',
        'text-font-size'          => '1em',
        'text-font-colour'        => '#465069',
        'error-font-colour'       => '#D31900',
        'error-border'            => '1px solid #D31900',
        'form-width'              => $register['formwidth'],
        'submitwidth'             => 'submitpercent',
        'submitposition'          => 'submitleft',
        'border'                  => 'none',
        'form-border'             => '1px solid #415063',
        'input-border'            => '1px solid #415063',
        'input-required'          => '1px solid #00C618',
        'bordercolour'            => '#415063',
        'inputborderdefault'      => '1px solid #415063',
        'inputborderrequired'     => '1px solid #00C618',
        'inputbackground'         => '#FFFFFF',
        'inputfocus'              => '#FFFFCC',
        'background'              => 'theme',
        'backgroundhex'           => '#FFF',
        'submit-colour'           => '#FFF',
        'submit-background'       => $register['submitbackground'],
        'submit-hover-background' => $register['hoversubmitbackground'],
        'submit-button'           => '',
        'submit-border'           => '1px solid #415063',
        'submitwidth'             => 'submitpercent',
        'submitposition'          => 'submitleft',
        'corners'                 => 'corner',
        'line_margin'             => 'margin: 2px 0 3px 0;padding: 6px;',
    );
    $style = array_merge( $default, $style );
    return $style;
}

function qem_get_stored_payment()
{
    $payment = get_option( 'qem_payment' );
    if ( !is_array( $payment ) ) {
        $payment = array();
    }
    $default = array(
        'useqpp'              => '',
        'qppform'             => '',
        'currency'            => 'USD',
        'paypalemail'         => '',
        'useprocess'          => '',
        'message'             => '',
        'payment'             => 'Thank you for registering. Please bring proof of payment to the event',
        'waiting'             => __( 'Waiting for PayPal', 'quick-event-manager' ) . '...',
        'processtype'         => '',
        'processpercent'      => '',
        'processfixed'        => '',
        'qempaypalsubmit'     => __( 'Register and Pay', 'quick-event-manager' ),
        'ipn'                 => '',
        'ipnblock'            => '',
        'title'               => __( 'Payment', 'quick-event-manager' ),
        'paid'                => __( 'Complete', 'quick-event-manager' ),
        'usecoupon'           => '',
        'usependingcleardown' => '',
        'pendingcleardownmsg' => __( 'Your payment for this event did not complete, please try again. If you have any issues please contact us', 'quick-event-manager' ),
        'couponcode'          => __( 'Coupon code', 'quick-event-manager' ),
        'attendeelabel'       => 'Enter number of places required',
        'itemlabel'           => '[label] at <em>[currency][cost]</em> each:',
        'totallabel'          => 'Total:',
        'currencysymbol'      => '$',
    );
    $payment = array_merge( $default, $payment );
    
    if ( $payment['processtype'] ) {
        if ( $payment['processtype'] == 'processfixed' ) {
            $payment['processpercent'] = false;
        }
        if ( $payment['processtype'] == 'processpercent' ) {
            $payment['processfixed'] = false;
        }
    }
    
    return $payment;
}

function qem_get_stored_autoresponder()
{
    global  $qem_fs ;
    $auto = get_option( 'qem_autoresponder' );
    if ( !is_array( $auto ) ) {
        $auto = array();
    }
    $fromemail = get_bloginfo( 'admin_email' );
    $title = get_bloginfo( 'name' );
    $default = array(
        'enable'                   => '',
        'whenconfirm'              => 'aftersubmission',
        'subject'                  => 'You have registered for ',
        'subjecttitle'             => 'checked',
        'subjectdate'              => '',
        'message'                  => 'Thank you for registering, we will be in contact soon. If you have any questions please reply to this email.',
        'useeventdetails'          => '',
        'eventdetailsblurb'        => __( 'Event Details', 'quick-event-manager' ),
        'useregistrationdetails'   => 'checked',
        'registrationdetailsblurb' => __( 'Your registration details', 'quick-event-manager' ),
        'sendcopy'                 => 'checked',
        'fromname'                 => $title,
        'fromemail'                => $fromemail,
        'permalink'                => '',
    );
    $auto = array_merge( $default, $auto );
    return $auto;
}

function qem_get_stored_incontext()
{
    $payment = get_option( 'qem_incontext' );
    if ( !is_array( $payment ) ) {
        $payment = array();
    }
    $default = array(
        'useincontext'    => false,
        'useapi'          => 'paypal',
        'merchantid'      => '',
        'api_username'    => '',
        'api_password'    => '',
        'api_key'         => '',
        'secret_key'      => '',
        'publishable_key' => '',
        'stripeimage'     => '',
    );
    $payment = array_merge( $default, $payment );
    return $payment;
}

function qem_get_stored_sandbox()
{
    $payment = get_option( 'qem_sandbox' );
    if ( !is_array( $payment ) ) {
        $payment = array();
    }
    $default = array(
        'useincontext' => false,
        'useapi'       => 'paypal',
        'merchantid'   => '',
        'api_username' => '',
        'api_password' => '',
        'api_key'      => '',
    );
    $payment = array_merge( $default, $payment );
    return $payment;
}

function qem_get_stored_api()
{
    $api = get_option( 'qem_api' );
    if ( !is_array( $api ) ) {
        $api = array();
    }
    $default = array(
        'validating'            => 'Validating payment information...',
        'waiting'               => 'Waiting for Payment Gateway...',
        'errortitle'            => 'There is a problem',
        'errorblurb'            => 'Your payment could not be processed. Please try again',
        'technicalerrorblurb'   => 'There seems to be a technical issue, contact an administrator!',
        'failuretitle'          => 'Order Failure',
        'failureblurb'          => 'The payment has not been completed.',
        'failureanchor'         => 'Try again',
        'pendingtitle'          => 'Payment Pending',
        'pendingblurb'          => 'The payment has been processed, but confimration is currently pending. Refresh this page for real-time changes to this order.',
        'pendinganchor'         => 'Refresh This Page',
        'confirmationtitle'     => 'Registration Confirmation',
        'confirmationblurb'     => 'The transaction has been completed successfully. Keep this information for your records.',
        'confirmationreference' => 'Payment Reference:',
        'confirmationamount'    => 'Amount Paid:',
        'confirmationanchor'    => 'Register another person',
    );
    $api = array_merge( $default, $api );
    return $api;
}

function qem_get_addons()
{
    global  $qem_fs ;
    return array();
}

function qem_stored_guest()
{
    $guest = get_option( 'qem_guest' );
    if ( !is_array( $guest ) ) {
        $guest = array();
    }
    $default = array(
        'title'                       => __( 'Create an Event', 'quick-event-manager' ),
        'blurb'                       => __( 'Complete the form below to add your own event', 'quick-event-manager' ),
        'thankstitle'                 => __( 'Thank you for submitting your event', 'quick-event-manager' ),
        'thanksblurb'                 => __( 'View all current events', 'quick-event-manager' ),
        'allowimage'                  => false,
        'imagesize'                   => 100000,
        'pendingblurb'                => __( 'Your event is awaiting review and will be published soon.', 'quick-event-manager' ),
        'errormessage'                => __( 'Please complete all marked fields', 'quick-event-manager' ),
        'errorduplicate'              => __( 'An Event with that Title already exists...', 'quick-event-manager' ),
        'errorcaptcha'                => __( 'The captcha answer is incorrect', 'quick-event-manager' ),
        'errorimage'                  => __( 'There is an error with the chosen image', 'quick-event-manager' ),
        'errorenddate'                => __( 'The event ends before it starts', 'quick-event-manager' ),
        'noui'                        => '',
        'event_title_checked'         => 'checked',
        'event_details_checked'       => '',
        'event_tags_checked'          => '',
        'event_category_checked'      => '',
        'event_date_checked'          => 'checked',
        'event_end_date_checked'      => '',
        'event_start_checked'         => '',
        'event_finish_checked'        => '',
        'event_desc_checked'          => '',
        'event_location_checked'      => '',
        'event_address_checked'       => '',
        'event_link_checked'          => '',
        'event_anchor_checked'        => '',
        'event_cost_checked'          => '',
        'event_donation_checked'      => '',
        'event_forms_checked'         => '',
        'event_image_checked'         => '',
        'event_register_checked'      => '',
        'event_pay_checked'           => '',
        'event_image_upload_checked'  => '',
        'event_captcha_checked'       => 'checked',
        'event_author_checked'        => 'checked',
        'event_author_email_checked'  => 'checked',
        'event_title_use'             => 'checked',
        'event_details_use'           => 'checked',
        'event_tags_use'              => '',
        'event_category_use'          => '',
        'event_date_use'              => 'checked',
        'event_end_date_use'          => 'checked',
        'event_start_use'             => 'checked',
        'event_finish_use'            => 'checked',
        'event_desc_use'              => 'checked',
        'event_location_use'          => 'checked',
        'event_address_use'           => 'checked',
        'event_link_use'              => 'checked',
        'event_anchor_use'            => 'checked',
        'event_cost_use'              => 'checked',
        'event_donation_use'          => '',
        'event_organiser_use'         => 'checked',
        'event_telephone_use'         => 'checked',
        'event_forms_use'             => 'checked',
        'event_image_upload_use'      => '',
        'event_register_use'          => '',
        'event_captcha_use'           => 'checked',
        'event_author_use'            => 'checked',
        'event_author_email_use'      => 'checked',
        'event_title_caption'         => __( 'Event Title', 'quick-event-manager' ),
        'event_details_caption'       => __( 'Event Details', 'quick-event-manager' ),
        'event_tags_caption'          => __( 'Tags', 'quick-event-manager' ),
        'event_category_caption'      => __( 'Category', 'quick-event-manager' ),
        'event_date_caption'          => __( 'Start Date', 'quick-event-manager' ),
        'event_end_date_caption'      => __( 'End Date', 'quick-event-manager' ),
        'event_start_caption'         => __( 'Start Time', 'quick-event-manager' ),
        'event_finish_caption'        => __( 'End Time', 'quick-event-manager' ),
        'event_desc_caption'          => __( 'Description', 'quick-event-manager' ),
        'event_location_caption'      => __( 'Location', 'quick-event-manager' ),
        'event_address_caption'       => __( 'Address', 'quick-event-manager' ),
        'event_link_caption'          => __( 'Website', 'quick-event-manager' ),
        'event_anchor_caption'        => __( 'Display As', 'quick-event-manager' ),
        'event_cost_caption'          => __( 'Cost', 'quick-event-manager' ),
        'event_donation_caption'      => __( 'Donation?', 'quick-event-manager' ),
        'event_organiser_caption'     => __( 'Organiser', 'quick-event-manager' ),
        'event_telephone_caption'     => __( 'Telephone', 'quick-event-manager' ),
        'event_register_caption'      => __( 'Registration Form', 'quick-event-manager' ),
        'event_image_upload_caption'  => __( 'Event Image', 'quick-event-manager' ),
        'event_captcha_label_caption' => __( 'Captcha', 'quick-event-manager' ),
        'event_author_caption'        => __( 'Author Name', 'quick-event-manager' ),
        'event_author_email_caption'  => __( 'Author Email', 'quick-event-manager' ),
        'event_forms_caption'         => __( 'Event Forms', 'quick-event-manager' ),
        'event_title'                 => __( 'Event Title', 'quick-event-manager' ),
        'event_details'               => __( 'Event Details', 'quick-event-manager' ),
        'event_tags'                  => __( 'Tags', 'quick-event-manager' ),
        'event_category'              => '1',
        'event_date'                  => __( 'Start Date', 'quick-event-manager' ),
        'event_end_date'              => __( 'End Date', 'quick-event-manager' ),
        'event_start'                 => __( 'Start Time', 'quick-event-manager' ),
        'event_finish'                => __( 'End Time', 'quick-event-manager' ),
        'event_desc'                  => __( 'Description', 'quick-event-manager' ),
        'event_location'              => __( 'Venue', 'quick-event-manager' ),
        'event_address'               => __( 'Address', 'quick-event-manager' ),
        'event_link'                  => __( 'Website', 'quick-event-manager' ),
        'event_anchor'                => __( 'Website Name', 'quick-event-manager' ),
        'event_cost'                  => __( 'Cost', 'quick-event-manager' ),
        'event_donation'              => __( 'Is a Donation?', 'quick-event-manager' ),
        'event_organiser'             => __( 'Organiser', 'quick-event-manager' ),
        'event_telephone'             => __( 'Telephone', 'quick-event-manager' ),
        'event_forms'                 => __( 'Event Forms', 'quick-event-manager' ),
        'event_register'              => __( 'Add a registration form to your event', 'quick-event-manager' ),
        'event_image_details'         => __( 'jpg, gif or png only. Max file size 100kb', 'quick-event-manager' ),
        'event_image_upload'          => __( 'Event Image (jpg, gif or png only. Max file size 100kb)', 'quick-event-manager' ),
        'event_captcha_label'         => __( 'Anti spam Captcha', 'quick-event-manager' ),
        'event_author'                => __( 'Your Name', 'quick-event-manager' ),
        'event_author_email'          => __( 'Your Email', 'quick-event-manager' ),
    );
    $guest = array_merge( $default, $guest );
    return $guest;
}

function qem_guest_list()
{
    $event = array(
        'event_title',
        'event_date',
        'event_end_date',
        'event_start',
        'event_finish',
        'event_desc',
        'event_location',
        'event_address',
        'event_link',
        'event_anchor',
        'event_number',
        'event_cost',
        'event_donation',
        'event_organiser',
        'event_telephone',
        'event_details',
        'event_register',
        'event_tags',
        'event_author',
        'event_author_email',
        'event_image_upload',
        'event_category',
        'event_repeat',
        'theday',
        'thenumber',
        'therepetitions',
        'thewmy'
    );
    return $event;
}
