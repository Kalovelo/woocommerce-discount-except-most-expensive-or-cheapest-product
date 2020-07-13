<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://kalovelo.com
 * @since      1.0.0
 *
 * @package    Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount
 * @subpackage Woo_Most_Expensive_Or_Cheapest_Product_Cart_Discount/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">

  <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

  <form method="post" name="woo-mecd_options" action="options.php">


    <?php
    //Grab all options
    $options = get_option($this->plugin_name);
    $discount = $options['discount'];
    // Discount
    ?>

    <?php
    settings_fields($this->plugin_name);
    do_settings_sections($this->plugin_name);
    ?>
    <!-- remove some meta and generators from the <head> -->
    <fieldset>
      <legend class="screen-reader-text"><span>Clean WordPress head section</span></legend>
      <label for="<?php echo $this->plugin_name; ?>-discount">
        <input type="number" value="<?php echo $discount ?>" name="<?php echo $this->plugin_name; ?>[discount]" id="<?php echo $this->plugin_name; ?>-discount" class="regular-text" min='1' max='100' />
        <span class="description"><?php esc_attr_e('Percentage discount for the most expensive product', 'WpAdminStyle'); ?></span><br>
      </label>
    </fieldset>

    <?php submit_button('Save all changes', 'primary', 'submit', TRUE); ?>

  </form>

</div>