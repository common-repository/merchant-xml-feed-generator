<?php
/*
Plugin Name: Google Merchant Product Feed (Free)
Plugin URI: http://www.gmpf.co.uk/
Description: Create Feeds for Google Merchant - promote your WooCommerce store on Google
Version: 1.1.4
Author: Nick Vaughan
Author URI: 
License: GPL
Copyright: Nick Vaughan
*/


require('gmpf-products.php');
require('gmpf-shipping.php');

class GoogleProductFeed
{




	function __construct()
	{


	}

	function actions_and_filters()
	{
		//check here if WooCommerce active.....display message if not


		$gmpf_products = new GoogleProductFeedProducts();
		$gmpf_shipping = new GoogleProductFeedShipping();

		add_action( 'init', array($this, 'init' ));

		add_action( 'admin_enqueue_scripts', array($this,'load_assets') );

		//load metabox on GMPF page
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );

		//load google category metabox on Products page
		//add_action( 'add_meta_boxes', array( $this, 'add_google_category_meta_box' ) );
		//add_action( 'save_post', array( $this, 'save_google_category' ) );

		//load google shipping meta boxes
		//add_action( 'save_post', array( $gmpf_shipping, 'save_google_shipping' ) );

		add_filter( 'single_template', array($this,'xml_feed_template') );

		//add_action("gmpf_load_category_filter", array($gmpf_categories,"categories"));

		add_action("gmpf_load_product_list", array($gmpf_products,"product_list"));

		add_action("gmpf_load_shipping", array($gmpf_shipping,"shipping_options"));

		//use the filter to determine how many products to show
   		add_filter("gmpf_products_to_show", array($this,"modify_products_to_show"));
   		add_filter("gmpf_hide_variants", array($gmpf_products,"hide_variant"));

   		//add upgrade notification
   		function my_update_notice() {
    
			    echo '<div class="updated notice" style="border: 2px solid #10ff00;border-radius: 10px;">
			        <p><span style="font-weight:bold">Google Merchant Product Feed: </span> Why not upgrade to the <a href="http://www.gmpf.co.uk/" target="_blank">Ultimate Version</a> for unlimited products and product variants</p>
			    </div>';
			    
			}
			
			//if (get_post_type() == 'gmpf') {
				add_action( 'admin_notices', 'my_update_notice' );
			//}
	}

	function init()
	{
		//setup the Google Feed Custom Post Type
		$labels = array(
		    'name' => __( 'Google Merchant Feeds', 'gmpf' ),
			'singular_name' => __( 'Google Merchant Feed', 'gmpf' ),
		    'add_new' => __( 'Add New' , 'gmpf' ),
		    'add_new_item' => __( 'Add New Feed' , 'gmpf' ),
		    'edit_item' =>  __( 'Edit Feed' , 'gmpf' ),
		    'new_item' => __( 'New Feed' , 'gmpf' ),
		    'view_item' => __('View Feed', 'gmpf'),
		    'search_items' => __('Search Feeds', 'gmpf'),
		    'not_found' =>  __('No Feeds found', 'gmpf'),
		    'not_found_in_trash' => __('No Feeds found in Trash', 'gmpf'), 
		);
		
		register_post_type('gmpf', array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true,
			'_builtin' =>  false,
			'capability_type' => 'post',
			'hierarchical' => true,
			'rewrite' => array('slug','gmpf'),
			'query_var' => true,
			'supports' => array(
				'title',
			),
			'show_in_menu'	=> true,
			'menu_icon'   => 'dashicons-editor-code',
		));
	}

	function load_assets() {

		if(get_post_type()!='gmpf') return;

		//load main js file
		wp_enqueue_script( 'gmpf_scripts', plugins_url('js/gmpf_scripts.js', __FILE__), array('datatable_scripts','tooltipster_scripts'));

		//load font awesome style
        wp_register_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css', false, '4.5.0' );
        wp_enqueue_style( 'font-awesome' );

		//load gmpf style
        wp_register_style( 'gmfp_styles', plugins_url('css/gmpf_styles.css', __FILE__), false, '1.0.0' );
        wp_enqueue_style( 'gmfp_styles' );

		//load data table style
        wp_register_style( 'datatable_styles', plugins_url('datatables/datatables.min.css', __FILE__), false, '1.0.0' );
        wp_enqueue_style( 'datatable_styles' );

        //load tooltipster style
        wp_register_style( 'tooltipster', plugins_url('tooltipster/css/tooltipster.css', __FILE__), false, '1.0.0' );
        wp_enqueue_style( 'tooltipster' );
        wp_register_style( 'tooltipster-theme', plugins_url('tooltipster/css/themes/tooltipster-light.css', __FILE__), false, '1.0.0' );
        wp_enqueue_style( 'tooltipster-theme' );

        //load data table scripts
        wp_enqueue_script( 'datatable_scripts', plugins_url('datatables/datatables.min.js', __FILE__) );

        //load tooltipster scripts
        wp_enqueue_script( 'tooltipster_scripts', plugins_url('tooltipster/js/jquery.tooltipster.min.js', __FILE__) );
	}


	function add_meta_box( $post_type ) {
		$post_types = array('gmpf');   //limit meta box to certain post types
		if ( in_array( $post_type, $post_types )) {
			add_meta_box(
				'gmpf_settings'
				,__( 'Your Feed', 'gmpf_textdomain' )
				,array( $this, 'render_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);
		}
	}

	
	public function save( $post_id ) {	
		
		if ( ! isset( $_POST['myplugin_inner_custom_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST['myplugin_inner_custom_box_nonce'];
		
		if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box' ) )
			return $post_id;
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;
		
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}		

		// Sanitize the user input.
		$shop_link = sanitize_text_field( $_POST['gmpf_shop_link'] );
		update_post_meta( $post_id, 'gmpf_shop_link', $shop_link );

		$description = sanitize_text_field( $_POST['gmpf_description'] );
		update_post_meta( $post_id, 'gmpf_description', $description );

		$google_category = sanitize_text_field( $_POST['gmpf_default_google_category'] );
		update_post_meta( $post_id, 'gmpf_default_google_category', $google_category );

		$filterTye = sanitize_text_field( $_POST['gmpf_filtertype'] );
		update_post_meta( $post_id, 'gmpf_filtertype', $filterTye );

		//products selected
		$products = sanitize_text_field( serialize($_POST['gmpf_products']) );
		update_post_meta( $post_id, 'gmpf_products', $products );


		//shipping method
		$shipping = sanitize_text_field($_POST['gmpf_shipping_method']);
		update_post_meta( $post_id, 'gmpf_shipping_method', $shipping );

		//shipping county
		$shipping_country = sanitize_text_field($_POST['shipping_country_selected']);
		update_post_meta( $post_id, 'gmpf_shipping_country', $shipping_country );


	}

	
	public function render_meta_box_content( $post ) {

		
	
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		//shop link
		$shop_link = get_post_meta( $post->ID, 'gmpf_shop_link', true );
		if(!$shop_link) $shop_link = get_permalink( woocommerce_get_page_id( 'shop' ));
		//description
		$description = get_post_meta( $post->ID, 'gmpf_description', true );
		//default google category
		$google_category = get_post_meta( $post->ID, 'gmpf_default_google_category', true );



		echo '	<ul class="tabs">
		<li class="tab-link current" data-tab="tab-1">Feed Settings</li>
		<li class="tab-link" data-tab="tab-2">Products</li>
		';

		$hide = apply_filters('gmpf_hide_variants', array($this, 'hide_variant'));	

		echo '<li class="tab-link" data-tab="tab-4">Shipping</li>
		</ul>';


		echo '<div id="tab-1" class="tab-content current">';

		echo '<h2>Feed Settings</h2>';


		/*Shop Link Field*/
		echo '<p>';
		echo '<label for="gmpf_shop_link" class="gmpf_block">';
		_e( 'Link to the shop or category page <i class="fa fa-question-circle fa-2" title="Add the link to the top level landing page for the feed. <br>This could be a category page or the main shop landing page"></i>', 'gmpf_textdomain' );
		echo '</label> ';
		echo '<input type="text" id="gmpf_shop_link" name="gmpf_shop_link"';
		echo ' value="' . esc_attr( $shop_link ) . '" size="25" />';
		echo '</p>';

			/*Description Field*/
		echo '<p>';
		echo '<label for="gmpf_description" class="gmpf_block">';
		_e( 'Feed description <i class="fa fa-question-circle fa-2" title="Give your feed a short description describing your products and shop. This will be added to the Google Feed"></i>', 'gmpf_textdomain' );
		echo '</label> ';
		echo '<textarea id="gmpf_description" name="gmpf_description">'.esc_attr( $description ).'</textarea>';		
		echo '</p>';

		/*Default Product Category Field*/
		echo '<p>';
		echo '<label for="gmpf_shop_link" class="gmpf_block">';
		_e( 'Default Google Product Category <i class="fa fa-question-circle fa-2" title="If the Google Category hasn\'t been set at a product level you can specify a default category to use here"></i>', 'gmpf_textdomain' );
		echo '</label> ';
		echo '<span class="note">For a full list of Google Categories, see here: <a target="_blank" href="https://support.google.com/merchants/answer/160081">https://support.google.com/merchants/answer/160081</a></span>';
		echo '<input placeholder="eg. Clothing & Accessories > Clothing > Outerwear" type="text" id="gmpf_default_google_category" name="gmpf_default_google_category"';
		echo ' value="' . esc_attr( $google_category ) . '" size="25" />';
		echo '</p>';

		echo '</div>';

		echo '<div id="tab-2" class="tab-content">';

		do_action("gmpf_load_category_filter"); //load in the categor filter

		do_action("gmpf_load_product_list"); //load in the product list
		echo '</div>';

		echo '<div id="tab-4" class="tab-content">';
		
		/* SHIPPING SETTINGS*/
		
		do_action("gmpf_load_shipping"); //load in the shipping options

		echo '</div>';

	}

	function add_google_category_meta_box( $post_type ) {
		$post_types = array('product');   //limit meta box to certain post types
		if ( in_array( $post_type, $post_types )) {
			add_meta_box(
				'gmpf_google_category'
				,__( 'Google Merchant Feed - Product Category', 'gmpf_product_textdomain' )
				,array( $this, 'render_google_category_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);
		}
	}


	function render_google_category_meta_box_content()
	{
		wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );

		$google_category = get_post_meta( get_the_ID(), 'gmpf_google_category', true );
		
		//echo '<h2>Feed Settings</h2>';


		/*Shop Link Field*/
		echo '<p>';
		echo '<label for="gmpf_google_category" class="gmpf_block">';
		_e( 'Category for Google Merchant', 'gmpf_product_textdomain' );
		echo '</label> ';
		echo '<div>';
		echo '<input type="text" id="gmpf_google_category" name="gmpf_google_category"';
		echo ' value="' . esc_attr( $google_category ) . '" style="width:100%" />';
		echo '<span class="note">For a full list of Google Categories, see here: <a target="_blank" href="https://support.google.com/merchants/answer/160081">https://support.google.com/merchants/answer/160081</a></span>';
		echo '</div>';
		echo '</p>';
		
	}




	function xml_feed_template( $template )
	{	    
		global $post;

		//print_r($template);

     	if ($post->post_type == 'gmpf') 
     	{
     		//echo 'GMPF';
			$template = plugin_dir_path( __FILE__ ) . 'single-gmpf.php';
	    	return $template;
		}
		//echo 'NOT GMPF';
		return $template;
	    
	}

	function modify_products_to_show() {
      return 5;
   }

}


$gpf = new GoogleProductFeed();
$gpf->actions_and_filters();

