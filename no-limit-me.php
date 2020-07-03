<?php
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
