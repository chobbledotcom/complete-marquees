<aside>

  <div class="content">
  
    <?php if (!is_page('packages')): ?>
    <div class="sidebar_cta">
      <a href="/contact-us/">
        <div class="upper orange">
          From small gatherings to a royal wedding, we have the right marquee whatever the event.
        </div>
        <div class="lower white">
            Packages from only
            <span>£330</span>
        </div>
        </a>
      </div>
    <?php endif; ?>

      <div class="sidebar_cta">

        <div class="upper">
          Find out why capri marquees have become the most popular marquee for any event.
        </div>
        <div class="lower clickthrough">
          <a href="/contact-us/" onclick="ga('send', 'event', 'CTA', 'click', 'Enquire Today');">
            Enquire Today <i class="fa fa-angle-double-right" aria-hidden="true"></i>
          </a>
        </div>
        
      </div>

      <div class="sidebar__testimonials">
        <h2>What our customers have to say...</h2>

        <?php
        $args = array( 'post_type' => 'testimonials', 'posts_per_page' => 2 );
        $loop = new WP_Query( $args );
        while ( $loop->have_posts() ) : $loop->the_post(); ?>

        <div itemscope itemtype="http://schema.org/Service">
          <div itemprop="review" itemscope itemtype="http://schema.org/Review">
            <article itemprop="description">
              <?php the_content(); ?>
            </article>
          </div>
        </div>
        
        <?php endwhile; ?>

      </div>

    </div>

</aside>