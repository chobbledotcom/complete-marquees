<?php
 /*
 Template Name: Full Width
 */
 ?>
<?php get_header(); ?>
	<div id="ContentWrapper">
		<div id="primary" class="content-area full">
			<main id="main" class="site-main" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

					<?php get_template_part( 'content', 'page' ); ?>

					

				<?php endwhile; // end of the loop. ?>

			</main><!-- #main -->
		</div><!-- #primary -->
	</div>
<?php get_footer(); ?>
