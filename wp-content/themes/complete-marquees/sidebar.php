<?php 
if ( is_page('contact-us') ) {
	get_template_part( '_partials/sidebar', 'contact' ); 
} elseif ( is_page_template( '_templates/page-area.php' ) ) {
	get_template_part('_partials/sidebar', 'area');
} else {
	get_template_part( '_partials/sidebar', 'default' ); 
}
?>