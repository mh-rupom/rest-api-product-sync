<?php
/**
 * Plugin Name: Product Sync
 * Description: WooCommerce REST API multiple sites.
 * Version: 1.1
 * Author: Mohrajul
 */

if( ! defined( 'ABSPATH' ) ) {
    exit;
}
require __DIR__ . '/vendor/autoload.php';
use Automattic\WooCommerce\Client;

function get_woocommerce_client( $url, $client_key, $client_secret, $options = [] ) {
    return new Client(
        $url,
        $client_key,
        $client_secret,
        [
            'wp_api' => true,
            'version' => 'wc/v3',
        ]
    );
}

add_action('save_post', 'wcss_sync_product_to_site', 10, 2);

function wcss_sync_product_to_site($post_id, $post) {
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    $product = wc_get_product($post_id);

    $site3_url = 'http://localhost/site3/';
    $site3_client_key = 'ck_6b88b021a658962a376a0403e192afa329e0fe34';
    $site3_client_secret = 'cs_057c576f66bfbdc1dac2762d7ee5d2645affc78f';
    $woocommerce_site3 = get_woocommerce_client($site3_url, $site3_client_key, $site3_client_secret);
    // product data
    $data = [
        'name' => $product->get_name(),
        'type' => $product->get_type(),
        'regular_price' => $product->get_regular_price(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'categories' => $product->get_category_ids(),
    ];

}

