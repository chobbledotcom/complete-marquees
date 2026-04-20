<section class="testimonials">

  <div class="container--large">

    <div class="grid">

      <div class="grid__item--whole">
        <h2>What our customers say:</h2>
      </div><!--
      --><?php
      $args = array( 'post_type' => 'testimonials', 'posts_per_page' => 3 );
      $loop = new WP_Query( $args );
      while ( $loop->have_posts() ) : $loop->the_post(); ?><!--
      --><div class="grid__item--third" itemscope itemtype="http://schema.org/Service">
        <div class="testimonial" itemprop="review" itemscope itemtype="http://schema.org/Review">
        <article itemprop="description">
          <?php echo excerpt(50); ?>
        </article>
        </div>
      </div><!--
      --><?php endwhile; ?><!--
      --></div>
      
  </div>

</section>

