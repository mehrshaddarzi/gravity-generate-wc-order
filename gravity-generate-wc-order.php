<?php
/**
 * Plugin Name:       Generate WooCommerce Order With Gravity
 * Plugin URI:        https://realwp.net
 * Description:       Automatic Generate Woocommerce Order after complete Gravity
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Mehrshad Darzi
 * Author URI:        https://realwp.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gravity-generate-wc-order
 * Domain Path:       /languages
 */

class Gravity_Generate_WooCommerce_Order
{

    public function __construct()
    {

        // Create Gravity Setting For Field
        // @see https://gist.github.com/remcotolsma/1c43f555c7f2c5b505b3
        add_filter('gform_form_settings_menu', array($this, 'gform_form_settings_menu'), 10, 8);
        add_action('gform_form_settings_page_woocommerce_order', array($this, 'settings_gravity_woocommerce_order'));
        add_action('admin_notices', array($this, 'sample_admin_notice__success'));

        // Add Action Save or Payment Gravity
        add_action('gform_after_submission', array($this, 'after_submission_form'), 10, 2);
        add_action('gform_post_payment_completed', array($this, 'after_payment_form'), 10, 2);
    }

    public function gform_form_settings_menu($setting_tabs, $form_id)
    {
        $setting_tabs[] = array(
            'name' => 'woocommerce_order',
            'label' => __('WooCommerce Download', 'gravity-generate-wc-order'),
            'capabilities' => array('gravityforms_edit_forms'),
        );

        return $setting_tabs;
    }

    public function settings_gravity_woocommerce_order()
    {
        // Page Header Gravity
        GFFormSettings::page_header();

        // Get Form ID
        $form_id = (isset($_GET['id']) ? trim($_GET['id']) : null);

        // Save Detail
        if (!empty(rgpost('wc_download_status'))) {
            $array = array(
                'status' => rgpost('wc_download_status'),
                'field_fullname' => rgpost('field_fullname'),
                'field_mobile' => rgpost('field_mobile'),
                'woocommerce_product_id' => rgpost('woocommerce_product_id'),
                'after' => rgpost('after')
            );

            $form = GFAPI::get_form($form_id);
            $form['wc_download_status'] = $array;
            GFAPI::update_form($form);
        }

        // Get form Meta
        $formMeta = self::getFormMeta($form_id);

        // Get Form Data
        $form = GFAPI::get_form($form_id);

        // Show Form
        include dirname(__FILE__) . '/views/form-settings.php';
        GFFormSettings::page_footer();
    }

    public static function getFormMeta($form_id)
    {
        $formMeta = RGFormsModel::get_form_meta($form_id);
        if (!isset($formMeta['wc_download_status']) || (isset($formMeta['wc_download_status']) and count($formMeta['wc_download_status']) != 5)) {
            $formMeta['wc_download_status'] = array(
                'status' => 'no',
                'field_fullname' => '',
                'field_mobile' => '',
                'woocommerce_product_id' => '',
                'after' => 'save'
            );
        }

        return $formMeta;
    }

    public function sample_admin_notice__success()
    {
        global $pagenow;
        if ($pagenow == "admin.php" and isset($_GET['page']) and $_GET['page'] == "gf_edit_forms" and isset($_GET['subview']) and $_GET['subview'] == "woocommerce_order" and !empty(rgpost('wc_download_status'))) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Saved Settings.', 'gravity-generate-wc-order'); ?></p>
            </div>
            <?php
        }
    }

    public function after_submission_form($entry, $form)
    {
        $form_id = $entry['form_id'];
        $formMeta = self::getFormMeta($form_id);

        // Check Status
        if ($formMeta['wc_download_status']['status'] != "yes") {
            return;
        }

        // Check After Save or Payment
        if ($formMeta['wc_download_status']['after'] != "save") {
            return;
        }

        // Check Empty Mobile
        $mobile_field_id = $formMeta['wc_download_status']['field_mobile'];
        $fullName_field_id = $formMeta['wc_download_status']['field_fullname'];
        $product_id = $formMeta['wc_download_status']['woocommerce_product_id'];
        if (empty($mobile_field_id) || empty($fullName_field_id) || empty($product_id)) {
            return;
        }
        $mobile = $entry[$mobile_field_id];
        $fullName = $entry[$fullName_field_id];
        if (empty($mobile)) {
            return;
        }

        // Create Order
        self::createOrder($fullName, $mobile, $product_id);
    }

    public function after_payment_form($entry, $action)
    {
        // Check Success Payment
        if (isset($action['payment_status']) and $action['payment_status'] == "Paid") {

            // Get Form ID
            $form_id = $entry['form_id'];
            $formMeta = self::getFormMeta($form_id);

            // Check Status
            if ($formMeta['wc_download_status']['status'] != "yes") {
                return;
            }

            // Check After Save or Payment
            if ($formMeta['wc_download_status']['after'] != "payment") {
                return;
            }

            // Check Empty Mobile
            $mobile_field_id = $formMeta['wc_download_status']['field_mobile'];
            $fullName_field_id = $formMeta['wc_download_status']['field_fullname'];
            $product_id = $formMeta['wc_download_status']['woocommerce_product_id'];
            if (empty($mobile_field_id) || empty($fullName_field_id) || empty($product_id)) {
                return;
            }
            $mobile = $entry[$mobile_field_id];
            $fullName = $entry[$fullName_field_id];
            if (empty($mobile)) {
                return;
            }

            // Create Order
            self::createOrder($fullName, $mobile, $product_id);
        }
    }

    public static function createOrder($name, $mobile, $product_id)
    {
        global $wpdb;

        // Get User ID
        $user_id = false;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        if ($user_id === false) {

            // Sanitize Mobile For Search
            $user_mobile = ltrim($mobile, '0');

            // Search User
            $mylink = $wpdb->get_row("SELECT * FROM `{$wpdb->prefix}users` WHERE `user_login` LIKE '%{$user_mobile}%' ORDER BY `ID` DESC LIMIT 1", ARRAY_A);
            if (null !== $mylink) {

                // get User ID
                $user_id = $mylink['ID'];
            } else {

                // Get Domain Name
                $url = home_url();
                $parse = parse_url($url);
                $domain = $parse['host'];
                $NewEmail = 'user_' . ltrim($mobile, '0') . '@' . $domain;

                // Create User
                $userdata = array(
                    'user_login' => ltrim($mobile, '0'),
                    'user_pass' => NULL,
                    'first_name' => $name,
                    'user_email' => $NewEmail,
                    'display_name' => $name
                );
                $user_id = wp_insert_user($userdata);

                // Set Digits Phone User Meta
                update_user_meta($user_id, 'user_meta_mobile', $mobile);
                $phoneNonZero = ltrim($mobile, '0');
                update_user_meta($user_id, 'digt_countrycode', '+98');
                update_user_meta($user_id, 'digits_phone', '+98' . $phoneNonZero);
                update_user_meta($user_id, 'digits_phone_no', $phoneNonZero);

                // Auto Login User
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
            }
        }

        if ($user_id === false) {
            return null;
        }

        // Check Auto Generate
        self::generateEmailUser($user_id);

        // Create Order
        $customer_id = $user_id;
        $order = wc_create_order(array(
            'customer_id' => $customer_id
        ));
        $order->add_product(
            wc_get_product($product_id),
            1,
            array('subtotal' => 0, 'total' => 0)
        );
        $order->calculate_totals();
        $order->update_status('completed', '', TRUE);
        $order->save();
        wc_downloadable_product_permissions($order->get_order_number());
        $wpdb->update($wpdb->prefix . 'woocommerce_downloadable_product_permissions', array('user_id' => $customer_id), array('order_id' => $order->get_order_number()), array('%d'), array('%d'));
        return $order->get_order_number();
    }

    public static function generateEmailUser($user_id)
    {
        global $wpdb;

        $user_data = get_userdata($user_id);
        $email = $user_data->user_email;
        if (empty($email)) {

            // Get Domain Name
            $url = home_url();
            $parse = parse_url($url);
            $domain = $parse['host'];
            $NewEmail = 'user_' . $user_id . '@' . $domain;

            // Update User
            add_filter('send_password_change_email', '__return_false');
            $user_id = wp_update_user(array(
                'ID' => $user_id,
                'user_email' => $NewEmail
            ));

            // Set Before Permission download
            $wpdb->query("UPDATE `{$wpdb->prefix}woocommerce_downloadable_product_permissions` SET `user_email` = '{$NewEmail}' WHERE `user_id` = {$user_id};");
        }
    }
}

new Gravity_Generate_WooCommerce_Order();