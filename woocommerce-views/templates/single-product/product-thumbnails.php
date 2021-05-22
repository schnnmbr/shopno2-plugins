<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     3.5.1
 */

defined( 'ABSPATH' ) || exit;

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
	return;
}

// Function to extend core css class for image item. We cannot replace it because WC is using it for JS.
if( ! function_exists( 'wooviews_replace_product_gallery_image_css_class' ) ) {
	function wooviews_replace_product_gallery_image_css_class( $html ) {
		return preg_replace(
			'#(class=[\'"].*?)(woocommerce-product-gallery__image)([ \'"])#im',
			'$1$2 wooviews-template-product-gallery__image$3',
			$html
		);
	}
}
// Add filter to extend core class.
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'wooviews_replace_product_gallery_image_css_class' );

global $product;

$attachment_ids = $product->get_gallery_image_ids();

if ( $attachment_ids && $product->get_image_id() ) {
	foreach ( $attachment_ids as $attachment_id ) {
		echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $attachment_id ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
	}
}

// Remove filter to extend core class. To make sure our class is only applied when our template is used.
remove_filter( 'woocommerce_single_product_image_thumbnail_html', 'wooviews_replace_product_gallery_image_css_class' );
