<?php
// Add custom Theme Functions here

function sort_cart_by_price($cart)
{
  usort($cart, function ($first, $second) {
    return $first['data']->get_price() < $second['data']->get_price() or $second['unique_key'];
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
  limit_cart_item_quantity($cart);

  $cart_products = sort_cart_by_price($cart->get_cart());

  $price_modifier = 0.05;
  $price_modifier_html = '<span class="cart__discounted_product_title"> -' . strval($price_modifier * 100) . '%</span>';

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



function limit_cart_item_quantity($cart)
{
  $cart_data = sort_cart_by_price($cart->cart_contents);
  $me = $cart_data[0];
  $me_q = $me['quantity'];

  // find products tagged with is_most_expensive
  $x = array_filter($cart->cart_contents, function ($el) {
    return $el['unique_key'] == 'is_most_expensive';
  });

  // if cart contains more than 1 product tagged with is_most_expensive, remove them
  if (sizeof($x) > 1) {
    $ids = [];
    foreach ($x as $key => $product) {
      if ($product['data']->get_price() < $me['data']->get_price()) {
        array_push($ids, (object) ["id" => $product['product_id'], "key" => $product['key'], "quantity" => $product['quantity']]);
      }
    }

    foreach ($ids as $el) {
      //remove since it is considered as most expensive
      $cart->remove_cart_item($el->key);
      //add again
      $cart->add_to_cart($el->id, $el->quantity);
    }
  }
  // if most expensive product has quantity>1, split it
  if ($me_q > 1) {
    $cart->remove_cart_item($me['key']);
    $cart->add_to_cart($me['product_id'], 1, null, null, array('unique_key' => 'is_most_expensive'));
    $cart->add_to_cart($me['product_id'], $me_q - 1);
    WC()->cart->calculate_totals();
  }
}
