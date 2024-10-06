<?php
/**
 * Plugin Name: Product Sync
 * Description: WooCommerce REST API.
 * Version: 1.0
 * Author: Mohrajul
 */


add_action('save_post_product', 'sync_product_to_site2', 10, 3);

function sync_product_to_site2($post_id, $post, $update) {
    if (wp_is_post_autosave($post_id)) {
        return;
    }
    $product = wc_get_product($post_id);
    if (!$product) {
        return;
    }

    $product_data = [
        'name'        => $product->get_name(),
        'type'        => $product->get_type(),
        'regular_price' => $product->get_regular_price(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'sku'         => $product->get_sku(),
        'manage_stock' => $product->get_manage_stock(),
        'stock_quantity' => $product->get_stock_quantity(),
        'categories' => $product->get_category_ids(),
    ];

    send_product_to_site2($product_data);
}

function send_product_to_site2($product_data) {
    $url = 'http://localhost/site2/wp-json/wc/v3/products';
    $consumer_key = 'ck_ed59aabfc4196f871c9c5262fadb84e59565b3eb'; 
    $consumer_secret = 'cs_c05d79d79a811b4c98b8a9c83bf94ef5fa9093e4'; 

    $response = wp_remote_post($url, [
        'body'    => json_encode($product_data),
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
            'Content-Type'  => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        echo 'error';
    } else {
        echo 'successfull';
    }
}