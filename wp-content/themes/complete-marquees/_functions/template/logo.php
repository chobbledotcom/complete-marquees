<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that calls and links the logo as per 'schema.org' recommendation.
//  The logo is set in the Global settings menu of the admin panel.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_logo' ) ) :

function hobbes_logo() { ?>
  
  <div class="logo">
     
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
        
        <?php $options = get_option( 'theme_options' );
        
        if ( $options['custom_logo'] != '' )
        
        //  If the logo has been set by the user, use that  //
        
        { ?>
          
          <img src="<?php echo $options['custom_logo']; ?>" alt="<?php bloginfo( 'name' ); ?>">
          
        <?php } else {
       
        //  If the user has not set a custom logo, use the site name and description instead  //
        
        ?>
          
          <h1 class="site-title"><?php bloginfo( 'name' ); ?></h1>
          <h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>
          
        <?php } ?>
        
      </a>
      
    </div>
  
  <?php }
  
endif;