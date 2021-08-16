<?php


?>

<form method="post">
    <div id="gf-ideal-feed-editor">

        <?php wp_nonce_field('pronamic_pay_save_pay_gf', 'pronamic_pay_nonce'); ?>

        <h4>
            <?php _e('Fields', 'pronamic_ideal'); ?>
        </h4>

        <table class="form-table">

            <tr>
                <th scope="row">
                    <label for="wc_download_status">
                        <?php _e('Status', 'gravity-generate-wc-order'); ?>
                    </label>
                </th>
                <td>
                    <select id="wc_download_status" name="wc_download_status">
                        <option value="no" <?php selected($formMeta['wc_download_status']['status'], 'no'); ?>><?php _e('No', 'gravity-generate-wc-order'); ?></option>
                        <option value="yes" <?php selected($formMeta['wc_download_status']['status'], 'yes'); ?>><?php _e('Yes', 'gravity-generate-wc-order'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="field_fullname">
                        <?php _e('Full Name Field', 'gravity-generate-wc-order'); ?>
                    </label>
                </th>
                <td>
                    <select id="field_fullname" name="field_fullname">
                        <?php
                        foreach ($form['fields'] as $field) {
                            $label = $field->label;
                            $id = $field->id;
                            ?>
                            <option value="<?php echo $id; ?>" <?php selected($formMeta['wc_download_status']['field_fullname'], $id); ?>><?php echo $label; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>


            <tr>
                <th scope="row">
                    <label for="field_mobile">
                        <?php _e('Mobile Field', 'gravity-generate-wc-order'); ?>
                    </label>
                </th>
                <td>
                    <select id="field_mobile" name="field_mobile">
                        <?php
                        foreach ($form['fields'] as $field) {
                            $label = $field->label;
                            $id = $field->id;
                            ?>
                            <option value="<?php echo $id; ?>" <?php selected($formMeta['wc_download_status']['field_mobile'], $id); ?>><?php echo $label; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>


            <tr>
                <th scope="row">
                    <label for="woocommerce_product_id">
                        <?php _e('WooCommerce Product', 'gravity-generate-wc-order'); ?>
                    </label>
                </th>
                <td>
                    <select id="woocommerce_product_id" name="woocommerce_product_id">
                        <?php
                        $product_ids = get_posts(array(
                            'post_type' => 'product',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'fields' => 'ids',
                            'meta_query' => array(array(
                                'key' => '_downloadable',
                                'value' => 'yes',
                                'compare' => '=',
                            )),
                        ));
                        foreach ($product_ids as $product_id) {
                            $product = wc_get_product($product_id);
                            ?>
                            <option value="<?php echo $product_id; ?>" <?php selected($formMeta['wc_download_status']['woocommerce_product_id'], $product_id); ?>><?php echo $product->get_name(); ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="after">
                        <?php _e('Create After', 'gravity-generate-wc-order'); ?>
                    </label>
                </th>
                <td>
                    <select id="after" name="after">
                        <option value="save" <?php selected($formMeta['wc_download_status']['after'], 'save'); ?>><?php _e('Submit Form', 'gravity-generate-wc-order'); ?></option>
                        <option value="payment" <?php selected($formMeta['wc_download_status']['after'], 'payment'); ?>><?php _e('Payment Success', 'gravity-generate-wc-order'); ?></option>
                    </select>
                </td>
            </tr>

        </table>
        <?php submit_button(); ?>
    </div>
</form>
