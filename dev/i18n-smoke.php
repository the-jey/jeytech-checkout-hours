<?php
require '/wordpress/wp-load.php';
$has_pack = is_file( WP_LANG_DIR . '/plugins/jeytech-checkout-hours-fr_FR.mo' );
$changed = switch_to_locale( 'fr_FR' );
$expected = array( 'Checkout Hours' => 'Horaires de commande', 'Weekly schedule' => 'Horaires de la semaine', 'Block checkout' => 'Bloquer les commandes', 'Next opening: %s.' => 'Prochaine ouverture : %s.' );
foreach ( $expected as $english => $french ) {
 if ( ( $has_pack ? $french : $english ) !== __( $english, 'jeytech-checkout-hours' ) ) { throw new RuntimeException( 'Language-pack mismatch: ' . $english ); }
}
if ( ! $changed ) { throw new RuntimeException( 'French core locale unavailable' ); }
restore_previous_locale();
if ( 'Checkout Hours' !== __( 'Checkout Hours', 'jeytech-checkout-hours' ) ) { throw new RuntimeException( 'English restoration failed' ); }
$report = $has_pack ? 'i18n-smoke-pack.txt' : 'i18n-smoke-no-pack.txt';
file_put_contents( __DIR__ . '/results/' . $report, 'PASS ' . ( $has_pack ? 'External French WordPress pack' : 'English fallback without a pack' ) . ' and English restoration. WP ' . get_bloginfo( 'version' ) . ', PHP ' . PHP_VERSION . ".\n" );
