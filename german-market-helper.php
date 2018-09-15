<?php
namespace epiphyt\German_Market_Helper;

/*
Plugin Name:	German Market Helper
Description:	A small utility to fix some weird German Market settings/features.
Version:		1.0.0
Author:			Epiphyt
Author URI:		https://epiph.yt
License:		GPL2
*/
class German_Market_Helper {
	/**
	 * German Market Helper constructor.
	 */
	public function __construct() {
		// make sure the functions of this plugin load after all other plugins
		\add_action( 'init', [ $this, 'german_market_helper_function' ], 100 );
		// on changing checkout settings, recheck for KUR
		\add_action( 'woocommerce_checkout_update_order_review', [ $this, 'german_market_helper_function' ] );
	}
	
	/**
	 * Helper function.
	 * 
	 * param	string		$data The changed data during Ajax changes
	 */
	public function german_market_helper_function( string $data = '' ) {
		// check if WooCommerce and German Market are available
		if (
			! \is_object( WC() )
			|| ! \class_exists( 'WGM_Helper' )
		) return;
		
		// get country code
		$country_code = WC()->customer->get_shipping_country();
		
		// get country code from data in checkout
		if ( ! empty( $data ) ) {
			\parse_str( $data, $post_data );
			
			// use either the new data if available or stay on the old value
			$country_code = ( $post_data['billing_country'] ?? $country_code );
		}
		
		// for German users, enable KUR in the frontend
		// the backend is still fully accessible (otherwise taxes couldn't be
		// changed)
		// every non-German users would have the full VAT rates
		if ( $country_code === 'DE' && ! \is_admin() ) {
			\update_option( \WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ), 'on' );
		}
		else {
			// manually disable KUR for non-German users
			\update_option( \WGM_Helper::get_wgm_option( 'woocommerce_de_kleinunternehmerregelung' ), 'off' );
			
			// remove notice in the backend if KUR is enabled
			\remove_action( 'woocommerce_review_order_after_order_total', [ 'WGM_Template', 'kur_review_order_notice' ], 10 );
			
			// remove KUR notices from frontend
			\remove_filter( 'woocommerce_get_formatted_order_total', [ 'WGM_Template', 'kur_review_order_item' ], 10 );
			\remove_filter( 'woocommerce_get_shipping_tax', [ 'WGM_Shipping', 'remove_kur_shipping_tax', ], 20 );
			
			// add taxes to the cart and checkout
			\update_option( 'woocommerce_calc_taxes', 'yes' );
			\update_option( 'woocommerce_prices_include_tax', 'yes' );
			\update_option( 'woocommerce_tax_display_shop', 'incl' );
			\update_option( 'woocommerce_tax_display_cart', 'incl' );
		}
	}
}

new German_Market_Helper();
