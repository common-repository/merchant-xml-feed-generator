<?php

//require('gmpf-attributes.php');

class GoogleProductFeedGenerator
{

	private $feed_id;
	
	function __construct($feed_id)
	{
		$this->feed_id = $feed_id;
	}

	function content_type()
	{
		header('Content-Type: text/xml');


	}

	function headers()
	{

		$header .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
		$header .= '<channel>';
		$header .= '<title>'.get_the_title().'</title>';
		$header .= '<link>'.get_post_meta( get_the_ID(), "gmpf_shop_link", true ).'</link>';
		$header .= '<description>'.get_post_meta( get_the_ID(), "gmpf_description", true ).'</description>';

		return $header;
	}

	function footer()
	{
		$footer .= '</channel>';
		$footer .= '</rss>';

		return $footer;
	}

	function get_products_array()
	{
		$products = get_post_meta( $this->feed_id, 'gmpf_products', true );
		$products = unserialize($products);
		return $products;
	}


	function products_loop()
	{
		$count = 1;
		$args = array( 'post_type' => 'product', 'post__in'=>$this->get_products_array(), 'posts_per_page' => -1 );
		$loop = new WP_Query( $args );
		while ( $loop->have_posts() ) : $loop->the_post();

			global $product;


			$cat = $this->get_google_category(get_the_ID());

			$feat_image = wp_get_attachment_url( get_post_thumbnail_id(get_the_ID())); 

			if ( $price = $product->get_price() ){
			   $price = $price;
			}
			$sale_price = $product->sale_price;
			$sku = ($product->sku) ? $sku : $count;

			$stock = ($product->is_in_stock()) ? 'in stock' : 'out of stock';

			if($this->has_variants(get_the_ID()))
			{
				$this->get_variants(get_the_ID());
			}
			else
			{

				$item .= '<item>';
					$item .= '<title>'.get_the_title().'</title>';
					$item .= '<link>'.get_the_permalink().'</link>';
					$item .= '<description><![CDATA['.strip_tags(get_the_content()).']]></description>';
					$item .= '<g:image_link>'.$feat_image.'</g:image_link>';
					$item .= '<g:product_type>'.$cat.'</g:product_type>';
					$item .= '<g:google_product_category>'.$cat.'</g:google_product_category>';
					$item .= '<g:price>'.$price.'</g:price>';
					if($sale_price) $item .= '<g:sale_price>'.$sale_price.'</g:sale_price>';
					$item .= '<g:condition>new</g:condition>';
					$item .= '<g:id>'.$sku.'</g:id>'; //should be SKU
					$item .= '<g:identifier_exists>FALSE</g:identifier_exists>';
					$item .= '<g:availability>'.$stock.'</g:availability>';
					
					//need to get shipping info
					
					$item .= $this->get_shipping();

				$item .= '</item>';

			}

			$count++; 
		endwhile;

		return $item;

	}

	function get_variants($product_id)
	{

		$children_array = get_children( array(
			'post_parent' => $product_id,
			'post_type'   => 'product_variation', 
			'numberposts' => -1,
			'post_status' => 'any' 
		), ARRAY_A );

		



		foreach ($children_array as $key => $value) {
			$variant = new WC_Product_Variation($value['ID']);



			$cat = $this->get_google_category($product_id);

			$stock = ($variant->is_in_stock()) ? 'in stock' : 'out of stock';

			$image_id = $variant->get_image_id();
			$image = wp_get_attachment_image_src($image_id);



			$item .= '<item>';
				$item .= '<title>'.get_the_title($product_id).'</title>';
				$item .= '<link>'.get_the_permalink($product_id).'</link>';
				$item .= '<description><![CDATA['.strip_tags(get_the_content()).']]></description>';
				$item .= '<g:image_link>'.$image[0].'</g:image_link>';
				$item .= '<g:product_type>'.$cat.'</g:product_type>';
				$item .= '<g:google_product_category>'.$cat.'</g:google_product_category>';
				$item .= '<g:price>'.$variant->get_price().'</g:price>';
				if($variant->get_sale_price()) $item .= '<g:sale_price>'.$variant->get_sale_price().'</g:sale_price>';
				$item .= '<g:condition>new</g:condition>';
				$item .= '<g:id>'.$variant->get_sku().'</g:id>'; //should be SKU
				$item .= '<g:identifier_exists>FALSE</g:identifier_exists>';
				$item .= '<g:availability>'.$stock.'</g:availability>';
				$item .= '<g:item_group_id>'.$product_id.'</g:item_group_id>';	
				if($this->get_attributes($value['ID'])) $item .= $this->get_attributes($value['ID']);
				$item .= $this->get_shipping();

			$item .= '</item>';

		}

		
		echo $item;

	}

	function get_attributes($variant_ID)
	{
		$attributes = new GoogleProductFeedAttributes();
		$variant_attributes = $attributes->get_attribute_list();

		//get assigned attributes
		foreach ($variant_attributes as $att) {
			$attribute_taxonomy = get_post_meta( $this->feed_id, 'gmpf_attribute_'.$att, true );

			$terms = wp_get_post_terms($value['ID'], 'pa_colour');

			if($attribute_taxonomy != '0')
			{

				$item .= '<g:'.$att.'>'.get_post_meta( $variant_ID, "attribute_".$attribute_taxonomy, true ).'</g:'.$att.'>';


			}

		}

		return $item;

	}

	function has_variants($product_id)
	{
		$variants = get_post_meta( $this->feed_id, 'gmpf_product_variants', true );
		$variants = unserialize($variants);

		if($variants && in_array($product_id, $variants))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function get_shipping()
	{
		$shipping_method = get_post_meta( $this->feed_id, 'gmpf_shipping_method', true );

		$wc_shipping = new WC_Shipping();
		$shipping_methods = $wc_shipping->load_shipping_methods();

		$ship_to = get_post_meta( $this->feed_id, 'gmpf_shipping_country', true );

		$shipping_price = $shipping_methods[$shipping_method]->settings['cost_per_order'];
		$shipping_name = $shipping_methods[$shipping_method]->method_title;
		$shipping_price = ($shipping_price) ? $shipping_price : 0;

		$currency = get_woocommerce_currency();

		$shipping .= '<g:shipping>';
			$shipping .= '<g:country>'.$ship_to.'</g:country>';
			$shipping .= '<g:service>'.$shipping_name.'</g:service>';
			$shipping .= '<g:price>'.$shipping_price.' '.$currency.'</g:price>';				
		$shipping .= '</g:shipping>';

		return $shipping;
	}


	function get_google_category($product_id)
	{
		$cat = get_post_meta( $product_id, 'gmpf_google_category', true );
		if(empty($cat)) $cat = get_post_meta( $this->feed_id, 'gmpf_default_google_category', true );

		$cat = str_replace("&", "&amp;", $cat);
		$cat = str_replace(">", "&gt;", $cat);

		return $cat;
	}







}