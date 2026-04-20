<?php

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

//  Sets up theme defaults and registers support for various WordPress features.
// 
//  Note that this function is hooked into the after_setup_theme hook, which
//  runs before the init hook. The init hook is too late for some features, such
//  as indicating support for post thumbnails.

//  ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** ***** *****  //

if ( ! function_exists( 'hobbes_setup' ) ) :
  
  function hobbes_setup() {
    
    //  Add default posts and comments RSS feed links to head  //
    add_theme_support( 'automatic-feed-links' );
    
    //  Switch core markup for search form, comment form, and comments to output HTML5  //
    add_theme_support( 'html5', array(
      'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
    ) );
    
    //  Enable support for Post Formats (http://codex.wordpress.org/Post_Formats)  //
    add_theme_support( 'post-formats',
      array(
        'aside',
        'image',
        'video',
        'quote',
        'link',
      )
    );
    
  }
  
endif;

add_action( 'after_setup_theme', 'hobbes_setup' );

//  Finally, we will automatically set the permalink structure  //

function hobbes_update_permalinks() {
  
  if (get_option('permalink_structure') == '') {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure('/%postname%');
    $wp_rewrite->flush_rules();
  }
  
}

add_action( 'after_switch_theme', 'hobbes_update_permalinks' );


//breadcrumbs
function marquees_breadcrumbs() {

  $showOnHome = 1;
  $delimiter = '<span class="delimiter">&raquo;</span>';
  if (is_single()) {
    $home = 'Blog';
  } else {
    $home = 'Home';
  }
  $showCurrent = 1;
  $before = '<span class="current">';
  $after = '</span>';

  global $post;
  if (is_single()) {
    $homeLink = get_bloginfo('url') . '/blog';
  } else {
    $homeLink = get_bloginfo('url');
  }

  if (is_home() || is_front_page()) {

    if ($showOnHome == 1) echo '<div id="crumbs"><p><a href="' . $homeLink . '"> <span> ' . $home . '</span></a> ' . $delimiter . ' <span> &nbsp; Blog</span>  </div>';

  } else {

    echo '<div id="crumbs" ><p><a href="' . $homeLink . '"><span>' . $home . '</span></a> ' . $delimiter . ' ' ;

    if ( is_category() ) {
      $thisCat = get_category(get_query_var('cat'), false);
      if ($thisCat->parent != 0) echo get_category_parents($thisCat->parent, TRUE, ' ' . $delimiter . ' ');
      echo $before . 'All posts in ' . single_cat_title('', false) . '' . $after;

    } elseif ( is_search() ) {
      echo $before . 'Search results for "' . get_search_query() . '"' . $after;

    } elseif ( is_day() ) {
      echo '<a href="' . get_year_link(get_the_time('Y')) . '"><span>' . get_the_time('Y') . '</span></a> ' . $delimiter . ' ';
      echo '<a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '"><span>' . get_the_time('F') . '</span></a> ' . $delimiter . ' ';
      echo $before . get_the_time('d') . $after;

    } elseif ( is_month() ) {
      echo '<a href="' . get_year_link(get_the_time('Y')) . '"><span>' . get_the_time('Y') . '</span></a> ' . $delimiter . ' ';
      echo $before . get_the_time('F') . $after;

    } elseif ( is_year() ) {
      echo $before . get_the_time('Y') . $after;

    } elseif ( is_single() && !is_attachment() ) {
      if ( get_post_type() != 'post' ) {
        $post_type = get_post_type_object(get_post_type());
        $slug = $post_type->rewrite;
        //echo '<a itemprop="url" href="' . $homeLink . '/' . $slug['slug'] . '/"><span itemprop="title">' . $post_type->labels->singular_name . '</span></a>';
        if ($showCurrent == 1) echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;
      } else {
        $cat = get_the_category(); $cat = $cat[0];
        //$cats = get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
        $cats = get_category_parents($cat, TRUE, ' ' . ' ');
        if ($showCurrent == 0) $cats = preg_replace("#^(.+)\s$delimiter\s$#", "$1", $cats);
        echo $cats;
        //if ($showCurrent == 1) echo $before . get_the_title() . $after;

      }

    } elseif ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ) {
      $post_type = get_post_type_object(get_post_type());
      echo $before . $post_type->labels->singular_name . $after;

    } elseif ( is_attachment() ) {
      $parent = get_post($post->post_parent);
      $cat = get_the_category($parent->ID); $cat = $cat[0];
      echo get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
      echo '<a href="' . get_permalink($parent) . '"><span >' . $parent->post_title . '</span></a>';
      if ($showCurrent == 1) echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;

    } elseif ( is_page() && !$post->post_parent ) {
      if ($showCurrent == 1) echo $before . get_the_title() . $after;

    } elseif ( is_page() && $post->post_parent ) {
      $parent_id  = $post->post_parent;
      $breadcrumbs = array();
      while ($parent_id) {
        $page = get_page($parent_id);
        $breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '"><span >' . get_the_title($page->ID) . '</span></a>';
        $parent_id  = $page->post_parent;
      }
      $breadcrumbs = array_reverse($breadcrumbs);
      for ($i = 0; $i < count($breadcrumbs); $i++) {
        echo $breadcrumbs[$i];
        if ($i != count($breadcrumbs)-1) echo ' ' . $delimiter . ' ';
      }
      if ($showCurrent == 1) echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;

    } elseif ( is_tag() ) {
      echo $before . 'All posts tagged "' . single_tag_title('', false) . '"' . $after;

    } elseif ( is_author() ) {
       global $author;
      $userdata = get_userdata($author);
      echo $before . 'All posts by ' . $userdata->display_name . $after;

    } elseif ( is_404() ) {
      echo $before . 'Error 404' . $after;
    }

    if ( get_query_var('paged') ) {
      if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ' (';
      echo __('Page') . ' ' . get_query_var('paged');
      if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ')';
    }

    echo '</p></div>';

  }
}


/*function marquees_breadcrumbs() {

  $showOnHome = 1;
  $delimiter = '<span class="delimiter">&raquo;</span>';
  if (is_single()) {
    $home = 'Blog';
  } else {
    $home = 'Home';
  }
  $showCurrent = 1;
  $before = '<span class="current" itemprop="title">';
  $after = '</span>';

  global $post;
  if (is_single()) {
    $homeLink = get_bloginfo('url') . '/blog';
  } else {
    $homeLink = get_bloginfo('url');
  }

  if (is_home() || is_front_page()) {

    if ($showOnHome == 1) echo '<div id="crumbs"><p><a itemprop="url" href="' . $homeLink . '"> <span itemprop="title"> ' . $home . '</span></a> ' . $delimiter . ' <span itemprop="title"> &nbsp; Blog</span>  </div>';

  } else {

    echo '<div id="crumbs" itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><p><a itemprop="url" href="' . $homeLink . '"><span itemprop="title">' . $home . '</span></a> ' . $delimiter . ' ' ;

    if ( is_category() ) {
      $thisCat = get_category(get_query_var('cat'), false);
      if ($thisCat->parent != 0) echo get_category_parents($thisCat->parent, TRUE, ' ' . $delimiter . ' ');
      echo $before . 'All posts in ' . single_cat_title('', false) . '' . $after;

    } elseif ( is_search() ) {
      echo $before . 'Search results for "' . get_search_query() . '"' . $after;

    } elseif ( is_day() ) {
      echo '<a itemprop="url" href="' . get_year_link(get_the_time('Y')) . '"><span itemprop="title">' . get_the_time('Y') . '</span></a> ' . $delimiter . ' ';
      echo '<a itemprop="url" href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '"><span itemprop="title">' . get_the_time('F') . '</span></a> ' . $delimiter . ' ';
      echo $before . get_the_time('d') . $after;

    } elseif ( is_month() ) {
      echo '<a itemprop="url" href="' . get_year_link(get_the_time('Y')) . '"><span itemprop="title">' . get_the_time('Y') . '</span></a> ' . $delimiter . ' ';
      echo $before . get_the_time('F') . $after;

    } elseif ( is_year() ) {
      echo $before . get_the_time('Y') . $after;

    } elseif ( is_single() && !is_attachment() ) {
      if ( get_post_type() != 'post' ) {
        $post_type = get_post_type_object(get_post_type());
        $slug = $post_type->rewrite;
        //echo '<a itemprop="url" href="' . $homeLink . '/' . $slug['slug'] . '/"><span itemprop="title">' . $post_type->labels->singular_name . '</span></a>';
        if ($showCurrent == 1) echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;
      } else {
        $cat = get_the_category(); $cat = $cat[0];
        //$cats = get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
        $cats = get_category_parents($cat, TRUE, ' ' . ' ');
        if ($showCurrent == 0) $cats = preg_replace("#^(.+)\s$delimiter\s$#", "$1", $cats);
        echo $cats;
        //if ($showCurrent == 1) echo $before . get_the_title() . $after;

      }

    } elseif ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ) {
      $post_type = get_post_type_object(get_post_type());
      echo $before . $post_type->labels->singular_name . $after;

    } elseif ( is_attachment() ) {
      $parent = get_post($post->post_parent);
      $cat = get_the_category($parent->ID); $cat = $cat[0];
      echo get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
      echo '<a itemprop="url" href="' . get_permalink($parent) . '"><span itemprop="title">' . $parent->post_title . '</span></a>';
      if ($showCurrent == 1) echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;

    } elseif ( is_page() && !$post->post_parent ) {
      if ($showCurrent == 1) echo $before . get_the_title() . $after;

    } elseif ( is_page() && $post->post_parent ) {
      $parent_id  = $post->post_parent;
      $breadcrumbs = array();
      while ($parent_id) {
        $page = get_page($parent_id);
        $breadcrumbs[] = '<a itemprop="url" href="' . get_permalink($page->ID) . '"><span itemprop="title">' . get_the_title($page->ID) . '</span></a>';
        $parent_id  = $page->post_parent;
      }
      $breadcrumbs = array_reverse($breadcrumbs);
      for ($i = 0; $i < count($breadcrumbs); $i++) {
        echo $breadcrumbs[$i];
        if ($i != count($breadcrumbs)-1) echo ' ' . $delimiter . ' ';
      }
      if ($showCurrent == 1) echo ' ' . $delimiter . ' ' . $before . get_the_title() . $after;

    } elseif ( is_tag() ) {
      echo $before . 'All posts tagged "' . single_tag_title('', false) . '"' . $after;

    } elseif ( is_author() ) {
       global $author;
      $userdata = get_userdata($author);
      echo $before . 'All posts by ' . $userdata->display_name . $after;

    } elseif ( is_404() ) {
      echo $before . 'Error 404' . $after;
    }

    if ( get_query_var('paged') ) {
      if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ' (';
      echo __('Page') . ' ' . get_query_var('paged');
      if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ')';
    }

    echo '</p></div>';

  }
}*/



add_action("publish_post", "eg_create_sitemap");
add_action("publish_page", "eg_create_sitemap");

//SITEMAP

function eg_create_sitemap() {
  $postsForSitemap = get_posts(array(
    'numberposts' => -1,
    'orderby' => 'modified',
    'post_type'  => array('post','page'),
    'order'    => 'DESC'
  ));

  $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
  $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

  foreach($postsForSitemap as $post) {
    setup_postdata($post);

    $postdate = explode(" ", $post->post_modified);

    $sitemap .= '<url>'.
      '<loc>'. get_permalink($post->ID) .'</loc>'.
      '<lastmod>'. $postdate[0] .'</lastmod>'.
      '<changefreq>monthly</changefreq>'.
    '</url>';
  }

  $sitemap .= '</urlset>';

  $fp = fopen(ABSPATH . "sitemap.xml", 'w');
  fwrite($fp, $sitemap);
  fclose($fp);
}