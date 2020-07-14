<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://kalovelo.com
 * @since      1.0.0
 *
 * @package    Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount
 * @subpackage Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount
 * @subpackage Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount/public
 * @author     Kalovelo <hello@kalovelo.com>
 */

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	class Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount_Public
	{

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string    $plugin_name       The name of the plugin.
		 * @param      string    $version    The version of this plugin.
		 */

		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles()
		{

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-most-expensive-or-cheapest-product-cart-discount-public.css', array(), $this->version, 'all');
		}

		/**
		 * Register the JavaScript for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts()
		{

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-most-expensive-or-cheapest-product-cart-discount-public.js', array('jquery'), $this->version, false);
		}




		/**
		 *
		 * public/class-woo-most-expensive-or-cheapest-product-cart-discount-public.php
		 *
		 **/

		public function __construct($plugin_name, $version)
		{
			$this->plugin_name = $plugin_name;
			$this->version = $version;
			$this->woo_mecd_options = get_option($this->plugin_name);

			// called only after woocommerce has finished loading
			add_action('woocommerce_init', array($this, 'woocommerce_loaded'));
		}

		function woocommerce_loaded()
		{
			if (!empty($this->woo_mecd_options['discount']) && !empty($this->woo_mecd_options['edge'])) {
				add_action('woocommerce_cart_updated', array($this, 'add_5perc_on_most_expensive_product'), 10, 0);
			}
		}

		/**
		 * Cleanup functions depending on each checkbox returned value in admin
		 *
		 * @since    1.0.0
		 */
		// Add custom Theme Functions here

		public function add_5perc_on_most_expensive_product()
		{
			if (is_admin() && !defined('DOING_AJAX'))
				return;

			if (did_action('woocommerce_before_calculate_totals') >= 1)
				return;

			$cart = WC()->cart;
			$discount_type = $this->woo_mecd_options['edge'];
			limit_cart_item_quantity($cart, $discount_type);

			$cart_products = sort_cart_by_price($cart->get_cart(), $discount_type);



			$price_modifier = $this->woo_mecd_options['discount'] / 100;
			$price_modifier_html = '<span class="cart__discounted_product_title"> -' . strval($price_modifier * 100) . '%</span>';

			// discount for each product in cart
			foreach ($cart_products as $key => $product) {

				//sorted array, skip the most expensive product
				if ($key == 0) {
					continue;
				}
				$product_price = floatval($product['data']->get_price());
				$discount = $product_price * $price_modifier;

				$new_price = $product_price - $discount;
				$product['data']->set_price($new_price);

				# show price modifier
				$product_name = $product['data']->get_name();
				$product['data']->set_name($product_name . $price_modifier_html);
			}
		}
	}



	function limit_cart_item_quantity($cart, $discount_type)
	{
		$cart_data = sort_cart_by_price($cart->cart_contents, $discount_type);
		$me = $cart_data[0];
		$me_q = $me['quantity'];


		// find products tagged with is_most_expensive
		$x = array_filter($cart->cart_contents, function ($el) {
			return $el['unique_key'] == 'is_most_expensive';
		});

		foreach ($x as $key => $product) {
			// if cart contains more than 1 products tagged with is_most_expensive, remove and re-insert them
			if (edge_comparison($product['data']->get_price(), $me['data']->get_price(), $discount_type)) {
				$cart->remove_cart_item($product['key']);
				$cart->add_to_cart($product['product_id'], $product['quantity']);
			}
		}

		// if most expensive product has quantity>1, split it
		if ($me_q > 1) {
			$cart->remove_cart_item($me['key']);
			$cart->add_to_cart($me['product_id'], 1, null, null, array('unique_key' => 'is_most_expensive'));
			$cart->add_to_cart($me['product_id'], $me_q - 1);
			WC()->cart->calculate_totals();
		}
	}

	function sort_cart_by_price($cart, $discount_type)
	{
		usort($cart, function ($first, $second) use (&$discount_type) {
			return edge_comparison($first['data']->get_price(), $second['data']->get_price(), $discount_type) or $second['unique_key'];
		});

		return $cart;
	}
	function edge_comparison($first, $second, $type)
	{
		if ($type == 'most expensive') {
			return $first < $second;
		}
		if ($type == 'cheapest') {
			return $first > $second;
		}
	}
}
