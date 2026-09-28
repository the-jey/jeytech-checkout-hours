<?php
require '/wordpress/wp-load.php';
$fr='fr_FR'===get_locale();
update_option('date_format',$fr?'j F Y':'F j, Y');
update_option('time_format',$fr?'H\\hi':'g:i a');
update_option('woocommerce_price_decimal_sep',$fr?',':'.');
update_option('woocommerce_price_thousand_sep',$fr?' ':',');
update_option('woocommerce_currency_pos',$fr?'right_space':'left');
echo 'Demo locale formatting configured';
