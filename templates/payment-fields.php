<?php
/**
 * Cashfree payments fields.
 */

defined( 'ABSPATH' ) || exit;
?>

<p><?php echo wpautop( wptexturize( $gateway->get_description() ) ); ?></p>