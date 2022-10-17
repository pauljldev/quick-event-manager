<?php
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );
header( 'Content-Type: text/css' );
$output = qem_generate_css();
echo $output;
?>
