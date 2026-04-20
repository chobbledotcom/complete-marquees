<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that calls prints company information as per 'schema.org',
//  including address, opening times, and phone number.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_full_details' ) ) :

function hobbes_full_details() {
  
  $options = get_option( 'theme_options' ); ?>

      <div class="contact__info">

      <?php $tel_number = $options['phone_number'];
      $tel_meta = str_replace( " ", "", $tel_number ); ?>
        
      <p>Tel: <span class="number"><?php echo $tel_number ?></span></p>
      
      <?php $company_email = $options['company_email']; ?>
      
      <p>Email: <?php echo hide_email('completemarquee@gmail.com'); ?></p>

      </div>  
    
  <?php }
  
endif;