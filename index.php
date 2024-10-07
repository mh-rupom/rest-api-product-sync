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
function get_woocommerce_client( $url, $client_key, $client_secret ) {
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
add_action('save_post_product', 'wcss_sync_product_to_site', 10, 2);
function wcss_sync_product_to_site($post_id, $post) {
    if (get_post_type($post_id) !== 'product') {
        return;
    }
    $product = wc_get_product($post_id);
    $site3_url = 'http://localhost/site3/';
    $site3_client_key = 'ck_6b88b021a658962a376a0403e192afa329e0fe34';
    $site3_client_secret = 'cs_057c576f66bfbdc1dac2762d7ee5d2645affc78f';
    $woocommerce_site = get_woocommerce_client($site3_url, $site3_client_key, $site3_client_secret);
    $categories = [];
    foreach ($product->get_category_ids() as $category_id) {
        $categories[] = ['id' => $category_id];
    }
    $data = [
        'name' => $product->get_name(),
        'type' => $product->get_type(),
        'regular_price' => $product->get_regular_price(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'categories' => $categories,
    ];
    $woocommerce_site->post('products', $data);
}
add_action('woocommerce_thankyou', 'wcss_sync_order_to_site', 10, 1);
function wcss_sync_order_to_site($order_id) {
    $order = wc_get_order($order_id);
    $site3_url = 'http://localhost/site3/';
    $site3_client_key = 'ck_6b88b021a658962a376a0403e192afa329e0fe34';
    $site3_client_secret = 'cs_057c576f66bfbdc1dac2762d7ee5d2645affc78f';
    $woocommerce_site = get_woocommerce_client($site3_url, $site3_client_key, $site3_client_secret);
    $order_data = [
        'payment_method' => $order->get_payment_method(),
        'payment_method_title' => $order->get_payment_method_title(),
        'set_paid' => $order->is_paid(),
        'billing' => [
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'address_1' => $order->get_billing_address_1(),
            'address_2' => $order->get_billing_address_2(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'postcode' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        ],
        'shipping' => [
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
        ],
        'line_items' => [],
        'shipping_lines' => [],
    ];
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $order_data['line_items'][] = [
            'product_id' => $product->get_id(),
            'quantity' => $item->get_quantity(),
            'subtotal' => $item->get_subtotal(),
            'total' => $item->get_total(),
        ];
    }
    foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
        $order_data['shipping_lines'][] = [
            'method_id' => $shipping_item->get_method_id(),
            'method_title' => $shipping_item->get_method_title(),
            'total' => $shipping_item->get_total(),
        ];
    }
    $woocommerce_site->post('orders', $order_data);
}