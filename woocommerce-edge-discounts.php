<?php
// Add custom Theme Functions here

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


  $price_modifier = 0.05;
  $price_modifier_html = '<span class="cart__discounted_product_title"> -' . strval($price_modifier * 100) . '%</span>';

  $la_key = 0;
  foreach ($cart_products as $key => $product) {
    if ($product['quantity'] == 1) {
      $la_key = $key;
      break;
    }
  }

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
  $most_expensive_product = $cart_products[0];
  $most_expensive_product_quantity = $cart_products[0]['quantity'];
}

add_action('woocommerce_before_calculate_totals', 'add_5perc_on_most_expensive_product', 10, 1);

add_filter('woocommerce_quantity_input_args', 'woo_limit_product_quantity', 10, 2); // Simple products
function woo_limit_product_quantity($args, $product)
{

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



add_action('woocommerce_cart_updated', 'limit_cart_item_quantity', 10);
function limit_cart_item_quantity()
{

  if (is_admin() && !defined('DOING_AJAX'))
    return;

  if (did_action('woocommerce_cart_updated') >= 2)
    return;

  $cart = WC()->cart;

  $cart_data = sort_cart_by_price($cart->cart_contents);
  $me = $cart_data[0];
  $me_q = $me['quantity'];


  $x = array_filter($cart->cart_contents, function ($el) {
    return $el['unique_key'] == 'is_most_expensive';
  });

  if (sizeof($x) > 0) {
    $ids = [];
    foreach ($x as $key => $product) {
      if ($product['data']->get_price() < $me['data']->get_price()) {
        array_push($ids, (object) ["id" => $product['product_id'], "key" => $product['key'], "quantity" => $product['quantity']]);
      }
    }

    foreach ($ids as $el) {
      $cart->remove_cart_item($el->key);
      $cart->add_to_cart($el->id, 2);
    }
  }
  if ($me_q > 1) {
    $cart->remove_cart_item($me['key']);
    $cart->add_to_cart($me['product_id'], 1, null, null, array('unique_key' => 'is_most_expensive'));
    $cart->add_to_cart($me['product_id'], $me_q - 1);
    WC()->cart->calculate_totals();
  }
}
