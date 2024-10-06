<?php
/**
 * Plugin Name: Product Sync
 * Description: WooCommerce REST API multiple sites.
 * Version: 1.0
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
add_action('admin_menu','wcss_admin_menu');
function wcss_admin_menu(){
    add_menu_page(
        'WooCommerce Store Sync',
        'WooCommerce Store Sync',
        'manage_options',
        'wcss',
        'wcss_admin_page',
        'dashicons-update',
        3
    );
}
function wcss_admin_page(){
    $site2_url = 'http://localhost/site2/';
    $site2_client_key = 'ck_2f59b5d7e694431740f9eeacbd398cb42ea55f6f';
    $site2_client_secret = 'cs_e02f48d6f4c757b50d0781f2f3f3ecf3deb052ac';

    $woocommerce_site2 = get_woocommerce_client( $site2_url, $site2_client_key, $site2_client_secret );
    
    $products_site2 = $woocommerce_site2->get('products');
    $sites = [
        [
            'url' => 'http://localhost/site3/',
            'client_key' => 'ck_6b88b021a658962a376a0403e192afa329e0fe34',
            'client_secret' => 'cs_057c576f66bfbdc1dac2762d7ee5d2645affc78f',
        ],
    ];

    foreach ($sites as $site) {
        $woocommerce_target_site = get_woocommerce_client($site['url'], $site['client_key'], $site['client_secret']);
        $existing_products = $woocommerce_target_site->get('products');
        $existing_titles = array_map(function ($product) {
            return $product->name;
        }, $existing_products);
    
        foreach ($products_site2 as $product) {
            if (!in_array($product->name, $existing_titles)) {
                $data = [
                    'name' => $product->name,
                    'type' => $product->type,
                    'regular_price' => $product->regular_price,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'categories' => $product->categories,
                    'images' => $product->images, 
                ];
                try {
                    $woocommerce_target_site->post('products', $data);
                    echo 'Product "' . $product->name . '" synced to ' . $site['url'] . '<br>';
                } catch (Exception $error) {
                    echo 'Error syncing product "' . $product->name . '": ' . $error->getMessage() . '<br>';
                }
            } else {
                echo 'Product "' . $product->name . '" already exists on ' . $site['url'] . '<br>';
            }
        }
        // Order Sync
        $orders_site2 = $woocommerce_site2->get('orders');
        $existing_orders = $woocommerce_target_site->get('orders');
        $existing_order_ids = array_map(function ($order) {
            return $order->id;
        }, $existing_orders);

        foreach ($orders_site2 as $order) {
            $line_items = [];
            if (!in_array($order->id, $existing_orders)){
                foreach ($order->line_items as $item) {
                    $line_items[] = [
                        'product_id' => $item->product_id, 
                        'quantity'   => $item->quantity,
                        'total'      => $item->total, 
                    ];
                }
                $data = [
                    'billing'      => $order->billing,
                    'shipping'     => $order->shipping,
                    'payment_method' => $order->payment_method,
                    'line_items'   => $line_items, 
                ];
                try {
                    $woocommerce_target_site->post('orders', $data);
                    echo 'Order "' . $order->id . '" synced to ' . $site['url'] . '<br>';
                } catch (Exception $error) {
                    echo 'Error syncing order "' . $order->id . '": ' . $error->getMessage() . '<br>';
                }
            }
        }
    }
}
