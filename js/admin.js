jQuery(document).ready(function($){
	// Admin settings
	$('.simple-product-counter-main-wrapper .spc-settings-type-itm').click(function(){
		$('.simple-product-counter-main-wrapper .spc-settings-cont-main').removeClass('act');
		$('.simple-product-counter-main-wrapper .spc-settings-cont .spc-settings-cont-main-settings').addClass('act');
		var target = $(this).attr('data-target');
		$('.spc-settings-cont-main-settings .tabs-nav ul li').removeClass('act');
		$('.spc-settings-cont-main-settings .tabs-nav ul li[data-target="'+target+'"]').addClass('act');

		$('.spc-settings-cont-main-settings .spc-main-settings-cont').removeClass('act');
		$('.spc-settings-cont-main-settings #' + target).addClass('act');

		$('.spc-settings-cont-main-settings input[name="simple_product_count_settings"]').val(target);
	});

	$('.spc-settings-cont-main-settings .tabs-nav ul li').click(function(){
		var target = $(this).attr('data-target');
		$('.spc-settings-cont-main-settings .tabs-nav ul li').removeClass('act');
		$(this).addClass('act');
		$('.spc-settings-cont-main-settings .spc-main-settings-cont').removeClass('act');
		$('.spc-settings-cont-main-settings #' + target).addClass('act');

		$('.spc-settings-cont-main-settings .only-one-tab').removeClass('active');
		$('.spc-settings-cont-main-settings .only-one-tab[data-tab="'+target+'"]').addClass('active');

		$('.spc-settings-cont-main-settings input[name="simple_product_count_settings"]').val(target);
	});

	$('.spc-settings-cont-main-settings .spc-border-admin input').change(function(){
		if($(this).is(':checked')){
			$(this).closest('.settings-cont-fields').find('.spc-border-admin-enable').addClass('act');
		}else{
			$(this).closest('.settings-cont-fields').find('.spc-border-admin-enable').removeClass('act');
		}
	});

	$('.spc-saved-notification .close').click(function(){
		$('.spc-saved-notification').remove();
	});

	setTimeout(function(){
		$('.spc-saved-notification').remove();
	}, 2000);

	// Color
	$('.spc-color-field').wpColorPicker({
		change: function(event, ui) {
			var color = ui.color.toString();
			var element = event.target;
			var field_name = $(element).attr('name');

			if( field_name == 'spc_settings_sales[styles][background]' ){
				$('.preview-not-sales .notification').css('background', color);
			}else if( field_name == 'spc_settings_clicks[styles][background]' ){
				$('.preview-not-clicks .notification').css('background', color);
			}else if( field_name == 'spc_settings_sales[styles][border_color]' ){
				$('.preview-not-sales .notification').css('border', '1px solid ' + color);
			}else if( field_name == 'spc_settings_clicks[styles][border_color]' ){
				$('.preview-not-clicks .notification').css('border', '1px solid ' + color);
			}else if( field_name == 'spc_settings_sales[styles][text_color]' ){
				$('.preview-not-sales .notification p').css('color', color);
				$('.preview-not-sales .notification i').css('color', color);
				$('.preview-not-sales .notification i *').css('color', color);
			}else if( field_name == 'spc_settings_clicks[styles][text_color]' ){
				$('.preview-not-clicks .notification p').css('color', color);
				$('.preview-not-clicks .notification i').css('color', color);
				$('.preview-not-clicks .notification i *').css('color', color);
			}
		}
	});

	// Preview
	$('.simple-product-counter-main-wrapper input[name="spc_settings_sales[styles][border_radius]"]').change(function(){
		$('.preview-not-sales .notification').css('border-radius', $(this).val() + 'px');
	});
	$('.simple-product-counter-main-wrapper input[name="spc_settings_clicks[styles][border_radius]"]').change(function(){
		$('.preview-not-clicks .notification').css('border-radius', $(this).val() + 'px');
	});
	$('.simple-product-counter-main-wrapper input[name="spc_settings_sales[styles][font_size]"]').change(function(){
		$('.preview-not-sales .notification p').css('font-size', $(this).val() + 'px');
	});
	$('.simple-product-counter-main-wrapper input[name="spc_settings_clicks[styles][font_size]"]').change(function(){
		$('.preview-not-clicks .notification p').css('font-size', $(this).val() + 'px');
	});
	$('.simple-product-counter-main-wrapper input[name="spc_settings_sales[styles][border]"]').change(function(){
		if($(this).is(':checked')){
			$('.preview-not-sales .notification').css('border', '1px solid ' + $('input[name="spc_settings_sales[styles][border_color]"]').val());
		}else{
			$('.preview-not-sales .notification').css('border', 'none');
		}
	});
	$('.simple-product-counter-main-wrapper input[name="spc_settings_clicks[styles][border]"]').change(function(){
		if($(this).is(':checked')){
			$('.preview-not-clicks .notification').css('border', '1px solid ' + $('input[name="spc_settings_clicks[styles][border_color]"]').val());
		}else{
			$('.preview-not-clicks .notification').css('border', 'none');
		}
	});
	
	// Confirm Navigation Popup
	var needToConfirm = false;
	var submitted = false;
	window.onbeforeunload = askConfirm;

	function askConfirm() {
		if (needToConfirm && !submitted) {
			return "Your unsaved data will be lost."; 
		}
	}

	$("#spc_main_settings_form").change(function() {
		needToConfirm = true;
	});

	$("#spc_main_settings_form").submit(function(){
		submitted = true;
	});

	$('.spc-upl-image').on('click', 'span', function(e){
		e.preventDefault();

		var el = $(this).closest('.spc-upl-image');
		var custom_uploader = wp.media({
			title: 'Insert icon',
			library : {
				type : 'image'
			},
			multiple: false
		}).on('select', function() {
			var attachment = custom_uploader.state().get('selection').first().toJSON();
			el.addClass('img');
			el.html('<span><img src="' + attachment.url + '"></span><div class="spc-upl-remove">+</div>');
			el.next().val(attachment.id);

			if( el.closest('#sales').length != 0 ){
				$('.preview-not-sales .notification').find('i').remove();
				$('.preview-not-sales .notification').find('img').remove();
				$('.preview-not-sales .notification').prepend('<img src="' + attachment.url + '">');
			}

			if( el.closest('#clicks').length != 0 ){
				$('.preview-not-clicks .notification').find('i').remove();
				$('.preview-not-clicks .notification').find('img').remove();
				$('.preview-not-clicks .notification').prepend('<img src="' + attachment.url + '">');
			}
		}).open();
	});

	$('.spc-upl-image').on('click', '.spc-upl-remove', function(e){
		e.preventDefault();

		if( $(this).parent().closest('#sales').length != 0 ){
			$('.preview-not-sales .notification').find('img').remove();
			$('.preview-not-sales .notification').find('i').remove();
			$('.preview-not-sales .notification').prepend('<i class="spc spc-purchased"></i>');
		}
		if( $(this).parent().closest('#clicks').length != 0 ){
			$('.preview-not-clicks .notification').find('img').remove();
			$('.preview-not-clicks .notification').find('i').remove();
			$('.preview-not-clicks .notification').prepend('<i class="spc spc-views"></i>');
		}
		$(this).parent().next().val('');
		$(this).parent().removeClass('img');
		$(this).parent().html('<span>Upload image</span>');
	});
});