<?php

require_once dirname( __FILE__ ) . '/scb/load.php';

function _qemnavi_init() {
	load_plugin_textdomain( 'wp-qemnavi' );

	require_once dirname( __FILE__ ) . '/core.php';

	$options = new scbOptions( 'qemnavi_options', __FILE__, array(
		'pages_text'                   => __( 'Page %CURRENT_PAGE% of %TOTAL_PAGES%', 'wp-qemnavi' ),
		'current_text'                 => '%PAGE_NUMBER%',
		'page_text'                    => '%PAGE_NUMBER%',
		'first_text'                   => __( '&laquo; First', 'wp-qemnavi' ),
		'last_text'                    => __( 'Last &raquo;', 'wp-qemnavi' ),
		'prev_text'                    => __( '&laquo;', 'wp-qemnavi' ),
		'next_text'                    => __( '&raquo;', 'wp-qemnavi' ),
		'dotleft_text'                 => __( '...', 'wp-qemnavi' ),
		'dotright_text'                => __( '...', 'wp-qemnavi' ),
		'num_pages'                    => 5,
		'num_larger_page_numbers'      => 3,
		'larger_page_numbers_multiple' => 10,
		'always_show'                  => false,
		'style'                        => 1,
	) );

	QEMNavi_Core::init( $options );

}

scb_init( '_qemnavi_init' );

