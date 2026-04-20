<?php

//  Template Name: Packages

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<section class="section--whole pads">

  <main class="container--large">

  <div class="grid--wide">

    <div class="grid__item--two__thirds">

      <div class="content__area">
        <?php marquees_breadcrumbs(); ?>
        <?php the_content(); ?>
      </div>

      <div class="packages__area">

        <div class="package" itemscope itemtype="http://schema.org/Product">
          <div class="info">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('package1_title'); ?></div>
            <div class="list__title" >Package Includes</div>
            <ul>
              <li><?php echo SmartMetaBox::get('package1_item1'); ?></li>
              <li><?php echo SmartMetaBox::get('package1_item2'); ?></li>
              <li><?php echo SmartMetaBox::get('package1_item3'); ?></li>
              <li><?php echo SmartMetaBox::get('package1_item4'); ?></li>
            </ul>

            <a href="/contact-us/" class="btn" itemprop="offers" itemscope itemtype="http://schema.org/Offer">Book Today From Only <span itemprop="priceCurrency" content="GBP">£</span><span itemprop="price" content="<?php echo SmartMetaBox::get('package1_price'); ?>.00"><?php echo SmartMetaBox::get('package1_price'); ?></span></a>
          </div>
          <span class="ribbonholder">
						<div class="ribbon">
							<div class="ribbon-bg">30 Guests</div>
						</div>
						<img src="<?php echo get_template_directory_uri (); ?>/img/packages/30-standing.png" alt="Capri Marquee for 30 Guests." itemprop="image">
          </span>

        </div>

        <div class="package" itemscope itemtype="http://schema.org/Product">
          <div class="info">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('package2_title'); ?></div>
            <div class="list__title">Package Includes</div>
            <ul>
              <li><?php echo SmartMetaBox::get('package2_item1'); ?></li>
              <li><?php echo SmartMetaBox::get('package2_item2'); ?></li>
              <li><?php echo SmartMetaBox::get('package2_item3'); ?></li>
              <li><?php echo SmartMetaBox::get('package2_item4'); ?></li>
              <li><?php echo SmartMetaBox::get('package2_item5'); ?></li>
              <li><?php echo SmartMetaBox::get('package2_item6'); ?></li>
              <li><?php echo SmartMetaBox::get('package2_item7'); ?></li>
            </ul>

            <a href="/contact-us/" class="btn" itemprop="offers" itemscope itemtype="http://schema.org/Offer">Book Today From Only <span itemprop="priceCurrency" content="GBP">£</span><span itemprop="price" content="<?php echo SmartMetaBox::get('package2_price'); ?>.00"><?php echo SmartMetaBox::get('package2_price'); ?></span></a>
          </div>
          <span class="ribbonholder">
						<div class="ribbon">
							<div class="ribbon-bg">40 Guests</div>
						</div>
          <img src="<?php echo get_template_directory_uri (); ?>/img/packages/40-sitting.png" alt="Capri Marquee for 40 Guests." itemprop="image">
					</span>
        </div>

        <div class="package" itemscope itemtype="http://schema.org/Product">
          <div class="info">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('package3_title'); ?></div>
            <div class="list__title">Package Includes</div>
            <ul>
              <li><?php echo SmartMetaBox::get('package3_item1'); ?></li>
              <li><?php echo SmartMetaBox::get('package3_item2'); ?></li>
              <li><?php echo SmartMetaBox::get('package3_item3'); ?></li>
              <li><?php echo SmartMetaBox::get('package3_item4'); ?></li>
              <li><?php echo SmartMetaBox::get('package3_item5'); ?></li>
              <li><?php echo SmartMetaBox::get('package3_item6'); ?></li>
              <li><?php echo SmartMetaBox::get('package3_item7'); ?></li>
            </ul>

            <a href="/contact-us/" class="btn" itemprop="offers" itemscope itemtype="http://schema.org/Offer">Book Today From Only <span itemprop="priceCurrency" content="GBP">£</span><span itemprop="price" content="<?php echo SmartMetaBox::get('package3_price'); ?>.00"><?php echo SmartMetaBox::get('package3_price'); ?></span></a>
          </div>
          <span class="ribbonholder">
						<div class="ribbon">
							<div class="ribbon-bg">80 Guests</div>
						</div>
          <img src="<?php echo get_template_directory_uri (); ?>/img/packages/80.png" alt="Capri Marquee for 80 Guests." itemprop="image">
					</span>
        </div>

        <div class="package" itemscope itemtype="http://schema.org/Product">
          <div class="info">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('package4_title'); ?></div>
            <div class="list__title">Package Includes</div>
            <ul>
              <li><?php echo SmartMetaBox::get('package4_item1'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item2'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item3'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item4'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item5'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item6'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item7'); ?></li>
              <li><?php echo SmartMetaBox::get('package4_item8'); ?></li>
            </ul>

            <a href="/contact-us/" class="btn" itemprop="offers" itemscope itemtype="http://schema.org/Offer">Book Today From Only <span itemprop="priceCurrency" content="GBP">£</span><span itemprop="price" content="<?php echo SmartMetaBox::get('package4_price'); ?>.00"><?php echo SmartMetaBox::get('package4_price'); ?></span></a>
          </div>
          <span class="ribbonholder">
						<div class="ribbon">
							<div class="ribbon-bg">100 Guests</div>
						</div>
          <img src="<?php echo get_template_directory_uri (); ?>/img/packages/100.png" alt="Capri Marquee for 100 Guests." itemprop="image">
					</span>
        </div>

        <div class="package last" itemscope itemtype="http://schema.org/Product">
          <div class="info">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('package5_title'); ?></div>
            <div class="list__title">Package Includes</div>
            <ul>
              <li><?php echo SmartMetaBox::get('package5_item1'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item2'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item3'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item4'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item5'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item6'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item7'); ?></li>
              <li><?php echo SmartMetaBox::get('package5_item8'); ?></li>
            </ul>

            <a href="/contact-us/" class="btn " itemprop="offers" itemscope itemtype="http://schema.org/Offer">Book Today From Only <span itemprop="priceCurrency" content="GBP">£</span><span itemprop="price" content="<?php echo SmartMetaBox::get('package5_price'); ?>.00"><?php echo SmartMetaBox::get('package5_price'); ?></span></a>
          </div>
          <span class="ribbonholder">
						<div class="ribbon">
							<div class="ribbon-bg">150 Guests</div>
						</div>
          	<img src="<?php echo get_template_directory_uri (); ?>/img/packages/150.png" alt="Capri Marquee for 150 Guests." itemprop="image">
					</span>
        </div>

      </div>

    </div><!--
    --><div class="grid__item--third">

      <?php get_sidebar(); ?>

    </div>

  </div>

  </main>

</section>

<?php endwhile; ?>

<?php get_footer(); ?>
