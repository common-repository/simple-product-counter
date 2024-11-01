<?php
/*
Plugin Name: Simple product counter
Description: Notice with product sales and views for certain period.
Version: 2.2.0
Author: WebArea
License: GPL-2.0+

------------------------------------------------------------------------
Copyright 2009-2018 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.
*/

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

// Set plugin instance
$simple_product_counter_class = new SimpleProductCounter();

class SimpleProductCounter {
	private $plugin_url;
	private $plugin_path;

	private $functionsClass;
	private $fieldsClass;

	public function __construct() {
		// Check requirements
		if ( !$this->check_requirements() ) {
   			return;
 		}

 		// Set plugin url
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_path = dirname(__FILE__);

		// Activate plugin
		register_activation_hook( __FILE__, array( $this, 'on_plugin_activate' ) );

		// Styles & Scripts
		add_action('admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ));
		add_action('login_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ));
		add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));

		// Settings page link
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), array( $this, 'simple_product_counter_settings_link' ));

		// Settings page
		add_action('admin_menu', array( $this, 'simple_product_counter_settings_page' ));

		// Update settings
		add_action('admin_init', array( $this, 'simple_product_counter_settings_update' ));

		// Classes
		include_once( plugin_dir_path( __FILE__ ) . 'inc/functions.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'inc/fields.php' );
		$this->functionsClass = new SimpleProductCounterFunctions();
		$this->fieldsClass = new SimpleProductCounterFields();

		// Output
		add_action('woocommerce_after_add_to_cart_button', array( $this->functionsClass, 'simple_product_counter_output_after' ));
		add_action('woocommerce_before_add_to_cart_button', array( $this->functionsClass, 'simple_product_counter_output_before' ));
		add_action('woocommerce_before_add_to_cart_form', array( $this->functionsClass, 'simple_product_counter_output_before_form' ));
	
		add_action('init', array( $this, 'start_session' ));

		// Add image size
		add_image_size( 'simple-product-counter-mini', 50, 50, true );
	}

	public function start_session(){
		$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
		if( isset( $simple_product_counter_option['clicks']['enable_session'] ) && !session_id() ){
			session_start();
		}
	}

	public function simple_product_counter_settings_link( $links ){
		$links[] = '<a href="' . admin_url( 'admin.php?page=simple_product_counter_settings_page' ) . '">' . __('Settings') . '</a>';
		return $links;	
	}

	private function check_requirements(){
		if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_no_woocommerce' ) );
        	return false;
		}

		return true;
	}

	public function admin_notice_no_woocommerce(){
		?>
		<div class="notice notice-error">
			<p>
 			<?php 
			printf( __( '<b>Simple product counter</b> is enabled but not effective. It requires %s in order to work.', 'ajax-search-for-woocommerce' ), '<a href="https://wordpress.org/plugins/woocommerce/"  target="_blank">WooCommerce</a>' );
			?>
			</p>
  		</div>
		<?php 
	}

	public function enqueue_admin_scripts(){
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );

		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style('simple_product_counter_admin_fonts', $this->plugin_url . 'css/fonts.css');
		wp_enqueue_style('simple_product_counter_admin_style', $this->plugin_url . 'css/admin.css');
		wp_enqueue_script('simple_product_counter_admin_script', $this->plugin_url . 'js/admin.js', array('jquery', 'wp-color-picker'));
	}

	public function enqueue_scripts(){
		wp_enqueue_style('simple_product_counter_fonts', $this->plugin_url . 'css/fonts.css');
		wp_enqueue_style('simple_product_counter_styles', $this->plugin_url . 'css/main.css');
	}

	public function simple_product_counter_settings_page(){
		add_menu_page( 'Product counter', 'Product counter', 'manage_options', 'simple_product_counter_settings_page', array( $this, 'simple_product_counter_settings_page_func' ), $this->plugin_url . 'images/spc.svg', null );
	}

	public function on_plugin_activate(){
		$simple_product_counter_option_j = get_option('simple_product_counter_option');
		if($simple_product_counter_option_j == ''){
			update_option( 'simple_product_counter_option', json_encode( serialize($this->default_settings())) );
		}
	}

	public function simple_product_counter_settings_page_func(){
		$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
		$settings_arr = array('sales', 'clicks');

		$active_tab = isset($_POST['simple_product_count_settings']) ? $_POST['simple_product_count_settings'] : 'sales';
		?>
		<div class="wrap simple-product-counter-main-wrapper">
			<div class="spc-settings-cont">
				<div class="spc-settings-cont-header">
					<img src="<?php echo $this->plugin_url . "images/logo.png"; ?>" />
				</div>
				
				<!-- Choose -->
				<div class="spc-settings-cont-main <?php if(!isset($_POST['simple_product_count_settings'])){echo 'act';} ?>">
					<div class="spc-settings-title center">
						<p>Choose type</p>
						<p class="info">Please, choose the notification type</p>
					</div>
					<div class="spc-settings-type-cont">
						<div class="spc-settings-type-itm" data-target="sales">
							<img src="<?php echo $this->plugin_url . "images/sales-icn.svg"; ?>" />
							<p>Sales counter<br> notification</p>
						</div>
						<div class="spc-settings-type-itm" data-target="clicks">
							<img src="<?php echo $this->plugin_url . "images/views-icn.svg"; ?>" />
							<p>Views counter<br> notification</p>
						</div>
					</div>
				</div>

				<!-- Main settings -->
				
				<?php if(isset($_POST['simple_product_count_settings'])){ ?>
				<div class="spc-saved-notification">
					<p>Settings saved</p>
					<div class="close">+</div>
				</div>
				<?php } ?>

				<div class="spc-settings-cont-main-settings <?php if(isset($_POST['simple_product_count_settings'])){echo 'act';} ?>">
					<form id="spc_main_settings_form" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
					<input type="hidden" name="simple_product_count_settings" value="<?php if(isset($_POST['simple_product_count_settings'])){echo esc_html($_POST['simple_product_count_settings']);}else{echo 'sales';} ?>">
					<div class="tabs-nav">
						<ul>
							<li class="<?php if( esc_html($active_tab) == 'sales'){echo 'act';} ?>" data-target="sales">Sales notification</li>
							<li class="<?php if( esc_html($active_tab) == 'clicks'){echo 'act';} ?>" data-target="clicks">Views notification</li>
						</ul>
					</div>

					<?php
						foreach ($settings_arr as $settings_value) {
						$simple_product_counter_option_el = $simple_product_counter_option[$settings_value];
					?>
					<div class="spc-main-settings-cont <?php if(esc_html($active_tab) == $settings_value){echo 'act';} ?>" id="<?php echo $settings_value; ?>">
						<div class="settings-fields">
							<div class="settings-cont">
								<div class="settings-cont-tit">
									<p>General</p>
								</div>

								<div class="settings-cont-fields">
									<div class="settings-field-cont">
										<p>Enable notification</p>
										
										<?php 
										$enable_not = isset($simple_product_counter_option_el['enable']) ? $simple_product_counter_option_el['enable'] : '';
										echo $this->fieldsClass->switcher_field('spc_settings_'.$settings_value.'[enable]', esc_html($enable_not)); ?>
									</div>
									<div class="settings-field-cont">
										<p>Period</p>
										<div class="field-period">
											<?php echo $this->fieldsClass->number_field('spc_settings_'.$settings_value.'[period_number]', esc_html($simple_product_counter_option_el['period_number'])); ?>
											<?php echo $this->fieldsClass->select_field('spc_settings_'.$settings_value.'[period]', $simple_product_counter_option_el['period'], array(array('name' => 'day', 'label' => 'Day'), array('name' => 'week', 'label' => 'Week'), array('name' => 'month', 'label' => 'Month'))); ?>
										</div>
									</div>
									<div class="settings-field-cont">
										<p>Location</p>
										<?php echo $this->fieldsClass->select_field('spc_settings_'.$settings_value.'[output_location]', $simple_product_counter_option_el['output_location'], array(array('name' => 'after_button', 'label' => 'After "Buy" button'), array('name' => 'before_button', 'label' => 'Before "Buy" button'), array('name' => 'before_form', 'label' => 'Before "Add to cart" form'))); ?>
									</div>
									<div class="settings-field-cont only-one-tab <?php if( esc_html($settings_value) == 'clicks'){echo 'active';} ?>" data-tab="clicks">
										<p>Сonsider session</p>

										<?php 
										$enable_session = isset($simple_product_counter_option_el['enable_session']) ? $simple_product_counter_option_el['enable_session'] : '';
										echo $this->fieldsClass->switcher_field('spc_settings_'.$settings_value.'[enable_session]', esc_html($enable_session)); ?>
									</div>
								</div>
							</div>
							<div class="settings-cont">
								<div class="settings-cont-tit">
									<p>Styles</p>
								</div>

								<div class="settings-cont-fields">
									<div class="settings-field-cont">
										<p>Background</p>
										<?php echo $this->fieldsClass->color_field('spc_settings_'.$settings_value.'[styles][background]', esc_html($simple_product_counter_option_el['styles']['background'])); ?>
									</div>
									<div class="settings-field-cont">
										<p>Icon</p>
										<?php echo $this->fieldsClass->image_field('spc_settings_'.$settings_value.'[styles][icon]', esc_html($simple_product_counter_option_el['styles']['icon'])); ?>
									</div>
									<div class="settings-field-cont">
										<p>Text</p>
										<?php echo $this->fieldsClass->text_field('spc_settings_'.$settings_value.'[styles][text]', esc_html($simple_product_counter_option_el['styles']['text'])); ?>
									</div>
									<div class="settings-field-cont">
										<p>Text color</p>
										<?php echo $this->fieldsClass->color_field('spc_settings_'.$settings_value.'[styles][text_color]', esc_html($simple_product_counter_option_el['styles']['text_color'])); ?>
									</div>
									<div class="settings-field-cont">
										<p>Font size (px)</p>
										<?php echo $this->fieldsClass->number_field('spc_settings_'.$settings_value.'[styles][font_size]', esc_html($simple_product_counter_option_el['styles']['font_size'])); ?>
									</div>
									<div class="settings-field-cont spc-border-admin">
										<p>Enable border</p>
										<?php 
										$enable_border = isset($simple_product_counter_option_el['styles']['border']) ? $simple_product_counter_option_el['styles']['border'] : '';
										echo $this->fieldsClass->switcher_field('spc_settings_'.$settings_value.'[styles][border]', esc_html($enable_border)); ?>
									</div>
									<div class="settings-field-cont spc-border-admin-enable <?php if(esc_html($enable_border) == 1){echo 'act';} ?>">
										<p>Border color</p>
										<?php 
										$border_color = isset($simple_product_counter_option_el['styles']['border_color']) ? $simple_product_counter_option_el['styles']['border_color'] : '#E0E0E0';
										echo $this->fieldsClass->color_field('spc_settings_'.$settings_value.'[styles][border_color]', esc_html($border_color)); ?>
									</div>
									<div class="settings-field-cont">
										<p>Border-radius (px)</p>
										<?php echo $this->fieldsClass->number_field('spc_settings_'.$settings_value.'[styles][border_radius]', esc_html($simple_product_counter_option_el['styles']['border_radius'])); ?>
									</div>
								</div>
							</div>

							<div class="settings-cont">
								<div class="settings-cont-tit">
									<p>Advanced</p>
								</div>

								<div class="settings-cont-fields">
									<div class="settings-field-cont">
										<p>Start from random number from range<br><i>This option allows to start count not from 0</i><br><i>Leave empty for disable this option</i></p>
										<div class="field-range">
											<div class="f">
												<label>Min</label>
												<?php echo $this->fieldsClass->number_field('spc_settings_'.$settings_value.'[min_range]', esc_html($simple_product_counter_option_el['min_range'])); ?>
											</div>
											<div class="f">
												<label>Max</label>
												<?php echo $this->fieldsClass->number_field('spc_settings_'.$settings_value.'[max_range]', esc_html($simple_product_counter_option_el['max_range'])); ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="spc-settings-preview preview-not-<?php echo $settings_value; ?>">
							<?php
							$styles = 'background: ' . esc_html($simple_product_counter_option_el['styles']['background']) . ';';
							if(isset( $simple_product_counter_option_el['styles']['border'] ) ){
								if($simple_product_counter_option_el['styles']['border'] == 1){
								$styles .= ' border: 1px solid ' . esc_html($simple_product_counter_option_el['styles']['border_color']) . ';';
								}
							}

							if( esc_html($simple_product_counter_option_el['styles']['border_radius']) != '' ){
								$styles .= ' border-radius: ' . esc_html($simple_product_counter_option_el['styles']['border_radius']) . 'px;';
							}
							$styles_p = 'color: ' . esc_html($simple_product_counter_option_el['styles']['text_color']) . ';';
							$styles_p_font = 'font-size: ' . esc_html($simple_product_counter_option_el['styles']['font_size']) . 'px;';
							?>
							<div class="notification" style="<?php echo $styles; ?>">
								<?php 
								if( $img_icon = wp_get_attachment_image_src( $simple_product_counter_option_el['styles']['icon'], 'simple-product-counter-mini' ) ){
									?>
										<img src="<?php echo esc_url($img_icon[0]); ?>" />
									<?php
								} else {
									if($settings_value == 'sales'){ ?>
										<i style="<?php echo $styles_p; ?>" class="spc spc-purchased"></i>
									<?php }else{ ?>
										<i style="<?php echo $styles_p; ?>" class="spc spc-views"></i>
									<?php 
									}
								}
								?>
								<p style="<?php echo $styles_p . $styles_p_font; ?>"><span>10</span> <?php echo esc_html($simple_product_counter_option_el['styles']['text']); ?></p>
							</div>
						</div>
					</div>

					<?php } ?>

					<div class="spc-save-bttn">
						<button>Save settings</button>
					</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	private function default_settings(){
		return array(
			'sales' => array(
				'enable' => '1',
				'period_number' => '1',
				'period' => 'day',
				'output_location' => 'after_button',
				'min_range' => '',
				'max_range' => '',
				'styles' => array(
					'background' => '#f7fbd6',
					'icon' => '',
					'text' => 'person(s) bought this product today',
					'text_color' => '#333',
					'font_size' => '18',
					'border' => '0',
					'border_color' => '#E0E0E0',
					'border_radius' => '3',
				)
			),
			'clicks' => array(
				'enable' => '1',
				'period_number' => '1',
				'period' => 'day',
				'enable_session' => '1',
				'output_location' => 'after_button',
				'min_range' => '',
				'max_range' => '',
				'styles' => array(
					'background' => '#f7fbd6',
					'icon' => '',
					'text' => 'person(s) viewed this product today',
					'text_color' => '#333',
					'font_size' => '18',
					'border' => '0',
					'border_color' => '#E0E0E0',
					'border_radius' => '3',
				)
			)
		);
	}

	private function simple_product_counter_set_defaults( $option ){
		$opt = unserialize(json_decode($option));
		$def = $this->default_settings();

		$clicks_arr = $this->compare_arrays($def['clicks'], $opt['clicks']);
		$sales_arr = $this->compare_arrays($def['sales'], $opt['sales']);
		$clicks_arr_styles = $this->compare_arrays($def['clicks']['styles'], $opt['clicks']['styles']);
		$sales_arr_styles = $this->compare_arrays($def['sales']['styles'], $opt['sales']['styles']);

		$opt['clicks'] = $clicks_arr;
		$opt['sales'] = $sales_arr;
		$opt['clicks']['styles'] = $clicks_arr_styles;
		$opt['sales']['styles'] = $sales_arr_styles;

		return $opt;
	}

	private function compare_arrays( $arr1, $arr2 ){
		if(!empty($arr1)){
		foreach ($arr1 as $arr1_key => $arr1_value) {
			if(!isset($arr2[$arr1_key]) && $arr1_key != 'enable' && $arr1_key != 'enable_session'){
				$arr2[$arr1_key] = $arr1_value;		
			}
		}
		}

		return $arr2;
	}

	private function get_all_products(){
		$params = array('posts_per_page' => -1, 'post_type' => 'product');

		$ids = array();

		$wc_query = new WP_Query($params);
		if ($wc_query->have_posts()) :
			while ($wc_query->have_posts()) :
	        	$wc_query->the_post();
	        	array_push($ids, get_post()->ID);
			endwhile;
		wp_reset_postdata();
		endif;

		return $ids;
	}

	public function simple_product_counter_settings_update(){
		$simple_product_counter_option_j = get_option('simple_product_counter_option');
		if($simple_product_counter_option_j == ''){
			update_option( 'simple_product_counter_option', json_encode( serialize($this->default_settings())) );
		}else{
			update_option( 'simple_product_counter_option', json_encode( serialize($this->simple_product_counter_set_defaults($simple_product_counter_option_j))) );
		}

		if(isset($_POST['simple_product_count_settings'])){
			$sales = array_map( 'sanitize_text_field', wp_unslash( $_POST['spc_settings_sales'] ));
			$sales_styles = array_map( 'sanitize_text_field', wp_unslash( $_POST['spc_settings_sales']['styles'] ));
			$sales['styles'] = $sales_styles;

			$clicks = array_map( 'sanitize_text_field', wp_unslash( $_POST['spc_settings_clicks'] ));
			$clicks_styles = array_map( 'sanitize_text_field', wp_unslash( $_POST['spc_settings_clicks']['styles'] ));
			$clicks['styles'] = $clicks_styles;

			// Validate numbers
			if(!is_numeric($sales['styles']['border_radius'])){
				$sales['styles']['border_radius'] = 0;
			}
			if(!is_numeric($clicks['styles']['border_radius'])){
				$clicks['styles']['border_radius'] = 0;
			}
			if(!is_numeric($sales['period_number'])){
				$sales['period_number'] = 1;
			}
			if(!is_numeric($clicks['period_number'])){
				$clicks['period_number'] = 1;
			}
			if(!is_numeric($sales['min_range']) && $sales['min_range'] != ''){
				$sales['min_range'] = '';
			}
			if(!is_numeric($clicks['min_range']) && $clicks['min_range'] != ''){
				$clicks['min_range'] = '';
			}
			if(!is_numeric($sales['max_range']) && $sales['max_range'] != ''){
				$sales['max_range'] = '';
			}
			if(!is_numeric($clicks['max_range']) && $clicks['max_range'] != ''){
				$clicks['max_range'] = '';
			}
			if(!is_numeric($sales['styles']['font_size'])){
				$sales['styles']['font_size'] = 18;
			}
			if(!is_numeric($clicks['styles']['font_size'])){
				$clicks['styles']['font_size'] = 18;
			}
			// Validate numbers END

			// Set random start number
			$products_ids = $this->get_all_products();
			if(!empty($products_ids)){
				foreach ($products_ids as $products_ids_value) {
					delete_post_meta($products_ids_value, 'simple_product_counter_range_meta_sales');
					delete_post_meta($products_ids_value, 'simple_product_counter_range_meta_clicks');

					if($sales['min_range'] != '' || $sales['max_range'] != ''){
						$min_s = ( $sales['min_range'] != '' ) ? $sales['min_range'] : 0;
						$max_s = ( $sales['max_range'] != '' ) ? $sales['max_range'] : intval($sales['min_range']) + 10;
						add_post_meta($products_ids_value, 'simple_product_counter_range_meta_sales', rand($min_s, $max_s));
					}

					if($clicks['min_range'] != '' || $clicks['max_range'] != ''){
						$min_s_cl = ( $clicks['min_range'] != '' ) ? $clicks['min_range'] : 0;
						$max_s_cl = ( $clicks['max_range'] != '' ) ? $clicks['max_range'] : intval($clicks['min_range']) + 10;
						add_post_meta($products_ids_value, 'simple_product_counter_range_meta_clicks', rand($min_s_cl, $max_s_cl));
					}
				}
			}
			// Set random start number END

			$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
			if(!empty($sales)){
				$simple_product_counter_option['sales'] = $sales;
			}

			if(!empty($clicks)){
				$simple_product_counter_option['clicks'] = $clicks;
			}			
			update_option('simple_product_counter_option', json_encode(serialize($simple_product_counter_option)));
		}
	}
}