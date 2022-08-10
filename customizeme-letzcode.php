<?php

/**
 * Plugin Name:       CustomizeMe | 3D configurator
 * Plugin URI:        https://customizeme.app/
 * Description:       low-code bowser-based 3D AR CPQ platform for your business
 * Version:           1.0.0
 * Author:            LetzCode
 * Author URI:        https://letzcode.io/
 * License:           Apache 2.0
 * License URI:       https://www.apache.org/licenses/LICENSE-2.0.txt
 * Text Domain:       letzcode
 * Domain Path:       /languages
 */

$customizeme_plugin_url = plugin_dir_url(__FILE__);

// Enqueue plugin styles
add_action('wp_enqueue_scripts', 'customizeme_enqueue_scripts_and_styles');
function customizeme_enqueue_scripts_and_styles()
{
    global $customizeme_plugin_url;
    wp_register_script('customizeme_script', $customizeme_plugin_url . './customizeme_script.js', array(), false, true);

    $customizeme_settings = get_option('customizeme_settings_fields');
    $customizeme_script_data = array(
        'productLink' =>  get_post_meta(get_the_ID(), 'customizeme_product_link', true),
        'customInjectTo' =>  get_post_meta(get_the_ID(), 'customizeme_product_custom_inject_to', true),
        'dontHideVariations' =>  get_post_meta(get_the_ID(), 'customizeme_product_dont_hide_variations', true),
        'price' =>  get_post_meta(get_the_ID(), '_price', true),
        'currency' => get_woocommerce_currency_symbol(),
        'settings' => $customizeme_settings
    );

    wp_localize_script('customizeme_script', 'customizemeScriptData', $customizeme_script_data);
    wp_enqueue_script('customizeme_script');
}

// The code for displaying WooCommerce Product Custom Fields
add_action('woocommerce_product_options_inventory_product_data', 'customizeme_product_custom_fields');
function customizeme_product_custom_fields()
{
    echo '<div class="product_custom_field">';
    echo '<h3 style="padding-left: 10px; margin-bottom:0">CustomizeMe</h3>';
    woocommerce_wp_text_input(
        array(
            'id' => 'customizeme_product_link',
            'placeholder' => 'https://',
            'label' => __('Link to product', 'woocommerce'),
        )
    );

    woocommerce_wp_text_input(
        array(
            'id' => 'customizeme_product_custom_inject_to',
            'label' => __('Custom inject to', 'woocommerce'),
            'desc_tip' => 'true',
            'description' => __('You can override here "Inject to" from CustomizeMe Settings for this product.', 'woocommerce')
        )
    );
    woocommerce_wp_checkbox(
        array(
            'id' => 'customizeme_product_dont_hide_variations',
            'label' => __("Don't hide variations", 'woocommerce'),
        )
    );

    echo '</div>';
}

// Following code Saves  WooCommerce Product Custom Fields
add_action('woocommerce_process_product_meta', 'customizeme_product_custom_fields_save');
function customizeme_product_custom_fields_save($post_id)
{
    $product_link = substr(sanitize_text_field($_POST['customizeme_product_link']), 0, 100);
    $inject_to = substr(sanitize_text_field($_POST['customizeme_product_custom_inject_to']), 0, 100);
    $dont_hide_variations = substr(sanitize_text_field($_POST['customizeme_product_dont_hide_variations']), 0, 3);

    update_post_meta($post_id, 'customizeme_product_link', $product_link);
    update_post_meta($post_id, 'customizeme_product_custom_inject_to', $inject_to);
    update_post_meta($post_id, 'customizeme_product_dont_hide_variations', $dont_hide_variations);
}

add_action('woocommerce_before_add_to_cart_button', 'customizeme_add_hidden_input');
function customizeme_add_hidden_input()
{
    echo '<input type="hidden" id="customizeme_price_input" name="customizeme_price_input" />';
    echo '<input type="hidden" id="customizeme_data_input" name="customizeme_data_input" />';
}

add_action('woocommerce_before_single_product', 'customizeme_add_root_to_product_page');
function customizeme_add_root_to_product_page()
{
    echo '<div id="customizeme_root" style="width:100%; height:80vh; position:relative; display:none; margin:5px 0" ></div>';
}

add_filter('woocommerce_get_price_html', 'customizeme_map_price_element');
function customizeme_map_price_element()
{
    $price = get_post_meta(get_the_ID(), '_price', true);
    $currency = get_woocommerce_currency_symbol();
    return '<price class="woocommerce-Price-amount amount price"><span id="customizeme_price_span">' . $price . '</span>' . $currency . '</price>';
}

add_filter('woocommerce_add_cart_item_data', 'customizeme_add_cart_item_data', 10, 1);
function customizeme_add_cart_item_data($cart_item_data)
{
    $product_data = sanitize_text_field($_POST['customizeme_data_input']);
    $product_price = sanitize_text_field($_POST['customizeme_price_input']);

    if (customizeme_validate_product_data($product_data)) {
        $cart_item_data['_customizeme_order_data'] = $product_data;
        if (customizeme_validate_product_price($product_price, $product_data)) {
            $cart_item_data['_customizeme_price'] = $product_price;
        }
    }

    return $cart_item_data;
}

function customizeme_validate_product_data($product_data)
{
    try {
        $data = json_decode($product_data, true,);
        foreach ($data as $part_customization) {
            if (empty($part_customization['name'])) return false;
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function customizeme_validate_product_price($product_price, $product_data)
{
    try {
        $data = json_decode($product_data, true,);
        $price = 0;
        foreach ($data as $index => $part_customization) {
            $price += $part_customization['price'] / cos($index + 0.5);
        }
        return abs($product_price - $price) < 2;
    } catch (Exception $e) {
        return false;
    }
}

add_action('woocommerce_before_calculate_totals', 'customizeme_add_custom_price');
function customizeme_add_custom_price()
{
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['_customizeme_price'])) {
            $cart_item['data']->set_price($cart_item['_customizeme_price']);
        }
    }
}

add_filter('woocommerce_get_cart_item_from_session', 'customizeme_add_cart_data_to_item_data', 10, 3);
function customizeme_add_cart_data_to_item_data($cart_item_data, $cart_session_data, $cartItemKey)
{
    if (isset($cart_session_data['_customizeme_order_data'])) {
        $cart_item_data['_customizeme_order_data'] = $cart_session_data['_customizeme_order_data'];
    }
    return $cart_item_data;
}

add_action('woocommerce_checkout_create_order_line_item', 'customizeme_add_custom_order_line_item_meta', 10, 4);
function customizeme_add_custom_order_line_item_meta($item, $cart_item_key, $values, $order)
{
    if (array_key_exists('_customizeme_order_data', $values)) {
        $item->add_meta_data('_customizeme_order_data', $values['_customizeme_order_data']);
    }
}

add_action('woocommerce_admin_order_data_after_order_details', 'customizeme_after_shop_loop_item', 10, 0);
function customizeme_after_shop_loop_item()
{
    global $customizeme_plugin_url, $post;
    wp_register_script('customizeme_make_order_table', $customizeme_plugin_url . './customizeme_make_order_table.js', array(), false, true);
    wp_enqueue_script('customizeme_make_order_table');
}


// add menu

function customizeme_add_menu()
{
    add_options_page('CustomizeMe', 'CustomizeMe', 'manage_options', 'customizeme_settings', 'customizeme_generate_settings');
}

function customizeme_generate_settings()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    echo '<form action="options.php" method="post">';
    settings_fields('customizeme_settings_fields');
    do_settings_sections('customizeme_settings');
    echo '<div style="display:flex; gap:8px">';
    echo '<input name="submit" class="button button-primary" type="submit" value="Save" />';
    echo '<div class="button button-primary">Go to Backoffice</div>';
    echo '</div>';
    echo '</form>';
}

function customizeme_register_settings()
{
    register_setting('customizeme_settings_fields', 'customizeme_settings_fields',);
    add_settings_section('customizeme_settings_section', 'CustomizeMe Settings', '', 'customizeme_settings');
    add_settings_field('customizeme_settings_fields_access_key', 'Access Key', 'customizeme_settings_make_access_key', 'customizeme_settings', 'customizeme_settings_section');
    add_settings_field('customizeme_settings_fields_inject', 'Inject to', 'customizeme_settings_make_inject', 'customizeme_settings', 'customizeme_settings_section');
}

function customizeme_settings_make_access_key()
{
    $options = get_option('customizeme_settings_fields');
    echo "<input name='customizeme_settings_fields[access_key]' type='text' value='" . esc_attr($options['access_key']) . "' />";
}

function customizeme_settings_make_inject()
{
    $options = get_option('customizeme_settings_fields');
    echo "<input name='customizeme_settings_fields[inject]' type='text' value='" . esc_attr($options['inject']) . "' />";
    echo "<p class='description'>Pseudo selector of element to which CustomizeMe should be added. Let it empty to add CustomizeMe above product section.</p>";
}

add_action('admin_init', 'customizeme_register_settings');
add_action('admin_menu', 'customizeme_add_menu');
