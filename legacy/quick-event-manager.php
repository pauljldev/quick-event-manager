<?php

global  $qem_fs ;
require_once plugin_dir_path( __FILE__ ) . '/WPHttp.class.php';
require_once plugin_dir_path( __FILE__ ) . '/quick-event-options.php';
require_once plugin_dir_path( __FILE__ ) . '/quick-event-akismet.php';
require_once plugin_dir_path( __FILE__ ) . '/quick-event-register.php';
require_once plugin_dir_path( __FILE__ ) . '/quick-event-payments.php';
require_once plugin_dir_path( __FILE__ ) . '/quick-event-widget.php';
require_once plugin_dir_path( __FILE__ ) . '/PaypalAPI/PaypalAPI.bootstrap.php';
require_once plugin_dir_path( __FILE__ ) . '/PaypalAPI_Integration.php';
require_once plugin_dir_path( __FILE__ ) . '/qemnavi/wp-qemnavi.php';
add_filter(
    'use_block_editor_for_post_type',
    function ( $bool, $post_type ) {
    if ( 'event' === $post_type ) {
        return false;
    }
    return $bool;
},
    10,
    2
);
$qem_calendars = 0;
$qem_ic = array();
/*
	Add: wordpress hooks for ajax for calendar
*/
add_action( 'wp_ajax_qem_ajax_calendar', 'qem_ajax_calendar' );
add_action( 'wp_ajax_nopriv_qem_ajax_calendar', 'qem_ajax_calendar' );
add_action( 'wp_ajax_qem_add_to_calendar', 'qem_add_to_calendar' );
add_action( 'wp_ajax_nopriv_qem_add_to_calendar', 'qem_add_to_calendar' );
add_action( 'wp_ajax_qem_download_ics', 'qem_download_ics' );
add_action( 'wp_ajax_nopriv_qem_download_ics', 'qem_download_ics' );
/*
	Add: qem_ajax_calendar
*/
function qem_ajax_calendar()
{
    echo  qem_show_calendar( qem_sanitize_text_or_array_field( $_POST['atts'] ) ) ;
}

require_once plugin_dir_path( __FILE__ ) . '/quick-event-editor.php';
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . '/settings.php';
}
add_shortcode( 'qem', 'qem_event_shortcode' );
add_shortcode( 'qem-calendar', 'qem_show_calendar' );
add_shortcode( 'qemcalendar', 'qem_show_calendar' );
add_shortcode( 'qemnewevent', 'qem_user_event' );
add_shortcode( 'qemregistration', 'qem_loop' );
global  $qem_fs ;
add_action( 'wp_enqueue_scripts', 'qem_enqueue_scripts' );
add_action( 'init', 'event_register' );
add_action( 'widgets_init', 'add_qem_widget' );
add_action( 'widgets_init', 'add_qem_calendar_widget' );
add_action( 'wp_head', 'qem_head_ic' );
add_filter(
    'plugin_action_links',
    'event_plugin_action_links',
    10,
    2
);
add_filter( 'pre_get_posts', 'qem_add_custom_types' );
add_filter( 'wp_dropdown_users', 'qem_users' );
add_filter(
    'qem_short_desc',
    'qem_short_desc_filter',
    10,
    3
);
function qem_short_desc_filter( $desc, $caption, $style )
{
    return '<p class="desc" ' . $style . '>' . $caption . do_shortcode( $desc ) . '</p>';
}

add_filter(
    'qem_description',
    'qem_description_filter',
    10,
    1
);
function qem_description_filter( $content )
{
    return do_shortcode( $content );
}

add_theme_support( 'post-thumbnails', array( 'post', 'page', 'event' ) );
function remove_menus()
{
    // get current login user's role
    $roles = wp_get_current_user()->roles;
    // test role
    if ( !in_array( 'event-manager', $roles ) ) {
        return;
    }
    remove_menu_page( 'edit.php' );
    //Posts
    remove_menu_page( 'upload.php' );
    //Media
    remove_menu_page( 'edit-comments.php' );
    //Comments
    remove_menu_page( 'tools.php' );
    //Tools
}

add_action( 'admin_menu', 'remove_menus', 100 );
register_activation_hook( __FILE__, 'qem_flush_rules' );
function add_qem_widget()
{
    return register_widget( 'qem_widget' );
}

function add_qem_calendar_widget()
{
    return register_widget( 'qem_calendar_widget' );
}

function qem_block_init()
{
    if ( !function_exists( 'register_block_type' ) ) {
        return;
    }
    wp_register_script( 'qem_block', plugins_url( 'block.js', __FILE__ ), array(
        'wp-blocks',
        'wp-element',
        'wp-components',
        'wp-editor'
    ) );
    register_block_type( 'quick-event-manager/eventlist', array(
        'title'           => 'Event List',
        'editor_script'   => 'qem_block',
        'render_callback' => 'qem_event_shortcode',
        'attributes'      => array(
        'id' => array(
        'type' => 'string',
    ),
    ),
    ) );
    register_block_type( 'quick-event-manager/calendar', array(
        'title'           => 'Event Calendar',
        'editor_script'   => 'qem_block',
        'render_callback' => 'qem_show_calendar',
    ) );
}

add_action( 'init', 'qem_block_init' );
function deactivate_plugin_conditional()
{
    if ( is_plugin_active( 'quick-event-extensions/quick-event-extensions.php' ) ) {
        deactivate_plugins( 'quick-event-extensions/quick-event-extensions.php' );
    }
}

function qem_create_css_file( $update )
{
    
    if ( function_exists( 'file_put_contents' ) ) {
        $css_dir = plugin_dir_path( __FILE__ ) . '/quick-event-manager-custom.css';
        $filename = plugin_dir_path( __FILE__ );
        
        if ( is_writable( $filename ) && !file_exists( $css_dir ) || !empty($update) ) {
            $data = qem_generate_css();
            file_put_contents( $css_dir, $data, LOCK_EX );
        }
    
    } else {
        add_action( 'wp_head', 'qem_head_css' );
    }

}

function event_register()
{
    $GLOBALS['qem_ic'] = qem_get_incontext();
    // load_plugin_textdomain( 'quick-event-manager', false, basename( dirname( __FILE__ ) ) . '/languages' );
    qem_create_css_file( '' );
    
    if ( !post_type_exists( 'event' ) ) {
        $labels = array(
            'name'               => _x( 'Events', 'post type general name', 'quick-event-manager' ),
            'singular_name'      => _x( 'Event', 'post type singular name', 'quick-event-manager' ),
            'add_new'            => _x( 'Add New', 'event', 'quick-event-manager' ),
            'add_new_item'       => __( 'Add New Event', 'quick-event-manager' ),
            'edit_item'          => __( 'Edit Event', 'quick-event-manager' ),
            'new_item'           => __( 'New Event', 'quick-event-manager' ),
            'view_item'          => __( 'View Event', 'quick-event-manager' ),
            'search_items'       => __( 'Search event', 'quick-event-manager' ),
            'not_found'          => __( 'Nothing found', 'quick-event-manager' ),
            'not_found_in_trash' => __( 'Nothing found in Trash', 'quick-event-manager' ),
            'parent_item_colon'  => '',
        );
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'menu_icon'           => 'dashicons-calendar-alt',
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'show_ui'             => true,
            'query_var'           => true,
            'rewrite'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'capability_type'     => array( 'event', 'events' ),
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'has_archive'         => true,
            'menu_position'       => null,
            'taxonomies'          => array( 'category', 'post_tag' ),
            'supports'            => array(
            'title',
            'editor',
            'author',
            'thumbnail',
            'comments',
            'excerpt',
            'revisions'
        ),
            'show_ui'             => true,
        );
        register_post_type( 'event', $args );
    }

}

function qem_event_shortcode( $atts, $widget )
{
    $listlink = $usecategory = $remaining = $all = $i = $monthnumber = $archive = $yearnumber = $daynumber = $thisday = $content = $notthisyear = '';
    $atts = shortcode_atts( array(
        'fullevent'        => '',
        'id'               => '',
        'posts'            => '99',
        'links'            => 'checked',
        'daterange'        => 'current',
        'size'             => '',
        'headersize'       => 'headtwo',
        'settings'         => 'checked',
        'vanillawidget'    => 'checked',
        'images'           => '',
        'category'         => '',
        'categoryplaces'   => '',
        'order'            => '',
        'fields'           => '',
        'listlink'         => '',
        'listlinkanchor'   => '',
        'listlinkurl'      => '',
        'cb'               => '',
        'y'                => '',
        'vw'               => '',
        'categorykeyabove' => '',
        'categorykeybelow' => '',
        'usecategory'      => '',
        'event'            => '',
        'popup'            => '',
        'fullevent'        => 'summary',
        'fullpopup'        => '',
        'calendar'         => '',
        'thisisapopup'     => false,
        'listplaces'       => true,
        'fulllist'         => false,
        'grid'             => '',
        'eventfull'        => '',
        'widget'           => '',
        'grid'             => '',
    ), $atts, 'qem' );
    global  $post ;
    global  $_GET ;
    $category = $atts['category'];
    if ( isset( $_GET['category'] ) ) {
        $category = $_GET['category'];
    }
    $display = event_get_stored_display();
    $atts['popup'] = qem_get_element( $display, 'linkpopup', false );
    $atts['widget'] = $widget;
    if ( $display['fullpopup'] && qem_get_element( $display, 'linkpopup', false ) ) {
        $atts['fullpopup'] = 'checked';
    }
    if ( $atts['fullevent'] == 'on' || qem_get_element( $display, 'fullevent', false ) ) {
        $atts['fulllist'] = true;
    }
    $cal = qem_get_stored_calendar();
    $addons = qem_get_addons();
    $style = qem_get_stored_style();
    if ( !$atts['listlinkurl'] ) {
        $atts['listlinkurl'] = qem_get_element( $display, 'back_to_url', false );
    }
    if ( !$atts['listlinkanchor'] ) {
        $atts['listlinkanchor'] = $display['back_to_list_caption'];
    }
    if ( $atts['listlink'] ) {
        $atts['listlink'] = 'checked';
    }
    if ( $atts['cb'] ) {
        $display['cat_border'] = 'checked';
    }
    $output_buffer = '';
    
    if ( qem_get_element( $display, 'event_descending', false ) || $atts['order'] == 'asc' ) {
        $args = array(
            'post_type'      => 'event',
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'event_date',
            'posts_per_page' => -1,
        );
    } elseif ( qem_get_element( $addons, 'pagination', false ) ) {
        $args = array(
            'post_type'      => 'event',
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'event_date',
            'order'          => 'asc',
            'posts_per_page' => $atts['posts'],
            'paged'          => get_query_var( 'paged', 1 ),
            'meta_query'     => array( array(
            'key'     => 'event_date',
            'value'   => time(),
            'compare' => '>=',
        ) ),
        );
    } else {
        $args = array(
            'post_type'      => 'event',
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'event_date',
            'order'          => 'asc',
            'posts_per_page' => -1,
        );
    }
    
    $the_query = new WP_Query( $args );
    $event_found = false;
    $today = strtotime( date( 'Y-m-d' ) );
    $catlabel = str_replace( ',', ', ', $category );
    $currentyear = date( 'Y' );
    if ( $atts['usecategory'] ) {
        $atts['cb'] = 'checked';
    }
    if ( $display['categorydropdown'] ) {
        $content .= qem_category_dropdown( $display );
    }
    if ( !$widget && $display['cat_border'] && ($display['showkeyabove'] || $atts['categorykeyabove']) ) {
        $content .= qem_category_key( $cal, $style, '' );
    }
    if ( $widget && $atts['usecategory'] && $atts['categorykeyabove'] ) {
        $content .= qem_category_key( $cal, $style, '' );
    }
    if ( $category && $display['showcategory'] ) {
        $content .= '<h2>' . $display['showcategorycaption'] . ' ' . $catlabel . '</h2>';
    }
    if ( qem_get_element( $display, 'eventmasonry', false ) == 'masonry' && !$atts['widget'] ) {
        $atts['grid'] = 'masonry';
    }
    if ( $atts['grid'] == 'masonry' ) {
        $content .= '<div id="qem">';
    }
    if ( $atts['id'] == 'all' ) {
        $all = 'all';
    }
    if ( $atts['id'] == 'current' ) {
        $monthnumber = date( 'n' );
    }
    $nextweek = 0;
    if ( $atts['id'] == 'nextweek' ) {
        $nextweek = strtotime( "+7 day", $today );
    }
    if ( $atts['id'] == 'remaining' ) {
        $remaining = date( 'n' );
    }
    if ( $atts['id'] == 'archive' ) {
        $archive = 'archive';
    }
    if ( $atts['id'] == 'notthisyear' ) {
        $notthisyear = 'checked';
    }
    if ( is_numeric( $atts['id'] ) ) {
        $monthnumber = $atts['id'];
    }
    if ( is_numeric( $atts['id'] ) && strlen( $atts['id'] ) == 4 ) {
        $yearnumber = $atts['id'];
    }
    if ( $atts['id'] == 'calendar' ) {
        
        if ( isset( $_GET['qemmonth'] ) ) {
            $monthnumber = $_GET['qemmonth'];
        } else {
            $monthnumber = date( 'n' );
        }
    
    }
    $thisyear = date( 'Y' );
    $thismonth = date( "M" );
    $currentmonth = date( "M" );
    
    if ( $atts['id'] == 'today' ) {
        $daynumber = date( "d" );
        $todaymonth = $thismonth;
    }
    
    if ( strpos( $atts['id'], 'D' ) !== false ) {
        $daynumber = filter_var( $atts['id'], FILTER_SANITIZE_NUMBER_INT );
    }
    
    if ( strpos( $atts['id'], 'M' ) !== false ) {
        $dm = explode( "D", $atts['id'] );
        $monthnumber = filter_var( $dm[0], FILTER_SANITIZE_NUMBER_INT );
        $daynumber = filter_var( $dm[1], FILTER_SANITIZE_NUMBER_INT );
    }
    
    if ( $category ) {
        $category = explode( ',', $category );
    }
    if ( $atts['event'] ) {
        $eventid = explode( ',', $atts['event'] );
    }
    
    if ( $the_query->have_posts() ) {
        if ( $cal['connect'] ) {
            $content .= '<p><a href="' . $cal['calendar_url'] . '">' . $cal['calendar_text'] . '</a></p>';
        }
        while ( $the_query->have_posts() ) {
            $the_query->the_post();
            $unixtime = get_post_meta( $post->ID, 'event_date', true );
            if ( !$unixtime ) {
                $unixtime = time();
            }
            $enddate = get_post_meta( $post->ID, 'event_end_date', true );
            $hide_event = get_post_meta( $post->ID, 'hide_event', true );
            $day = date( "d", $unixtime );
            $monthnow = date( "n", $unixtime );
            $eventmonth = date( "M", $unixtime );
            $eventmonthnumber = date( "m", $unixtime );
            $month = ( $display['monthtype'] == 'short' ? date_i18n( "M", $unixtime ) : date_i18n( "F", $unixtime ) );
            $year = date( "Y", $unixtime );
            $monthheading = ( $display['monthheadingorder'] == 'ym' ? $year . ' ' . $month : $month . ' ' . $year );
            
            if ( $atts['y'] ) {
                $thisyear = $atts['y'];
                $yearnumber = 0;
            }
            
            
            if ( $atts['event'] ) {
                $atts['id'] = 'event';
                $event = $post->ID;
                $eventbyid = ( in_array( $event, $eventid ) ? 'checked' : '' );
            }
            
            /*
             * @TODO this selected part was put in to help understand overly complx if statement rules
             * in future may replace it
             */
            $selected = false;
            if ( $all ) {
                $selected = true;
            }
            // Event by ID
            if ( $atts['event'] && $eventbyid ) {
                $selected = true;
            }
            // Archive Events
            if ( $archive && $unixtime < $today && $enddate < $today ) {
                $selected = true;
            }
            // All if no ID
            if ( $atts['id'] == '' && ($unixtime >= $today || $enddate >= $today || $display['event_archive'] == 'checked') ) {
                $selected = true;
            }
            // Today only
            if ( $daynumber == $day && $todaymonth == $eventmonth && $thisyear == $year ) {
                $selected = true;
            }
            // Day and Month
            if ( $daynumber == $day && $monthnumber == $eventmonthnumber && $thisyear == $year ) {
                $selected = true;
            }
            // Next 7 days
            if ( $nextweek && $unixtime >= $today && $unixtime <= $nextweek ) {
                $selected = true;
            }
            // This month
            if ( !$daynumber && $monthnumber && $monthnow == $monthnumber && $thisyear == $year ) {
                $selected = true;
            }
            // Rest of the month
            if ( $remaining && $monthnow == $remaining && $thisyear == $year && ($unixtime >= $today || $enddate >= $today) ) {
                $selected = true;
            }
            // This year
            if ( $yearnumber && $yearnumber == $year ) {
                $selected = true;
            }
            // Not this year
            if ( $notthisyear && $currentyear > $year ) {
                $selected = true;
            }
            if ( $i < $atts['posts'] ) {
                
                if ( ($all || $atts['event'] && $eventbyid || $archive && $unixtime < $today && $enddate < $today || $atts['id'] == '' && ($unixtime >= $today || $enddate >= $today || $display['event_archive'] == 'checked') || $daynumber == $day && $todaymonth == $eventmonth && $thisyear == $year || $daynumber == $day && $monthnumber == $eventmonthnumber && $thisyear == $year || $nextweek && $unixtime >= $today && $unixtime <= $nextweek || !$daynumber && $monthnumber && $monthnow == $monthnumber && $thisyear == $year || $remaining && $monthnow == $remaining && $thisyear == $year && ($unixtime >= $today || $enddate >= $today) || $yearnumber && $yearnumber == $year || $notthisyear && $currentyear > $year) && (in_category( $category ) || !$category) ) {
                    
                    if ( !$atts['grid'] && $display['monthheading'] && ($currentmonth || $month != $thismonth || $year != $thisyear) ) {
                        $content .= '<h2>' . $monthheading . '</h2>';
                        $thismonth = $month;
                        $thisyear = $year;
                        $currentmonth = '';
                    }
                    
                    if ( !$hide_event ) {
                        $content .= qem_event_construct( $atts ) . "\r\n";
                    }
                    $event_found = true;
                    $i++;
                    if ( qem_get_element( $display, 'norepeat', false ) ) {
                        $thisday = $day;
                    }
                }
            
            }
        }
        if ( $atts['grid'] == 'masonry' ) {
            $content .= '</div>';
        }
        if ( !$widget && $display['cat_border'] && ($display['showkeybelow'] || $atts['categorykeyabove']) ) {
            $content .= qem_category_key( $cal, $style, '' );
        }
        if ( $widget && $atts['usecategory'] && $atts['categorykeyabove'] ) {
            $content .= qem_category_key( $cal, $style, '' );
        }
        if ( $atts['listlink'] ) {
            $content .= '<p><a href="' . $atts['listlinkurl'] . '">' . $atts['listlinkanchor'] . '</a></p>';
        }
        $output_buffer .= $content;
        if ( !$atts['widget'] ) {
            $output_buffer .= wp_qemnavi( array(
                'query' => $the_query,
            ) );
        }
    }
    
    if ( !$event_found ) {
        $output_buffer .= "<h2>" . $display['noevent'] . "</h2>";
    }
    wp_reset_postdata();
    wp_reset_query();
    return $output_buffer;
}

// Manages the categories
function qem_category_key( $cal, $style, $calendar )
{
    $cat = array(
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j'
    );
    $arr = get_categories();
    $display = event_get_stored_display();
    $pageurl = qem_current_page_url();
    $parts = explode( "&", $pageurl );
    $pageurl = $parts['0'];
    $link = ( strpos( $pageurl, '?' ) ? '&' : '?' );
    $uncatkey = $caturl = $allcats = '';
    $catkey = '';
    if ( $style['linktocategories'] ) {
        $catkey = '<style>.qem-category:hover {background: #CCC !important;border-color: #343848 !important;}.qem-category a:hover {color:#383848 !important;}</style>' . "\r\n";
    }
    if ( qem_get_element( $cal, 'keycaption', false ) ) {
        $catkey .= ( $calendar ? '<p><span class="qem-caption">' . $cal['keycaption'] . '</span>' : '<p><span class="qem-caption">' . $display['keycaption'] . '</span>' );
    }
    
    if ( $calendar && $cal['calallevents'] ) {
        $allcats = $cal['calalleventscaption'];
        $caturl = $cal['calendar_url'];
    }
    
    
    if ( !$calendar && $display['catallevents'] ) {
        $allcats = $display['catalleventscaption'];
        $caturl = $display['back_to_url'];
    }
    
    $eventbackground = '';
    if ( $style['event_background'] == 'bgwhite' ) {
        $eventbackground = 'background:white;';
    }
    if ( $style['event_background'] == 'bgcolor' ) {
        $eventbackground = 'background:' . $style['event_backgroundhex'] . ';';
    }
    
    if ( $caturl && $allcats ) {
        $bg = ( $style['date_background'] == 'color' ? $style['date_backgroundhex'] : $style['date_background'] );
        $catkey .= '<span class="qem-category" style="border:' . $style['date_border_width'] . 'px solid ' . $style['date_border_colour'] . ';background:#CCC"><a style="color:' . $style['date_colour'] . '" href="' . $caturl . '">' . $allcats . '</a></span>';
    }
    
    $class = 'class="qem-category qem-key"';
    foreach ( $cat as $i ) {
        foreach ( $arr as $option ) {
            
            if ( $style['cat' . $i] == $option->slug ) {
                $thecat = ( empty($option->name) ? $option->slug : $option->name );
                break;
            } else {
                $thecat = '';
            }
        
        }
        if ( !empty($style['cat' . $i]) ) {
            
            if ( $calendar ) {
                
                if ( qem_get_element( $cal, 'linktocategories', false ) ) {
                    
                    if ( qem_get_element( $cal, 'catstyle' ) == "colorAsBorder" ) {
                        $catkey .= '<span ' . $class . ' style="' . $eventbackground . ';border:' . $style['date_border_width'] . 'px solid ' . $style['cat' . $i . 'back'] . ';"><a href="' . $pageurl . $link . 'category=' . $thecat . '">' . $thecat . '</a></span>';
                    } else {
                        $catkey .= '<span ' . $class . ' style="border:' . $style['date_border_width'] . 'px solid ' . $style['cat' . $i . 'text'] . ';background:' . $style['cat' . $i . 'back'] . '"><a style="color:' . $style['cat' . $i . 'text'] . '" href="' . $pageurl . $link . 'category=' . $thecat . '">' . $thecat . '</a></span>';
                    }
                
                } else {
                    
                    if ( qem_get_element( $cal, 'catstyle' ) == "colorAsBorder" ) {
                        $catkey .= '<span ' . $class . ' style="' . $eventbackground . ';border:' . $style['date_border_width'] . 'px solid ' . $style['cat' . $i . 'back'] . ';"><a href="' . $pageurl . $link . 'category=' . $thecat . '">' . $thecat . '</a></span>';
                    } else {
                        $catkey .= '<span ' . $class . ' style="border:' . $style['date_border_width'] . 'px solid ' . qem_get_element( $cal, 'cat' . $i . 'text', '#343838' ) . ';background:' . $style['cat' . $i . 'back'] . ';color:' . $style['cat' . $i . 'text'] . ';">' . $thecat . '</span>';
                    }
                
                }
                
                if ( $style['showuncategorised'] ) {
                    $uncatkey = '<span ' . $class . ' style="border:' . $style['date_border_width'] . 'px solid ' . $style['date_border_colour'] . ';">Uncategorised</span>';
                }
            } else {
                
                if ( $display['linktocategories'] ) {
                    $catkey .= '<span ' . $class . ' style="' . $eventbackground . ';border:' . $style['date_border_width'] . 'px solid ' . $style['cat' . $i . 'back'] . ';"><a href="' . $pageurl . $link . 'category=' . $thecat . '">' . $thecat . '</a></span>';
                } else {
                    $catkey .= '<span ' . $class . ' style="' . $eventbackground . ';border:' . $style['date_border_width'] . 'px solid ' . $style['cat' . $i . 'back'] . ';">' . $thecat . '</span>';
                }
                
                if ( $display['showuncategorised'] ) {
                    $uncatkey = '<span ' . $class . ' style="border:' . $style['date_border_width'] . 'px solid ' . $style['date_border_colour'] . ';">Uncategorise
d</span>';
                }
            }
        
        }
    }
    $catkey .= $uncatkey . '</p><div style="clear:left;"></div>' . "\r\n";
    return $catkey;
}

function qem_category_dropdown( $display )
{
    $args = array(
        'exclude' => 1,
    );
    $arr = get_categories( $args );
    $width = ( $display['categorydropdownwidth'] ? ' style="width:100%"' : '' );
    $content = '<form>
    <div class="qem-register"' . $width . '>
    <select onchange="this.form.submit()" name="category">
    <option>' . $display['categorydropdownlabel'] . '</option>';
    foreach ( $arr as $option ) {
        $thecat = $option->name;
        $selected = '';
        if ( isset( $_REQUEST['category'] ) && $_REQUEST['category'] == $thecat ) {
            $selected = 'selected';
        }
        $content .= '<option value="' . $thecat . '" ' . $selected . '>' . $thecat . '</option>';
    }
    $content .= '</select>
    </div></form>';
    return $content;
}

// Builds the event page
/**
 * @param $atts
 * e.g.
 * array (
 * 'links' => 'off',
 * 'size' => '',
 * 'headersize' => '',
 * 'settings' => 'checked',
 * 'fullevent' => 'fullevent',
 * 'images' => '',
 * 'fields' => '',
 * 'widget' => '',
 * 'cb' => '',
 * 'vanillawidget' => '',
 * 'linkpopup' => '',
 * 'thisday' => '',
 * 'popup' => '',
 * 'vw' => '',
 * 'categoryplaces' => '',
 * 'fulllist' => '',
 * 'thisisapopup' => '',
 * 'listplaces' => true,
 * 'calendar' => false,
 * 'fullpopup' => false,
 * 'grid' => '',
 * )
 *
 * @return string
 */
function qem_event_construct( $atts )
{
    global  $post ;
    $event = event_get_stored_options();
    $display = event_get_stored_display();
    $vertical = ( isset( $display['vertical'] ) ? $display['vertical'] : '' );
    $style = qem_get_stored_style();
    $cal = qem_get_stored_calendar();
    $custom = get_post_custom();
    $link = get_post_meta( $post->ID, 'event_link', true );
    $endtime = get_post_meta( $post->ID, 'event_end_time', true );
    $endmonth = $amalgamated = $target = '';
    $unixtime = get_post_meta( $post->ID, 'event_date', true );
    $day = date_i18n( "d", $unixtime );
    $enddate = get_post_meta( $post->ID, 'event_end_date', true );
    $image = get_post_meta( $post->ID, 'event_image', true );
    $readmore = get_post_meta( $post->ID, 'event_readmore', true );
    if ( strtolower( $atts['links'] ) == 'off' ) {
        $display['titlelink'] = $display['readmorelink'] = true;
    }
    $display['read_more'] = ( $readmore ? $readmore : $display['read_more'] );
    if ( $image ) {
        $image = 'src="' . $image . '">';
    }
    $usefeatured = false;
    
    if ( has_post_thumbnail( $post->ID ) && $display['usefeatured'] ) {
        $image = get_the_post_thumbnail( null, 'large' );
        $usefeatured = true;
    }
    
    $register = qem_get_stored_register();
    $payment = qem_get_stored_payment();
    $atts['usereg'] = get_post_meta( $post->ID, 'event_register', true );
    $rightcontent = $notfullpop = $hideattendance = '';
    $today = strtotime( date( 'Y-m-d' ) );
    $grid = $cat = $clear = $eventfull = $cutoff = $fullcontent = $titlecat = $datecat = $linkopen = $linkclose = $regform = $nomap = '';
    
    if ( !is_singular( 'event' ) && !qem_get_element( $atts, 'widget', false ) && (qem_get_element( $display, 'eventgrid', false ) || $atts['grid']) ) {
        $grid = '-columns';
        if ( $atts['grid'] == 'masonry' ) {
            $grid = '-masonry';
        }
        $display['map_in_list'] = '';
        $style['vanilla'] = 'checked';
        $nomap = true;
    }
    
    if ( is_singular( 'event' ) ) {
        $display['show_end_date'] = true;
    }
    if ( isset( $display['fullevent'] ) && $display['fullevent'] ) {
        $atts['popup'] = false;
    }
    
    if ( isset( $display['loginlinks'] ) && $display['loginlinks'] && !is_user_logged_in() || is_singular( 'event' ) || qem_get_element( $atts, 'fulllist', false ) ) {
        $display['titlelink'] = true;
        $display['readmorelink'] = true;
    }
    
    
    if ( qem_get_element( $atts, 'widget', false ) && !$atts['links'] ) {
        $display['titlelink'] = true;
        $display['readmorelink'] = true;
    }
    
    // Build Category Information
    $category = get_the_category();
    $cat = ( $category && (!qem_get_element( $atts, 'widget', false ) && qem_get_element( $display, 'cat_border', false ) || $atts['cb']) ? ' ' . $category[0]->slug : ' ' );
    
    if ( isset( $display['showcategoryintitle'] ) && $display['showcategoryintitle'] ) {
        if ( $display['categorylocation'] == 'title' ) {
            $titlecat = ' - ' . $category[0]->name;
        }
        if ( $display['categorylocation'] == 'date' ) {
            $datecat = ' - ' . $category[0]->name;
        }
    }
    
    if ( qem_get_element( $atts, 'categoryplaces', false ) && $category[0]->slug == qem_get_element( $atts, 'categoryplaces', false ) ) {
        $event['summary']['field11'] = 'checked';
    }
    // Hide form for old events
    if ( $today > $unixtime && isset( $register['notarchive'] ) && $register['notarchive'] ) {
        $atts['usereg'] = '';
    }
    // No images
    if ( qem_get_element( $atts, 'images', 'on' ) == 'off' ) {
        $image = '';
    }
    // Clear Icon styling from widget
    if ( qem_get_element( $atts, 'vw', false ) || qem_get_element( $atts, 'vanillawidget', false ) ) {
        $style['vanillawidget'] = 'checked';
    }
    // Field override
    
    if ( qem_get_element( $atts, 'fields', false ) ) {
        foreach ( explode( ',', $event['sort'] ) as $name ) {
            $event['summary'][$name] = '';
        }
        $derek = explode( ',', $atts['fields'] );
        $event['sort'] = '';
        foreach ( $derek as $item ) {
            $event['summary']['field' . $item] = 'checked';
            $event['sort'] = $event['sort'] . 'field' . $item . ',';
        }
    }
    
    // Link externally
    if ( isset( $display['external_link'] ) && $display['external_link'] && $link ) {
        add_filter(
            'post_type_link',
            'qem_external_permalink',
            10,
            2
        );
    }
    // Build pop up
    if ( $atts['popup'] && !$display['fullevent'] ) {
        $popupcontent = get_event_popup( $atts );
    }
    // Combine end date
    
    if ( qem_get_element( $display, 'show_end_date', false ) && $enddate || $enddate && qem_get_element( $atts, 'eventfull' ) ) {
        $join = 'checked';
    } else {
        $join = '';
    }
    
    // Set size of icon
    
    if ( qem_get_element( $display, 'false', false ) ) {
        $width = '-' . $atts['size'];
    } else {
        $atts['size'] = $style['calender_size'];
        $width = '-' . $style['calender_size'];
    }
    
    // Set header size
    $h = ( qem_get_element( $atts, 'headersize', false ) == 'headthree' ? 'h3' : 'h2' );
    // Build title link
    if ( qem_get_element( $display, 'catalinkslug', false ) ) {
        $category = explode( ',', $display['catalinkslug'] );
    }
    
    if ( !qem_get_element( $display, 'titlelink', false ) ) {
        $linkclose = '</a>';
        
        if ( $atts['popup'] ) {
            $linkopen = '<a href="javascript:xlightbox(\'' . $popupcontent . '\'); ">';
        } else {
            
            if ( $category && in_category( $category ) ) {
                $linkopen = '<a href="' . qem_get_element( $display, 'catalinkurl', false ) . '"' . $target . '>';
            } else {
                $linkopen = '<a href="' . get_permalink() . '"' . $target . '>';
            }
        
        }
    
    }
    
    // Test for date amalgamation
    
    if ( isset( $display['amalgamated'] ) && $display['amalgamated'] ) {
        $month = date_i18n( "M", $unixtime );
        $year = date_i18n( "Y", $unixtime );
        
        if ( $enddate ) {
            $endmonth = date_i18n( "M", $enddate );
            $endday = date_i18n( "d", $enddate );
            $endyear = date_i18n( "Y", $enddate );
        }
        
        if ( $month == $endmonth && $year == $endyear && $endday ) {
            $amalgamated = 'checked';
        }
    }
    
    // Start Content creation
    $content = '<div class="qem' . $grid . $cat . '">';
    // Build data icon
    
    if ( (!isset( $style['vanilla'] ) || !$style['vanilla']) && !$style['vanillawidget'] || (!isset( $style['vanilla'] ) || !$style['vanilla']) && $style['vanillawidget'] && !$atts['widget'] ) {
        $content .= '<div class="qem-icon">' . get_event_calendar_icon(
            $atts,
            'event_date',
            $join,
            $style
        );
        if ( $join && !$amalgamated && !$vertical ) {
            $content .= '</div><div class="qem-icon">';
        }
        if ( (qem_get_element( $display, 'show_end_date', false ) || qem_get_element( $atts, 'eventfull', false ) || is_singular( 'event' )) && !$amalgamated ) {
            $content .= get_event_calendar_icon(
                $atts,
                'event_end_date',
                '',
                $style
            );
        }
        $content .= '</div>';
        $content .= '<div class="qem' . $width . '">';
        $clear = '<div style="clear:both"></div></div>';
    }
    
    // Add image
    
    if ( $image ) {
        $imageclass = ( $atts['grid'] ? 'qem-grid-image' : 'qem-list-image' );
        if ( $display['event_image'] && !is_singular( 'event' ) && !$atts['widget'] ) {
            $rightcontent = $linkopen . '<img class="' . $imageclass . '" ' . $image . $linkclose . '<br>';
        }
        if ( $atts['fullevent'] == 'fullevent' || $atts['thisisapopup'] || $atts['fulllist'] ) {
            $rightcontent = '<img class="qem-image" ' . $image . '<br>';
        }
        if ( $usefeatured ) {
            $rightcontent = $image;
        }
    }
    
    // Add map
    $map_in_list = qem_get_element( $display, 'map_in_list', false );
    
    if ( !$nomap && function_exists( 'file_get_contents' ) && ($atts['fullevent'] && $atts['thisisapopup'] || $atts['fulllist'] || $map_in_list || $display['map_and_image'] && $map_in_list || is_singular( 'event' ) && !$atts['widget']) ) {
        $mapwidth = '300';
        if ( is_singular( 'event' ) ) {
            $mapwidth = $display['event_image_width'];
        }
        if ( $map_in_list && !is_singular( 'event' ) ) {
            $mapwidth = $display['image_width'];
        }
        $j = preg_split( '#(?<=\\d)(?=[a-z%])#i', $mapwidth );
        if ( !$j[0] ) {
            $j[0] = '300';
        }
        $mapwidth = $j[0];
        $rightcontent .= get_event_map( $mapwidth );
    }
    
    // Add form (if on the right)
    
    if ( !qem_get_element( $atts, 'widget', false ) && (qem_get_element( $atts, 'fulllist', false ) || is_singular( 'event' )) && !qem_get_element( $atts, 'thisisapopup', false ) && ($event['active_buttons']['field12'] && qem_get_element( $atts, 'usereg', false ) && $register['ontheright']) ) {
        $rightcontent .= '<div class="qem-rightregister">' . qem_loop() . '</div>';
        $thereisaform = true;
    }
    
    // Build right content
    $gridclass = ( $atts['grid'] ? 'qemgridright' : 'qemlistright' );
    if ( $rightcontent ) {
        
        if ( is_singular( 'event' ) || $atts['thisisapopup'] || $atts['fulllist'] ) {
            $content .= '<div class="qemright">' . "\r\n" . $rightcontent . "\r\n" . '</div>' . "\r\n";
        } else {
            $content .= '<div class="' . $gridclass . '">' . "\r\n" . $rightcontent . "\r\n" . '</div>' . "\r\n";
        }
    
    }
    // Build event title link
    if ( (!is_singular( 'event' ) || qem_get_element( $atts, 'widget', false )) && !qem_get_element( $style, 'vanillaontop', false ) ) {
        $content .= '<' . $h . ' class="qem_title">' . $linkopen . $post->post_title . $titlecat . $linkclose . '</' . $h . '>';
    }
    // Build vanilla date
    
    if ( isset( $style['vanilla'] ) && $style['vanilla'] || $style['vanillawidget'] && qem_get_element( $atts, 'widget', false ) ) {
        $content .= '<h3 class="qem_date">' . get_event_calendar_icon(
            $atts,
            'event_date',
            $join,
            $style
        );
        if ( ($display['show_end_date'] || qem_get_element( $atts, 'eventfull', false )) && !$amalgamated ) {
            $content .= get_event_calendar_icon(
                $atts,
                'event_end_date',
                '',
                $style
            );
        }
        $content .= $datecat . '</h3>';
    }
    
    // Put title below vanilla date
    if ( (!is_singular( 'event' ) || qem_get_element( $atts, 'widget', false )) && qem_get_element( $style, 'vanillaontop', false ) ) {
        $content .= '<' . $h . ' class="qem_title">' . $linkopen . $post->post_title . $titlecat . $linkclose . '</' . $h . '>';
    }
    // Build event content
    if ( qem_get_element( $atts, 'calendar', false ) && !qem_get_element( $atts, 'fullpopup', false ) ) {
        $notfullpop = 'checked';
    }
    if ( qem_get_element( $atts, 'listplaces', false ) && !qem_get_element( $atts, 'fullpopup', false ) ) {
        $notfullpop = 'checked';
    }
    $a = is_single();
    $b = is_singular( 'event' );
    
    if ( is_singular( 'event' ) && !qem_get_element( $atts, 'widget', false ) || qem_get_element( $atts, 'thisisapopup', false ) && !$notfullpop || qem_get_element( $atts, 'fulllist', false ) ) {
        foreach ( explode( ',', $event['sort'] ) as $name ) {
            if ( isset( $event['active_buttons'][$name] ) && $event['active_buttons'][$name] ) {
                $content .= qem_build_event(
                    $name,
                    $event,
                    $display,
                    $custom,
                    $atts,
                    $register,
                    $payment
                );
            }
        }
    } else {
        foreach ( explode( ',', $event['sort'] ) as $name ) {
            if ( qem_get_element( $event['summary'], $name, false ) ) {
                $content .= qem_build_event(
                    $name,
                    $event,
                    $display,
                    $custom,
                    $atts,
                    $register,
                    $payment
                );
            }
        }
    }
    
    // Add ICS button to list and event
    if ( $display['uselistics'] && !is_singular( 'event' ) || $display['useics'] && !qem_get_element( $atts, 'widget', false ) && (is_singular( 'event' ) || $atts['fulllist']) ) {
        $content .= '<p>' . qem_ics_button( $post->ID, $display['useicsbutton'] ) . '</p>';
    }
    // Add Read More
    
    if ( !is_singular( 'event' ) && !qem_get_element( $atts, 'widget', false ) || qem_get_element( $atts, 'widget', false ) && qem_get_element( $atts, 'links', false ) ) {
        $event_number_max = get_post_meta( $post->ID, 'event_number', true );
        $num = qem_number_places_available( $post->ID );
        $cutoffdate = get_post_meta( $post->ID, 'event_cutoff_date', true );
        if ( qem_get_element( $atts, 'usereg', false ) ) {
            $regform = true;
        }
        $gotform = true;
        if ( !$regform && qem_get_element( $atts, 'thisisapopup', false ) && qem_get_element( $atts, 'fullpopup', false ) ) {
            $gotform = false;
        }
        if ( $cutoffdate && $cutoffdate < time() ) {
            $cutoff = 'checked';
        }
        if ( '' !== $event_number_max && (int) $event_number_max > 0 && (int) $num == 0 && !$register['waitinglist'] || $cutoff ) {
            $eventfull = true;
        }
        if ( qem_get_element( $atts, 'thisisapopup', false ) && qem_get_element( $atts, 'fullpopup', false ) && $regform && !$eventfull ) {
            $display['read_more'] = $register['title'];
        }
        
        if ( qem_get_element( $display, 'fullevent', false ) || qem_get_element( $atts, 'widget', false ) || (qem_get_element( $atts, 'fullevent', false ) == 'summary' || !$eventfull) && !qem_get_element( $display, 'readmorelink', false ) && $gotform ) {
            
            if ( qem_get_element( $atts, 'popup', false ) ) {
                $readmoreopen = '<a href="javascript:xlightbox(\'' . $popupcontent . '\'); ">';
            } else {
                $readmoreopen = '<a href="' . get_permalink() . '"' . $target . '>';
            }
            
            $content .= '<p class="readmore">' . $readmoreopen . qem_get_element( $display, 'read_more' ) . '</a></p>';
        }
    
    }
    
    // Add back to list link
    if ( !qem_get_element( $atts, 'widget', false ) ) {
        if ( isset( $display['back_to_list'] ) && $display['back_to_list'] && is_singular( 'event' ) ) {
            
            if ( $display['back_to_url'] ) {
                $content .= '<p class="qemback"><a href="' . $display['back_to_url'] . '">' . $display['back_to_list_caption'] . '</a></p>';
            } else {
                $content .= '<p class="qemback"><a href="javascript:history.go(-1)">' . $display['back_to_list_caption'] . '</a></p>';
            }
        
        }
    }
    // $content .= '<br>Full popup: '.$atts['fullpopup'].'<br>Widget: '.$atts['widget'].'<br>Is Popup: '.$atts['thisisapopup'].'<br>Not full pop: '.$notfullpop.'<br>Popup: '.$atts['popup'].'<br>Full Event:'.$atts['fullevent'].'<br>Event Full: '.$eventfull.'<br>Full List:'.$atts['fulllist'].'<br>Links: '.$atts['links'].'<br>Link List: '.$atts['listlink'].'<br>Reg form:'.$regform.'<br>Got Form: '.$gotform.'<br>Read More: '.$display['read_more'].'<br>';
    $content .= $clear . "</div>";
    return $content;
}

// Builds the Calendar Icon
function get_event_calendar_icon(
    $atts,
    $dateicon,
    $join,
    $style
)
{
    global  $post ;
    $width = $atts['size'];
    $vw = qem_get_element( $atts, 'vw', false );
    $widget = qem_get_element( $atts, 'widget', false );
    $display = event_get_stored_display();
    $vertical = ( isset( $display['vertical'] ) ? $display['vertical'] : '' );
    $mrcombi = '2' * $style['date_border_width'] . 'px';
    $mr = '5' + $style['date_border_width'] . 'px';
    $mb = ( $vertical ? ' 8px' : ' 0' );
    $sep = $bor = $boldon = $italicon = $month = $italicoff = $boldoff = $endname = $amalgum = $bar = '';
    $tl = 'border-top-left-radius:0;';
    $tr = 'border-top-right-radius:0;';
    $bl = 'border-bottom-left-radius:0';
    $br = 'border-bottom-right-radius:0';
    if ( $dateicon == 'event_date' && (!isset( $display['combined'] ) || !$display['combined']) && !$vertical ) {
        $mb = ' ' . $mr;
    }
    
    if ( $dateicon == 'event_end_date' && isset( $display['combined'] ) && $display['combined'] && !$vertical ) {
        $bar = $bor = '';
        $bar = 'style="border-left-width:1px;' . $tl . $bl . '"';
    }
    
    
    if ( $style['date_bold'] ) {
        $boldon = '<b>';
        $boldoff = '</b>';
    }
    
    
    if ( $style['date_italic'] ) {
        $italicon = '<em>';
        $italicoff = '</em>';
    }
    
    if ( $vw ) {
        $style['vanillawidget'] = 'checked';
    }
    $unixtime = get_post_meta( $post->ID, $dateicon, true );
    $endtime = get_post_meta( $post->ID, 'event_end_date', true );
    
    if ( $unixtime ) {
        $month = date_i18n( "M", $unixtime );
        if ( isset( $style['vanilla'] ) && $style['vanilla'] && $style['vanillamonth'] ) {
            $month = date_i18n( "F", $unixtime );
        }
        $dayname = date_i18n( "D", $unixtime );
        if ( isset( $style['vanilla'] ) && $style['vanilla'] && $style['vanilladay'] ) {
            $dayname = date_i18n( "l", $unixtime );
        }
        $day = date_i18n( "d", $unixtime );
        $year = date_i18n( "Y", $unixtime );
        
        if ( $endtime && qem_get_element( $display, 'amalgamated' ) ) {
            $endmonth = date_i18n( "M", $endtime );
            if ( $style['vanilla'] && $style['vanillamonth'] ) {
                $endmonth = date_i18n( "F", $endtime );
            }
            $endday = date_i18n( "d", $endtime );
            $endyear = date_i18n( "Y", $endtime );
            
            if ( $month == $endmonth && $year == $endyear && $endday && $dateicon != 'event_end_date' ) {
                
                if ( $style['use_dayname'] ) {
                    $endname = date_i18n( "D", $endtime ) . ' ';
                    if ( $style['vanilla'] && $style['vanilladay'] ) {
                        $endname = date_i18n( "l", $endtime ) . ' ';
                    }
                }
                
                $day = $day . ' - ' . $endname . $endday;
                $amalgum = 'on';
            }
        
        }
        
        
        if ( $dateicon == 'event_date' && isset( $display['combined'] ) && $display['combined'] && $join && !$amalgum ) {
            $bar = $bor = '';
            $bar = 'style="border-right:none;' . $tr . $br . '"';
            $mr = ' 0';
        }
        
        
        if ( $style['iconorder'] == 'month' ) {
            $top = $month;
            $middle = $day;
            $bottom = $year;
        } elseif ( $style['iconorder'] == 'year' ) {
            $top = $year;
            $middle = $day;
            $bottom = $month;
        } elseif ( $style['iconorder'] == 'dm' ) {
            $top = $day;
            $middle = $month;
        } elseif ( $style['iconorder'] == 'md' ) {
            $top = $month;
            $middle = $day;
        } else {
            $top = $day;
            $middle = $month;
            $bottom = $year;
        }
        
        $label = '';
        if ( $dateicon == 'event_date' && $endtime && $style['uselabels'] ) {
            $label = $style['startlabel'] . '<br>';
        }
        if ( $dateicon == 'event_end_date' && $endtime && $style['uselabels'] ) {
            $label = $style['finishlabel'] . '<br>';
        }
        if ( isset( $display['amalgamated'] ) && $display['amalgamated'] && $amalgum ) {
            $label = '';
        }
        
        if ( isset( $style['vanilla'] ) && $style['vanilla'] || $style['vanillawidget'] && $widget ) {
            if ( $dateicon == 'event_end_date' ) {
                $sep = '&nbsp; - &nbsp;';
            }
            $content = $sep;
            if ( $style['use_dayname'] ) {
                $content .= $dayname . '&nbsp;';
            }
            $content .= $top . '&nbsp;' . $middle . '&nbsp;' . $bottom;
        } else {
            $content = '<div class="qem-calendar-' . $width . '" style="margin:0 ' . $mr . $mb . ' 0;"><span class="day" ' . $bar . '>' . $label;
            
            if ( isset( $style['use_dayname'] ) && $style['use_dayname'] ) {
                $content .= $dayname;
                $content .= ( $style['use_dayname_inline'] ? ' ' : '<br>' );
            }
            
            $content .= $top . '</span><span class="nonday" ' . $bar . '><span class="month">' . $boldon . $italicon . $middle . $italicoff . $boldoff . '</span><span class="year">' . $bottom . '</span></span></div>';
        }
        
        return $content;
    }

}

function qem_checkdate( $myDateString )
{
    return (bool) strtotime( $myDateString );
}

// Builds the event content
function qem_build_event(
    $name,
    $event,
    $display,
    $custom,
    $atts,
    $register,
    $payment
)
{
    global  $post ;
    $style = $output = $caption = $target = '';
    
    if ( qem_get_element( $atts, 'settings', false ) ) {
        if ( qem_get_element( $event['bold'], $name, false ) == 'checked' ) {
            $style .= 'font-weight: bold; ';
        }
        if ( qem_get_element( $event['italic'], $name, false ) == 'checked' ) {
            $style .= 'font-style: italic; ';
        }
        if ( qem_get_element( $event['colour'], $name, false ) ) {
            $style .= 'color: ' . $event['colour'][$name] . '; ';
        }
        if ( qem_get_element( $event['size'], $name, false ) ) {
            $style .= 'font-size: ' . $event['size'][$name] . '%; ';
        }
        if ( $style ) {
            $style = 'style="' . $style . '" ';
        }
    }
    
    switch ( $name ) {
        case 'field1':
            if ( !empty($event['description_label']) ) {
                $caption = $event['description_label'] . ' ';
            }
            if ( !empty($custom['event_desc'][0]) ) {
                $output .= apply_filters(
                    'qem_short_desc',
                    $custom['event_desc'][0],
                    $caption,
                    $style
                );
            }
            break;
        case 'field2':
            
            if ( !empty($custom['event_start'][0]) ) {
                $output .= '<p class="start" ' . $style . '>' . $event['start_label'] . ' ' . $custom['event_start'][0];
                if ( !empty($custom['event_finish'][0]) ) {
                    $output .= ' ' . $event['finish_label'] . ' ' . $custom['event_finish'][0];
                }
                if ( $display['usetimezone'] && $custom['event_timezone'][0] ) {
                    $output .= ' ' . $display['timezonebefore'] . ' ' . $custom['event_timezone'][0] . ' ' . $display['timezoneafter'];
                }
                $output .= '</p>';
            }
            
            break;
        case 'field3':
            if ( !empty($event['location_label']) ) {
                $caption = $event['location_label'] . ' ';
            }
            if ( !empty($custom['event_location'][0]) ) {
                $output .= '<p class="location" ' . $style . '>' . $caption . $custom['event_location'][0] . '</p>';
            }
            break;
        case 'field4':
            if ( !empty($event['address_label']) ) {
                $caption = $event['address_label'] . ' ';
            }
            if ( !empty($custom['event_address'][0]) ) {
                $output .= '<p class="address" ' . $style . '>' . $caption . $custom['event_address'][0] . '</p>';
            }
            break;
        case 'field5':
            if ( !empty($event['url_label']) ) {
                $caption = $event['url_label'] . ' ';
            }
            if ( isset( $display['external_link_target'] ) && $display['external_link_target'] ) {
                $target = 'target="_blank"';
            }
            
            if ( !preg_match( "~^(?:f|ht)tps?://~i", $custom['event_link'][0] ) ) {
                $url = 'http://' . $custom['event_link'][0];
            } else {
                $url = $custom['event_link'][0];
            }
            
            if ( empty($custom['event_anchor'][0]) ) {
                $custom['event_anchor'][0] = $custom['event_link'][0];
            }
            if ( !empty($custom['event_link'][0]) ) {
                $output .= '<p class="website" ' . $style . '>' . $caption . '<a itemprop="url" ' . $style . ' ' . $target . ' href="' . $url . '">' . $custom['event_anchor'][0] . '</a></p>';
            }
            break;
        case 'field6':
            if ( !empty($event['cost_label']) ) {
                $caption = $event['cost_label'] . ' ';
            }
            
            if ( !empty($custom['event_cost'][0]) ) {
                $output .= '<p ' . $style . '>' . $caption . $custom['event_cost'][0];
                if ( !empty($event['deposit_before_label']) ) {
                    $bcaption = $event['deposit_before_label'] . ' ';
                }
                if ( !empty($event['deposit_after_label']) ) {
                    $acaption = ' ' . $event['deposit_after_label'];
                }
                if ( !empty($custom['event_deposit'][0]) ) {
                    $output .= ' (' . $bcaption . $custom['event_deposit'][0] . $acaption . ')';
                }
            }
            
            if ( $output ) {
                $output .= '</p>';
            }
            break;
        case 'field7':
            if ( !empty($event['organiser_label']) ) {
                $caption = $event['organiser_label'] . ' ';
            }
            
            if ( !empty($custom['event_organiser'][0]) ) {
                $output .= '<p class="organisation" ' . $style . '>' . $caption . $custom['event_organiser'][0];
                if ( !empty($custom['event_telephone'][0]) && $event['show_telephone'] ) {
                    $output .= ' / ' . $custom['event_telephone'][0];
                }
                $output .= '</p>';
            }
            
            break;
        case 'field8':
            $output .= apply_filters( 'qem_description', get_the_content() );
            break;
        case 'field9':
            $str = qem_get_the_numbers( $post->ID, $payment );
            $event_number_max = get_post_meta( $post->ID, 'event_number', true );
            if ( '' !== $event_number_max && (int) $str > (int) $event_number_max ) {
                $str = $event_number_max;
            }
            
            if ( $str ) {
                
                if ( $str == 1 ) {
                    $str = $event['oneattendingbefore'];
                } else {
                    $str = $event['numberattendingbefore'] . ' ' . $str . ' ' . $event['numberattendingafter'];
                }
                
                $output .= '<p id="whoscoming" class="totalcoming" ' . $style . '>' . $str . '</p>';
            }
            
            break;
        case 'field10':
            $hide = '';
            global  $qem_fs ;
            $event_number_max = get_post_meta( $post->ID, 'event_number', true );
            $num = 0;
            $str = $grav = $content = '';
            $whoscoming = get_option( 'qem_messages_' . $post->ID );
            
            if ( $whoscoming ) {
                
                if ( qem_get_element( $register, 'listnames', false ) ) {
                    foreach ( $whoscoming as $item ) {
                        $num = $num + (int) $item['yourplaces'];
                        $ipn = qem_check_ipnblock( $payment, $item );
                        
                        if ( ('' === $event_number_max || (int) $num <= (int) $event_number_max) && !$item['notattend'] && !$ipn && ($register['moderate'] && $item['approved'] || !$register['moderate']) ) {
                            
                            if ( isset( $item['yourblank1'] ) ) {
                                $url = preg_replace( '/(?:https?:\\/\\/)?(?:www\\.)?(.*)\\/?$/i', '$1', $item['yourblank1'] );
                                if ( $url ) {
                                    $url = ' <a href="' . $item['yourblank1'] . '">' . $url . '</a>';
                                }
                            }
                            
                            $msg = qem_get_element( $register, 'listblurb' );
                            $msg = str_replace( '[name]', qem_get_element( $item, 'yourname' ), $msg );
                            $msg = str_replace( '[email]', qem_get_element( $item, 'youremail' ), $msg );
                            $msg = str_replace( '[mailto]', '<a href="mailto:' . qem_get_element( $item, 'youremail' ) . '">' . qem_get_element( $item, 'youremail' ) . '</a>', $msg );
                            $msg = str_replace( '[places]', qem_get_element( $item, 'yourplaces' ), $msg );
                            $msg = str_replace( '[telephone]', qem_get_element( $item, 'telephone' ), $msg );
                            $msg = str_replace( '[user1]', qem_get_element( $item, 'yourblank1' ), $msg );
                            $msg = str_replace( '[user2]', qem_get_element( $item, 'yourblank2' ), $msg );
                            $msg = str_replace( '[website]', $url, $msg );
                            if ( qem_get_element( $item, 'yourname', false ) ) {
                                $str = $str . '<li>' . $msg . '</li>';
                            }
                        }
                    
                    }
                    if ( $str && qem_get_element( $event, 'whoscoming', false ) && $hide != 'checked' ) {
                        $content .= '<p id="whoscoming_names" class="qem__whoscoming_names"' . $style . '>' . $event['whoscomingmessage'] . '</p><ul>' . $str . '</ul>';
                    }
                } else {
                    foreach ( $whoscoming as $item ) {
                        $num = $num + (int) $item['yourplaces'];
                        $ipn = qem_check_ipnblock( $payment, $item );
                        
                        if ( ('' === $event_number_max || (int) $num <= (int) $event_number_max) && !$item['notattend'] && !$ipn && ($register['moderate'] && $item['approved'] || !$register['moderate']) ) {
                            $str = $str . $item['yourname'] . ', ';
                            $grav = $grav . '<img title="' . $item['yourname'] . '" src="http://www.gravatar.com/avatar/' . md5( $item['youremail'] ) . '?s=40&&d=identicon" /> ';
                        }
                    
                    }
                    $str = substr( $str, 0, -2 );
                    if ( $str && $event['whoscoming'] && $hide != 'checked' ) {
                        $content .= '<p id="whoscoming_names" class="qem__whoscoming_names" ' . $style . '>' . $event['whoscomingmessage'] . ' ' . $str . '</p>';
                    }
                    if ( $event['whosavatar'] && $hide != 'checked' ) {
                        $content .= '<p>' . $grav . '</p>';
                    }
                }
                
                $output .= $content;
            }
            
            break;
        case 'field11':
            $event_number_max = get_post_meta( $post->ID, 'event_number', true );
            $num = qem_number_places_available( $post->ID );
            $placesavailable = 'checked';
            if ( isset( $event['iflessthan'] ) && $event['iflessthan'] && $num > $event['iflessthan'] ) {
                $placesavailable = '';
            }
            
            if ( $register['waitinglist'] && $num == 0 && '' !== $event_number_max ) {
                $output .= '<p id="whoscoming">' . $event['placesbefore'] . ' 0 ' . $event['placesafter'] . ' <span id="waitinglistmessage">' . $register['waitinglistmessage'] . '</span><p>';
            } elseif ( $placesavailable ) {
                $output .= '<p class="placesavailable" ' . $style . '>' . qem_places(
                    $register,
                    $post->ID,
                    $event_number_max,
                    $event
                ) . '</p>';
            }
            
            break;
        case 'field12':
            if ( !$atts['popup'] && !$register['ontheright'] && (is_singular( 'event' ) || $atts['fulllist']) && !$atts['widget'] && $atts['usereg'] ) {
                $output .= qem_loop();
            }
            break;
        case 'field13':
            if ( !empty($event['category_label']) ) {
                $caption = $event['category_label'] . '&nbsp;';
            }
            $categories = get_the_category();
            foreach ( $categories as $category ) {
                $cat_name = $cat_name . ' ' . $category->cat_name;
            }
            $output .= '<p ' . $style . '>' . $caption . $cat_name . '</p>';
            break;
        case 'field14':
            $link = get_permalink();
            $output .= '<h4>';
            
            if ( $event['facebook_label'] ) {
                $facebook_svg = '<svg fill="#3B5998" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 64 64" width="24px" height="24px"><path d="M32,6C17.642,6,6,17.642,6,32c0,13.035,9.603,23.799,22.113,25.679V38.89H21.68v-6.834h6.433v-4.548	c0-7.529,3.668-10.833,9.926-10.833c2.996,0,4.583,0.223,5.332,0.323v5.965h-4.268c-2.656,0-3.584,2.52-3.584,5.358v3.735h7.785	l-1.055,6.834h-6.73v18.843C48.209,56.013,58,45.163,58,32C58,17.642,46.359,6,32,6z"/></svg>';
                $output .= '<div id="fb-root"></div>
                <script>(function(d, s, id) {var js, fjs = d.getElementsByTagName(s)[0];if (d.getElementById(id)) return;js = d.createElement(s); js.id = id;js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8&appId=205093296628825";fjs.parentNode.insertBefore(js, fjs);}(document, \'script\', \'facebook-jssdk\'));
                </script>
                <a 
                	style="display: inline-flex;align-items: center; margin-right: 10px;" 
                	target="_blank" 
                	OnClick="window.open(this.href,\'targetWindow\',\'titlebar=no,toolbar=no,location=0,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=250\'); return false;" href="https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $link ) . '">' . $facebook_svg . '<span style="margin-left: 3px;">' . $event['facebook_label'] . '</span>' . '</a>';
            }
            
            
            if ( $event['twitter_label'] ) {
                $twitter_svg = '<svg fill="#1DA1F2" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 64 64" width="24px" height="24px"><path d="M61.932,15.439c-2.099,0.93-4.356,1.55-6.737,1.843c2.421-1.437,4.283-3.729,5.157-6.437	c-2.265,1.328-4.774,2.303-7.444,2.817C50.776,11.402,47.735,10,44.366,10c-6.472,0-11.717,5.2-11.717,11.611	c0,0.907,0.106,1.791,0.306,2.649c-9.736-0.489-18.371-5.117-24.148-12.141c-1.015,1.716-1.586,3.726-1.586,5.847	c0,4.031,2.064,7.579,5.211,9.67c-1.921-0.059-3.729-0.593-5.312-1.45c0,0.035,0,0.087,0,0.136c0,5.633,4.04,10.323,9.395,11.391	c-0.979,0.268-2.013,0.417-3.079,0.417c-0.757,0-1.494-0.086-2.208-0.214c1.491,4.603,5.817,7.968,10.942,8.067	c-4.01,3.109-9.06,4.971-14.552,4.971c-0.949,0-1.876-0.054-2.793-0.165C10.012,54.074,16.173,56,22.786,56	c21.549,0,33.337-17.696,33.337-33.047c0-0.503-0.016-1.004-0.04-1.499C58.384,19.83,60.366,17.78,61.932,15.439"/></svg>';
                $unixtime = $custom['event_date'][0];
                $date = date_i18n( "j+M+y", $unixtime );
                $title = get_the_title();
                $title = str_replace( ' ', '+', $title );
                $output .= '<a 
					style="display: inline-flex; align-items: center;" 
					target="_blank" 
					OnClick="window.open(this.href,\'targetWindow\',\'titlebar=no,toolbar=no,location=0,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=250\'); return false;" href="https://twitter.com/share?url=' . $link . '&text=' . $date . '+-+' . $title . '&hashtags=WFTR">' . $twitter_svg . '<span style="margin-left: 3px;">' . $event['twitter_label'] . '</span>' . '</a>
					<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script> ';
            }
            
            if ( qem_get_element( $event, 'useicsbutton', false ) && (!$display['useics'] || !$display['uselistics']) ) {
                $output .= qem_ics_button( $post->ID, $event['useicsbutton'] );
            }
            $output .= '</h4>';
            break;
    }
    return $output;
}

// Check for IPN payment
function qem_check_ipnblock( $payment, $item )
{
    
    if ( qem_get_element( $payment, 'paypal', false ) && qem_get_element( $payment, 'ipn', false ) && qem_get_element( $payment, 'ipnblock', false ) && $item['ipn'] && $item['ipn'] != 'Paid' ) {
        return true;
    } else {
        return false;
    }

}

/**
 * Calculates how many are coming
 *
 * @param $pid
 * @param $payment
 *
 * @return int
 */
function qem_get_the_numbers( $pid, $payment )
{
    $str = 0;
    $register = qem_get_stored_register();
    $whoscoming = get_option( 'qem_messages_' . $pid );
    if ( $whoscoming ) {
        foreach ( $whoscoming as $item ) {
            if ( !qem_check_ipnblock( $payment, $item ) && !qem_get_element( $item, 'notattend', false ) && ($register['moderate'] && isset( $item['approved'] ) || !$register['moderate'] || $register['moderate'] && $register['moderateplaces']) ) {
                $str = $str + (int) $item['yourplaces'];
            }
        }
    }
    return (int) $str;
}

/**
 * Calculate the number of places available
 * if there is no limit then available places is retunred as zero so the limit should always be checked
 * the limit is post meta event_number and no limit is returned as a blank string
 *
 * @param $pid
 *
 * @return int
 */
function qem_number_places_available( $pid )
{
    $payment = qem_get_stored_payment();
    $number = get_post_meta( $pid, 'event_number', true );
    if ( '' === $number ) {
        return 0;
    }
    $attending = qem_get_the_numbers( $pid, $payment );
    $places = $number - $attending;
    
    if ( $places >= 0 ) {
        return (int) $places;
    } else {
        return 0;
    }

}

// Displays how many places available
function qem_places(
    $register,
    $pid,
    $event_number_max,
    $event = array()
)
{
    $places = qem_number_places_available( $pid );
    $cutoff = '';
    $content = '';
    $cutoffdate = get_post_meta( $pid, 'event_cutoff_date', true );
    if ( $cutoffdate && $cutoffdate < time() ) {
        $cutoff = 'checked';
    }
    if ( isset( $event['iflessthan'] ) && $event['iflessthan'] && $places > $event['iflessthan'] ) {
        return $content;
    }
    if ( '' !== $event_number_max ) {
        
        if ( $places == 0 || $cutoff ) {
            $content = '<div class="qem-places qem-registration-closed">' . $register['eventfullmessage'] . '</div>';
        } elseif ( $places == 1 ) {
            $content = '<div class="qem-places qem-registration-oneplacebefore">' . $event['oneplacebefore'] . '</div>';
        } else {
            $content = '<div class="qem-places qem-registration-places">' . $event['placesbefore'] . ' ' . $places . ' ' . $event['placesafter'] . '</div>';
        }
    
    }
    return $content;
}

// Generates the map
function get_event_map( $mapwidth )
{
    global  $post ;
    $event = event_get_stored_options();
    $display = event_get_stored_display();
    $mapurl = $target = '';
    if ( isset( $display['map_target'] ) && $display['map_target'] ) {
        $target = ' target="_blank" ';
    }
    $custom = get_post_custom();
    if ( $display['show_map'] == 'checked' && !empty($custom['event_address'][0]) ) {
        
        if ( !isset( $display['apikey'] ) || empty($display['apikey']) ) {
            $mapurl = '<div class="qemmap">' . __( 'Since June 2016 you need to have a valid API key enabled to display Google maps, see plugin settings', 'quick-event-manager' ) . '</div>';
        } else {
            $map = str_replace( ' ', '+', $custom['event_address'][0] );
            $mapurl .= '<div class="qemmap"><a href="https://maps.google.com/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=' . $map . '&amp;t=m" ' . $target . '><img src="https://maps.googleapis.com/maps/api/staticmap?center=' . $map . '&size=' . $mapwidth . 'x' . $display['map_height'] . '&markers=color:blue%7C' . $map . '&key=' . $display['apikey'] . '" alt="' . $custom['event_address'][0] . '" /></a></div>';
        }
    
    }
    return $mapurl;
}

// Calendar symbol on small screens
function qem_widget_calendar( $atts )
{
    $arr = array(
        'arrow'            => '\\25B6',
        'square'           => '\\25A0',
        'box'              => '\\20DE',
        'asterix'          => '\\2605',
        'blank'            => ' ',
        'categorykeyabove' => '',
        'categorykeybelow' => '',
    );
    $smallicon = '';
    foreach ( $arr as $item => $key ) {
        if ( $item == $atts['smallicon'] ) {
            $smallicon = '#qem-calendar-widget .qemtrim span {display:none;}#qem-calendar-widget .qemtrim:after{content:"' . $key . '";font-size:150%;text-align:center}';
        }
    }
    if ( $atts['headerstyle'] ) {
        $headerstyle = '#qem-calendar-widget ' . $atts['header'] . '{' . $atts['headerstyle'] . '}';
    }
    return '<div id="qem-calendar-widget"><style>' . $smallicon . ' ' . $headerstyle . '</style>' . qem_show_calendar( $atts ) . '</div>' . "\r\n";
}

/*
	Added function to normalize this action across most functions
*/
function qem_actual_link()
{
    
    if ( isset( $_REQUEST['action'] ) ) {
        $actual_link = explode( '?', $_SERVER['HTTP_REFERER'] );
        $actual_link = $actual_link[0];
    } else {
        $prefix = 'http://';
        if ( is_ssl() ) {
            $prefix = 'https://';
        }
        $actual_link = $prefix . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    return $actual_link;
}

// Generates the months
function qem_calendar_months( $cal )
{
    $month = date_i18n( "n" );
    $year = date_i18n( "Y" );
    $actual_link = qem_actual_link();
    $parts = explode( "&", $actual_link );
    $actual_link = $parts['0'];
    $link = ( strpos( $actual_link, '?' ) ? '&' : '?' );
    $reload = ( $cal['jumpto'] ? '#qem_calreload' : '' );
    $content = '<p>' . $cal['monthscaption'] . '</p>
        <p class="clearfix">';
    for ( $i = $month ;  $i <= 12 ;  $i++ ) {
        $monthname = date_i18n( "M", mktime(
            0,
            0,
            0,
            $i,
            10
        ) );
        $content .= '<span class="qem-category qem-month"><a href="' . $actual_link . $link . 'qemmonth=' . $i . '&amp;qemyear=' . $year . $reload . '">' . $monthname . '</a></span>';
    }
    $year = $year + 1;
    $month = $month - 1;
    for ( $i = 1 ;  $i <= $month ;  $i++ ) {
        $monthname = date_i18n( "M", mktime(
            0,
            0,
            0,
            $i,
            10
        ) );
        $content .= '<span class="qem-category qem-month"><a href="' . $actual_link . $link . 'qemmonth=' . $i . '&amp;qemyear=' . $year . $reload . '">' . $monthname . '</a></span>';
    }
    $content .= '</p>';
    return $content;
}

// Builds the calendar page
function qem_show_calendar( $atts )
{
    global  $qem_calendars ;
    if ( !isset( $qem_calendars ) ) {
        $qem_calendars = 0;
    }
    $c = ( isset( $_REQUEST['qemcalendar'] ) ? (int) $_REQUEST['qemcalendar'] : $qem_calendars++ );
    $cal = qem_get_stored_calendar();
    $style = qem_get_stored_style();
    $category = '';
    $defaults = array(
        'category'         => '',
        'navigation'       => '',
        'month'            => '',
        'year'             => '',
        'links'            => 'on',
        'categorykeyabove' => '',
        'categorykeybelow' => '',
        'usecategory'      => '',
        'smallicon'        => 'trim',
        'widget'           => '',
        'header'           => 'h2',
        'fullpopup'        => '',
        'calendar'         => 'checked',
        'thisisapopup'     => false,
    );
    $atts = (array) $atts;
    $natts = array_merge( $defaults, $atts );
    extract( shortcode_atts( $natts, $atts ) );
    global  $post ;
    global  $_GET ;
    if ( !$widget ) {
        $header = $cal['header'];
    }
    if ( $cal['hidenavigation'] ) {
        $navigation = 'off';
    }
    $reload = ( $cal['jumpto'] ? '#qem_calreload' : '' );
    if ( isset( $_REQUEST['category'] ) ) {
        $category = $_REQUEST['category'];
    }
    $args = array(
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'event_date',
        'order'          => 'asc',
        'posts_per_page' => -1,
        'category'       => '',
        'links'          => 'on',
    );
    $catarry = explode( ",", $category );
    $leftnavicon = '';
    $rightnavicon = '';
    
    if ( $cal['navicon'] == 'arrows' ) {
        $leftnavicon = '&#9668; ';
        $rightnavicon = ' &#9658;';
    }
    
    
    if ( $cal['navicon'] == 'unicodes' ) {
        $leftnavicon = $cal['leftunicode'] . ' ';
        $rightnavicon = ' ' . $cal['rightunicode'];
    }
    
    $monthnames = array();
    $monthstamp = 0;
    for ( $i = 0 ;  $i <= 12 ;  $i++ ) {
        $monthnames[] = date_i18n( 'F', $monthstamp );
        $monthstamp = strtotime( '+1 month', $monthstamp );
    }
    if ( $cal['startday'] == 'monday' ) {
        $timestamp = strtotime( 'next sunday' );
    }
    if ( $cal['startday'] == 'sunday' ) {
        $timestamp = strtotime( 'next saturday' );
    }
    $days = array();
    for ( $i = 0 ;  $i <= 7 ;  $i++ ) {
        $days[] = date_i18n( 'D', $timestamp );
        $timestamp = strtotime( '+1 day', $timestamp );
    }
    
    if ( isset( $_REQUEST["qemmonth"] ) ) {
        $month = $_REQUEST["qemmonth"];
    } else {
        
        if ( $month ) {
            
            if ( !is_numeric( $month ) ) {
                $month = strtotime( $month );
                if ( false === $month ) {
                    $month = date_i18n( "n" );
                }
                $month = date( 'n', $month );
            }
        
        } else {
            $month = date_i18n( "n" );
        }
    
    }
    
    
    if ( isset( $_REQUEST["qemyear"] ) ) {
        $year = $_REQUEST["qemyear"];
    } else {
        
        if ( $year ) {
            
            if ( !is_numeric( $year ) ) {
                $year = strtotime( $year );
                if ( false === $year ) {
                    $year = date_i18n( "Y" );
                }
                $year = date( 'Y', $year );
            }
        
        } else {
            $year = date_i18n( "Y" );
        }
    
    }
    
    $currentmonth = filter_var( $month, FILTER_SANITIZE_NUMBER_INT );
    $currentyear = filter_var( $year, FILTER_SANITIZE_NUMBER_INT );
    $calendar = '<div class="qem_calendar" id="qem_calendar_' . $c . '"><a name="qem_calreload"></a>';
    /*
    	Build attribute array into json object to use later
    */
    $calendar .= "\r\n<script type='text/javascript'>\r\n";
    $calendar .= "\tqem_calendar_atts[{$c}] = " . json_encode( $atts ) . ";\r\n";
    $calendar .= "\tqem_month[{$c}] = {$currentmonth};\r\n";
    $calendar .= "\tqem_year[{$c}] = {$currentyear};\r\n";
    $calendar .= "\tqem_category[{$c}] = '{$category}';\r\n";
    $calendar .= "</script>\r\n";
    $p_year = $currentyear;
    $n_year = $currentyear;
    $p_month = $currentmonth - 1;
    $n_month = $currentmonth + 1;
    
    if ( $p_month == 0 ) {
        $p_month = 12;
        $p_year = $currentyear - 1;
    }
    
    
    if ( $n_month == 13 ) {
        $n_month = 1;
        $n_year = $currentyear + 1;
    }
    
    $atts['calendar'] = true;
    if ( qem_get_element( $cal, 'fullpopup', false ) ) {
        $atts['fullpopup'] = 'checked';
    }
    $qem_dates = array();
    $eventdate = array();
    $eventenddate = array();
    $eventtitle = array();
    $eventsummary = array();
    $eventlinks = array();
    $eventslug = array();
    $eventimage = array();
    $eventdesc = array();
    $eventnumbers = array();
    $eventx = false;
    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            
            if ( in_category( $catarry ) || !$category || 'undefined' === $category ) {
                $startdate = get_post_meta( $post->ID, 'event_date', true );
                $enddate = get_post_meta( $post->ID, 'event_end_date', true );
                if ( !$startdate || !is_numeric( $startdate ) ) {
                    $startdate = time();
                }
                $startmonth = date( "m", (int) $startdate );
                $startyear = date( "Y", (int) $startdate );
                $match = false;
                // Do matches for the start of an event
                if ( $startyear == $currentyear && $startmonth == $currentmonth ) {
                    $match = true;
                }
                // Do matches for end of event (If it applies)
                
                if ( !$match && $enddate ) {
                    $endmonth = date( "m", $enddate );
                    $endyear = date( "Y", $enddate );
                    if ( $endyear == $currentyear && $endmonth == $currentmonth ) {
                        $match = true;
                    }
                }
                
                
                if ( $match ) {
                    // $startdate = strtotime(date("d M Y", $startdate));
                    $enddate = get_post_meta( $post->ID, 'event_end_date', true );
                    $image = get_post_meta( $post->ID, 'event_image', true );
                    $desc = get_post_meta( $post->ID, 'event_desc', true );
                    $link = get_permalink();
                    $whoscoming = get_option( 'qem_messages_' . $post->ID );
                    $attendees = ( $whoscoming ? true : false );
                    $cat = get_the_category();
                    if ( !$cat ) {
                        $cat = array();
                    }
                    $slug = ( isset( $cat[0] ) ? $cat[0]->slug : '' );
                    if ( $cal['eventlink'] == 'linkpopup' ) {
                        $eventx = get_event_popup( $atts );
                    }
                    $title = get_the_title();
                    
                    if ( qem_get_element( $cal, 'showmultiple', false ) ) {
                        do {
                            array_push( $eventdate, $startdate );
                            array_push( $eventtitle, $title );
                            array_push( $eventslug, $slug );
                            array_push( $eventsummary, $eventx );
                            array_push( $eventlinks, $link );
                            array_push( $eventnumbers, $attendees );
                            $startdate = $startdate + 24 * 60 * 60;
                        } while ($startdate <= $enddate);
                    } else {
                        array_push( $eventdate, $startdate );
                        array_push( $eventtitle, $title );
                        array_push( $eventslug, $slug );
                        array_push( $eventsummary, $eventx );
                        array_push( $eventlinks, $link );
                        array_push( $eventnumbers, $attendees );
                        array_push( $eventimage, $image );
                        array_push( $eventdesc, $desc );
                    }
                
                }
            
            }
        
        }
    }
    wp_reset_postdata();
    wp_reset_query();
    if ( $cal['connect'] ) {
        $calendar .= '<p><a href="' . $cal['eventlist_url'] . '">' . $cal['eventlist_text'] . '</a></p>';
    }
    $actual_link = qem_actual_link();
    $parts = explode( "&", $actual_link );
    $actual_link = $parts['0'];
    $link = ( strpos( $actual_link, '?' ) ? '&' : '?' );
    $catkey = qem_category_key( $cal, $style, 'calendar' );
    if ( qem_get_element( $cal, 'showkeyabove', false ) && !$widget || qem_get_element( $atts, 'categorykeyabove', false ) == 'checked' ) {
        $calendar .= $catkey;
    }
    if ( qem_get_element( $cal, 'showmonthsabove', false ) ) {
        $calendar .= qem_calendar_months( $cal );
    }
    // Build Category Information
    $category = qem_get_element( $atts, 'category' );
    if ( isset( $_GET['category'] ) ) {
        $category = $_GET['category'];
    }
    $catlabel = str_replace( ',', ', ', $category );
    if ( $category && $display['showcategory'] ) {
        $calendar .= '<h2>' . $display['showcategorycaption'] . ' ' . $catlabel . '</h2>';
    }
    $calendar .= '<div id="qem-calendar">
    <table cellspacing="' . $cal['cellspacing'] . '" cellpadding="0">
    <tr class="caltop">
    <td>';
    if ( $navigation != 'off' ) {
        $calendar .= '<a class="calnav" href="' . $actual_link . $link . 'qemmonth=' . $p_month . '&amp;qemyear=' . $p_year . $reload . '">' . $leftnavicon . $cal['prevmonth'] . '</a>';
    }
    $headerorder = ( $cal['headerorder'] == 'ym' ? $currentyear . ' ' . $monthnames[$currentmonth - 1] : $monthnames[$currentmonth - 1] . ' ' . $currentyear );
    $calendar .= '</td>
    <td class="calmonth"><' . $header . '>' . $headerorder . '</' . $header . '></td>
    <td>';
    if ( $navigation != 'off' ) {
        $calendar .= '<a class="calnav" href="' . $actual_link . $link . 'qemmonth=' . $n_month . '&amp;qemyear=' . $n_year . $reload . '">' . $cal['nextmonth'] . $rightnavicon . '</a>';
    }
    $calendar .= '</td>
    </tr>
    </table>
    <table>
    <tr>' . "\r\n";
    for ( $i = 1 ;  $i <= 7 ;  $i++ ) {
        $calendar .= '<td class="calday">' . $days[$i] . '</td>';
    }
    $calendar .= '</tr>' . "\r\n";
    $timestamp = mktime(
        0,
        0,
        0,
        $currentmonth,
        1,
        $currentyear
    );
    $maxday = date_i18n( "t", $timestamp );
    $thismonth = getdate( $timestamp );
    
    if ( $cal['startday'] == 'monday' ) {
        $startday = $thismonth['wday'] - 1;
        if ( $startday == '-1' ) {
            $startday = '6';
        }
    } else {
        $startday = $thismonth['wday'];
    }
    
    $firstday = '';
    $henry = $startday - 1;
    $startday = (int) $startday;
    for ( $i = 0 ;  $i < $maxday + $startday ;  $i++ ) {
        $oldday = '';
        $blankday = ( $i < $startday ? ' class="blankday" ' : '' );
        $firstday = ( $i == $startday - 1 ? ' class="firstday" ' : '' );
        $xxx = mktime(
            0,
            0,
            0,
            $currentmonth,
            $i - $startday + 1,
            $currentyear
        );
        if ( date_i18n( "d" ) > $i - $startday + 1 && $currentmonth <= date_i18n( "n" ) && $currentyear == date_i18n( "Y" ) ) {
            $oldday = 'oldday';
        }
        if ( $currentmonth < date_i18n( "n" ) && $currentyear == date_i18n( "Y" ) ) {
            $oldday = 'oldday';
        }
        if ( $currentyear < date_i18n( "Y" ) ) {
            $oldday = 'oldday';
        }
        
        if ( $cal['archive'] && $oldday || !$oldday ) {
            $show = 'checked';
        } else {
            $show = '';
        }
        
        $tdstart = '<td class="day ' . $oldday . ' ' . $firstday . '"><' . $header . '>' . ($i - $startday + 1) . '</' . $header . '><br>';
        $tdcontent = $eventcontent = '';
        $flag = ( $cal['attendeeflagcontent'] ? $cal['attendeeflagcontent'] : '&#x25cf;' );
        foreach ( $eventdate as $key => $day ) {
            $m = date( 'm', $day );
            $d = date( 'd', $day );
            $y = date( 'Y', $day );
            $zzz = mktime(
                0,
                0,
                0,
                $m,
                $d,
                $y
            );
            
            if ( $xxx == $zzz && $show ) {
                $tdstart = '<td class="eventday ' . $oldday . ' ' . $firstday . '"><' . $header . '>' . ($i - $startday + 1) . '</' . $header . '>';
                $img = ( $eventimage[$key] && $cal['eventimage'] && !$widget ? '<br><img src="' . $eventimage[$key] . '">' : '' );
                $tooltip = '';
                $tooltipclass = '';
                
                if ( qem_get_element( $cal, 'usetooltip', false ) ) {
                    $desc = ( $eventdesc[$key] ? ' - ' . $eventdesc[$key] : '' );
                    $tooltip = 'data-tooltip="' . $eventtitle[$key] . $desc . '"';
                    $tooltipclass = ( $i % 7 == 6 ? ' tooltip-left ' : '' );
                    if ( $widget ) {
                        $tooltipclass = ( $i % 7 > 2 ? ' tooltip-left ' : '' );
                    }
                }
                
                $length = $cal['eventlength'];
                $short = $length - 3;
                $numbers = '';
                if ( $cal['attendeeflag'] ) {
                    $numbers = ( $eventnumbers[$key] ? $flag . '&nbsp;' : '' );
                }
                $tagless_title = strip_tags( $eventtitle[$key] );
                $trim_title = ( strlen( $tagless_title ) > $length ? mb_substr(
                    $tagless_title,
                    0,
                    $short,
                    "utf-8"
                ) . '...' : $tagless_title );
                // put back tags - this is for translation plugins like TranslatePress
                $trim = str_replace( $tagless_title, $trim_title, $eventtitle[$key] );
                
                if ( $cal['eventlink'] == 'linkpopup' ) {
                    $tdcontent .= '<a ' . $tooltip . ' class="event ' . $eventslug[$key] . $tooltipclass . '" href="javascript:xlightbox(\'' . $eventsummary[$key] . '\'); "><div class="qemtrim"><span>' . $numbers . $trim . '</span>' . $img . '</div></a>';
                } else {
                    $eventcontent = '<a ' . $tooltip . ' class="' . $eventslug[$key] . $tooltipclass . '" href="' . $eventlinks[$key] . '"><div class="qemtrim"><span>' . $numbers . $trim . '</span>' . $img . '</div></a>';
                    $tdcontent .= preg_replace( "/\r|\n/", "", $eventcontent );
                }
            
            }
        
        }
        $tdbuilt = $tdstart . $tdcontent . '</td>';
        if ( $i % 7 == 0 ) {
            $calendar .= "<tr>\r\t";
        }
        
        if ( $i < $startday ) {
            $calendar .= '<td' . $firstday . $blankday . '></td>';
        } else {
            $calendar .= $tdbuilt;
        }
        
        if ( $i % 7 == 6 ) {
            $calendar .= "</tr>" . "\r\n";
        }
    }
    $calendar .= "</table></div>";
    if ( qem_get_element( $cal, 'showkeybelow', false ) && !$widget || qem_get_element( $atts, 'categorykeybelow', false ) == 'checked' ) {
        $calendar .= $catkey;
    }
    if ( qem_get_element( $cal, 'showmonthsbelow', false ) ) {
        $calendar .= qem_calendar_months( $cal );
    }
    $eventdate = remove_empty( $eventdate );
    return $calendar . "</div>";
}

function remove_empty( $array )
{
    return array_filter( $array, '_remove_empty_internal' );
}

function _remove_empty_internal( $value )
{
    return !empty($value) || $value === 0;
}

// Creates the content for the popup
function get_event_popup( $atts )
{
    $atts['links'] = 'checked';
    $atts['popup'] = $atts['grid'] = '';
    $atts['linkpopup'] = '';
    $atts['thisday'] = '';
    $atts['fullevent'] = 'full';
    $atts['thisisapopup'] = true;
    if ( qem_get_element( $atts, 'listplaces', false ) && !qem_get_element( $atts, 'fullpopup', false ) ) {
        $atts['fullevent'] = 'summary';
    }
    if ( $atts['calendar'] && !qem_get_element( $atts, 'fullpopup', false ) ) {
        $atts['fullevent'] = 'summary';
    }
    $output = qem_event_construct( $atts );
    $output = str_replace( '"', '&quot;', $output );
    $output = str_replace( '<', '&lt;', $output );
    $output = str_replace( '>', '&gt;', $output );
    $output = str_replace( "'", "&#8217;", $output );
    $output = str_replace( "&#39;", "&#8217;", $output );
    return $output;
}

// Builds the CSS
function qem_generate_css()
{
    $style = qem_get_stored_style();
    $cal = qem_get_stored_calendar();
    $display = event_get_stored_display();
    $register = qem_get_stored_register();
    $register_style = qem_get_register_style();
    $color = $script = $showeventborder = $formborder = $daycolor = $eventbold = $colour = $eventitalic = '';
    if ( $style['calender_size'] == 'small' ) {
        $radius = 7;
    }
    if ( $style['calender_size'] == 'medium' ) {
        $radius = 10;
    }
    if ( $style['calender_size'] == 'large' ) {
        $radius = 15;
    }
    $size = 50 + 2 * $style['date_border_width'];
    $ssize = $size . 'px';
    $srm = $size + 5 + $style['date_border_width'];
    $srm = $srm . 'px';
    $size = 70 + 2 * $style['date_border_width'];
    $msize = $size . 'px';
    $mrm = $size + 5 + $style['date_border_width'];
    $mrm = $mrm . 'px';
    $size = 90 + 2 * $style['date_border_width'];
    $lsize = $size . 'px';
    $lrm = $size + 5 + $style['date_border_width'];
    $lrm = $lrm . 'px';
    if ( $style['date_background'] == 'color' ) {
        $color = $style['date_backgroundhex'];
    }
    if ( $style['date_background'] == 'grey' ) {
        $color = '#343838';
    }
    if ( $style['date_background'] == 'red' ) {
        $color = 'red';
    }
    
    if ( $style['month_background'] == 'colour' ) {
        $colour = $style['month_backgroundhex'];
    } else {
        $colour = '#FFF';
    }
    
    $eventbackground = '';
    if ( $style['event_background'] == 'bgwhite' ) {
        $eventbackground = 'background:white;';
    }
    if ( $style['event_background'] == 'bgcolor' ) {
        $eventbackground = 'background:' . $style['event_backgroundhex'] . ';';
    }
    $formwidth = preg_split( '#(?<=\\d)(?=[a-z%])#i', $register['formwidth'] );
    if ( !isset( $formwidth[0] ) ) {
        $formwidth[0] = '280';
    }
    if ( !isset( $formwidth[1] ) ) {
        $formwidth[1] = 'px';
    }
    $regwidth = $formwidth[0] . $formwidth[1];
    $dayborder = 'color:' . $style['date_colour'] . ';background:' . $color . '; border: ' . $style['date_border_width'] . 'px solid ' . $style['date_border_colour'] . ';border-bottom:none;';
    $nondayborder = 'border: ' . $style['date_border_width'] . 'px solid ' . $style['date_border_colour'] . ';border-top:none;background:' . $colour . ';';
    $monthcolor = 'span.month {color:' . $style['month_colour'] . ';}';
    $eventborder = 'border: ' . $style['date_border_width'] . 'px solid ' . $style['date_border_colour'] . ';';
    
    if ( $style['icon_corners'] == 'rounded' ) {
        $dayborder = $dayborder . '-webkit-border-top-left-radius:' . $radius . 'px; -moz-border-top-left-radius:' . $radius . 'px; border-top-left-radius:' . $radius . 'px; -webkit-border-top-right-radius:' . $radius . 'px; -moz-border-top-right-radius:' . $radius . 'px; border-top-right-radius:' . $radius . 'px;';
        $nondayborder = $nondayborder . '-webkit-border-bottom-left-radius:' . $radius . 'px; -moz-border-bottom-left-radius:' . $radius . 'px; border-bottom-left-radius:' . $radius . 'px; -webkit-border-bottom-right-radius:' . $radius . 'px; -moz-border-bottom-right-radius:' . $radius . 'px; border-bottom-right-radius:' . $radius . 'px;';
        $eventborder = $eventborder . '-webkit-border-radius:' . $radius . 'px; -moz-border-radius:' . $radius . 'px; border-radius:' . $radius . 'px;';
    }
    
    if ( $style['event_border'] ) {
        $showeventborder = 'padding:' . $radius . 'px;' . $eventborder;
    }
    if ( $register['formborder'] ) {
        $formborder = "\n.qem-register {" . $eventborder . "padding:" . $radius . "px;}";
    }
    
    if ( $style['widthtype'] == 'pixel' ) {
        $eventwidth = preg_replace( "/[^0-9]/", "", $style['width'] ) . 'px;';
    } else {
        $eventwidth = '100%';
    }
    
    $j = preg_split( '#(?<=\\d)(?=[a-z%])#i', $display['event_image_width'] );
    if ( !$j[0] ) {
        $j[0] = '300';
    }
    $i = $j[0] . 'px';
    if ( qem_get_element( $cal, 'eventbold', false ) ) {
        $eventbold = 'font-weight:bold;';
    }
    if ( qem_get_element( $cal, 'eventitalic', false ) ) {
        $eventitalic = 'font-style:italic;';
    }
    $ec = ( $cal['event_corner'] == 'square' ? 0 : 3 );
    $script .= '.qem {width:' . $eventwidth . ';' . $style['event_margin'] . ';}
.qem p {' . $style['line_margin'] . ';}
.qem p, .qem h2 {margin: 0 0 8px 0;padding:0;}' . "\n";
    if ( $style['font'] == 'plugin' ) {
        $script .= ".qem p {font-family: " . $style['font-family'] . "; font-size: " . $style['font-size'] . ";}\n.qem h2, .qem h2 a {font-size: " . $style['header-size'] . " !important;color:" . $style['header-colour'] . " !important}\n";
    }
    $script .= '@media only screen and (max-width:' . $cal['trigger'] . ') {.qemtrim span {font-size:50%;}
.qemtrim, .calday, data-tooltip {font-size: ' . $cal['eventtextsize'] . ';}}';
    $arr = array(
        'arrow'   => '\\25B6',
        'square'  => '\\25A0',
        'box'     => '\\20DE',
        'asterix' => '\\2605',
        'blank'   => ' ',
    );
    foreach ( $arr as $item => $key ) {
        if ( $item == $cal['smallicon'] ) {
            $script .= '#qem-calendar-widget h2 {font-size: 1em;}
#qem-calendar-widget .qemtrim span {display:none;}
#qem-calendar-widget .qemtrim:after{content:"' . $key . '";font-size:150%;}
@media only screen and (max-width:' . $cal['trigger'] . ';) {.qemtrim span {display:none;}.qemtrim:after{content:"' . $key . '";font-size:150%;}}' . "\n";
        }
    }
    // missing items
    $eventgridborder = ( isset( $display['eventgridborder'] ) ? $display['eventgridborder'] : 'inherit' );
    $script .= '.qem-small, .qem-medium, .qem-large {' . $showeventborder . $eventbackground . '}' . $formborder . ".qem-register{max-width:" . $regwidth . ";}\n.qemright {max-width:" . $display['max_width'] . "%;width:" . $i . ";height:auto;overflow:hidden;}\n.qemlistright {max-width:" . $display['max_width'] . "%;width:" . $display['image_width'] . "px;height:auto;overflow:hidden;}\nimg.qem-image {width:100%;height:auto;overflow:hidden;}\nimg.qem-list-image {width:100%;height:auto;overflow:hidden;}\n.qem-category {" . $eventborder . "}\n.qem-icon .qem-calendar-small {width:" . $ssize . ";}\n.qem-small {margin-left:" . $srm . ";}\n.qem-icon .qem-calendar-medium {width:" . $msize . ";}\n.qem-medium {margin-left:" . $mrm . ";}\n.qem-icon .qem-calendar-large {width:" . $lsize . ";}\n.qem-large {margin-left:" . $lrm . ";}\n.qem-calendar-small .nonday, .qem-calendar-medium .nonday, .qem-calendar-large .nonday {display:block;" . $nondayborder . "}\n.qem-calendar-small .day, .qem-calendar-medium .day, .qem-calendar-large .day {display:block;" . $daycolor . $dayborder . "}\n.qem-calendar-small .month, .qem-calendar-medium .month, .qem-calendar-large .month {color:" . $style['month_colour'] . "}\n.qem-error { border-color: red !important; }\n.qem-error-header { color: red !important; }\n.qem-columns, .qem-masonry {border:" . $eventgridborder . ";}\n#qem-calendar " . $cal['header'] . " {margin: 0 0 8px 0;padding:0;" . $cal['headerstyle'] . "}\n#qem-calendar .calmonth {text-align:center;}\n#qem-calendar .calday {background:" . $cal['calday'] . "; color:" . $cal['caldaytext'] . "}\n#qem-calendar .day {background:" . $cal['day'] . ";}\n#qem-calendar .eventday {background:" . $cal['eventday'] . ";}\n#qem-calendar .eventday a {-webkit-border-radius:" . $ec . "px; -moz-border-radius:" . $ec . "px; border-radius:" . $ec . "px;color:" . $cal['eventtext'] . " !important;background:" . $cal['eventbackground'] . " !important;border:" . $cal['eventborder'] . " !important;}\n#qem-calendar .eventday a:hover {background:" . $cal['eventhover'] . " !important;}\n#qem-calendar .oldday {background:" . $cal['oldday'] . ";}\n#qem-calendar table {border-collapse: separate;border-spacing:" . $cal['cellspacing'] . "px;}\n.qemtrim span {" . $eventbold . $eventitalic . "}\n@media only screen and (max-width: 700px) {.qemtrim img {display:none;}}\n@media only screen and (max-width: 480px) {.qem-large, .qem-medium {margin-left: 50px;}\n    .qem-icon .qem-calendar-large, .qem-icon .qem-calendar-medium  {font-size: 80%;width: 40px;margin: 0 0 10px 0;padding: 0 0 2px 0;}\n    .qem-icon .qem-calendar-large .day, .qem-icon .qem-calendar-medium .day {padding: 2px 0;}\n    .qem-icon .qem-calendar-large .month, .qem-icon .qem-calendar-medium .month {font-size: 140%;padding: 2px 0;}\n}";
    if ( isset( $style['vanilla'] ) && $style['vanilla'] ) {
        $script .= '.qem h2, .qem h3 {display:block;}';
    }
    if ( $cal['tdborder'] ) {
        
        if ( $cal['cellspacing'] > 0 ) {
            $script .= '#qem-calendar td.day, #qem-calendar td.eventday, #qem-calendar td.calday {border: ' . $cal['tdborder'] . ';}';
        } else {
            $script .= '#qem-calendar td.day, #qem-calendar td.eventday, #qem-calendar td.calday {border-left:none;border-top:none;border-right: ' . $cal['tdborder'] . ';border-bottom: ' . $cal['tdborder'] . ';}
#qem-calendar tr td.day:first-child,#qem-calendar tr td.eventday:first-child,#qem-calendar tr td.calday:first-child{border-left: ' . $cal['tdborder'] . ';}' . "\n" . '
#qem-calendar tr td.calday{border-top: ' . $cal['tdborder'] . ';}
#qem-calendar tr td.blankday {border-bottom: ' . $cal['tdborder'] . ';}
#qem-calendar tr td.firstday {border-right: ' . $cal['tdborder'] . ';border-bottom: ' . $cal['tdborder'] . ';}';
        }
    
    }
    $lbmargin = $display['lightboxwidth'] / 2;
    $script .= '#xlightbox {width:' . $display['lightboxwidth'] . '%;margin-left:-' . $lbmargin . '%;}
@media only screen and (max-width: 480px) {#xlightbox {width:90%;margin-left:-45%;}}';
    if ( $register['ontheright'] ) {
        $script .= '.qem-register {width:100%;} .qem-rightregister {max-width:' . $i . 'px;margin: 0px 0px 10px 0;}';
    }
    if ( $style['use_custom'] == 'checked' ) {
        $script .= $style['custom'];
    }
    $cat = array(
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j'
    );
    foreach ( $cat as $i ) {
        
        if ( $style['cat' . $i] ) {
            $eb = ( $cal['fixeventborder'] || $cal['eventborder'] == 'none' ? '' : 'border:1px solid ' . $style['cat' . $i . 'text'] . ' !important;' );
            $script .= "#qem-calendar a." . $style['cat' . $i] . " {background:" . $style['cat' . $i . 'back'] . " !important;color:" . $style['cat' . $i . 'text'] . " !important;" . $eb . "}";
            $script .= '.' . $style['cat' . $i] . ' .qem-small, .' . $style['cat' . $i] . ' .qem-medium, .' . $style['cat' . $i] . ' .qem-large {border-color:' . $style['cat' . $i . 'back'] . ';}.' . $style['cat' . $i] . ' .qem-calendar-small .day, .' . $style['cat' . $i] . ' .qem-calendar-medium .day, .' . $style['cat' . $i] . ' .qem-calendar-large .day, .' . $style['cat' . $i] . ' .qem-calendar-small .nonday, .' . $style['cat' . $i] . ' .qem-calendar-medium .nonday, .' . $style['cat' . $i] . ' .qem-calendar-large .nonday {border-color:' . $style['cat' . $i . 'back'] . ';}';
            if ( $style['date_background'] == 'category' ) {
                $script .= '.' . $style['cat' . $i] . ' .qem-calendar-small .day, .' . $style['cat' . $i] . ' .qem-calendar-medium .day, .' . $style['cat' . $i] . ' .qem-calendar-large .day {background:' . $style['cat' . $i . 'back'] . ';color:' . $style['cat' . $i . 'text'] . ';}';
            }
        }
    
    }
    $code = $header = $font = $submitfont = $fontoutput = $border = '';
    $headercolour = $corners = $input = $background = $submitwidth = $paragraph = $submitbutton = $submit = '';
    $register_style = qem_get_register_style();
    
    if ( !isset( $register_style['nostyling'] ) || !$register_style['nostyling'] ) {
        $code .= '.qem-register {text-align: left;margin: 10px 0 10px 0;padding: 0;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;}
.qem-register #none {border: 0px solid #FFF;padding: 0;}
.qem-register #plain {border: 1px solid #415063;padding: 10px;margin: 0;}
.qem-register #rounded {border: 1px solid #415063;padding: 10px;-moz-border-radius: 10px;-webkit-box-shadow: 10px;border-radius: 10px;}
.qem-register #shadow {border: 1px solid #415063;padding: 10px;margin: 0 10px 20px 0;-webkit-box-shadow: 5px 5px 5px #415063;-moz-box-shadow: 5px 5px 5px #415063;box-shadow: 5px 5px 5px #415063;}
.qem-register #roundshadow {border: 1px solid #415063;padding: 10px; margin: 0 10px 20px 0;-webkit-box-shadow: 5px 5px 5px #415063;-moz-box-shadow: 5px 5px 5px #415063;box-shadow: 5px 5px 5px #415063;-moz-border-radius: 10px;-webkit-box-shadow: 10px;border-radius: 10px;}
.qem-register form, .qem-register p {margin: 0;padding: 0;}
.qem-register input[type=text], .qem-register input[type=number], .qem-register textarea, .qem-register select, .qem-register #submit {margin: 5px 0 7px 0;padding: 4px;color: #465069;font-family: inherit;font-size: inherit;height:auto;border:1px solid #415063;width: 100%;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;}
.qem-register input[type=text] .required, .qem-register input[type=number] .required, .qem-register textarea .required {border:1px solid green;}
.qem-register #submit {text-align: center;cursor: pointer;}
div.toggle-qem {color: #FFF;background: #343838;text-align: center;cursor: pointer;margin: 5px 0 7px 0;padding: 4px;font-family: inherit;font-size: inherit;height:auto;border:1px solid #415063;width: 100%;-moz-box-sizing: border-box;-webkit-box-sizing: border-box;box-sizing: border-box;}
div.toggle-qem a {background: #343838;text-align: center;cursor: pointer;color:#FFFFFF;}
div.toggle-qem a:link, div.toggle-qem a:visited, div.toggle-qem a:hover {color:#FFF;text-decoration:none !important;}';
        $hd = ( $register_style['header-type'] ? $register_style['header-type'] : 'h2' );
        if ( $register_style['header-colour'] ) {
            $headercolour = "color: " . $register_style['header-colour'] . ";";
        }
        $header = ".qem-register " . $hd . " {" . $headercolour . ";height:auto;}";
        // missing
        $font_colour = ( isset( $register_style['font-colour'] ) ? 'color:' . $register_style['font-colour'] . ';' : '' );
        $input = '.qem-register input[type=text], .qem-register input[type=number], .qem-register textarea, .qem-register select {' . $font_colour . 'border:' . $register_style['input-border'] . ';background:' . $register_style['inputbackground'] . ';line-height:normal;height:auto;margin: 2px 0 3px 0;padding: 6px;}';
        $required = '.qem-register input[type=text].required, .qem-register input[type=number].required, .qem-register textarea.required, .qem-register select.required {border:' . $register_style['input-required'] . '}';
        $focus = ".qem-register input:focus, .qem-register textarea:focus {background:" . $register_style['inputfocus'] . ";}";
        $text = ".qem-register p {" . $font_colour . "margin: 6px 0 !important;padding: 0 !important;}";
        $error = ".qem-register .error {.qem-error {color:" . $register_style['error-font-colour'] . " !important;border-color:" . $register_style['error-font-colour'] . " !important;}";
        if ( $register_style['border'] != 'none' ) {
            $border = ".qem-register #" . $register_style['border'] . " {border:" . $register_style['form-border'] . ";}";
        }
        if ( $register_style['background'] == 'white' ) {
            $background = ".qem-register div {background:#FFF;}";
        }
        if ( $register_style['background'] == 'color' ) {
            $background = ".qem-register div {background:" . $register_style['backgroundhex'] . ";}";
        }
        $formwidth = preg_split( '#(?<=\\d)(?=[a-z%])#i', $register_style['form-width'] );
        if ( !$formwidth[0] ) {
            $formwidth[0] = '280';
        }
        if ( !isset( $formwidth[1] ) || !$formwidth[1] ) {
            $formwidth[1] = 'px';
        }
        $width = $formwidth[0] . $formwidth[1];
        if ( $register_style['submitwidth'] == 'submitpercent' ) {
            $submitwidth = 'width:100%;';
        }
        if ( $register_style['submitwidth'] == 'submitrandom' ) {
            $submitwidth = 'width:auto;';
        }
        if ( $register_style['submitwidth'] == 'submitpixel' ) {
            $submitwidth = 'width:' . $style['submitwidthset'] . ';';
        }
        
        if ( $register_style['submitposition'] == 'submitleft' ) {
            $submitposition = 'float:left;';
        } else {
            $submitposition = 'float:right;';
        }
        
        $submit = "color:" . $register_style['submit-colour'] . ";background:" . $register_style['submit-background'] . ";border:" . $register_style['submit-border'] . $submitfont . ";font-size: inherit;";
        $submithover = "background:" . $register_style['submit-hover-background'] . ";";
        $submitbutton = ".qem-register #submit {" . $submitposition . $submitwidth . $submit . "}\n.qem-register #submit:hover {" . $submithover . "}";
        
        if ( $register_style['corners'] == 'round' ) {
            $corner = '5px';
        } else {
            $corner = '0';
        }
        
        $corners = ".qem-register  input[type=text], .qem-register  input[type=number], .qem-register textarea, .qem-register select, .qem-register #submit {border-radius:" . $corner . ";}\r\n";
        if ( $register_style['corners'] == 'theme' ) {
            $corners = '';
        }
        $code .= "\r\n.qem-register {max-width:100%;overflow:hidden;width:" . $width . ";}" . $submitbutton . "\r\n" . $border . "\r\n" . $corners . "\r\n" . $header . "\r\n" . $paragraph . "\r\n" . $input . "\r\n" . $focus . "\r\n" . $required . "\r\n" . $text . "\r\n" . $error . "\r\n" . $background . "\r\n";
    }
    
    return $script . $code;
}

function qem_head_ic()
{
    global  $post ;
    
    if ( is_singular( 'event' ) ) {
        $unixtime = get_post_meta( $post->ID, 'event_date', true );
        $date = date_i18n( "j M y", $unixtime );
        echo  '<meta property="og:locale" content="en_GB" />
<meta property="og:type" content="website" />
<meta property="og:title" content="' . $date . ' - ' . get_the_title() . '" />
<meta property="og:description" content="' . get_post_meta( $post->ID, 'event_desc', true ) . '" />
<meta property="og:url" content="' . get_permalink() . '" />
<meta property="og:site_name" content="WFTR" />
<meta property="og:image" content="' . get_post_meta( $post->ID, 'event_image', true ) . '" />' ;
    }
    
    $incontext = qem_get_incontext();
    $GLOBALS['qem_ic'] = $incontext;
    $data = '';
    
    if ( $incontext['useincontext'] && $incontext['useapi'] == 'paypal' ) {
        $data = "\r\n<script type='text/javascript'> qem_ic = {api:'paypal',id:'" . $incontext['merchantid'] . "',environment:'{$incontext['api_mode']}'};</script>\r\n";
    } elseif ( $incontext['useapi'] == 'stripe' ) {
        $data = "\r\n<script type='text/javascript'> qem_ic = {api:'stripe',publishable_key:'" . $incontext['publishable_key'] . "',environment:'{$incontext['api_mode']}'};</script>\r\n";
    }
    
    $data .= '<script type="text/javascript">ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '"; qem_calendar_atts = []; qem_year = []; qem_month = []; qem_category = [];</script>';
    echo  $data ;
}

// Adds the CSS to the page head
function qem_head_css()
{
    echo  '<style type="text/css" media="screen">' . "\r\n" . qem_generate_css() . "\r\n" . '</style>' ;
}

function dateToCal( $timestamp )
{
    if ( $timestamp ) {
        return date( 'Ymd\\THis', $timestamp );
    }
}

function escapeString( $string )
{
    return preg_replace( '/([\\,;])/', '\\\\$1', $string );
}

function qem_time( $starttime )
{
    $time = (int) strtotime( '1 jan 1970 ' . $starttime );
    if ( $time >= 86400 ) {
        return 0;
    }
    return $time;
}

// Builds the ICS files
function qem_ics_button( $id, $label )
{
    $cal_ics_svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
             x="0px" y="0px" viewBox="0 0 512 512"
             width="24px" height="24px
             xml:space="preserve">
<path d="M160,240v32c0,8.844-7.156,16-16,16h-32c-8.844,0-16-7.156-16-16v-32c0-8.844,7.156-16,16-16h32  C152.844,224,160,231.156,160,240z M144,352h-32c-8.844,0-16,7.156-16,16v32c0,8.844,7.156,16,16,16h32c8.844,0,16-7.156,16-16v-32  C160,359.156,152.844,352,144,352z M272,224h-32c-8.844,0-16,7.156-16,16v32c0,8.844,7.156,16,16,16h32c8.844,0,16-7.156,16-16v-32  C288,231.156,280.844,224,272,224z M272,352h-32c-8.844,0-16,7.156-16,16v32c0,8.844,7.156,16,16,16h32c8.844,0,16-7.156,16-16v-32  C288,359.156,280.844,352,272,352z M400,224h-32c-8.844,0-16,7.156-16,16v32c0,8.844,7.156,16,16,16h32c8.844,0,16-7.156,16-16v-32  C416,231.156,408.844,224,400,224z M400,352h-32c-8.844,0-16,7.156-16,16v32c0,8.844,7.156,16,16,16h32c8.844,0,16-7.156,16-16v-32  C416,359.156,408.844,352,400,352z M112,96h32c8.844,0,16-7.156,16-16V16c0-8.844-7.156-16-16-16h-32c-8.844,0-16,7.156-16,16v64  C96,88.844,103.156,96,112,96z M512,128v320c0,35.344-28.656,64-64,64H64c-35.344,0-64-28.656-64-64V128c0-35.344,28.656-64,64-64  h16v16c0,17.625,14.359,32,32,32h32c17.641,0,32-14.375,32-32V64h160v16c0,17.625,14.375,32,32,32h32c17.625,0,32-14.375,32-32V64  h16C483.344,64,512,92.656,512,128z M480,192c0-17.625-14.344-32-32-32H64c-17.641,0-32,14.375-32,32v256c0,17.656,14.359,32,32,32  h384c17.656,0,32-14.344,32-32V192z M368,96h32c8.844,0,16-7.156,16-16V16c0-8.844-7.156-16-16-16h-32c-8.844,0-16,7.156-16,16v64  C352,88.844,359.156,96,368,96z"/>
            ';
    return '<h4><a  style="display: inline-flex;align-items: center; margin-right: 10px;" href="' . admin_url( 'admin-ajax.php?action=qem_download_ics&id=' . $id ) . '" target="_blank">' . $cal_ics_svg . '<span style="margin-left: 3px;">' . $label . '</span>' . '</a></h4>';
}

function qem_download_ics()
{
    
    if ( isset( $_GET['id'] ) ) {
        $post = get_post( $_GET['id'] );
        header( 'Content-Type: text/calendar' );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: filename="' . $post->post_title . '.ics' . '"' );
        echo  qem_ics( $post ) ;
        exit;
    }
    
    return false;
}

function qem_ics( $post )
{
    // @TODO this will use local time but if you calandar is different to site it will be wrong
    // add Z to dates and convert to UTC
    $display = event_get_stored_display();
    $summary = $post->post_title;
    $eventstart = get_post_meta( $post->ID, 'event_date', true );
    if ( !$eventstart ) {
        $eventstart = time();
    }
    $start = get_post_meta( $post->ID, 'event_start', true );
    $date = date( 'Ymd\\T', $eventstart );
    $time = qem_time( $start );
    $time = date( 'His', $time );
    $datestart = $date . $time;
    $dateend = get_post_meta( $post->ID, 'event_end_date', true );
    $address = get_post_meta( $post->ID, 'event_address', true );
    $url = get_permalink();
    $description = get_post_meta( $post->ID, 'event_desc', true );
    $filename = $post->post_title . '.ics';
    
    if ( !$dateend ) {
        $dateend = $eventstart;
        $finish = get_post_meta( $post->ID, 'event_finish', true );
        $date = date( 'Ymd\\T', $eventstart );
        $time = qem_time( $finish );
        $time = date( 'His', $time );
        $dateend = $date . $time;
    } else {
        $finish = get_post_meta( $post->ID, 'event_finish', true );
        $date = date( 'Ymd\\T', $dateend );
        $time = qem_time( $finish );
        $time = date( 'His', $time );
        $dateend = $date . $time;
    }
    
    $ics = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:' . uniqid() . '
DTSTAMP:' . dateToCal( time() ) . '
DTSTART:' . $datestart . '
DTEND:' . $dateend . '
LOCATION:' . $address . '
DESCRIPTION:' . $description . '
URL;VALUE=URI:' . $url . '
SUMMARY:' . $summary . '
END:VEVENT
END:VCALENDAR';
    return $ics;
}

// Generates and downloads the CSV file
function qem_add_to_calendar()
{
    qem_generate_csv();
    die;
}

function qem_generate_csv()
{
    global  $qem_fs ;
    
    if ( isset( $_POST['qem_create_ics'] ) ) {
        $ics = $_POST['qem_ics'];
        $filename = sanitize_text_field( $_POST['qem_filename'] );
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Type: text/csv' );
        $fh = fopen( "php://output", 'w' );
        fwrite( $fh, $ics );
        fclose( $fh );
        exit;
    }
    
    
    if ( isset( $_POST['qem_download_csv'] ) ) {
        $event = (int) $_POST['qem_download_form'];
        $title = sanitize_text_field( $_POST['qem_download_title'] );
        //$register = qem_get_stored_register();
        $register = get_custom_registration_form( $event );
        $payment = qem_get_stored_payment();
        $sort = explode( ',', $register['sort'] );
        $filename = urlencode( $title . '.csv' );
        if ( !$title ) {
            $filename = urlencode( 'default.csv' );
        }
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Type: text/csv' );
        $outstream = fopen( "php://output", 'w' );
        $message = get_option( 'qem_messages_' . $event );
        if ( !is_array( $message ) ) {
            $message = array();
        }
        $headerrow = array();
        foreach ( $sort as $name ) {
            switch ( $name ) {
                case 'field1':
                    if ( $register['usename'] ) {
                        array_push( $headerrow, $register['yourname'] );
                    }
                    break;
                case 'field2':
                    if ( $register['usemail'] ) {
                        array_push( $headerrow, $register['youremail'] );
                    }
                    break;
                case 'field3':
                    if ( $register['$fields'] ) {
                        $dashboard .= '<th>' . $register['yourattend'] . '</th>';
                    }
                    break;
                case 'field4':
                    if ( $register['usetelephone'] ) {
                        array_push( $headerrow, $register['yourtelephone'] );
                    }
                    break;
                case 'field5':
                    if ( $register['useplaces'] ) {
                        array_push( $headerrow, $register['yourplaces'] );
                    }
                    if ( $register['usemorenames'] ) {
                        array_push( $headerrow, $register['morenames'] );
                    }
                    break;
                case 'field6':
                    if ( $register['usemessage'] ) {
                        array_push( $headerrow, $register['yourmessage'] );
                    }
                    break;
                case 'field9':
                    if ( $register['useblank1'] ) {
                        array_push( $headerrow, $register['yourblank1'] );
                    }
                    break;
                case 'field10':
                    if ( $register['useblank2'] ) {
                        array_push( $headerrow, $register['yourblank2'] );
                    }
                    break;
                case 'field11':
                    if ( $register['usedropdown'] ) {
                        array_push( $headerrow, $register['yourdropdown'] );
                    }
                    break;
                case 'field14':
                    if ( $register['useselector'] ) {
                        array_push( $headerrow, $register['yourselector'] );
                    }
                    break;
                case 'field12':
                    if ( $register['usenumber1'] ) {
                        array_push( $headerrow, $register['yournumber1'] );
                    }
                    break;
                case 'field16':
                    if ( $register['usechecks'] ) {
                        array_push( $headerrow, $register['checkslabel'] );
                    }
                    break;
                case 'field17':
                    if ( $register['usedonation'] ) {
                        array_push( $headerrow, $register['donation'] );
                    }
                    break;
            }
        }
        array_push( $headerrow, 'Date Sent' );
        fputcsv(
            $outstream,
            $headerrow,
            ',',
            '"'
        );
        foreach ( $message as $value ) {
            $cells = array();
            $value['morenames'] = preg_replace( "/\r|\n/", ", ", $value['morenames'] );
            foreach ( $sort as $name ) {
                switch ( $name ) {
                    case 'field1':
                        if ( $register['usename'] ) {
                            array_push( $cells, $value['yourname'] );
                        }
                        break;
                    case 'field2':
                        if ( $register['usemail'] ) {
                            array_push( $cells, $value['youremail'] );
                        }
                        break;
                    case 'field3':
                        if ( $register['useattend'] ) {
                            $content .= '<td>' . $value['notattend'] . '</td>';
                        }
                        break;
                    case 'field4':
                        if ( $register['usetelephone'] ) {
                            array_push( $cells, $value['yourtelephone'] );
                        }
                        break;
                    case 'field5':
                        if ( $register['useplaces'] ) {
                            array_push( $cells, $value['yourplaces'] );
                        }
                        if ( $register['usemorenames'] ) {
                            array_push( $cells, $value['morenames'] );
                        }
                        break;
                    case 'field6':
                        if ( $register['usemessage'] ) {
                            array_push( $cells, $value['yourmessage'] );
                        }
                        break;
                    case 'field9':
                        if ( $register['useblank1'] ) {
                            array_push( $cells, $value['yourblank1'] );
                        }
                        break;
                    case 'field10':
                        if ( $register['useblank2'] ) {
                            array_push( $cells, $value['yourblank2'] );
                        }
                        break;
                    case 'field11':
                        if ( $register['usedropdown'] ) {
                            array_push( $cells, $value['yourdropdown'] );
                        }
                        break;
                    case 'field14':
                        if ( $register['useselector'] ) {
                            array_push( $cells, $value['yourselector'] );
                        }
                        break;
                    case 'field12':
                        if ( $register['usenumber1'] ) {
                            array_push( $cells, $value['yournumber1'] );
                        }
                        break;
                    case 'field16':
                        if ( $register['usechecks'] ) {
                            array_push( $cells, $value['checkslist'] );
                        }
                        break;
                    case 'field17':
                        if ( $register['usedonation'] ) {
                            array_push( $cells, $value['donation_amount'] );
                        }
                        break;
                }
            }
            array_push( $cells, $value['sentdate'] );
            fputcsv(
                $outstream,
                $cells,
                ',',
                '"'
            );
        }
        fclose( $outstream );
        exit;
    }

}

add_action( 'admin_menu', 'event_page_init' );
add_filter( 'the_content', 'get_event_content' );
function qem_add_role_caps()
{
    qem_add_role();
    $roles = array( 'administrator', 'editor', 'event-manager' );
    foreach ( $roles as $item ) {
        $role = get_role( $item );
        
        if ( null !== $role ) {
            $role->add_cap( 'read' );
            $role->add_cap( 'read_event' );
            $role->add_cap( 'read_private_event' );
            $role->add_cap( 'edit_event' );
            $role->add_cap( 'edit_events' );
            $role->add_cap( 'edit_others_events' );
            $role->add_cap( 'edit_published_events' );
            $role->add_cap( 'publish_events' );
            $role->add_cap( 'delete_events' );
            $role->add_cap( 'delete_others_events' );
            $role->add_cap( 'delete_private_events' );
            $role->add_cap( 'delete_published_events' );
            $role->add_cap( 'manage_categories' );
            $role->add_cap( 'upload_files' );
            $role->add_cap( 'edit_posts' );
        }
    
    }
}

function qem_users( $output )
{
    global  $post ;
    
    if ( $post->post_type == 'event' ) {
        $users = get_users();
        $output = "<select id='post_author_override' name='post_author_override' class=''>";
        foreach ( $users as $user ) {
            $sel = ( $post->post_author == $user->ID ? "selected='selected'" : '' );
            $output .= '<option value="' . $user->ID . '"' . $sel . '>' . $user->user_login . '</option>';
        }
        $output .= "</select>";
    }
    
    return $output;
}

function qem_add_role()
{
    remove_role( 'event-manager' );
    add_role( 'event-manager', 'Event Manager', array(
        'read'           => true,
        'edit_posts'     => false,
        'edit_event'     => true,
        'edit_events'    => true,
        'publish_events' => true,
        'delete_events'  => true,
    ) );
}

register_activation_hook( __FILE__, 'qem_add_role' );
add_action( 'template_redirect', 'qem_ipn' );
function qem_ipn()
{
    global  $qem_fs ;
    if ( !isset( $_GET['qem_ipn'] ) ) {
        return;
    }
    $payment = qem_get_stored_payment();
    if ( !qem_get_element( $payment, 'ipn', false ) ) {
        return;
    }
    if ( !defined( "DEBUG" ) ) {
        define( "DEBUG", 0 );
    }
    if ( !defined( "LOG_FILE" ) ) {
        define( "LOG_FILE", "./ipn.log" );
    }
    $raw_post_data = file_get_contents( 'php://input' );
    $raw_post_array = explode( '&', $raw_post_data );
    $myPost = array();
    foreach ( $raw_post_array as $keyval ) {
        $keyval = explode( '=', $keyval );
        if ( count( $keyval ) == 2 ) {
            $myPost[$keyval[0]] = urldecode( $keyval[1] );
        }
    }
    // see https://developer.paypal.com/docs/ipn/integration-guide/ht-ipn/#do-it
    $req = 'cmd=_notify-validate';
    if ( function_exists( 'get_magic_quotes_gpc' ) ) {
        $get_magic_quotes_exists = true;
    }
    foreach ( $myPost as $key => $value ) {
        
        if ( $get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1 ) {
            $value = urlencode( stripslashes( $value ) );
        } else {
            $value = urlencode( $value );
        }
        
        $req .= "&{$key}={$value}";
    }
    
    if ( qem_get_element( $payment, 'sandbox', false ) ) {
        $paypal_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        $paypal_url = "https://ipnpb.paypal.com/cgi-bin/webscr";
    }
    
    $response = wp_remote_post( $paypal_url, array(
        'timeout' => 30,
        'body'    => $req,
    ) );
    
    if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
        if ( DEBUG == true ) {
            error_log( date( '[Y-m-d H:i e] ' ) . "Can't connect to PayPal to validate IPN message: Indetermined" . PHP_EOL, 3, LOG_FILE );
        }
        return;
    }
    
    $status = wp_remote_retrieve_body( $response );
    
    if ( DEBUG == true ) {
        error_log( date( '[Y-m-d H:i e] ' ) . "HTTP request of validation request:  for IPN payload: {$req}" . print_r( wp_remote_retrieve_headers( $response ), true ) . PHP_EOL, 3, LOG_FILE );
        error_log( date( '[Y-m-d H:i e] ' ) . "HTTP response of validation request: {$status}" . PHP_EOL, 3, LOG_FILE );
    }
    
    
    if ( 'VERIFIED' == $status ) {
        $custom = sanitize_text_field( $_POST['custom'] );
        $args = array(
            'post_type'      => 'event',
            'posts_per_page' => -1,
        );
        query_posts( $args );
        if ( have_posts() ) {
            while ( have_posts() ) {
                the_post();
                $id = get_the_id();
                $message = get_option( 'qem_messages_' . $id );
                
                if ( $message ) {
                    $count = count( $message );
                    for ( $i = 0 ;  $i <= $count ;  $i++ ) {
                        
                        if ( $message[$i]['ipn'] == $custom ) {
                            $message[$i]['ipn'] = 'Paid';
                            $auto = qem_get_stored_autoresponder();
                            $register = get_custom_registration_form( $id );
                            $addons = qem_get_addons();
                            $payment = qem_get_stored_payment();
                            $values = array(
                                'yourname'        => $message[$i]['yourname'],
                                'youremail'       => $message[$i]['youremail'],
                                'yourtelephone'   => $message[$i]['yourtelephone'],
                                'yourmessage'     => $message[$i]['yourmessage'],
                                'yourplaces'      => $message[$i]['yourplaces'],
                                'yourblank1'      => $message[$i]['yourblank1'],
                                'yourdropdown'    => $message[$i]['yourdropdown'],
                                'yourselector'    => $message[$i]['yourselector'],
                                'yournumber1'     => $message[$i]['yournumber1'],
                                'morenames'       => $message[$i]['morenames'],
                                'ignore'          => $message[$i]['ignore'],
                                'donation_amount' => $message[$i]['donation_amount'],
                            );
                            $date = get_post_meta( $id, 'event_date', true );
                            $enddate = get_post_meta( $id, 'event_end_date', true );
                            $date = date_i18n( "d M Y", $date );
                            $enddate = date_i18n( "d M Y", $enddate );
                            $start = get_post_meta( $id, 'event_start', true );
                            $finish = get_post_meta( $id, 'event_finish', true );
                            
                            if ( $auto['enable'] && $message[$i]['youremail'] && $auto['whenconfirm'] == 'afterpayment' ) {
                                $content = qem_build_event_message( $values, $register );
                                qem_send_confirmation(
                                    $auto,
                                    $values,
                                    $content,
                                    $register,
                                    $id
                                );
                            }
                            
                            if ( $auto['whenconfirm'] == 'afterpaymnet' ) {
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
                            update_option( 'qem_messages_' . $id, $message );
                        }
                    
                    }
                }
            
            }
        }
    }

}

function qem_add_custom_types( $query )
{
    
    if ( !is_admin() && $query->is_category() || $query->is_tag() && $query->is_main_query() ) {
        $query->set( 'post_type', array( 'post', 'event', 'nav_menu_item' ) );
        return $query;
    }

}

function event_plugin_action_links( $links, $file )
{
    
    if ( $file == QUICK_EVENT_MANAGER_PLUGIN_FILE ) {
        $event_links = '<a href="' . get_admin_url() . 'options-general.php?page=' . QUICK_EVENT_MANAGER_PLUGIN_NAME . '">' . __( 'Settings', 'quick-event-manager' ) . '</a>';
        array_unshift( $links, $event_links );
    }
    
    return $links;
}

function qem_flush_rules()
{
    event_register();
    flush_rewrite_rules();
}

function qem_add_custom_post_type_to_query( $query )
{
    if ( is_home() ) {
        $query->set( 'post_type', array( 'post', 'event' ) );
    }
}

$display = event_get_stored_display();
if ( $display['recentposts'] ) {
    add_action( 'pre_get_posts', 'qem_add_custom_post_type_to_query' );
}
function qem_enqueue_scripts()
{
    $style = qem_get_stored_style();
    wp_enqueue_style( 'event_style', plugins_url( 'quick-event-manager.css', __FILE__ ), null );
    wp_enqueue_script(
        'event_script',
        plugins_url( 'quick-event-manager.js', __FILE__ ),
        array( 'jquery' ),
        false,
        true
    );
    $ic = qem_get_incontext();
    switch ( $ic['useapi'] ) {
        case 'paypal':
            wp_register_script(
                'paypal_checkout',
                '//www.paypalobjects.com/api/checkout.js',
                array(),
                false,
                true
            );
            break;
        case 'stripe':
            wp_register_script(
                'stripe_checkout',
                '//js.stripe.com/v3/',
                array(),
                false,
                true
            );
            break;
    }
    $qemkey = get_option( 'qem_freemius_state' );
    
    if ( $style['location'] == 'php' ) {
        wp_enqueue_style( 'qcf_custom_style', plugins_url( 'quick-event-manager-custom.css', __FILE__ ) );
    } else {
        add_action( 'wp_head', 'qem_head_css' );
    }
    
    wp_enqueue_script( 'jquery-ui-datepicker' );
    
    if ( isset( $qemkey['authorised'] ) && $qemkey['authorised'] ) {
        $guest = qem_stored_guest();
        if ( !$guest['noui'] ) {
            wp_enqueue_style( 'jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
        }
    }

}

function qem_external_permalink( $link, $post )
{
    $meta = get_post_meta( $post->ID, 'event_link', true );
    $url = esc_url( filter_var( $meta, FILTER_VALIDATE_URL ) );
    return ( $url ? $url : $link );
}

function get_event_content( $content )
{
    global  $post ;
    $pw = get_post_meta( $post->ID, 'event_password_details', true );
    
    if ( post_password_required( $post ) && $pw ) {
        return get_the_password_form();
    } else {
        $atts = array(
            'links'          => 'off',
            'size'           => '',
            'headersize'     => '',
            'settings'       => 'checked',
            'fullevent'      => 'fullevent',
            'images'         => '',
            'fields'         => '',
            'widget'         => '',
            'cb'             => '',
            'vanillawidget'  => '',
            'linkpopup'      => '',
            'thisday'        => '',
            'popup'          => '',
            'vw'             => '',
            'categoryplaces' => '',
            'fulllist'       => '',
            'thisisapopup'   => '',
            'listplaces'     => true,
            'calendar'       => false,
            'fullpopup'      => false,
            'grid'           => '',
        );
        if ( is_singular( 'event' ) ) {
            $content = qem_event_construct( $atts );
        }
        return $content;
    }

}

function qem_get_next( $rules, $current_date )
{
    $frequency = $rules['frequency'];
    $target = $rules['target'];
    $for = $rules['for'];
    // Format the string based on the given values
    $string = "";
    switch ( $target ) {
        case 'Day':
            $string = 'Tomorrow';
            break;
        case 'Week':
            $string = '+1 Week';
            break;
        case 'Month':
            $string = '+1 Month';
            break;
        default:
            // week day
            $string = $target;
            
            if ( $frequency != 'Every' ) {
                $month = 'OF THIS MONTH';
                $string = $frequency . ' ' . $string;
                /*
                	Run a quick test
                
                	Testing if the returned value is < start date, if so, change the string
                */
                if ( strtotime( $string . ' ' . $month, $current_date ) <= $current_date ) {
                    $month = 'OF NEXT MONTH';
                }
                $string = $string . ' ' . $month;
            } else {
                $string = 'Next ' . $string;
            }
            
            break;
    }
    return strtotime( $string, $current_date );
}

function qem_get_end( $rules )
{
    $end = strtotime( "+" . $rules['number'] . " " . $rules['for'], $rules['start'] );
    if ( false === $end ) {
        $end = time();
    }
    return $end;
}

function qem_duplicate_new_post( $posts, $post_id, $publish )
{
    //  event date can be either string or epoch
    
    if ( is_numeric( $posts['event_date'] ) && $posts['event_date'] >= time() ) {
        $start = $posts['event_date'];
    } else {
        $start = strtotime( $posts['event_date'] );
        if ( false === $start ) {
            $start = time();
        }
    }
    
    $rules = array(
        'frequency' => $posts['thenumber'],
        'target'    => $posts['theday'],
        'number'    => (int) $posts['therepetitions'] + 1,
        'for'       => $posts['thewmy'],
        'start'     => $start,
        'end'       => 0,
    );
    $rules['end'] = qem_get_end( $rules );
    $dates = array();
    $current_time = $rules['start'];
    while ( ($time = qem_get_next( $rules, $current_time )) < $rules['end'] ) {
        $current_time = $time;
        if ( $time < $rules['start'] ) {
            continue;
        }
        qem_create_post(
            $time,
            $post_id,
            $publish,
            false
        );
        $dates[] = array(
            'time' => $time,
            'date' => date( 'l F jS, Y', $time ),
        );
    }
}

function qem_create_post(
    $date,
    $post_id,
    $publish,
    $duplicate
)
{
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;
    $post = get_post( $post_id );
    $new_post = $args = array(
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
        'post_author'    => $new_post_author,
        'post_content'   => $post->post_content,
        'post_excerpt'   => $post->post_excerpt,
        'post_name'      => $post->post_name,
        'post_parent'    => $post->post_parent,
        'post_password'  => $post->post_password,
        'post_status'    => $publish,
        'post_title'     => $post->post_title,
        'post_type'      => $post->post_type,
        'to_ping'        => $post->to_ping,
        'menu_order'     => $post->menu_order,
    );
    $new_post_id = wp_insert_post( $new_post );
    $taxonomies = get_object_taxonomies( $post->post_type );
    foreach ( $taxonomies as $taxonomy ) {
        $post_terms = wp_get_object_terms( $post_id, $taxonomy );
        for ( $i = 0 ;  $i < count( $post_terms ) ;  $i++ ) {
            wp_set_object_terms(
                $new_post_id,
                $post_terms[$i]->slug,
                $taxonomy,
                true
            );
        }
    }
    $postmeta = get_post_meta( $post_id );
    // remove times from date stamps to get true days difference
    $end_diff = (int) $postmeta['event_end_date'][0] - qem_time( $postmeta['event_finish'][0] ) - ((int) $postmeta['event_date'][0] - qem_time( $postmeta['event_start'][0] ));
    $end_date = ( $end_diff <= 0 ? '' : $date + $end_diff );
    // now add back times
    $postmeta['event_date'][0] = $date + qem_time( $postmeta['event_start'][0] );
    $postmeta['event_end_date'][0] = $end_date;
    if ( $end_date !== '' ) {
        $postmeta['event_end_date'][0] = $end_date + qem_time( $postmeta['event_finish'][0] );
    }
    foreach ( $postmeta as $key => $value ) {
        update_post_meta( $new_post_id, $key, $value[0] );
    }
    return $new_post_id;
}

function qem_get_element( $array, $key, $default = '' )
{
    if ( !is_array( $array ) ) {
        return $array;
    }
    if ( array_key_exists( $key, $array ) ) {
        return $array[$key];
    }
    return $default;
}

function qem_wp_mail(
    $type,
    $qem_email,
    $title,
    $content,
    $headers
)
{
    add_action(
        'wp_mail_failed',
        function ( $wp_error ) {
        /**  @var $wp_error \WP_Error */
        if ( defined( 'WP_DEBUG' ) && true == WP_DEBUG && is_wp_error( $wp_error ) ) {
            trigger_error( 'QEM Email - wp_mail error msg : ' . $wp_error->get_error_message(), E_USER_WARNING );
        }
    },
        10,
        1
    );
    if ( defined( 'WP_DEBUG' ) && true == WP_DEBUG ) {
        trigger_error( 'QEM Email message about to send: ' . $type . ' To: ' . $qem_email, E_USER_NOTICE );
    }
    $decode_title = html_entity_decode( $title, ENT_QUOTES );
    $res = wp_mail(
        $qem_email,
        $decode_title,
        $content,
        $headers
    );
    if ( defined( 'WP_DEBUG' ) && true == WP_DEBUG ) {
        
        if ( true === $res ) {
            trigger_error( 'QEM Email - wp_mail responded OK : ' . $type . ' To: ' . $qem_email, E_USER_NOTICE );
        } else {
            trigger_error( 'QEM Email - wp_mail responded FAILED to send : ' . $type . ' To: ' . $qem_email, E_USER_WARNING );
        }
    
    }
}

function qem_sanitize_email_list( $email )
{
    $qem_email_in = explode( ',', sanitize_text_field( $email ) );
    $qem_email = array();
    foreach ( $qem_email_in as $email ) {
        $out = sanitize_email( $email );
        if ( !empty($out) ) {
            $qem_email[] = $out;
        }
    }
    return implode( ',', $qem_email );
}

/**
 * Recursive sanitation for text or array
 *
 * @param $array_or_string (array|string)
 *
 * @return mixed
 * @since  0.1
 */
function qem_sanitize_text_or_array_field( $array_or_string )
{
    
    if ( is_string( $array_or_string ) ) {
        $array_or_string = sanitize_text_field( $array_or_string );
    } elseif ( is_array( $array_or_string ) ) {
        foreach ( $array_or_string as $key => &$value ) {
            
            if ( is_array( $value ) ) {
                $value = qem_sanitize_text_or_array_field( $value );
            } else {
                $value = sanitize_text_field( $value );
            }
        
        }
    }
    
    return $array_or_string;
}
