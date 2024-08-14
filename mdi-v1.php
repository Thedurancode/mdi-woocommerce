<?php
/**
 * Plugin Name: MD Integrations API Integration
 * Description: A plugin to integrate with the MD Integrations API and add custom fields to the WooCommerce billing section.
 * Version: 1.0
 * Author: DuranCode
 * Text Domain: md-integrations-api
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class MD_Integrations_API {
    private static $instance = null;
    private $bearer_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJiMjYwMDMyYi1lMjllLTQ5NjUtOGJmOC05NGExMmU0YzFmNmUiLCJqdGkiOiJjM2RlYmRkNDA3ZWFkNGVhMDY3NGE3NmI3NWViN2YyOTU3Y2U1MDQxMDIxMzhiNWRiMTM2ZThkODdiYzFhZjIxMzU4OGJmM2ExYjRkMDNiMyIsImlhdCI6MTcyMzY0ODIzMi4xNDAzMDYsIm5iZiI6MTcyMzY0ODIzMi4xNDAzMDksImV4cCI6MTcyMzczNDYzMi4xMzI4OCwic3ViIjoiIiwic2NvcGVzIjpbIioiXX0.a6EQHc-uHOPmqz2HGHazwUAobD1blxPjKBODz11Gda6JcdFw2eK6Y0SN8XL0FwlXV-5g17KJ068xs_qkeFrfiTZ4goAbJHbmDkqGcTxhpI7klLpJr9r_oSfzW3SfmC-gO4dAOGBWdeycY_lv34QiBpIWHbOnTgs7kgt2ktyV26yNdZdT7bmOGHhybH_aWXrupFSA-4NRSyPSw8TG0cGzbT6jF2sFdLgpvEUOBkRvTKSOy4eVr3r-ofYlTRqTP3PQ6Vf8q2YD6Z2fz2ZzjrbN5z1-InjbyKDNmeu3fepSznCZ7TDPEKoHiBWVnJqCJ8YWku9NN1O_T_nQe3SiYriaF49VKDqWzRN6hkKQHFQRZCWj4CByQ5neWiCqe-pKXckhvOIotdLZLPvfJQava8WJP2SvYiKkcuggm0itDolnIsmrgLGmsHNMazLhlRYPoJ_g71iwXT5nLSIFm1uyjTCDStF_JhDNmn2qlwaxeEKsT2WByVeoxnbtduUSup2niuqKygs0jqPl2VX1-SgkN3pFv0-cw3EvPbklOy1P7KIbOOIbh-J0OUbDpuSgjtRhvLqEJ_DLIOi1BlS9Z5QSb0CGdrhoCLgAQuFRCLSwAKyvLo8QTLHT_VzrDI2PdhdbIqb71ChKMc3TteXY8mGCBus675ToEj7sCGsEmR3OaLgmJDU'; // Replace with your actual token
    private $client_id = 'b260032b-e29e-4965-8bf8-94a12e4c1f6e'; // Your Client ID from the screenshot
    private $client_secret = 'L6aR72ARZun2dd3eekUp0Jwt4r5H9qSsVj0u48oq'; // Your Client Secret from the screenshot

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Hook into WooCommerce order processing to create patient
        add_action('woocommerce_order_status_processing', [$this, 'create_patient_from_order']);

        // Add custom fields to the billing section
        add_filter('woocommerce_billing_fields', [$this, 'md_custom_billing_fields']);

        // Save custom fields when order is placed
        add_action('woocommerce_checkout_update_order_meta', [$this, 'md_save_custom_billing_fields']);

        // Display custom fields in WooCommerce admin order page
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'md_display_custom_billing_fields_in_admin'], 10, 1);
    }

    public function add_admin_menu() {
        add_options_page(
            __('MD Integrations API Settings', 'md-integrations-api'),
            __('MD Integrations API', 'md-integrations-api'),
            'manage_options',
            'md_integrations_api',
            [$this, 'options_page']
        );
    }

    public function settings_init() {
        register_setting('mdIntegrationsApi', 'md_integrations_api_options', [$this, 'sanitize_options']);

        add_settings_section(
            'md_integrations_api_section',
            __('API Settings', 'md-integrations-api'),
            null,
            'mdIntegrationsApi'
        );

        add_settings_field(
            'md_integrations_api_client_id',
            __('Client ID', 'md-integrations-api'),
            [$this, 'client_id_render'],
            'mdIntegrationsApi',
            'md_integrations_api_section'
        );

        add_settings_field(
            'md_integrations_api_client_secret',
            __('Client Secret', 'md-integrations-api'),
            [$this, 'client_secret_render'],
            'mdIntegrationsApi',
            'md_integrations_api_section'
        );
    }

    public function sanitize_options($options) {
        // Hard-coding credentials, so no need to sanitize options for now
        return $options;
    }

    public function client_id_render() {
        echo '<input type="text" name="md_integrations_api_options[client_id]" value="' . esc_attr($this->client_id) . '" readonly>';
    }

    public function client_secret_render() {
        echo '<input type="password" name="md_integrations_api_options[client_secret]" value="' . esc_attr($this->client_secret) . '" readonly>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('mdIntegrationsApi');
                do_settings_sections('mdIntegrationsApi');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        if ('settings_page_md_integrations_api' !== $hook) {
            return;
        }
        wp_enqueue_script('md-integrations-api-admin', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0', true);
    }

    private function make_authenticated_request($endpoint, $method = 'GET', $body = null) {
        // Use the hard-coded token for testing purposes
        $bearer_token = $this->bearer_token;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer_token,
                'Content-Type' => 'application/json',
            ],
        ];

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($endpoint, $args);

        if (is_wp_error($response)) {
            error_log('API request failed: ' . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function create_patient_from_order($order_id) {
        error_log("MD Integrations: create_patient_from_order() called for order ID: " . $order_id);

        $order = wc_get_order($order_id);

        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();
        $address = $order->get_billing_address_1();
        $address2 = $order->get_billing_address_2();
        $city = $order->get_billing_city();
        $state = $order->get_billing_state();
        $postcode = $order->get_billing_postcode();

        // Retrieve custom fields from the order meta
        $gender = get_post_meta($order_id, 'md_patient_gender', true);
        $date_of_birth = get_post_meta($order_id, 'md_patient_date_of_birth', true);
        $driver_license = get_post_meta($order_id, 'md_patient_driver_license', true);

        // Convert gender to integer (1 for Male, 2 for Female, 3 for Other)
        $gender_map = [
            'Male' => 1,
            'Female' => 2,
            'Other' => 3
        ];
        $gender = isset($gender_map[$gender]) ? $gender_map[$gender] : null;

        // Debug date of birth and gender
        error_log("MD Integrations: Raw date of birth from order meta - " . print_r($date_of_birth, true));
        error_log("MD Integrations: Converted gender - " . $gender);

        // Format the date of birth to Y-m-d
        if (!empty($date_of_birth)) {
            $date_of_birth = date('Y-m-d', strtotime($date_of_birth));
            error_log("MD Integrations: Formatted date of birth - " . $date_of_birth);
        } else {
            error_log("MD Integrations: Date of birth is empty or not set.");
        }

        // Prepare the patient data
        $patient_data = [
            'prefix' => 'Mr.', // Change accordingly (Sr., Ms., etc.)
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone,
            'phone_type' => 2, // Assuming 2 = Mobile
            'metadata' => 'Created from WooCommerce order ID ' . $order_id,
            'email' => $email,
            'address' => [
                'address' => $address,
                'address2' => $address2,
                'zip_code' => $postcode,
                'city_name' => $city,
                'state_name' => $state,
            ],
        ];

        // Only add fields if they are present
        if (!empty($gender)) {
            $patient_data['gender'] = $gender;
        }
        if (!empty($date_of_birth)) {
            $patient_data['date_of_birth'] = $date_of_birth;
        }
        if (!empty($driver_license)) {
            // Ensure driver_license is a valid UUID
            if (wp_is_uuid($driver_license, 4)) {
                $patient_data['driver_license_id'] = $driver_license;
            } else {
                error_log("MD Integrations: Driver License ID is not a valid UUID.");
            }
        }

        error_log("MD Integrations: Patient data prepared - " . print_r($patient_data, true));

        // Make the API request to create the patient
        $response = $this->make_authenticated_request('https://api.mdintegrations.com/v1/partner/patients', 'POST', $patient_data);

        if ($response && isset($response['patient_id'])) {
            // Patient successfully created, store patient_id in order meta
            update_post_meta($order_id, '_md_patient_id', $response['patient_id']);
            
            // Add patient_id to order notes
            $order->add_order_note('Patient ID ' . $response['patient_id'] . ' created successfully in MD Integrations.');
            
            error_log("MD Integrations: Patient created with ID: " . $response['patient_id']);
        } else {
            // Log an error if the patient creation fails
            error_log("MD Integrations: Failed to create patient. Response: " . print_r($response, true));

            // Optionally, add a note to the order about the failure
            $order->add_order_note('Failed to create patient in MD Integrations.');
        }
    }

    public function md_custom_billing_fields($fields) {
        // Add Date of Birth field
        $fields['md_patient_date_of_birth'] = array(
            'type' => 'date',
            'label' => __('Date of Birth'),
            'required' => true,
            'class' => array('form-row-wide'),
            'priority' => 21, // Adjust the priority to control the position
        );

        // Add Gender field
        $fields['md_patient_gender'] = array(
            'type' => 'select',
            'label' => __('Gender'),
            'required' => true,
            'class' => array('form-row-wide'),
            'options' => array(
                '' => __('Select an option', 'md-integrations-api'),
                'Male' => __('Male', 'md-integrations-api'),
                'Female' => __('Female', 'md-integrations-api'),
                'Other' => __('Other', 'md-integrations-api'),
            ),
            'priority' => 22, // Adjust the priority to control the position
        );

        // Add Driver License field
        $fields['md_patient_driver_license'] = array(
            'type' => 'text',
            'label' => __('Driver License (optional)'),
            'required' => false,
            'class' => array('form-row-wide'),
            'priority' => 23, // Adjust the priority to control the position
        );

        return $fields;
    }

    public function md_save_custom_billing_fields($order_id) {
        if (isset($_POST['md_patient_date_of_birth'])) {
            update_post_meta($order_id, 'md_patient_date_of_birth', sanitize_text_field($_POST['md_patient_date_of_birth']));
        }

        if (isset($_POST['md_patient_gender'])) {
            update_post_meta($order_id, 'md_patient_gender', sanitize_text_field($_POST['md_patient_gender']));
        }

        if (isset($_POST['md_patient_driver_license'])) {
            update_post_meta($order_id, 'md_patient_driver_license', sanitize_text_field($_POST['md_patient_driver_license']));
        }
    }

    public function md_display_custom_billing_fields_in_admin($order) {
        $date_of_birth = get_post_meta($order->get_id(), 'md_patient_date_of_birth', true);
        $gender = get_post_meta($order->get_id(), 'md_patient_gender', true);
        $driver_license = get_post_meta($order->get_id(), 'md_patient_driver_license', true);

        if ($date_of_birth) {
            echo '<p><strong>' . __('Date of Birth') . ':</strong> ' . $date_of_birth . '</p>';
        }

        if ($gender) {
            echo '<p><strong>' . __('Gender') . ':</strong> ' . $gender . '</p>';
        }

        if ($driver_license) {
            echo '<p><strong>' . __('Driver License') . ':</strong> ' . $driver_license . '</p>';
        }
    }
}

// Initialize the plugin
function md_integrations_api_init() {
    if (class_exists('WooCommerce')) {
        MD_Integrations_API::get_instance();
    } else {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . __('MD Integrations API requires WooCommerce to be active.', 'md-integrations-api') . '</p></div>';
        });
    }
}
add_action('plugins_loaded', 'md_integrations_api_init');
