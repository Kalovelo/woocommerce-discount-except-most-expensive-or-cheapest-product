<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://kalovelo.com
 * @since      1.0.0
 *
 * @package    Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount
 * @subpackage Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount
 * @subpackage Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount/includes
 * @author     Kalovelo <hello@kalovelo.com>
 */
class Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woo-most-expensive-or-cheapest-product-cart-discount',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
