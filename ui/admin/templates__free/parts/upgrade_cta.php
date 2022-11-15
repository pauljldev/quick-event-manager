<?php
/**
 * @var mixed $data Custom data for the template.
 */
$output = '
<div class="qemupgrade"><a href="' . esc_url( $data->freemius->get_upgrade_url() ) . '">
        <h3>' . esc_html__( 'Upgrade QEM from just $2.99 / month', 'quick-event-manager' ) . '<sup>*</sup></h3>
        <p>' . esc_html__( 'Upgrading gives you access the Guest Event creator, CSV uploader, a range of registration reports and downloads, Mailchimp subscriber and Stripe Checkout.', 'quick-event-manager' ) . ' </p>
        <p>' . esc_html__( 'Click to find out more', 'quick-event-manager' ) . '</p>
        <p>* ' . esc_html__( 'single site, when paid annually, excludes taxes', 'quick-event-manager_upsell' ) . '</p>
    </a>
</div>';

$data->template_loader->set_output( $output );
