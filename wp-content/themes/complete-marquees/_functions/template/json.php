<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Function that calls the company information which is outputted via 'json',
//  including address, opening times, and phone number.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_json' ) ) :

function hobbes_json() {

  $options = get_option( 'theme_options' );
  $tel_number = $options['phone_number'];
  $logo = $options['custom_logo'];
  $fb = $options['fb_link'];
  $twit = $options['twit_link']; ?>

    <script type="application/ld+json">
      {
        "@context": "http://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php bloginfo( 'name' ); ?>",
        "address": {
          "@type": "PostalAddress",
          "streetAddress": "27 The Oakwood Centre, Downley Road",
          "addressLocality": "Havant",
          "addressRegion": "Hampshire",
          "postalCode": "PO9 2NP"           
        },
        "geo": {
          "@type":"GeoShape",
          "circle":"50.8620115919537 -0.967706117696445 120701"
        },
        "openingHours": [
          "Mo 09:00-20:00",             
          "Tu 09:00-20:00",             
          "We 09:00-20:00",             
          "Th 09:00-20:00",             
          "Fr 09:00-20:00",             
          "Sa 09:00-20:00",             
          "Su 09:00-20:00"             
        ], 

        "telephone": "<?php echo $tel_number; ?>",
        "email":"completemarquee@gmail.com",
        "url": "<?php echo site_url(); ?>",
        
        "logo": "<?php echo $logo; ?>",
        "sameAs" : [ "<?php echo $fb; ?>",
        "<?php echo $twit; ?>"],
        "currenciesAccepted": "GBP",
        "paymentAccepted": [
          "cash",
          "credit card", 
          "debit card"
        ]
      }
    </script>

  <?php }

endif; ?>
