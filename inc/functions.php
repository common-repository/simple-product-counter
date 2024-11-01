<?php

class SimpleProductCounterFunctions {
	public function simple_product_counter_output_before(){
		$this->set_simple_product_views();
		$this->set_simple_product_total_views();

		$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
		if(!empty($simple_product_counter_option)){
		foreach ($simple_product_counter_option as $key => $value) {
			if(isset($value['enable'])){
				if($key == 'sales' && esc_html($value['output_location']) == 'before_button' && esc_html($value['enable']) == '1'){
					$this->spc_output_notification('sales', $value);
				}
			}
			if(isset($value['enable'])){
				if($key == 'clicks' && esc_html($value['output_location']) == 'before_button' && esc_html($value['enable']) == '1'){
					$this->spc_output_notification('clicks', $value);
				}
			}
		}
		}
	}

	public function simple_product_counter_output_after(){
		$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
		if(!empty($simple_product_counter_option)){
		foreach ($simple_product_counter_option as $key => $value) {
			if(isset($value['enable'])){
				if($key == 'sales' && esc_html($value['output_location']) == 'after_button' && esc_html($value['enable']) == '1'){
					$this->spc_output_notification('sales', $value);
				}
			}
			if(isset($value['enable'])){
				if($key == 'clicks' && esc_html($value['output_location']) == 'after_button' && esc_html($value['enable']) == '1'){
					$this->spc_output_notification('clicks', $value);
				}
			}
		}
		}
	}

	public function simple_product_counter_output_before_form(){
		$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
		if(!empty($simple_product_counter_option)){
		foreach ($simple_product_counter_option as $key => $value) {
			if(isset($value['enable'])){
				if($key == 'sales' && esc_html($value['output_location']) == 'before_form' && esc_html($value['enable']) == '1'){
					$this->spc_output_notification('sales', $value);
				}
			}
			if(isset($value['enable'])){
				if($key == 'clicks' && esc_html($value['output_location']) == 'before_form' && esc_html($value['enable']) == '1'){
					$this->spc_output_notification('clicks', $value);
				}
			}
		}
		}
	}

	public function get_simple_product_sales( $period = 'week', $period_number = '1' ){
		global $product;
		$all_orders = wc_get_orders(
			array(
				'limit' => -1,
				'status' => array_map( 'wc_get_order_status_name', wc_get_is_paid_statuses() ),
				'date_after' => date( 'Y-m-d', strtotime( '-' . $period_number . ' ' . $period ) ),
				'return' => 'ids',
			)
		);

		$count = 0;
		if(!empty($all_orders)){
		foreach ( $all_orders as $all_order ) {
			$order = wc_get_order( $all_order );
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id = $item->get_product_id();
				if ( $product_id == $product->get_id() ) {
					$count = $count + absint( $item['qty'] ); 
				}
			}
		}
		}

		return $count;
	}

	public function get_simple_product_views( $period = 'week', $period_number = '1' ){
		global $product;
		$product_id = $product->get_id();
		$count_arr_meta = get_post_meta($product_id, 'simple_product_counter_meta', true);
		$count_arr = json_decode($count_arr_meta);
		$count_arr_all = array($count_arr);

		$count_store = $this->get_simple_product_views_store($product_id);
		if(!empty($count_store)){
		foreach ($count_store as $count_store_v) {
			array_push($count_arr_all, json_decode($count_store_v));
		}
		}

		$result_count = 0;

		if(!empty($count_arr_all)){
			foreach ($count_arr_all as $count_arr_all_v) {
				if(!empty($count_arr_all_v)){
					foreach ($count_arr_all_v as $count_arr_value) {
						if( strtotime( '-' . $period_number . ' ' . $period ) < intval($count_arr_value->time)){
							$result_count += intval($count_arr_value->count);
						}
					}
				}
			}
		}

		return $result_count;
	}

	private function get_simple_product_views_store( $product_id ){
		global $wpdb;
		$res_arr = array();		
		$results = $wpdb->get_results(
			"SELECT $wpdb->postmeta.meta_value 
			FROM $wpdb->postmeta 
			WHERE $wpdb->postmeta.meta_key LIKE 'simple_product_counter_meta_%' AND post_id = '".$product_id."'", ARRAY_A);

		if(!empty($results)){
		foreach ($results as $key => $value) {
			array_push($res_arr, $value['meta_value']);
		}
		}

		return $res_arr;
	}

	public function set_simple_product_views(){
		global $product;
		$product_id = $product->get_id();
		$count_arr_meta = get_post_meta($product_id, 'simple_product_counter_meta', true);
		$count_arr = json_decode($count_arr_meta);
		$simple_product_counter_option = unserialize(json_decode(get_option('simple_product_counter_option')));
		if( isset( $simple_product_counter_option['clicks']['enable_session'] ) && isset($_SESSION) && isset( $_SESSION['simple_product_counter_clicks'] ) ){
			return;
		}

		if(empty($count_arr)){
			$count = 1;
			$rr = (60 * 60 * 24) * 0;
			$count_val = json_encode(array(array( 'count' => $count, 'time' => time() - $rr )));
			delete_post_meta($product_id, 'simple_product_counter_meta');
			add_post_meta($product_id, 'simple_product_counter_meta', $count_val);
		}else{

			$rr = (60 * 60 * 24) * 0;
			foreach ($count_arr as $count_arr_key => $count_arr_value) {
				$count_arr_value_date_diff = round((time() - $rr - intval($count_arr_value->time)) / (60 * 60 * 24));
				if( $count_arr_value_date_diff > 95 ){
					// unset($count_arr[$count_arr_key]);
					$this->store_simple_product_total_views($product_id, $count_arr_meta);
					$count_arr = array();
				}
			}

			if(!empty($count_arr)){
				if( count($count_arr) > 1 ){
					$last_key = end(array_keys($count_arr));
				}else{
					$last_key = 0;
				}

				$last_time = $count_arr[$last_key]->time;
				$last_count = intval($count_arr[$last_key]->count);
				$date_diff = round((time()- $rr - intval($last_time)) / (60 * 60 * 24));

				if($date_diff == 0){
					$last_count++;
					$count_arr[$last_key]->count = $last_count;
					update_post_meta($product_id, 'simple_product_counter_meta', json_encode(array_values($count_arr)));
				}else{
					$new_time_arr = array('count' => 1, 'time' => time()- $rr);
					array_push($count_arr, $new_time_arr);
					update_post_meta($product_id, 'simple_product_counter_meta', json_encode(array_values($count_arr)));
				}
			}else{
				$count = 1;
				$count_val = json_encode(array(array( 'count' => $count, 'time' => time()- $rr )));
				delete_post_meta($product_id, 'simple_product_counter_meta');
				add_post_meta($product_id, 'simple_product_counter_meta', $count_val);
			}
		}
		if(isset($_SESSION)){
			$_SESSION['simple_product_counter_clicks'] = 1;
		}
	}

	private function store_simple_product_total_views( $product_id, $data ){
		$now_time = time();
		add_post_meta($product_id, 'simple_product_counter_meta_' . $now_time, $data);
	}
	
	public function set_simple_product_total_views(){
		global $product;
		$product_id = $product->get_id();
		$count = get_post_meta($product_id, 'simple_product_counter_total_meta', true);

		if($count == ''){
        	$count = 0;
	        delete_post_meta($product_id, 'simple_product_counter_total_meta');
	        add_post_meta($product_id, 'simple_product_counter_total_meta', '0');
	    }else{
	    	$count++;
        	update_post_meta($product_id, 'simple_product_counter_total_meta', $count);
	    }
	}

	private function spc_output_notification( $type, $data ){
		$period = $data['period'];
		$period_number = $data['period_number'];
		$count_views = $this->get_simple_product_views($period, $period_number);
		$count_sales = $this->get_simple_product_sales($period, $period_number);

		global $product;
		$product_id = $product->get_id();
		$range_sales = get_post_meta($product_id, 'simple_product_counter_range_meta_sales', true);
		$range_views = get_post_meta($product_id, 'simple_product_counter_range_meta_clicks', true);

		if($range_sales != ''){
			$count_sales = intval($count_sales) + intval($range_sales);
		}

		if($range_views != ''){
			$count_views = intval($count_views) + intval($range_views);
		}

		$styles = 'background: ' . esc_html($data['styles']['background']) . ';';
		if(isset($data['styles']['border'])){
		if($data['styles']['border'] == 1){
			$styles .= ' border: 1px solid ' . esc_html($data['styles']['border_color']) . ';';
		}
		}

		if(esc_html($data['styles']['border_radius']) != ''){
			$styles .= ' border-radius: ' . esc_html($data['styles']['border_radius']) . 'px;';
		}

		$styles_p = 'color: ' . esc_html($data['styles']['text_color']) . ';';
		$styles_p_font = 'font-size: ' . esc_html($data['styles']['font_size']) . 'px;';
		?>
		<div class="spc-notification-cont">
			<div class="spc-notification-main" style="<?php echo $styles; ?>">
				<?php 
				if( $img_icon = wp_get_attachment_image_src( $data['styles']['icon'], 'simple-product-counter-mini' ) ){
					?>
						<img src="<?php echo esc_url($img_icon[0]); ?>" width="50" height="50" />
					<?php
				} else {
					if($type == 'sales'){ ?>
					<i style="<?php echo $styles_p; ?>" class="spc spc-purchased"></i>
					<?php }else{ ?>
					<i style="<?php echo $styles_p; ?>" class="spc spc-views"></i>
					<?php 
					}
				}
				if($type == 'sales'){ ?>
					<p style="<?php echo $styles_p . $styles_p_font; ?>"><span><?php echo $count_sales; ?></span> <?php echo esc_html($data['styles']['text']); ?></p>
				<?php } else { ?>
					<p style="<?php echo $styles_p . $styles_p_font; ?>"><span><?php echo $count_views; ?></span> <?php echo esc_html($data['styles']['text']); ?></p>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}