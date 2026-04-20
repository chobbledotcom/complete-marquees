<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that prints a clickable phone number as spcified in the global
//  options page

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_phone_number' ) ) :

function hobbes_phone_number($string) {

  $options = get_option( 'theme_options' );

  if ( $options['phone_number'] != '' ) {

    $tel_number = $options['phone_number'];
    $tel_meta = str_replace( " ", "", $tel_number ); ?>

    <?php _e( $string, 'hobbes' ); ?>

    <div class="telephone__number"><span class="callustoday">Call Us Today</span> <a class="rulertel" href="tel:<?php echo $tel_meta; ?>"><?php echo $tel_number; ?></a></div>

  <?php } }

endif;
