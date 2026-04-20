<section class="tabs">

  <div class="container--large">

    <div class="grid">

      <div class="grid__item--quarter">

        <div id="wedding__marquees" class="card" itemscope itemtype="http://schema.org/Service">
          <img src="<?php echo get_template_directory_uri (); ?>/img/wedding-1.jpg" alt="Wedding Marquees" itemprop="image"/>
          <div class="card__lower">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('wedding_title'); ?></div>
            <div class="text" itemprop="description">
              <?php echo SmartMetaBox::get('wedding_content'); ?>
            </div>
            <a href="/wedding-marquees/" class="btn--small--full--green">Weddings</a>
          </div>
        </div>

      </div><!--
      --><div class="grid__item--quarter">

        <div id="party__marquees" class="card" itemscope itemtype="http://schema.org/Service">
          <img src="<?php echo get_template_directory_uri (); ?>/img/party.jpg" alt="Party Marquees" itemprop="image"/>
          <div class="card__lower">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('party_title'); ?></div>
            <div class="text" itemprop="description">
              <?php echo SmartMetaBox::get('party_content'); ?>
            </div>
            <a href="/party-marquees/" class="btn--small--full--green">Parties</a>
          </div>
        </div>

      </div><!--
      --><div class="grid__item--quarter">

        <div id="event__marquees" class="card" itemscope itemtype="http://schema.org/Service">
          <img src="<?php echo get_template_directory_uri (); ?>/img/corporate.jpg" alt="Corporate Event Marquees" itemprop="image"/>
          <div class="card__lower">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('event_title'); ?></div>
            <div class="text" itemprop="description">
                <?php echo SmartMetaBox::get('event_content'); ?>
            </div>
            <a href="/corporate-event-marquees/" class="btn--small--full--green">Events</a>
          </div>
        </div>

      </div><!--
      --><div class="grid__item--quarter">

        <div id="special__marquees" class="card" itemscope itemtype="http://schema.org/Service">
          <img src="<?php echo get_template_directory_uri (); ?>/img/special.jpg" alt="Special Event Marquees" itemprop="image"/>
          <div class="card__lower">
            <div class="title" itemprop="name"><?php echo SmartMetaBox::get('Special_title'); ?></div>
            <div class="text" itemprop="description">
              <?php echo SmartMetaBox::get('special_content'); ?>
            </div>
            <a href="/special-events/" class="btn--small--full--green">Occasions</a>
          </div>
        </div>

      </div>

    </div>

  </div>

</section>
