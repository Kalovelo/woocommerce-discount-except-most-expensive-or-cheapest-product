<?php

function sort_cart_by_price($cart)
{
  usort($cart, function ($first, $second) {
    return $first['data']->get_price() < $second['data']->get_price();
  });

  return $cart;
}
function add_5perc_on_most_expensive_product($cart)
{

  if (is_admin() && !defined('DOING_AJAX'))
    return;

  if (did_action('woocommerce_before_calculate_totals') >= 2)
    return;

  $cart = WC()->cart;

  $cart_products = sort_cart_by_price($cart->get_cart());

  $most_expensive_product = $cart_products[0];


  $x = array_filter($cart->get_cart(), function ($el) {
    return $el['unique_key'] == 'is_most_expensive';
  });


  $ids = [];
  foreach ($x as $key => $product) {
    if ($product['data']->get_price() < $most_expensive_product['data']->get_price()) {
      array_push($ids, (object) ["id" => $product['product_id'], "key" => $product['key'], 'quantity' => $product['quantity']]);
    }
  }


  foreach ($ids as $el) {
    $cart->remove_cart_item($el->key);
    $cart->add_to_cart($el->id, $el->quantity);
  }

  $cart_products = sort_cart_by_price($cart->get_cart());

  $price_modifier = 0.05;
  $price_modifier_html = '<span class="cart__discounted_product_title"> -' . strval($price_modifier * 100) . '%</span>';

  $unique_product_key = 0;
  foreach ($cart_products as $key => $product) {
    if ($product['quantity'] == 1) {
      $unique_product_key = $key;
      break;
    }
  }
  // discount for each product in cart
  foreach ($cart_products as $key => $product) {

    //sorted array, skip the most expensive product
    if ($key == $unique_product_key) {
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
  $most_expensive_product = $cart_products[0];
  $most_expensive_product_quantity = $cart_products[0]['quantity'];


  if ($most_expensive_product_quantity > 1) {
    #foreach extra copy, add a 0.05 off discount
    $price_modifier *= $most_expensive_product_quantity - 1;

    # get the price of a single copy
    $most_expensive_product_details = $most_expensive_product['data'];
    $price = $most_expensive_product_details->get_price();
    $single_reg_price = $price / $most_expensive_product_quantity;

    # show price modifier
    $most_expensive_product_name = $most_expensive_product_details->get_name();
    $most_expensive_product_details->set_name($most_expensive_product_name . $price_modifier_html);

    #set the new price of the bundle relative to the price of single_price multiplied by the modifer
    $new_price = $price - $single_reg_price * $price_modifier;
    $most_expensive_product_details->set_price($new_price);
  }
}

add_action('woocommerce_before_calculate_totals', 'add_5perc_on_most_expensive_product', 10, 1);

function conditionally_split_product_individual_cart_items($cart_item_data, $product_id)
{
  if (is_admin() && !defined('DOING_AJAX'))
    return;

  if (did_action('woocommerce_add_cart_item_data') >= 2)
    return;

  $cart_products = WC()->cart->get_cart();

  // if cart is not empty
  if (!sizeof($cart_products) == 0) {

    $cart_products = sort_cart_by_price($cart_products);

    // product being added on cart does not exist in the cart until now
    $unique_product = false;

    //find the most expensive product
    $most_expensive_product = $cart_products[0];

    foreach ($cart_products as $cart_product) {

      // if product exists in the cart, get the id
      if ($cart_product['product_id'] == $product_id) {
        $unique_product = $cart_product;
        break;
      }
    }

    //if our product exists in the cart and it has the most expensive price
    if ($unique_product and $most_expensive_product['data']->get_price() == $unique_product['data']->get_price()) {
      $unique_cart_item_key = 'is_most_expensive'; //save it with a new id
      $cart_item_data['unique_key'] = $unique_cart_item_key;
    }
  }

  return $cart_item_data;
}

add_filter('woocommerce_add_cart_item_data', 'conditionally_split_product_individual_cart_items', 10, 2);

add_filter('woocommerce_quantity_input_args', 'woo_limit_product_quantity', 10, 2); // Simple products

add_filter('woocommerce_quantity_input_args', 'woo_limit_product_quantity', 10, 2); // Simple products
function woo_limit_product_quantity($args, $product)
{
  if (is_singular('product')) {
    $args['input_value'] = 2; // Starting value (we only want to affect product pages, not cart)
  }

  $cart = WC()->cart->get_cart();

  $more_instances = array_filter($cart, function ($el) use (&$product) {
    return $el['product_id'] == $product->get_id();
  });

  $most_expensive = sort_cart_by_price($cart)[0];

  if (sizeof($more_instances) > 1 && $most_expensive['data']->get_price() <= $product->get_price()) {
    $args['max_value'] = 1; // Maximum value
    $args['step'] = 1; // Quantity steps
  }
  return $args;
}
