<?php

class SimpleProductCounterFields {
	public function text_field( $name, $val, $class = '' ){
		?>
		<input class="<?php echo $class; ?>" type="text" name="<?php echo $name; ?>" value="<?php echo $val; ?>" />
		<?php
	}

	public function number_field( $name, $val, $class = '' ){
		?>
		<input class="<?php echo $class; ?>" type="number" min="0" name="<?php echo $name; ?>" value="<?php echo $val; ?>" />
		<?php
	}

	public function color_field( $name, $val, $class = '' ){
		?>
		<input class="<?php echo $class; ?> spc-color-field" type="text" name="<?php echo $name; ?>" value="<?php echo $val; ?>" />
		<?php
	}

	public function select_field( $name, $val, $options, $class = '' ){
		?>
			<select class="<?php echo $class; ?>" name="<?php echo $name; ?>">
			<?php if(!empty($options)): ?>
			<?php foreach($options as $key => $value){ ?>
				<option value="<?php echo $value['name']; ?>" <?php selected($val, $value['name']); ?>><?php echo $value['label']; ?></option>
			<?php } ?>
			<?php endif; ?>
			</select>
		<?php
	}

	public function textarea_field( $name, $val, $class = '' ){
		?>
		<textarea class="<?php echo $class; ?>" name="<?php echo $name; ?>"><?php echo $val; ?></textarea>
		<?php
	}

	public function switcher_field( $name, $val ){
		?>
		<div class="spc-switsher">
			<input id="<?php echo $name; ?>" type="checkbox" name="<?php echo $name; ?>" value="1"<?php checked( 1 == $val ); ?>>
			<label for="<?php echo $name; ?>"></label>
		</div>
		<?php
	}

	public function image_field( $name, $val ) {
		if( $image = wp_get_attachment_image_src( $val, 'simple-product-counter-mini' ) ) {
		?>
			<div class="spc-upl-image img">
				<span>
					<img src="<?php echo esc_url($image[0]); ?>" />
				</span>
				<div class="spc-upl-remove">+</div>
			</div>
	    	<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $val; ?>" />
		<?php
		} else {
		?>
			<div class="spc-upl-image"><span>Upload image</span></div>
	    	<input type="hidden" name="<?php echo $name; ?>" value="" />
		<?php
		}
	}
}