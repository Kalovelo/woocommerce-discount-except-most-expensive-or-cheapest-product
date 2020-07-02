<?php
function add_5perc_on_most_expensive_product($cart)
{

  if (is_admin() && !defined('DOING_AJAX'))
    return;

  if (did_action('woocommerce_before_calculate_totals') >= 2)
    return;

  $cart = WC()->cart;

  $cart_products = $cart->get_cart();

  usort($cart_products, function ($first, $second) {
    return $first['data']->get_price() < $second['data']->get_price();
  });

  $most_expensive_product = $cart_products[0];


  $x = array_filter($cart->get_cart(), function ($el) {
    return $el['unique_key'] == 'test';
  });


  $ids = [];
  foreach ($x as $key => $product) {
    if ($product['data']->get_price() < $most_expensive_product['data']->get_price()) {
      array_push($ids, (object) ["id" => $product['product_id'], "key" => $product['key']]);
    }
  }


  foreach ($ids as $el) {
    $cart->remove_cart_item($el->key);
    $cart->add_to_cart($el->id, 2);
  }
  $cart_products = $cart->get_cart();





  usort($cart_products, function ($first, $second) {
    return $first['data']->get_price() < $second['data']->get_price();
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
    if ($key == $la_key) {
      continue;
    }
    $product_reg_price = floatval($product['data']->get_regular_price());
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
    $reg_price = $most_expensive_product_details->get_regular_price();
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

    //sort by price
    usort($cart_products, function ($first, $second) {
      return $first['data']->get_price() < $second['data']->get_price();
    });

    // product being added on cart does not exist in the cart until now
    $la_product = false;

    //find the most expensive product
    $most_expensive_product = $cart_products[0];

    foreach ($cart_products as $cart_product) {

      // if product exists in the cart, get the id
      if ($cart_product['product_id'] == $product_id) {
        $la_product = $cart_product;
      }
    }

    //if our product exists in the cart and it has the most expensive price
    if ($la_product and $most_expensive_product['data']->get_price() ==  $la_product['data']->get_price()) {
      //save it with a new id
      $unique_cart_item_key = 'test';
      $cart_item_data['unique_key'] = $unique_cart_item_key;
    }
  }

  return $cart_item_data;
}

add_filter('woocommerce_add_cart_item_data', 'conditionally_split_product_individual_cart_items', 10, 2);

add_filter('woocommerce_quantity_input_args', 'jk_woocommerce_quantity_input_args', 10, 2); // Simple products

function jk_woocommerce_quantity_input_args($args, $product)
{
  if (is_singular('product')) {
    $args['input_value']   = 2;  // Starting value (we only want to affect product pages, not cart)
  }

  $key = array_search('test', array_column($cart_products, 'unique_key'));
  echo $key;

  if ($product and !strpos($product->get_name(), '-5%')) {
    echo var_dump($product->get_attributes());
    $args['max_value']   = 1;   // Maximum value
    $args['step']     = 1;    // Quantity steps
  }
  return $args;
}
