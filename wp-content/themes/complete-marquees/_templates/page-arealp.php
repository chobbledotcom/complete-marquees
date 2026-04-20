<?php

//  Template Name: Area LP

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header('lp'); ?>

<?php while ( have_posts() ) : the_post(); ?>

<section class="section--whole hero capri-bg">
    <h1><?php the_title(); ?></h1>
    <p><?php echo SmartMetaBox::get('sub_title');?></p>
    <a href="#form" class="btn">Book Today</a>
</section>
<section class="section--whole pad">
	<div class="container--small">
		<h2>Complete your event</h2>
		<p><?php the_content(); ?></p>
		<div class="lptable">
		<h2>What you get :</h2>
		<ul>
			<li>Your choice of gazebo and marquee to complete your event</li>
			<li>Delivery and setup </li>
			<li>Additional equipment available, including seating, flooring and lighting</li>
			<li>Packages tailored to your event’s needs </li>
			<li>Friendly and fast service at excellent prices</li>
		</ul>
		</div>
		<a href="#form" class="btn--full">Book Today</a>
	</div>
</section>	
<section class="section--whole image_bar">
	<div class="grid--full">
		<div class="grid__item--quarter">
			<img src="/wp-content/themes/complete-marquees/img/imgbar/internal1.jpg" alt="Internal Marquee Shot">
		</div><!--
		--><div class="grid__item--quarter">
			<img src="/wp-content/themes/complete-marquees/img/imgbar/pagoda1.jpg" alt="Pagoda Marquee">
		</div><!--
		--><div class="grid__item--quarter">
			<img src="/wp-content/themes/complete-marquees/img/imgbar/nightshot.jpg" alt="Marquee at night">
		</div><!--
		--><div class="grid__item--quarter">
			<img src="/wp-content/themes/complete-marquees/img/imgbar/internal2.jpg" alt="Internal Marquee Photo">
		</div>
	</div>
</section>
<section class="setion--whole lp-testimonials pad">
	<div class="container--large">
		<h2>What our customers say:</h2>
		<div class="grid">
			<div class="grid__item--third">
				<div class="lp_testimonial">
					<p>“Just a quick e-mail to say how happy i was with the marquee’s, they looked fantastic, your team were polite on set up and take down, if we ever need a marquee again, we know where to call and will recommend you to all my family and friends.”</p>
					<span class="name">Judith</span>
				</div>
			</div><!--
			--><div class="grid__item--third">
				<div class="lp_testimonial">
					<p>“This is somewhat delayed thanks to the wonderful teams that you sent with the marquee for my party last weekend. We had a lovely party and I’m still revelling in the joy of having everyone in one place at the the same time! Please pass on my thanks – the marquee looked incredible.”</p>
					<span class="name">Judy</span>
				</div>
			</div><!--
			--><div class="grid__item--third">
				<div class="lp_testimonial">
					<p>“Just a short note to say how great the marquee hire was on Saturday. The guys you sent to set-up were really friendly and efficient. The pirates ahoy was a complete success! Many thanks, and if / when we’re in need of a marquee again, I will be in touch. Kind regards, Lucy”</p>
					<span class="name">Lucy</span>
				</div>
			</div>
		</div>
	</div>
</section>
<section id="form" class="section--whole pad">
	<div class="container--small">
		<h2>Enquire about your special event now</h2>
		<p>Please complete this short form to enquire about any of our selection of marquees. We supply marquees for any special event or occasion.</p>
		<?php echo do_shortcode('[contact-form-7 id="411" title="LP Form"]'); ?>
          
	</div>
</section>

<?php endwhile; ?>

<?php get_footer('lp'); ?>