<?php


class GoogleProductFeedProducts
{
	public function __construct()
	{

	}



	function product_list()
	{
		//Products
		$products = get_post_meta( get_the_ID(), 'gmpf_products', true );
		//need to unserialize them to give the array
		$products = unserialize($products);
		if(empty($products)) $products = array();

		$prods = 5;
	
		$table .= '<h2>Products</h2>';


		$table .= "<table id='product-table' class='display' cellspacing='0' width='100%'>
        <thead>
            <tr>
                <th style='width:5%; text-align:left'></th>
                <th style='width:30%; text-align:left'>Name</th>
                <th style='width:10%; text-align:left'>Price</th>
                <th style='width:20%; text-align:left'>Image</th>
                <th style='width:25%; text-align:left'>Variants as seperate items</th>
            </tr>
        </thead>
        <tbody>";

			$args = array( 'posts_per_page' => $prods, 'post_type'=> 'product');

			$myposts = get_posts( $args );
			foreach ( $myposts as $post ) : setup_postdata( $post ); 				
				global $product;
				//get product image
				if ( $price = $product->get_price() ) :
					$price = $price;
				endif;
				//get categories
				$product_cats = wp_get_post_terms( $post->ID, 'product_cat' );

				$product_category_class = $this->create_category_classes($product_cats);

				$feat_image = wp_get_attachment_url( get_post_thumbnail_id($post->ID)); 
				$checked_products = in_array($post->ID, $products) ? 'checked' : '';			
			  $table .= "
				<tr class='".$product_category_class."'>
	                <td><input ".$checked_products." type='checkbox' name='gmpf_products[]' value='".$post->ID."' /></td>
	                <td>".$post->post_title."</td>
	                <td>".$price."</td>
	                <td><img src='".$feat_image."' style='max-width:60px; max-height:60px' /></td>";
	            if($product->has_child()):	
	            		$table .= "<td><a href='http://www.gmpf.co.uk' target='_blank'>Upgrade to Pro include variants</a></td>";
	            else:
	            	$table .= "<td></td>";
	            endif;
	             $table .= "</tr>";
			endforeach; 
			wp_reset_postdata();

        $table .= "</tbody>
    </table>";

    echo $table;

    wp_reset_postdata(); wp_reset_query();

	}

	function create_category_classes($product_cat_array)
	{
		$cat_class = '';
		foreach ($product_cat_array as $cat) {
			$cat_class .= $cat->slug.' ';
		}
		return $cat_class;
	}

	function hide_variant()
	{
		return true;
	}
	
}