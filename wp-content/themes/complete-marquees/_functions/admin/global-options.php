<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This file adds a Global Settings page to the Wordpress backend, along with
//  a number of options that can be used to easily customise the theme/
//
//  [_1_] Create the Options Page
//  [_2_] Options Page Styling (backend only)
//  [_3_] Options Page Markup
//    [_3.1_] Company Logo
//    [_3.2_] Contact Details
//    [_3.3_] Address
//    [_3.4_] Additional Schema
//    [_3.5_] Save/Submit Options
//  [_4_] Options Page Scripts (backend only)
//  [_5_] Sanitise and Validate Options

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_1_] Create the Options Page  //

function theme_options_init(){
  register_setting( 'theme_options', 'theme_options' ); //  Register settings  //
}

//add settings page to menu
function add_settings_page() {
  add_menu_page( __( 'Theme Options' ), __( 'Global' ), 'manage_options', 'settings', 'theme_settings_page'); //  Add to backend menu  //
}

add_action( 'admin_init', 'theme_options_init' );
add_action( 'admin_menu', 'add_settings_page' );


function theme_settings_page() { //  start settings page  //

if ( ! isset( $_REQUEST['updated'] ) )
$_REQUEST['updated'] = false;

global $color_scheme; //  get variables outside scope  //


//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_2_] Options Page Styling (backend only)  // ?>

<style>

  .theme-options .postbox { margin-right: 2em; }

  .theme-options h2 {
    padding-bottom: 11px;
    font-size: 23px;
  }

  .hndle {
    padding: 0.75em 1em;
    margin: 0;
  }

  .postbox .inside {
    /*border-bottom:1px solid #eee; */
    padding: 1em;
    margin: 0;
  }

  .postbox input, .postbox textarea {
    width: 100%;
    padding: 5px;
    margin: 10px 0;
  }

  .postbox textarea {
    min-width: 100%;
    max-width: 100%;
    min-height: 150px;
  }

  .postbox hr {
    border: 0;
    border-top: 1px solid #eee;
  }

  .header_logo {
    max-width: 200px;
    height: auto;
    margin-top: 16px;
  }

  .logo-upload {
    margin-right: 80px;
  }

  .logo-choose {
    text-align: right;
    margin-top: -41px;
  }

</style>


<?php //  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_3_] Options Page Markup  // ?>


<div class="theme-options">

  <h2><?php _e( 'Global Settings' ) //  Your admin panel title  // ?></h2>

  <?php
    //  show saved options message  //
    if ( false !== $_REQUEST['updated'] ) : ?>
    <div><p><strong><?php _e( 'Options saved' ); ?></strong></p></div>
  <?php endif; ?>

  <form method="post" action="options.php"> <?php //  Start of the options form  // ?>

  <?php settings_fields( 'theme_options' ); ?>
  <?php $options = get_option( 'theme_options' );

  //  [_3.1_] Company Logo  //  ?>

  <div id="postimgdiv" class="postbox">
    <h3 class="hndle">Custom Logo</h3>
    <div class="inside">

      <?php
      //  Load the scripts required for the media uploader  //

      if(function_exists( 'wp_enqueue_media' )){
        wp_enqueue_media();

      }else{
        wp_enqueue_style('thickbox');
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');

      } ?>

      <label for="theme_options[custom_logo]"><b><?php _e( 'Enter a URL or upload an image' ); ?></b></label><br>

      <div class="logo-upload">
        <?php //  Logo URL Field  // ?>
        <input class="header_logo_url" type="text" name="theme_options[custom_logo]" value="<?php esc_attr_e( $options['custom_logo'] ); ?>">
      </div>

      <div class="logo-choose">
        <?php //  Logo Uploader Button  // ?>
        <a class="button button-primary button-large header_logo_upload" href="#">Upload</a>
      </div>

      <img class="header_logo" src="<?php esc_attr_e( $options['custom_logo'] ); ?>" />
    </div>
  </div>

  <?php //  [_3.2_] Contact Details  // ?>

  <div id="postimgdiv" class="postbox">
    <h3 class="hndle">Contact Details</h3>
    <div class="inside">

      <?php //  Phone Number Field  // ?>
      <label for="theme_options[phone_number]"><b><?php _e( 'Telephone Number' ); ?></b></label><br>
      <input id="theme_options[phone_number]" type="text" name="theme_options[phone_number]" value="<?php esc_attr_e( $options['phone_number'] ); ?>" />

    </div>

    <hr>

    <div class="inside">

      <?php //  Company Email  // ?>
      <label for="theme_options[company_email]"><b><?php _e( 'Company Email' ); ?></b></label><br>
      <input id="theme_options[company_email]" type="text" name="theme_options[company_email]" value="<?php esc_attr_e( $options['company_email'] ); ?>" />

    </div>

  </div>

  <?php //  [_3.3_] Address  // ?>

  <div id="postimgdiv" class="postbox">
    <h3 class="hndle">Company Address</h3>

    <div class="inside">

      <?php //  Address Line 1 Field  // ?>
      <label for="theme_options[address_street_1]"><b><?php _e( 'Address Line 1' ); ?></b></label><br>
      <input id="theme_options[address_street_1]" type="text" name="theme_options[address_street_1]" value="<?php esc_attr_e( $options['address_street_1'] ); ?>" />

    </div>

    <hr>

    <div class="inside">

      <?php //  Address Line 2 Field  // ?>
      <label for="theme_options[address_street_2]"><b><?php _e( 'Address Line 2' ); ?></b></label><br>
      <input id="theme_options[address_street_2]" type="text" name="theme_options[address_street_2]" value="<?php esc_attr_e( $options['address_street_2'] ); ?>" />

    </div>

    <hr>

    <div class="inside">

      <?php //  City/Town Field  // ?>
      <label for="theme_options[address_city]"><b><?php _e( 'City / Town' ); ?></b></label><br>
      <input id="theme_options[address_city]" type="text" name="theme_options[address_city]" value="<?php esc_attr_e( $options['address_city'] ); ?>" />

    </div>

    <hr>

    <div class="inside">

      <?php //  County/State Field  // ?>
      <label for="theme_options[address_county]"><b><?php _e( 'County' ); ?></b></label><br>
      <input id="theme_options[address_county]" type="text" name="theme_options[address_county]" value="<?php esc_attr_e( $options['address_county'] ); ?>" />

    </div>

    <div class="inside">

      <?php //  Postcode/Zip Field  // ?>
      <label for="theme_options[address_postcode]"><b><?php _e( 'Postcode' ); ?></b></label><br>
      <input id="theme_options[address_postcode]" type="text" name="theme_options[address_postcode]" value="<?php esc_attr_e( $options['address_postcode'] ); ?>" />

    </div>
  </div>

  <?php //  [_3.5_] Additional Schema  // ?>

  <div id="postimgdiv" class="postbox">
    <h3 class="hndle">Additional Schema</h3>

    <div class="inside">

      <?php //  Opening Times  // ?>
      <label for="theme_options[opening_times]"><b><?php _e( 'Opening Times' ); ?></b></label><br>
      <input id="theme_options[opening_times]" type="text" name="theme_options[opening_times]" value="<?php esc_attr_e( $options['opening_times'] ); ?>" />

    </div>

    <hr>

    <div class="inside">

      <?php //  Maps Link  // ?>
      <label for="theme_options[maps_link]"><b><?php _e( 'Google Maps Link' ); ?></b></label><br>
      <input id="theme_options[maps_link]" type="text" name="theme_options[maps_link]" value="<?php esc_attr_e( $options['maps_link'] ); ?>" />

    </div>

    <hr>

     <div class="inside">

      <?php //  Google+ Page  // ?>
      <label for="theme_options[g_plus_link]"><b><?php _e( 'Google+ Page' ); ?></b></label><br>
      <input id="theme_options[g_plus_link]" type="text" name="theme_options[g_plus_link]" value="<?php esc_attr_e( $options['g_plus_link'] ); ?>" />

    </div>

    <hr>

     <div class="inside">

      <?php //  Facebook+ Page  // ?>
      <label for="theme_options[fb_link]"><b><?php _e( 'Facebook Page' ); ?></b></label><br>
      <input id="theme_options[fb_link]" type="text" name="theme_options[fb_link]" value="<?php esc_attr_e( $options['fb_link'] ); ?>" />

    </div>

    <hr>

     <div class="inside">

      <?php //  Twitter Page  // ?>
      <label for="theme_options[twit_link]"><b><?php _e( 'Twitter Page' ); ?></b></label><br>
      <input id="theme_options[twit_link]" type="text" name="theme_options[twit_link]" value="<?php esc_attr_e( $options['twit_link'] ); ?>" />

    </div>
  </div>

  <?php //  [_3.5_] Save/Submit Options  // ?>

  <p><input class="button button-primary button-large" name="submit" id="submit" value="Save Changes" type="submit"></p>
  </form>

</div>


<?php //  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_4_] Options Page Scripts (backend only)  // ?>


<script>
  jQuery(document).ready(function($) {
    $('.header_logo_upload').click(function(e) {
      e.preventDefault();

      var custom_uploader = wp.media({
        title: 'Custom Logo',
        button: {
          text: 'Upload Logo'
        },
        multiple: false  //  Set this to true to allow multiple files to be selected  //
      })
      .on('select', function() {
        var attachment = custom_uploader.state().get('selection').first().toJSON();
        $('.header_logo').attr('src', attachment.url);
        $('.header_logo_url').val(attachment.url);

      })
      .open();
    });
  });
</script>


<?php } //  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //


//  [_5_] Sanitise and Validate Options  //


function options_validate( $input ) {

  global $select_options, $radio_options;

  if ( ! isset( $input['option1'] ) )
    $input['option1'] = null;
    $input['option1'] = ( $input['option1'] == 1 ? 1 : 0 );
    $input['sometext'] = wp_filter_nohtml_kses( $input['sometext'] );

  if ( ! isset( $input['radioinput'] ) )
    $input['radioinput'] = null;

  if ( ! array_key_exists( $input['radioinput'], $radio_options ) )
    $input['radioinput'] = null;
    $input['sometextarea'] = wp_filter_post_kses( $input['sometextarea'] );

  return $input;
}
