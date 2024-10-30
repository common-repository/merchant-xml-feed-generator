<?php

class GoogleProductFeedShipping
{

	public function __construct()
	{

	}



	function shipping_options()
	{
		global $post;

		wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );

	
		$shipping_type = get_post_meta( get_the_ID(), 'gmpf_shipping_method', true );
		$shipping_ctry = get_post_meta( get_the_ID(), 'gmpf_shipping_country', true );

		$ship_to = get_post_meta( get_the_ID(), 'gmpf_shipping_country', true );
		$ship_to = unserialize($ship_to);


		$shipping_method .=  '<h2>Shipping Settings</h2>';

		$shipping_method .= '<p>Enable your shipping methods and countries to which you ship to within the WooCommerce settings. You will then be able to select the shipping method and country to be used within your feed</p>';

		$wc_shipping = new WC_Shipping();

		$shipping = $wc_shipping->load_shipping_methods();

		$ship_to = get_post_meta( get_the_ID(), 'gmpf_shipping_country', true );

		$shipping_available = false;

		$shipping_method .= '<span class="label">Shipping Method: </span>';

		$shipping_method .= '<select name="gmpf_shipping_method" class="shipping_method">';
		$shipping_method .= '<option value="0">Please Select</option>';

		foreach ($shipping as $key => $value) {

			$settings = $value->settings;
			

			if($settings['enabled']=='yes'){
				$selected = ($shipping_type==$key) ? 'selected' : '';
				$shipping_method .= '<option '.$selected.' value="'.$key.'">'.$value->method_title.'</option>';
				$shipping_available = true;
			}

		}
		$shipping_method .= '</select>';
		//countries
		
		foreach ($shipping as $key => $value) {

			$visibility = ($shipping_type == $key) ? 'style="display:block"' : '';
			$shipping_country .= '<div class="country_select '.$key.'" '.$visibility.'>';
			$shipping_country .= '<span class="label">Ship to: </span>';
			$shipping_country .= '<select name="gmpf_shipping_country[]" class="shipping_country">';
			$shipping_country .= '<option value="0">Please Select</option>';

				$settings = $value->settings;	

				$countries = $settings['countries'];

				if($countries):
					foreach($countries as $country)
					{
						$selected = ($country == $ship_to) ? 'selected' : '';
						$shipping_country .= '<option '.$selected.' value="'.$country.'">'.$country.'</option>';
					}
				endif;

			$shipping_country .= '</select>';

			$shipping_country .= '<input type="hidden" class="shipping_country_selected" name="shipping_country_selected" value="'.$ship_to.'" />';

			$shipping_country .= '</div>';
		}
		



		if($shipping_available){
			echo $shipping_method;

			echo $shipping_country;
		}
		else
		{
			echo 'There are no shipping methods enabled within WooCommerce';
		}

	
		// echo '<p>';
		// echo '<label for="gmpf_shipping_country" class="gmpf_block">';
		// _e( 'Sgipping Country <i class="fa fa-question-circle fa-2"></i>', 'gmpf_textdomain' );
		// echo '</label> ';
		// echo '<textarea id="gmpf_shipping_country" name="gmpf_shipping_country">'.esc_attr( $shipping_country ).'</textarea>';		
		// echo '</p>';
	}





}