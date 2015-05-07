<?php
/**
 * Plugin Name: Woocommerce Mailchimp
 * Plugin URI: www.dreamfoxmedia.nl 
 * Version: 1.0.2
 * Author URI: www.dreamfoxmedia.nl
 * Description: Extend Woocommerce plugin to connect with Mailchimp ( Free ).
 * Requires at least: 3.7
 * Tested up to: 4.4.2
 * @Developer : Anand Rathi ( Softsdev )
 */
/**
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
{
    /* ----------------------------------------------------- */
    // Submenu on woocommerce section
    add_action('admin_menu', 'softsdev_mailchimp_submenu_page');
    /* ----------------------------------------------------- */

    /**
     * Menu of mailchimp page
     */
    function softsdev_mailchimp_submenu_page()
    {
        add_submenu_page('woocommerce', __('Mailchimp Full', 'softsdev'), __('Mailchimp Full', 'softsdev'), 'manage_options', 'softsdev-mailchimp', 'softsdev_mailchimp_settings');
    }

    /**
     * Setting form of mailchimp category
     */
    function softsdev_mailchimp_settings()
    {
        require 'inc/MCAPI.class.php';
        // fwt and set settings
        if (isset($_POST['softsdev_mailchimp']))
        {
            $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting( $_POST['softsdev_mailchimp'] );

            $api = new MCAPI($softsdev_wc_mc_setting['api']);
            if (!$api->ping())
            {
                $softsdev_wc_mc_setting = '';
            }
            update_option('softsdev_wc_mc_full_setting', $softsdev_wc_mc_setting);
        } else
        {
            $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();
        }
        // Getting lists of mailchimp from api
        $lists = array();
        if (isset($softsdev_wc_mc_setting['api']))
        {
            $api = new MCAPI($softsdev_wc_mc_setting['api']);
            $listing = $api->lists();
            $lists = $listing['data'];
        }

        echo '<div class="wrap "><div id="icon-tools" class="icon32"></div>';
        echo '<h2>' . __('Woocommerce MailChimp', 'softsdev') . '</h2>';
        ?>
        <form id="woo_dd" action="<?php echo $_SERVER['PHP_SELF'] . '?page=softsdev-mailchimp' ?>" method="post">
            <div class="postbox " style="padding: 10px; margin: 10px 0px;">
                <h3 class="hndle"><?php echo __('Mailchimp Setting', 'softsdev'); ?></h3>
                <table width="100%" class="form-table">
                    <tr>
                        <th width="170px">
                            <label for="softsdev_mailchimp_api"><?php echo __('Mailchimp API', 'softsdev') ?> </label>
                            <img width="16" height="16" src="<?php echo plugins_url('images/help.png', __FILE__) ?>" class="help_tip" title="<?php echo __('0', 'softsdev'); ?>">
                        </th>
                        <td>
                            <input id="softsdev_mailchimp_api" name="softsdev_mailchimp[api]" type="text" value="<?php echo @$softsdev_wc_mc_setting['api'] ?>" size="40"/>
                            <br />
                        </td>
                    </tr>
                    <tr style="display: <?php echo @$softsdev_wc_mc_setting['api'] ? '' : 'none' ?>">
                        <th width="170px">
                            <label for="softsdev_mailchimp_list"><?php echo __('Mailchimp List Name', 'softsdev') ?> </label>
                            <img width="16" height="16" src="<?php echo plugins_url('images/help.png', __FILE__) ?>" class="help_tip" title="<?php echo __('1', 'softsdev'); ?>">
                        </th>
                        <td>
                            <select id="softsdev_mailchimp[list_mgroup_id]" name="softsdev_mailchimp[list_mgroup_id]">
                                <option value="">None</option>

                                <?php
                                /**
                                 * ":" is seprator
                                 */
                                foreach ($lists as $list) {
                                    // get all groups of list
                                    $groups = $api->listInterestGroupings($list['id']);
                                    echo "<optgroup label='" . $list['name'] . "'>";
                                    foreach ($groups as $group) {
                                        echo "<option value='".$list['id'].':'.$group['id']."' '".selected( $list['id'].':'.$group['id'], $softsdev_wc_mc_setting['list_mgroup_id'])."'>" . $group['name'] . "</option>";
                                    }
                                    echo "</optgroup>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr> 

                </table>
            </div>				
            <input class="button-large button-primary" type="submit" value="save" />
        </form>
        <?php
    }

    /**
     * 
     * @param type $array
     * @param type $fields1
     * @param type $fields2
     * @return type
     */
    function softsdev_mc_list_data($array, $fields1, $fields2)
    {
        if (!is_array($array) || count($array) < 1)
            return array();
        $listData = array();
        foreach ($array as $key => $value) {
            $listData[$value[$fields1]] = $value[$fields2];
        }

        return $listData;
    }

    /**
     * Get Mailchimp Groups 
     * @return type
     */
    function softsdev_get_mc_groups()
    {
        require 'inc/MCAPI.class.php';
        $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();

        if (isset($softsdev_wc_mc_setting['api']) && isset($softsdev_wc_mc_setting['list_id']) && $softsdev_wc_mc_setting['list_id'] != '')
        {
            $api = new MCAPI($softsdev_wc_mc_setting['api']);
            $mgroups = $api->listInterestGroupings($softsdev_wc_mc_setting['list_id']);
            foreach ($mgroups as $groups ){
                if( $groups['id'] == $softsdev_wc_mc_setting['mgroup_id'] )
                    return $groups['groups'];
            }
            
        }
        return array();
    }

    /*     * ************************************** */

    /*
     * Add Extra colum to woocommerce product category
     */
    function softsdev_mc_product_cat_add_new_meta_field()
    {
        $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();
        if( !@$softsdev_wc_mc_setting['api'] ) return '';
        $groups = softsdev_get_mc_groups();
        // this will add the custom meta field to the add new term page
        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="softsdev_mc_group"><?php _e('Mailchimp Group', 'softsdev_mc'); ?></label></th>
            <td>
                <?php
                echo woocommerce_wp_select(
                        array(
                            'value' => '',
                            'label' => '',
                            'id' => 'softsdev_mc_group',
                            'options' => array('1' => 'Select Group') + softsdev_mc_list_data($groups, 'name', 'name')
                        )
                );
                ?>
                <p class="description"><?php _e('Enter a value for this field', 'softsdev'); ?></p>
            </td>
        </tr>
        <?php
    }

    add_action('product_cat_add_form_fields', 'softsdev_mc_product_cat_add_new_meta_field', 10, 2);

    /**
     * Render Edit page of product category
     * @param type $term
     */
    function softsdev_mc_product_cat_edit_meta_field($term)
    {
        $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();
        if( !@$softsdev_wc_mc_setting['api'] ) return '';        
        $groups = softsdev_get_mc_groups();

        // put the term ID into a variable
        $t_id = $term->term_id;
        // retrieve the existing value(s) for this meta field. This returns an array
        $softsdev_mc_terms_groups = get_option('softsdev_mc_term_group');


        ?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="softsdev_mc_group"><?php _e('Mailchimp Group', 'softsdev'); ?></label></th>
            <td>
                <?php
                echo woocommerce_wp_select(
                        array(
                            'value' => @$softsdev_mc_terms_groups['term'.$softsdev_wc_mc_setting['list_mgroup_id'].'_' . $t_id],
                            'id' => 'softsdev_mc_group',
                            'options' => array('' => 'Select Group') + softsdev_mc_list_data($groups, 'name', 'name')
                        )
                );
                ?>
                <p class="description"><?php _e('Enter a value for this field', 'softsdev'); ?></p>
            </td>
        </tr>
        <?php
    }

    add_action('product_cat_edit_form_fields', 'softsdev_mc_product_cat_edit_meta_field', 10, 2);

    /**
     * Save Custom field softsdev_mc_group got woocommerce product category
     * @param type $term_id
     */
    function save_softsdev_mc_product_cat_custom_meta($term_id)
    {
        if (isset($_POST['softsdev_mc_group']))
        {
            $t_id = $term_id;
            $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();
            $softsdev_mc_terms_groups = get_option('softsdev_mc_term_group');
            
            $softsdev_mc_terms_groups['term'.$softsdev_wc_mc_setting['list_mgroup_id'].'_' . $t_id] = $_POST['softsdev_mc_group'];
            // Save the option array.
            update_option(softsdev_mc_term_group, $softsdev_mc_terms_groups);
        }
    }

    add_action('edited_product_cat', 'save_softsdev_mc_product_cat_custom_meta', 10, 2);
    add_action('create_product_cat', 'save_softsdev_mc_product_cat_custom_meta', 10, 2);
    /*     * ************************************** */
    
    
    
    /**
     * 
     * @param WC_Order $order
     */
    add_action('woocommerce_thankyou', 'softsdev_mc_subscribe', 20);
    function softsdev_mc_subscribe($order)
    {
        $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();
        if( !@$softsdev_wc_mc_setting['api'] ) return '';        
        $softsdev_mc_terms_groups = get_option('softsdev_mc_term_group');
        // get order details
        $order = new WC_Order($order);
        // getting all products
        $products = $order->get_items();
        // define group variable
        $groups = array();

        foreach( $products as $product ){
            // Get terms of product
            $terms = get_the_terms( $product['product_id'], 'product_cat' );
            // getting all groups of term
            foreach ($terms as $term){
                if(array_key_exists( 'term'.$softsdev_wc_mc_setting['list_mgroup_id'].'_'.$term->term_id, $softsdev_mc_terms_groups) ){
                    $groups[] = $softsdev_mc_terms_groups['term'.$softsdev_wc_mc_setting['list_mgroup_id'].'_'.$term->term_id];
                }
            }
        }
        // subscribe to mailchimp
        softsdev_subscribe_to_mc( $groups );
    }
    
    /**
     * 
     * @param type $groups
     * 
     */
    function softsdev_subscribe_to_mc( $groups ){
        if( count( $groups ) > 0 )
        {
            $softsdev_wc_mc_setting = get_softsdev_wc_mc_setting();
            require 'inc/MCAPI.class.php';

            $api = new MCAPI($softsdev_wc_mc_setting['api']);
            if (isset($softsdev_wc_mc_setting['list_id'])){
                // getting current user data
                $current_user = wp_get_current_user();

                $my_email = $current_user->user_email;

                $merge_vars = Array(
                    'EMAIL' => $current_user->user_email,
                    'FNAME' => $current_user->user_firstname,
                    'LNAME' => $current_user->user_lastname,
                    'GROUPINGS' => /*implode(',', $groups)*/array(
                        array('id' => $softsdev_wc_mc_setting['mgroup_id'], 'groups'=>implode( ',', $groups ) )
                    )
                );
                //send subscription to mailchimp 
                $api->listSubscribe( $softsdev_wc_mc_setting['list_id'], $my_email, $merge_vars, 'html', true, true );
            }           
        }
    }
    
    function get_softsdev_wc_mc_setting( $setting = '' ){
        if( !$setting )
            $setting = get_option('softsdev_wc_mc_full_setting');
        if( !$setting )
            return array();
        if( $setting['list_mgroup_id'] )
            list($setting['list_id'], $setting['mgroup_id']) = explode(':', $setting['list_mgroup_id'] );
        else{
            $setting['list_id'] = $setting['mgroup_id'] = '';
        }
        return $setting;
    }
}