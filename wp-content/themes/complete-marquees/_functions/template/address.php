<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that calls prints the address as per 'schema.org' recommendation.
//  The address is set in the Global settings menu of the admin panel.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_address' ) ) :

function hobbes_address_schema($itemscope = 'ul', $itemprop = 'li') {
  
  $options = get_option( 'theme_options' ); ?>
    
    <<?php echo $itemscope; ?> class="address">
     
      <?php
        //  Address Line One  //
        if ( $options['address_street_1'] != '' ) { 
          echo'<'. $itemprop .'>'. $options['address_street_1'] .',&nbsp;</'. $itemprop .'>';
        }
        
        //  Address Line Two  //
        if ( $options['address_street_2'] != '' ) { 
          echo'<'. $itemprop .'>'. $options['address_street_2'] .',&nbsp;</'. $itemprop .'>'; 
        }
        
        //  Town/City  //
        if ( 
          $options['address_city'] != '' ) { echo'<'. $itemprop .'>'. $options['address_city'] .',&nbsp;</'. $itemprop .'>';
        }
        
        //  County/State  //
        if ( $options['address_county'] != '' ) { 
          echo'<'. $itemprop .'>'. $options['address_county'] .',&nbsp;</'. $itemprop .'>';
        }
        
        //  Postcode/Zip  //
        if ( $options['address_postcode'] != '' ) { 
          echo'<'. $itemprop .'>'. $options['address_postcode'] .',&nbsp;</'. $itemprop .'>';
        }
        
        //  Company Number  //
        if ( $options['company_number'] != '' ) { 
          echo'<'. $itemprop .'>Company No. '. $options['company_number'] .'</'. $itemprop .'>';
        }
      ?>
     
    </<?php echo $itemscope; ?>>
    
  <?php }
  
endif;

