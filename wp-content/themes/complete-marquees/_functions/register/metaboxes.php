<?php

function meta_info_get_meta( $value ) {
	global $post;

	$field = get_post_meta( $post->ID, $value, true );
	if ( ! empty( $field ) ) {
		return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
	} else {
		return false;
	}
}



function meta_info_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['meta_info_nonce'] ) || ! wp_verify_nonce( $_POST['meta_info_nonce'], '_meta_info_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['meta_info_meta_title'] ) )
		update_post_meta( $post_id, 'meta_info_meta_title', esc_attr( $_POST['meta_info_meta_title'] ) );
	if ( isset( $_POST['meta_info_meta_description'] ) )
		update_post_meta( $post_id, 'meta_info_meta_description', esc_attr( $_POST['meta_info_meta_description'] ) );
}
add_action( 'save_post', 'meta_info_save' );

/*
	Usage: meta_info_get_meta( 'meta_info_meta_title' )
	Usage: meta_info_get_meta( 'meta_info_meta_description' )
*/

function area_map_get_meta( $value ) {
	global $post;

	$field = get_post_meta( $post->ID, $value, true );
	if ( ! empty( $field ) ) {
		return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
	} else {
		return false;
	}
}

function area_map_add_meta_box() {
	$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;
	$template_file = get_post_meta($post_id,'_wp_page_template',TRUE);
	  // check for a template type
	  if ($template_file == '_templates/page-area.php') {
		add_meta_box(
			'area_map-area-map',
			__( 'Area Map', 'area_map' ),
			'area_map_html',
			'page',
			'normal',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'area_map_add_meta_box' );

function area_map_html( $post) {
	wp_nonce_field( '_area_map_nonce', 'area_map_nonce' ); ?>

	<p>
		<label for="area_map_area_map_iframe"><?php _e( 'Enter URL from iframe', 'area_map' ); ?></label><br>
		<input type="text" name="area_map_area_map_iframe" id="area_map_area_map_iframe" value="<?php echo area_map_get_meta( 'area_map_area_map_iframe' ); ?>">
	</p><?php
}

function area_map_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['area_map_nonce'] ) || ! wp_verify_nonce( $_POST['area_map_nonce'], '_area_map_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['area_map_area_map_iframe'] ) )
		update_post_meta( $post_id, 'area_map_area_map_iframe', esc_attr( $_POST['area_map_area_map_iframe'] ) );
}
add_action( 'save_post', 'area_map_save' );

/*
	Usage: area_map_get_meta( 'area_map_area_map_iframe' )
*/




/**
 * Meta box generator for WordPress
 * Compatible with custom post types
 *
 * Support input types: text, textarea, checkbox, select, radio
 *
 * @author: Nikolay Yordanov <me@nyordanov.com> http://nyordanov.com
 * @version: 1.0
 *
 */
if (!class_exists('SmartMetaBox')) {

	class SmartMetaBox {

		protected $meta_box;
		protected $id;
		static $prefix = '_smartmeta_';

// create meta box based on given data

		public function __construct($id, $opts) {
			if (!is_admin())
				return;
			$this->meta_box = $opts;
			$this->id = $id;
			add_action('add_meta_boxes', array(&$this,
					'add'
			));
			add_action('save_post', array(&$this,
					'save'
			));
		}

// Add meta box for multiple post types

		public function add() {
			foreach ($this->meta_box['pages'] as $page) {
				add_meta_box($this->id, $this->meta_box['title'], array(&$this,
						'show'
								), $page, $this->meta_box['context'], $this->meta_box['priority']);
			}
		}

// Callback function to show fields in meta box

		public function show($post) {

// Use nonce for verification
			echo '<input type="hidden" name="' . $this->id . '_meta_box_nonce" value="', wp_create_nonce('smartmetabox' . $this->id), '" />';
			echo '<table class="form-table">';
			foreach ($this->meta_box['fields'] as $field) {
				extract($field);
				$id = self::$prefix . $id;
				$value = self::get($field['id']);
				if (empty($value) && !sizeof(self::get($field['id'], false))) {
					$value = isset($field['default']) ? $default : '';
				}
				echo '<tr>', '<th style="width:20%"><label for="', $id, '">', $name, '</label></th>', '<td>';
				include "fields/$type.php";
				if (isset($desc)) {
					echo '&nbsp;<span class="description">' . $desc . '</span>';
				}
				echo '</td></tr>';
			}
			echo '</table>';
		}

// Save data from meta box

		public function save($post_id) {

// verify nonce
			if (!isset($_POST[$this->id . '_meta_box_nonce']) || !wp_verify_nonce($_POST[$this->id . '_meta_box_nonce'], 'smartmetabox' . $this->id)) {
				return $post_id;
			}

// check autosave
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return $post_id;
			}

// check permissions
			if ('post' == $_POST['post_type']) {
				if (!current_user_can('edit_post', $post_id)) {
					return $post_id;
				}
			} elseif (!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
			foreach ($this->meta_box['fields'] as $field) {
				$name = self::$prefix . $field['id'];
				$sanitize_callback = (isset($field['sanitize_callback'])) ? $field['sanitize_callback'] : '';
				if (isset($_POST[$name]) || isset($_FILES[$name])) {
					$old = self::get($field['id'], true, $post_id);
					$new = $_POST[$name];
					if ($new != $old) {
						self::set($field['id'], $new, $post_id, $sanitize_callback);
					}
				} elseif ($field['type'] == 'checkbox') {
					self::set($field['id'], 'false', $post_id, $sanitize_callback);
				} else {
					self::delete($field['id'], $name);
				}
			}
		}

		static function get($name, $single = true, $post_id = null) {
			global $post;
			return get_post_meta(isset($post_id) ? $post_id : $post->ID, self::$prefix . $name, $single);
		}

		static function set($name, $new, $post_id = null, $sanitize_callback = '') {
			global $post;

			$id = (isset($post_id)) ? $post_id : $post->ID;
			$meta_key = self::$prefix . $name;
			$new = ($sanitize_callback != '' && is_callable($sanitize_callback)) ? call_user_func($sanitize_callback, $new, $meta_key, $id) : $new;
			return update_post_meta($id, $meta_key, $new);
		}

		static function delete($name, $post_id = null) {
			global $post;
			return delete_post_meta(isset($post_id) ? $post_id : $post->ID, self::$prefix . $name);
		}

	}

}

if (!function_exists('add_smart_meta_box')) {

	function add_smart_meta_box($id, $opts) {
		new SmartMetaBox($id, $opts);
	}

}



//My Meta Boxes
/*--------------------------------Meta--------------------------------*/
add_smart_meta_box('lp-area', array(
	'title'     => 'Area Fields',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		//Meta title
		array(
		'name' => 'Sub Title',
		'id' => 'sub_title',
		'default' => '',
		'desc' => 'Sub Title ',
		'type' => 'text',
		),
	)
));


add_smart_meta_box('equipment-hire', array(
	'title'     => 'Equipment Hire',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
		'name' => 'Plastic Patio Table Product details',
		'id' => 'ppt_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Plastic Patio Table Product Price',
		'id' => 'ppt_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Round Table 1 Product details',
		'id' => 'rt1_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Round Table 1 Product price',
		'id' => 'rt1_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Round Table 2 Product details',
		'id' => 'rt2_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Round Table 2 Product price',
		'id' => 'rt2_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Trestle Table Product details',
		'id' => 'tt_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Trestle Table Product price',
		'id' => 'tt_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'White Bistro Chairs Product details',
		'id' => 'wbc_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'White Bistro Chairs Product details',
		'id' => 'wbc_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri Matting 1 Product details',
		'id' => 'cm1_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Capri Matting 1 Product details',
		'id' => 'cm1_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri Matting 2 Product details',
		'id' => 'cm2_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Capri Matting 2 Product details',
		'id' => 'cm2_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri Matting 3 Product details',
		'id' => 'cm3_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Capri Matting 3 Product details',
		'id' => 'cm3_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri Matting 4 Product details',
		'id' => 'cm4_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Capri Matting 4 Product details',
		'id' => 'cm4_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Dance Floor Product details',
		'id' => 'df_product_details',
		'default' => '',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Dance Floor Product price',
		'id' => 'df_product_price',
		'default' => '',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Up Lighter Product price',
		'id' => 'ul_product_price',
		'default' => '12',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Up Lighter Product per ',
		'id' => 'ul_product_price_info',
		'default' => 'each',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Ivy Garlands Product price',
		'id' => 'ig_product_price',
		'default' => '85',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Ivy Garlands Product per ',
		'id' => 'ig_product_price_info',
		'default' => 'per marquee',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Archway Lighting Product price',
		'id' => 'al_product_price',
		'default' => '75',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Archway Lighting Product per ',
		'id' => 'al_product_price_info',
		'default' => 'per marquee',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Archway Ivy & Lighting Product price',
		'id' => 'ail_product_price',
		'default' => '150',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Archway Ivy &  Lighting Product per ',
		'id' => 'ail_product_price_info',
		'default' => 'per marquee',
		'desc' => '',
		'type' => 'text',
		),

	)
));


add_smart_meta_box('homepage-slider', array(
	'title'     => 'Homepage Slider',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
		'name' => 'Slider Title',
		'id' => 'slider_title',
		'default' => 'Welcome to Complete Marquees',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Slider Content',
		'id' => 'slider_content',
		'default' => 'Complete Marquees is a friendly and family run marquee and event hire company based in Havant near Southampton and Portsmouth in Hampshire.',
		'desc' => '',
		'type' => 'textarea',
		),

	)
));

add_smart_meta_box('homepage-tabs', array(
	'title'     => 'Homepage Tabs',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
		'name' => 'Wedding Title',
		'id' => 'wedding_title',
		'default' => 'Wedding Marquees',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Wedding Content',
		'id' => 'wedding_content',
		'default' => "Create the perfect wedding reception space using a Capri marquee with it's elegant and contemporary look.",
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Party Title',
		'id' => 'party_title',
		'default' => 'Party Marquees',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Party Content',
		'id' => 'party_content',
		'default' => "Capri marquees are perfect for any party or celebration whether it’s a birthday, christening or a social event.",
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Event Title',
		'id' => 'event_title',
		'default' => 'Event Marquees',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Event Content',
		'id' => 'event_content',
		'default' => "Marquees are ideal for all events, from launches, team building days, to festivals and college and university events.",
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Special Title',
		'id' => 'Special_title',
		'default' => 'Special Events',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Special Content',
		'id' => 'special_content',
		'default' => "Capri marquees are a perfect solution for a minimal party of 25 guests or a larger gathering of 400 people.",
		'desc' => '',
		'type' => 'textarea',
		),

	)
));

add_smart_meta_box('size-prices-table', array(
	'title'     => 'Table',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
		'name' => 'Capri 1 Description',
		'id' => 'capri1_desc',
		'default' => 'Capri',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 1 Size',
		'id' => 'capri1_size',
		'default' => "20' x 20'",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 1 Seated',
		'id' => 'capri1_seated',
		'default' => "20",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 1 Standing',
		'id' => 'capri1_standing',
		'default' => "25",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 1 Price',
		'id' => 'capri1_price',
		'default' => "£250",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 2 Description',
		'id' => 'capri2_desc',
		'default' => 'Capri',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 2 Size',
		'id' => 'capri2_size',
		'default' => "20' x 30'",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 2 Seated',
		'id' => 'capri2_seated',
		'default' => "48",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 2 Standing',
		'id' => 'capri2_standing',
		'default' => "60",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 2 Price',
		'id' => 'capri2_price',
		'default' => "£300",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 3 Description',
		'id' => 'capri3_desc',
		'default' => 'Capri',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 3 Size',
		'id' => 'capri3_size',
		'default' => "28' x 28'",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 3 Seated',
		'id' => 'capri3_seated',
		'default' => "40",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 3 Standing',
		'id' => 'capri3_standing',
		'default' => "54",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 3 Price',
		'id' => 'capri3_price',
		'default' => "£290",
		'desc' => '',
		'type' => 'text',
		),array(
		'name' => 'Capri 4 Description',
		'id' => 'capri4_desc',
		'default' => 'Capri',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 4 Size',
		'id' => 'capri4_size',
		'default' => "28' x 38'",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 4 Seated',
		'id' => 'capri4_seated',
		'default' => "80",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 4 Standing',
		'id' => 'capri4_standing',
		'default' => "100",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Capri 4 Price',
		'id' => 'capri4_price',
		'default' => "£350",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 1 Description',
		'id' => 'pagoda1_desc',
		'default' => 'Pagoda',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 1 Size',
		'id' => 'pagoda1_size',
		'default' => "6m x 6m",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 1 Seated',
		'id' => 'pagoda1_seated',
		'default' => "40",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 1 Standing',
		'id' => 'pagoda1_standing',
		'default' => "-",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 1 Price',
		'id' => 'pagoda1_price',
		'default' => "£350",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 2 Description',
		'id' => 'pagoda2_desc',
		'default' => 'Pagoda',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 2 Size',
		'id' => 'pagoda2_size',
		'default' => "5m x 5m",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 2 Seated',
		'id' => 'pagoda2_seated',
		'default' => "-",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 2 Standing',
		'id' => 'pagoda2_standing',
		'default' => "-",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 2 Price',
		'id' => 'pagoda2_price',
		'default' => "£320",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 3 Description',
		'id' => 'pagoda3_desc',
		'default' => 'Pagoda',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 3 Size',
		'id' => 'pagoda3_size',
		'default' => "4m x 4m",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 3 Seated',
		'id' => 'pagoda3_seated',
		'default' => "-",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 3 Standing',
		'id' => 'pagoda3_standing',
		'default' => "-",
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Pagoda 3 Price',
		'id' => 'pagoda3_price',
		'default' => "£300",
		'desc' => '',
		'type' => 'text',
		),

	)
));


add_smart_meta_box('packages', array(
	'title'     => 'Packages',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
		'name' => 'Package 1 Title',
		'id' => 'package1_title',
		'default' => 'Capri Marquee for <span>30</span> Guests',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 1 Item 1',
		'id' => 'package1_item1',
		'default' => '20 x 20ft Capri Marquee',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 1 Item 2',
		'id' => 'package1_item2',
		'default' => '2 x 6ft Trestle Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 1 Item 3',
		'id' => 'package1_item3',
		'default' => 'Flooring',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 1 Item 4',
		'id' => 'package1_item4',
		'default' => 'Side Walls - Plain and Clear',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 1 Price',
		'id' => 'package1_price',
		'default' => '£330',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Title',
		'id' => 'package2_title',
		'default' => 'Capri Marquee for <span>40</span> Guests Seated',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 1',
		'id' => 'package2_item1',
		'default' => '20 x 30ft Capri Marquee',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 2',
		'id' => 'package2_item2',
		'default' => '4 x 5ft Round Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 3',
		'id' => 'package2_item3',
		'default' => '2 x 6ft Trestle Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 4',
		'id' => 'package2_item4',
		'default' => '40 Chairs',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 5',
		'id' => 'package2_item5',
		'default' => 'Flooring',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 6',
		'id' => 'package2_item6',
		'default' => 'Lighting ( 6 Uplighters )',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Item 7',
		'id' => 'package2_item7',
		'default' => 'Side Walls - Plain and Clear',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 2 Price',
		'id' => 'package2_price',
		'default' => '£640',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Title',
		'id' => 'package3_title',
		'default' => 'Capri Marquee for <span>80</span> Guests Seated',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 1',
		'id' => 'package3_item1',
		'default' => '28 x 38ft Capri Marquee',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 2',
		'id' => 'package3_item2',
		'default' => '8 x 5ft Round Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 3',
		'id' => 'package3_item3',
		'default' => '2 x 6ft Trestle Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 4',
		'id' => 'package3_item4',
		'default' => '80 Chairs',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 5',
		'id' => 'package3_item5',
		'default' => 'Flooring',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 6',
		'id' => 'package3_item6',
		'default' => 'Lighting ( 6 Uplighters )',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Item 7',
		'id' => 'package3_item7',
		'default' => 'Side Walls - Plain and Clear',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 3 Price',
		'id' => 'package3_price',
		'default' => '£835',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Title',
		'id' => 'package4_title',
		'default' => 'Capri Marquee for <span>80</span> Guests Seated',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 1',
		'id' => 'package4_item1',
		'default' => '2 x 28 x 38ft Capri Marquees (and linking kit)',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 2',
		'id' => 'package4_item2',
		'default' => '10 x 5ft Round Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 3',
		'id' => 'package4_item3',
		'default' => '3 x 6ft Trestle Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 4',
		'id' => 'package4_item4',
		'default' => '100 Chairs',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 5',
		'id' => 'package4_item5',
		'default' => 'Flooring',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 6',
		'id' => 'package4_item6',
		'default' => 'Dancefloor',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 7',
		'id' => 'package4_item7',
		'default' => 'Lighting ( 12 Uplighters )',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Item 8',
		'id' => 'package4_item8',
		'default' => 'Side Walls - Plain and Clear',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 4 Price',
		'id' => 'package4_price',
		'default' => '£1655',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Title',
		'id' => 'package5_title',
		'default' => 'Capri Marquee for <span>150</span> Guests Seated',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 1',
		'id' => 'package5_item1',
		'default' => '3 x 28 x 38ft Capri Marquees (and linking kit)',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 2',
		'id' => 'package5_item2',
		'default' => '16 x 5ft Round Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 3',
		'id' => 'package5_item3',
		'default' => '3 x 6ft Trestle Tables',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 4',
		'id' => 'package5_item4',
		'default' => '150 Chairs',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 5',
		'id' => 'package5_item5',
		'default' => 'Flooring',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 6',
		'id' => 'package5_item6',
		'default' => 'Dancefloor',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 7',
		'id' => 'package5_item7',
		'default' => 'Lighting ( 18 Uplighters )',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Item 8',
		'id' => 'package5_item8',
		'default' => 'Side Walls - Plain and Clear',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Package 5 Price',
		'id' => 'package5_price',
		'default' => '£2440',
		'desc' => '',
		'type' => 'text',
		),

	)
));

add_smart_meta_box('faq', array(
	'title'     => 'FAQ',
	'pages'		=> array('page'),
	'context'   => 'normal',
	'priority'  => 'high',
	'fields'    => array(
		array(
		'name' => 'Question 1',
		'id' => 'question1',
		'default' => 'How many guests can I accommodate?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 1',
		'id' => 'answer1',
		'default' => '<p>It depends on the size of the marquee and whether your guests will be seated around tables or standing.</p><ul> <li>28’ X 38’ marquee – up to 80 seated or 100 standing</li> <li>28’ x 28’ marquee – up to 40 seated or 54 standing</li> <li>20’ x 30’ marquee – up to 48 seated or 60 standing</li> <li>20’ x 20’ marquee – up to 24 seated and 32 standing</li> </ul>',
		'desc' => '',
		'type' => 'textarea',
		),
		array(
		'name' => 'Question 2',
		'id' => 'question2',
		'default' => 'Are the marquees watertight?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 2',
		'id' => 'answer2',
		'default' => 'Yes in light weather. No in heavy rain conditions.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 3',
		'id' => 'question3',
		'default' => 'How do the side walls attach?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 3',
		'id' => 'answer3',
		'default' => 'The side walls fit nicely into the arches in the marquee. There is a cord that runs along inside of the arches and the side walls clip on to the cord. They can also be pegged to the ground if required.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 4',
		'id' => 'question4',
		'default' => 'Do I pay extra for side walls?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 4',
		'id' => 'answer4',
		'default' => 'No we bring them as matter of course and are included in the price.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 5',
		'id' => 'question5',
		'default' => 'Can I attach them myself?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 5',
		'id' => 'answer5',
		'default' => 'If you decide to attach them later in the day they should take no longer than a couple of minutes per side wall.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 6',
		'id' => 'question6',
		'default' => 'Will I need matting?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 6',
		'id' => 'answer6',
		'default' => 'It does depend on the summer weather, the type of event and the condition of the grass, many of the more informal events that we cover work well with or without the matting.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 7',
		'id' => 'question7',
		'default' => 'Will I need heaters?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 7',
		'id' => 'answer7',
		'default' => 'Again it does depend on the weather and how late your event is likely to go on in the evening and the time of year.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 8',
		'id' => 'question8',
		'default' => 'What do we need to provide, for us to have lighting?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 8',
		'id' => 'answer8',
		'default' => 'We require a standard plug point and we provide circuit breakers.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 9',
		'id' => 'question9',
		'default' => 'If my event is on a Saturday when would you set up?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 9',
		'id' => 'answer9',
		'default' => 'It depends on the type of event, we like to work with the venue and yourself organise this.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 10',
		'id' => 'question10',
		'default' => 'Do you hire other equipment that we might need for a party?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 10',
		'id' => 'answer10',
		'default' => 'We can supply tables and chairs, and entertainment options.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 11',
		'id' => 'question11',
		'default' => 'Can the marquees be set up on hard surfaces?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 11',
		'id' => 'answer11',
		'default' => 'No capri marquees can not, they can only put up on grass, however we can supply frame marquees for this.',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Question 12',
		'id' => 'question12',
		'default' => 'Will I need matting?',
		'desc' => '',
		'type' => 'text',
		),
		array(
		'name' => 'Answer 12',
		'id' => 'answer12',
		'default' => 'Without matting the marquee may become dirty on the inside due to the way it is erected.',
		'desc' => '',
		'type' => 'text',
		),
	)
));



//Action for hiding unwanted metaboxes on pages
add_action('admin_head', 'wpse_50092_script_enqueuer');

function wpse_50092_script_enqueuer() {
    global $current_screen;
    if('page' != $current_screen->id) return;

    echo <<<HTML
        <script type="text/javascript">
        jQuery(document).ready( function($) {
            /**
             * Adjust visibility of the meta box at startup
            */
						if($('#page_template').val() == '_templates/page-faq.php') {
								$('#faq').show();
						} else {
								$('#faq').hide();
						}

						if($('#page_template').val() == '_templates/page-arealp.php') {
								$('#lp-area').show();
						} else {
								$('#lp-area').hide();
						}

						if($('#page_template').val() == '_templates/page-equipment.php') {
								$('#equipment-hire').show();
						} else {
								$('#equipment-hire').hide();
						}

						if($('#page_template').val() == '_templates/page-home.php') {
								$('#homepage-slider').show();
								$('#homepage-tabs').show();
						} else {
								$('#homepage-slider').hide();
								$('#homepage-tabs').hide();
						}

						if($('#page_template').val() == '_templates/page-size-price.php') {
								$('#size-prices-table').show();
						} else {
								$('#size-prices-table').hide();
						}

						if($('#page_template').val() == '_templates/page-packages.php') {
								$('#packages').show();
						} else {
								$('#packages').hide();
						}

            /**
             * Live adjustment of the meta box visibility
            */
						$('#page_template').live('change', function(){
                    if($(this).val() == '_templates/page-faq.php') {
                    $('#faq').show();
                } else {
                    $('#faq').hide();
                }
            });
						$('#page_template').live('change', function(){
                    if($(this).val() == '_templates/page-arealp.php') {
                    $('#lp-area').show();
                } else {
                    $('#lp-area').hide();
                }
            });
						$('#page_template').live('change', function(){
                    if($(this).val() == '_templates/page-equipment.php') {
                    $('#equipment-hire').show();
                } else {
                    $('#equipment-hire').hide();
                }
            });
						$('#page_template').live('change', function(){
                    if($(this).val() == '_templates/page-home.php') {
                    $('#homepage-slider').show();
                    $('#homepage-tabs').show();
                } else {
                    $('#homepage-slider').hide();
                    $('#homepage-tabs').hide();
                }
            });
						$('#page_template').live('change', function(){
                    if($(this).val() == '_templates/page-size-price.php') {
                    $('#size-prices-table').show();
                } else {
                    $('#size-prices-table').hide();
                }
            });
						$('#page_template').live('change', function(){
                    if($(this).val() == '_templates/page-packages.php') {
                    $('#packages').show();
                } else {
                    $('#packages').hide();
                }
            });

        });
        </script>
HTML;
}
