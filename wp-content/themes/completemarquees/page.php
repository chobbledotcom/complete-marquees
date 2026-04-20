<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package CompleteMarquees
 */

get_header(); ?>
	<div id="ContentWrapper">
		<div id="primary" class="content-area pageleft">
			
			<main id="main" class="site-main" role="main">

				<?php while ( have_posts() ) : the_post(); ?>

					<?php get_template_part( 'content', 'page' ); ?>

					

				<?php endwhile; // end of the loop. ?>

			</main><!-- #main -->
			
		</div><!-- #primary -->

		<?php get_sidebar(); ?>
	</div>
<?php get_footer(); ?>
