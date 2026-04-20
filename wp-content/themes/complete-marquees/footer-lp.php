<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the template for displaying the footer. It is used universally,
//  for all posts and page templates.
//
//  It contains the closing tags for <main> and for the opening .container div.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

?>
<footer>
  <div class="footer__bottom">
    <div class="container--large">
     <a href="tel:02392717925" class="btn mobile_up_hide">Give Us A Call</a>
      <div class="grid">
        <div class="grid__item--third copyright">
          	&copy; Complete Marquees
        </div><!--
        --><div class="grid__item--third exvat__prices">
          Please Note: All prices quoted are exclusive of VAT
        </div><!--
        --><div class="grid__item--third">
          <div class="created__by">
            <span>Created by</span> <a href="http://midasmedia.co.uk" rel="nofollow" target="_blank"><img src="<?php echo get_template_directory_uri (); ?>/img/midas-small.png" alt="Midas Media" /></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
<!--[if lt IE 9]>
<script>svgeezy.init(false, 'png');</script>
<![endif]-->
</body>
</html>
