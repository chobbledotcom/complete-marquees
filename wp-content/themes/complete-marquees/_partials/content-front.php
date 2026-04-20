<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  This is the template part used for displaying the home
//  page content in 'page.php'

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

?>

<?php get_template_part('_partials/content', 'slider'); ?>

<?php get_template_part('_partials/content', 'tabs'); ?>

<section class="section--whole">

	<main class="container--large"><?php //  This is closed in 'footer.php'  // ?>

	  <div class="content pads">

	    <?php the_content(); ?>

	  </div>

	</main>

</section>

<?php get_template_part('_partials/content', 'testimonials'); ?>
