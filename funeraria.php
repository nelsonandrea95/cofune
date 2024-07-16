<?php
/*
Plugin Name: Funeraria
Plugin URI:  https://softwareagil.com/es/
Description: A plugin for managing obituaries, services, and grief resources for funeral homes.
Version:     1.0.0
Author:      Nelson Mendez
Author URI:  https://softwareagil.com/es/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: funeraria
*/

// Funciones que se ejecutan al activar el plugin
function funeraria_activate_plugin() {
    funeraria_create_comunas_table();
    funeraria_add_user_role();
    funeraria_create_solicitudes_table();

    //flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'funeraria_activate_plugin' ); 

// Crear el role "Funeraria"
function funeraria_add_user_role() {
    add_role(
        'funeraria',
        'Funeraria',
        array(
            'read'         => true,
            'edit_posts'   => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'edit_published_posts' => true,
        )
    );
}

// Agregar capacidades especiales al role
add_action( 'init', 'funeraria_add_custom_capabilities' );
function funeraria_add_custom_capabilities() {
    $role = get_role( 'funeraria' );
    $role->add_cap( 'manage_paquetes_funerarios' );
    $role->add_cap( 'servicios_assign_terms' );
    $role->add_cap( 'delete_posts' ); 
    $role->add_cap( 'delete_others_posts' );
    $role->add_cap( 'upload_files' );
}

// Crear campos para agregar titulo, telefono y dirección del usuario funeraria
function funeraria_add_profile_fields( $user ) {

    if ( in_array( 'funeraria', (array) $user->roles ) ) {
        ?>
        <h3><?php esc_html_e( 'Información de la funeraria', 'funeraria' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="title"><?php esc_html_e( 'Título', 'funeraria' ); ?></label></th>
                <td><input type="text" name="title" id="title" value="<?php echo esc_attr( get_the_author_meta( 'title', $user->ID ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="phone"><?php esc_html_e( 'Teléfono', 'funeraria' ); ?></label></th>
                <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="address"><?php esc_html_e( 'Dirección', 'funeraria' ); ?></label></th>
                <td><input type="text" name="address" id="address" value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID ) ); ?>" class="regular-text" /></td>
            </tr>
            </table>
        <?php
    } 
}
add_action( 'show_user_profile', 'funeraria_add_profile_fields' );
add_action( 'edit_user_profile', 'funeraria_add_profile_fields' );

// Guardar los campos
function funeraria_save_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'title', $_POST['title'] );
    update_user_meta( $user_id, 'phone', $_POST['phone'] );
    update_user_meta( $user_id, 'address', $_POST['address'] );
}
add_action( 'personal_options_update', 'funeraria_save_profile_fields' );
add_action( 'edit_user_profile_update', 'funeraria_save_profile_fields' );

// Redireccionar el usuario luego del login
function funeraria_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) && in_array( 'funeraria', $user->roles ) ) {
        $dashboard_page = get_page_by_title( 'Funeraria Dashboard' );
        return get_permalink( $dashboard_page->ID );
    } else {
        return home_url( '/funeraria-login/?login=failed' );
    }
}
//add_filter( 'login_redirect', 'funeraria_login_redirect', 10, 3 );

// Procesar el login al dashboard
function funeraria_process_login() {
    if ( ! isset( $_POST['funeraria_login_nonce'] ) || ! wp_verify_nonce( $_POST['funeraria_login_nonce'], 'funeraria-login-nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Error de seguridad en el inicio de sesión.', 'funeraria' ) ) );
    }

    $creds = array(
        'user_login'    => sanitize_user( $_POST['log'] ),
        'user_password' => $_POST['pwd'],
        'remember'      => isset( $_POST['rememberme'] )
    );
    $user = wp_signon( $creds, false );

    if ( is_wp_error( $user ) ) {
        wp_send_json_error( array( 'message' => __( 'Nombre de usuario o contraseña incorrectos.', 'funeraria' ) ) );
    } 

    if ( in_array( 'funeraria', (array) $user->roles ) ) {
        $redirect_to = get_permalink( get_page_by_title( 'Funeraria Dashboard' ) );
    } else {
        $redirect_to = home_url(); 
    }

    wp_send_json_success( array( 'redirect' => $redirect_to ) );
    wp_die(); 
}
add_action( 'wp_ajax_funeraria_login', 'funeraria_process_login' );
add_action( 'wp_ajax_nopriv_funeraria_login', 'funeraria_process_login' ); 

// Procesar el logout
function funeraria_process_logout() {
    if (!wp_verify_nonce($_POST['nonce'], 'funeraria_logout')) {
        wp_send_json_error('Invalid nonce.');
    }

    wp_logout();
    wp_send_json_success(array(
        'redirect' => home_url( '/funeraria-login/' )
    ));

    wp_die(); 
}
add_action( 'wp_ajax_funeraria_logout', 'funeraria_process_logout' ); 
add_action( 'wp_ajax_nopriv_funeraria_logout', 'funeraria_process_logout' ); 

// Agregar scripts necesarios
function funeraria_enqueue_scripts() {
    wp_enqueue_media();
    wp_enqueue_script('jquery');

    wp_enqueue_style( 'bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' );
    wp_enqueue_script( 'bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), null, true );

    wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' ); 
    wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), null, true );

    wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css'); // Use the latest version
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js', array('jquery'), null, true);

    
    wp_enqueue_script('funeraria-scripts', plugin_dir_url(__FILE__) . 'assets/js/funeraria-scripts.js', array('jquery'), '1.0', true); // Replace with actual JS file path and version
        
    
    wp_enqueue_script('funeraria-multistep', plugin_dir_url(__FILE__) . 'assets/js/funeraria-multistep.js', array('jquery'), '1.0', true);
    

    if (is_post_type_archive('paquetes_funerarios')) {
        wp_enqueue_style('jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('filter-scripts', plugins_url('assets/js/filter-scripts.js', __FILE__), array('jquery'), '1.0', true);

        global $wpdb;
        $table_name = $wpdb->prefix . 'listado_comunas';
        $regions = $wpdb->get_results("SELECT DISTINCT codigo_region, nombre_region FROM $table_name ORDER BY nombre_region ASC");
        $comunas_data = array();
        foreach ($regions as $region) {
            $comunas = $wpdb->get_results($wpdb->prepare("SELECT codigo_comuna, nombre_comuna FROM $table_name WHERE codigo_region = %d ORDER BY nombre_comuna ASC", $region->codigo_region));
            $comunas_data[$region->codigo_region] = $comunas;
        }
        $min_price_result = $wpdb->get_row("SELECT MIN(meta_value) AS min_price FROM $wpdb->postmeta WHERE meta_key = 'price'");
        $max_price_result = $wpdb->get_row("SELECT MAX(meta_value) AS max_price FROM $wpdb->postmeta WHERE meta_key = 'price'");
        
        $min_price = (int) $min_price_result->min_price;
        $max_price = (int) $max_price_result->max_price; 

        wp_localize_script('filter-scripts', 'funeraria_vars', array(
            'ajaxurl'           => admin_url('admin-ajax.php'),
            'nonce_obtener_comunas' => wp_create_nonce( 'obtener_comunas_por_region' ),
            'nonce_buscar_paquetes' => wp_create_nonce( 'filter_paquetes_funerarios' ),
            'get_paquete_content_nonce' => wp_create_nonce( 'funeraria_get_paquete_content_nonce' ),
            'regions'           => $regions,
            'comunas_data' => $comunas_data,
            'min_price' => $min_price,
            'max_price' => $max_price,
        ));
    }
    wp_localize_script('funeraria-scripts', 'funeraria_script_vars', array(
        'ajaxurl'           => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce( 'modal_form' ),
        'get_paquete_content_nonce' => wp_create_nonce( 'funeraria_get_paquete_content_nonce' ),
    ));
}
add_action('wp_enqueue_scripts', 'funeraria_enqueue_scripts');


// Registrar los templates
function funeraria_register_page_templates( $templates ) {
    $templates['funeraria-paquete.php'] = __('Paquete Funerario Form', 'funeraria');
    $templates['funeraria-login.php'] = __('Funeraria login', 'funeraria');
    $templates['funeraria-dashboard.php'] = __('Funeraria dashboard', 'funeraria');
    return $templates;
}
add_filter( 'theme_page_templates', 'funeraria_register_page_templates' );

function funeraria_page_template( $page_template ) {
    if ( get_page_template_slug() == 'funeraria-login.php' ) {
        $page_template = plugin_dir_path(__FILE__) . 'templates/funeraria-login.php';
    } elseif ( get_page_template_slug() == 'funeraria-dashboard.php' ) {
        $page_template = plugin_dir_path(__FILE__) . 'templates/funeraria-dashboard.php';
    } elseif ( get_page_template_slug() == 'funeraria-paquete.php' ) {
        $page_template = plugin_dir_path(__FILE__) . 'templates/funeraria-paquete.php';
    }
    return $page_template;
}
add_filter( 'page_template', 'funeraria_page_template' );

// Agregar estilos
function funeraria_enqueue_styles() {
    wp_register_style( 
        'funeraria-styles',
        plugins_url( 'assets/css/funeraria-styles.css', __FILE__ ),
        array(),
        '1.0',
        'all'
    );

    // Enqueue the style on your template pages
    if ( is_page_template( array(
        'funeraria-login.php',
        'funeraria-dashboard.php',
        'funeraria-paquete.php' ) 
    ) ) {
        wp_enqueue_style( 'funeraria-styles' );
    }
    wp_enqueue_style( 'funeraria-styles' );
}
add_action( 'wp_enqueue_scripts', 'funeraria_enqueue_styles' );


// Registrar el post type "Paquetes funerarios"
function funeraria_register_post_types() {
    $labels = array(
        'name'                  => _x( 'Paquetes Funerarios', 'Post Type General Name', 'funeraria' ),
        'singular_name'         => _x( 'Paquete Funerario', 'Post Type Singular Name', 'funeraria' ),
        
    );

    $args = array(
        'label'                 => __( 'Paquetes Funerarios', 'funeraria' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ),
        'taxonomies'            => array( 'servicios' ), 
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'has_archive'           => 'paquetes-funerarios',
        
    );

    register_post_type( 'paquetes_funerarios', $args ); 
}
add_action( 'init', 'funeraria_register_post_types' );

// Registrar el archive template
function funeraria_register_archive_template( $archive_template ) {
    global $post;

    if ( is_post_type_archive( 'paquetes_funerarios' ) ) {
        $archive_template = plugin_dir_path( __FILE__ ) . 'templates/archive-paquetes_funerarios.php';
    }
    return $archive_template;
}
add_filter( 'archive_template', 'funeraria_register_archive_template');


function funeraria_template_loader( $template ) {
    global $post;

    if ( is_single() && $post->post_type == 'paquetes_funerarios' ) {
        $template = plugin_dir_path( __FILE__ ) . 'templates/single-paquetes_funerarios.php';
    }
    return $template;
}

add_filter( 'single_template', 'funeraria_template_loader' );

// Agregar custom fields al post type
function funeraria_add_paquetes_fields() {
    add_meta_box(
        'funeraria_paquetes_metabox',
        __( 'Detalles del Paquete', 'funeraria' ),
        'funeraria_paquetes_metabox_callback',
        'paquetes_funerarios',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'funeraria_add_paquetes_fields' );

function funeraria_paquetes_metabox_callback( $post ) {
    global $wpdb;
    // Add nonce for security
    wp_nonce_field( 'funeraria_paquetes_nonce', 'funeraria_paquetes_nonce' );

    // Price Field
    $price = get_post_meta( $post->ID, 'price', true );
    echo '<label for="price" style="font-weight:bold;">' . __( 'Precio:', 'funeraria' ) . '</label> ';
    echo '<input type="text" id="price" name="price" value="' . esc_attr( $price ) . '" />';

    // Comuna Fields (Multi-Select)
    $selected_comunas = get_post_meta( $post->ID, 'comuna', true );
    $selected_comunas = is_array( $selected_comunas ) ? $selected_comunas : array();
    $comunas = funeraria_get_comunas_options();
    echo '<label for="comuna-available" style="font-weight:bold;">' . __( 'Comunas Disponibles:', 'funeraria' ) . '</label>';
    echo '<select id="comuna-available" name="comuna-available[]" multiple="multiple" style="width: 100%; height:150px;">';
    foreach ( $comunas as $index => $comuna ) { // <-- Add $index for tracking original order
        // Exclude comunas already selected
        if ( ! in_array( $comuna->codigo_comuna, $selected_comunas ) ) {
            echo '<option value="' . esc_attr( $comuna->codigo_comuna ) . '" data-original-index="' . $index . '">' // <-- Add data-original-index
                 . esc_html( $comuna->nombre_region ) . ' - ' . esc_html( $comuna->nombre_comuna ) . '</option>';
        }
    }
    echo '</select>';

    echo '<button type="button" id="add-comuna-button" class="button button-secondary">'. __( 'Agregar >>', 'funeraria' ) .'</button>';

    echo '<label for="comuna-selected" style="font-weight:bold;">' . __( 'Comunas Seleccionadas:', 'funeraria' ) . '</label>';
    echo '<select id="comuna-selected" name="selected-comuna" multiple="multiple" style="width: 100%; height:150px;">'; // This is the field that will be saved
    foreach ( $selected_comunas as $codigo_comuna ) {
        echo $codigo_comuna;
        $comuna = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}listado_comunas WHERE codigo_comuna = %d", $codigo_comuna ) );
        if ( $comuna ) {
            echo '<option value="' . esc_attr( $comuna->codigo_comuna ) . '">' 
                 . esc_html( $comuna->nombre_region ) . ' - ' . esc_html( $comuna->nombre_comuna ) . '</option>';
        }
    }
    echo '</select>';

    echo '<button type="button" id="remove-comuna-button" class="button button-secondary">'. __( '<< Quitar', 'funeraria' ) .'</button>';
    echo '<input type="hidden" id="selected-comunas-input" name="comuna" value="'. implode(',', $selected_comunas).'">';

    // Tipo de Servicio Field
    $tipo_servicio = get_post_meta( $post->ID, 'tipo_servicio', true );
    $options = array(
        '' => __( 'Seleccionar Tipo de Servicio', 'funeraria' ),
        'inhumacion' => __( 'Inhumación', 'funeraria' ),
        'cremacion' => __( 'Cremación', 'funeraria' ),
        'traslado_nacional' => __( 'Traslado Nacional', 'funeraria' ),
        'repatriacion' => __( 'Repatración', 'funeraria' ),
    );

    echo '<label for="tipo_servicio" style="font-weight:bold;">' . __( 'Tipo de Servicio:', 'funeraria' ) . '</label> ';
    echo '<select id="tipo_servicio" name="tipo_servicio">';
    foreach ( $options as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $tipo_servicio, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';

    // Sala velatorio
    $sala_velatorio = get_post_meta( $post->ID, 'sala_velatorio', true );
    $velatorio_options = array(
        '' => __( 'Incluye sala velatorio?', 'funeraria' ),
        'si' => __( 'Si', 'funeraria' ),
        'no' => __( 'No', 'funeraria' ),
    );

    echo '<label for="sala_velatorio" style="font-weight:bold;">' . __( 'Incluye sala velatorio?', 'funeraria' ) . '</label> ';
    echo '<select id="sala_velatorio" name="sala_velatorio">';
    foreach ( $velatorio_options as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $sala_velatorio, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';

    // Not included Field
    $not_included = get_post_meta( $post->ID, 'not_included', true );
    echo '<label for="not_included" style="font-weight:bold;">' . __( 'No incluido:', 'funeraria' ) . '</label> ';
    echo '<textarea id="not_included" name="not_included" value="' . esc_attr( $not_included ) . '" rows="5">' . esc_attr( $not_included ) . '</textarea>';

    // Funeraria user
    $funeraria_id = get_post_meta( $post->ID, 'funeraria', true );

    echo '<label for="funeraria" style="font-weight:bold;">' . __( 'Funeraria:', 'funeraria' ) . '</label> ';
    echo '<select id="funeraria" name="funeraria">';
    echo funeraria_get_funeraria_user_options( $funeraria_id );
    echo '</select>';

    // Rate
    $rate = get_post_meta( $post->ID, 'rate', true );
    $rate_options = array(
        '0' => __( 'Puntuación', 'funeraria' ),
        '1' => __( '1', 'funeraria' ),
        '2' => __( '2', 'funeraria' ),
        '3' => __( '3', 'funeraria' ),
        '4' => __( '4', 'funeraria' ),
        '5' => __( '5', 'funeraria' ),
    );

    echo '<label for="rate" style="font-weight:bold;">' . __( 'Puntuación (1 - 5)', 'funeraria' ) . '</label> ';
    echo '<select id="rate" name="rate">';
    foreach ( $rate_options as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $rate, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';

    // Destacado Checkbox
    $destacado = get_post_meta( $post->ID, 'destacado', true );
    echo '<label for="destacado" style="font-weight:bold;">';
    echo '<input type="checkbox" id="destacado" name="destacado" value="1" ' . checked( $destacado, 1, false ) . ' />';
    echo __( ' Destacado', 'funeraria' );
    echo '</label>';

    ?>
    <style>
        #funeraria_paquetes_metabox label {
            display: block;
            margin-bottom: 5px;
        }

        #funeraria_paquetes_metabox input[type="text"],
        #funeraria_paquetes_metabox select,
        #funeraria_paquetes_metabox textarea {
            width: 50%;
            padding: 5px;
            margin-bottom: 15px;
        }
    </style>

    <script>
        jQuery(document).ready(function ($) {

            $('#comuna-available option').each(function(index) {
                $(this).data('original-index', index);
            });

            $('#add-comuna-button').on('click', function () {
                $('#comuna-available option:selected').appendTo('#comuna-selected');
                $('#comuna-available option:selected').remove();
                updateHiddenInput();
            });

            $('#remove-comuna-button').on('click', function () {
                $('#comuna-selected option:selected').each(function () {
                    var option = $(this);
                    var originalIndex = option.data('original-index');

                    var availableOptions = $('#comuna-available option');
                    if (originalIndex < availableOptions.length) {
                        availableOptions.eq(originalIndex).before(option.clone());
                    } else {
                        $('#comuna-available').append(option.clone());
                    }
                });
                $('#comuna-selected option:selected').remove();
                updateHiddenInput();
            });

            function updateHiddenInput() {
                var selectedValues = $('#comuna-selected option').map(function () {
                    return this.value;
                }).get();
                $('#selected-comunas-input').val(selectedValues.join(','));
            }
        });

    </script>
    
    <?php
} 

function funeraria_save_paquetes_meta( $post_id, $post ) {
    

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if (is_admin()) {
        if ($post->post_type !== 'paquetes_funerarios') {
            return $post_id;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
    } elseif (
        !isset($_POST['funeraria_paquete_nonce']) 
        || !wp_verify_nonce($_POST['funeraria_paquete_nonce'], 'funeraria_save_paquete') 
    ) {
        return $post_id;
    }

    //error_log( print_r( $_POST, true ) );
    
    if (isset($_POST['comuna'])) { 
        error_log(print_r($_POST['comuna'], true)); // Log the raw array for debugging
    
        
        $comuna_string = $_POST['comuna'];
        $comunas = explode(',', $comuna_string); // Split into an array

        // Sanitize each individual comuna value
        $sanitized_comunas = array_map('sanitize_text_field', $comunas);

        update_post_meta($post_id, 'comuna', $sanitized_comunas);
    }

    if ( isset( $_POST['price'] ) ) {
        $sanitized_price = floatval( sanitize_text_field( $_POST['price'] ) );
        update_post_meta( $post_id, 'price', $sanitized_price );
    }

    if ( isset( $_POST['tipo_servicio'] ) ) {
        update_post_meta( $post_id, 'tipo_servicio', sanitize_text_field( $_POST['tipo_servicio'] ) );
    }

    if ( isset( $_POST['sala_velatorio'] ) ) {
        update_post_meta( $post_id, 'sala_velatorio', sanitize_text_field( $_POST['sala_velatorio'] ) );
    }

    if ( isset( $_POST['not_included'] ) ) {
        update_post_meta( $post_id, 'not_included', sanitize_text_field( $_POST['not_included'] ) );
    }

    if ( isset( $_POST['images_gallery'] ) ) {
        $sanitized_image_ids = array_map( 'intval', $_POST['images_gallery'] );
        update_post_meta( $post_id, 'images_gallery', $sanitized_image_ids );
    } else {
        delete_post_meta( $post_id, 'images_gallery' );
    }

    if ( isset( $_POST['destacado'] ) ) {
        update_post_meta( $post_id, 'destacado', 1 );
    } else {
        delete_post_meta( $post_id, 'destacado' );
    }

    if ( isset( $_POST['funeraria'] ) ) {
        update_post_meta( $post_id, 'funeraria', sanitize_text_field( $_POST['funeraria'] ) );
    }
    error_log( 'saving rate' );
    if ( isset( $_POST['rate'] ) ) {
        $sanitized_rate = intval( sanitize_text_field( $_POST['rate'] ) );
        update_post_meta( $post_id, 'rate', $sanitized_rate );
        
    }

    
    $taxonomy = 'servicios'; 
    error_log('servicio_terms after sanitization: ' . print_r($taxonomy, true));
    // Check if the service terms were submitted from the frontend form
    if ( isset( $_POST['servicio_terms'] ) ) {

        // Sanitize the term IDs (using array_map instead of array_walk)
        $servicio_terms = array_map( 'intval', $_POST['servicio_terms'] );
    } else {
        // If no terms were submitted from the frontend, it means the data was saved in the backend
        $servicio_terms = isset( $_POST['tax_input'][$taxonomy] ) ? $_POST['tax_input'][$taxonomy] : array(); 
    }

    error_log('servicio_terms after sanitization: ' . print_r($servicio_terms, true)); // Log for debugging

    if ( current_user_can( $taxonomy . '_assign_terms' ) ) {
        $result = wp_set_object_terms( $post_id, $servicio_terms, $taxonomy );
        if ( is_wp_error( $result ) ) {
            error_log( 'Error saving terms: ' . $result->get_error_message() );
        }
    } else {
        error_log( 'User does not have permission to assign "servicios" terms.' );
    }

    // Check if the current user is the author of the post (for frontend security)
    if ( !is_admin() && $post_id && get_post_meta( $post_id, 'funeraria', true ) != get_current_user_id() ) {
        wp_die( __( 'Error: You do not have permission to edit this post.', 'funeraria' ) );
    }
}
add_action( 'save_post_paquetes_funerarios', 'funeraria_save_paquetes_meta', 10, 2 );
//add_action( 'save_post', 'funeraria_save_paquetes_meta' );

function funeraria_get_funeraria_user_options( $selected = '' ) {
    $users = get_users( array( 'role' => 'funeraria' ) );
    $options = '<option value="">' . __( 'Seleccionar Funeraria', 'funeraria' ) . '</option>';

    foreach ( $users as $user ) {
        $options .= '<option value="' . esc_attr( $user->ID ) . '" ' . selected( $selected, $user->ID, false ) . '>' 
                    . esc_html( $user->display_name ) . '</option>';
    }

    return $options;
}

function funeraria_register_taxonomies() {
    $labels = array(
        'name'              => _x( 'Servicios', 'Taxonomy General Name', 'funeraria' ),
        'singular_name'     => _x( 'Servicio', 'Taxonomy Singular Name', 'funeraria' ), 
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
    );

    register_taxonomy( 'servicios', array( 'paquetes_funerarios' ), $args );

    $labels_complementarios = array(
        'name'              => _x( 'Servicios Complementarios', 'Taxonomy General Name', 'funeraria' ),
        'singular_name'     => _x( 'Servicio Complementario', 'Taxonomy Singular Name', 'funeraria' ),
    );

    $args_complementarios = array(
        'labels'            => $labels_complementarios,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
    );
    
    register_taxonomy( 'servicios_complementarios', array( 'paquetes_funerarios' ), $args_complementarios );
}
add_action( 'init', 'funeraria_register_taxonomies' );

function funeraria_add_term_icon_field($term = null) {
    wp_enqueue_media();

    $icon_id = '';
    $icon_url = '';

    // If editing an existing term
    if ($term && isset($term->term_id)) {
        $icon_id = get_term_meta($term->term_id, 'icon', true);
        if ($icon_id) {
            $icon_url = wp_get_attachment_image_url($icon_id, 'thumbnail');
        }
    } 

    // Display image upload field 
    ?>
    <div class="form-field term-icon-wrap">
        <label for="term-icon"><?php _e('Icono', 'funeraria'); ?></label>
        <div id="term-icon-preview">
            <?php if ($icon_url): ?>
                <img src="<?php echo esc_url($icon_url); ?>" alt="" style="max-width:100px; height:auto;" />
            <?php endif; ?>
        </div>
        <input type="hidden" id="term-icon" name="term-icon" value="<?php echo esc_attr($icon_id); ?>">
        <button type="button" class="button upload_image_button"><?php _e('Seleccionar Imagen', 'funeraria'); ?></button>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('.upload_image_button').click(function (event) {
                event.preventDefault();

                var frame = wp.media({
                    title: 'Seleccionar o subir imagen',
                    button: {
                        text: 'Usar esta imagen'
                    },
                    multiple: false
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#term-icon').val(attachment.id);
                    $('#term-icon-preview').html('<img src="' + attachment.sizes.thumbnail.url + '" alt="" style="max-width:100px; height:auto;" />'); 
                });

                frame.open();
            });
        });
    </script>
    <?php
}
add_action('servicios_edit_form_fields', 'funeraria_add_term_icon_field');
add_action('servicios_add_form_fields', 'funeraria_add_term_icon_field');


function funeraria_save_servicio_meta( $term_id, $tt_id ) {
    if ( isset( $_POST['term-icon'] ) ) {
        $icon_id = absint( $_POST['term-icon'] );
        update_term_meta( $term_id, 'icon', $icon_id );
    } else {
        delete_term_meta( $term_id, 'icon' );
    }
}
add_action( 'create_servicios', 'funeraria_save_servicio_meta', 10, 2 );
add_action( 'edited_servicios', 'funeraria_save_servicio_meta', 10, 2 );




// Create Comuna list on the WordPress DB
function funeraria_create_comunas_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'listado_comunas';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          codigo_region mediumint(9) NOT NULL,
          nombre_region varchar(255) NOT NULL,
          codigo_comuna mediumint(9) NOT NULL,
          nombre_comuna varchar(255) NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Import data from CSV
        if (($handle = fopen(plugin_dir_path(__FILE__) . 'listado-comunas.csv', 'r')) !== FALSE) {
            // Skip the first row (header)
            fgetcsv($handle);
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                $sanitized_data = array(
                    'codigo_region' => intval(sanitize_text_field($data[0])),
                    'nombre_region' => sanitize_text_field($data[1]),
                    'codigo_comuna' => intval(sanitize_text_field($data[2])),
                    'nombre_comuna' => sanitize_text_field($data[3])
                );

                $wpdb->insert(
                    $table_name,
                    $sanitized_data
                );
            }
            fclose($handle);
        }
    }
}


function funeraria_get_comunas_options($selected = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'listado_comunas';
    return $wpdb->get_results("SELECT codigo_comuna, nombre_region, nombre_comuna FROM $table_name ORDER BY nombre_region ASC, nombre_comuna ASC");

    
}

function funeraria_enqueue_media() {
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'funeraria_enqueue_media' );

// Add the metabox
function funeraria_add_paquetes_gallery_metabox() {
    add_meta_box(
        'funeraria_paquetes_gallery',
        __( 'Galería de Imágenes', 'funeraria' ),
        'funeraria_paquetes_gallery_metabox_callback',
        'paquetes_funerarios',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'funeraria_add_paquetes_gallery_metabox' );

// Metabox callback function
function funeraria_paquetes_gallery_metabox_callback( $post ) {
    wp_nonce_field( 'funeraria_gallery_save', 'funeraria_gallery_nonce' );

    $image_ids = get_post_meta( $post->ID, 'images_gallery', true );
    if ( empty( $image_ids ) ) {
        $image_ids = array();
    }

    echo '<div id="funeraria-gallery-container">';

    foreach ( $image_ids as $image_id ) {
        $image_src = wp_get_attachment_image_src( $image_id, 'thumbnail' ); 
        echo '<div class="funeraria-gallery-image">';
        echo '<img src="' . esc_url( $image_src[0] ) . '" />';
        echo '<input type="hidden" name="images_gallery[]" value="' . esc_attr( $image_id ) . '" />';
        echo '<a href="#" class="funeraria-remove-image">x</a>';
        echo '</div>';
    }

    echo '</div>';
    echo '<button type="button" id="funeraria-add-image">' . __( 'Añadir imágenes', 'funeraria' ) . '</button>';

    ?>
    <style>
        #funeraria-gallery-container{
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .funeraria-gallery-image{
            position: relative;
        }
        .funeraria-remove-image{
            position: absolute;
            top: 5px;
            right: 5px;
            font-weight: bold;
            width: 20px;
            height: 20px;
            color: #fff;
            background-color: red;
            border-radius: 50%;
            text-align: center;
            text-decoration: none;
        }
    </style>
    <script>
        jQuery(document).ready(function($){
            // Open the media uploader when the "Add Images" button is clicked
            $('#funeraria-add-image').click(function(e){
                e.preventDefault();
                var frame = wp.media({ 
                    title: '<?php echo esc_js( __( 'Selecciona o sube imágenes', 'funeraria' ) ); ?>',
                    button: {
                        text: '<?php echo esc_js( __( 'Usa estas imágenes', 'funeraria' ) ); ?>'
                    },
                    multiple: true,
                    library : {
                        author : <?php echo get_current_user_id(); ?>, 
                        type : ['image/jpeg', 'image/png']
                    }
                });

                frame.on( 'select', function() {
                    var attachments = frame.state().get('selection');

                    attachments.forEach(function(attachment){

                        attachment = attachment.toJSON();

                        if (attachment.filesize > 3 * 1024 * 1024) { 
                            alert("<?php esc_html_e( 'Error: El tamaño del archivo excede el límite de 3MB.', 'funeraria' ); ?>");
                            return;
                        }
                        
                        $('#funeraria-gallery-container').append('<div class="funeraria-gallery-image"><img src="' + attachment.sizes.thumbnail.url + '" /><input type="hidden" name="images_gallery[]" value="' + attachment.id + '" /><a href="#" class="funeraria-remove-image">x</a></div>'); 
                    });
                });

                frame.open();
            });

            // Remove images when the "Remove" link is clicked
            $(document).on('click', '.funeraria-remove-image', function(e){
                e.preventDefault();
                $(this).parent().remove(); 
            });
        });
    </script>
    <?php
}

//Save Packages Form
function funeraria_save_paquete_form() {
    // Check nonce and permissions
    if (!isset($_POST['funeraria_paquete_nonce']) || !wp_verify_nonce($_POST['funeraria_paquete_nonce'], 'funeraria_save_paquete')) {
        wp_die(__('Error: Nonce verification failed.', 'funeraria'));
        return;
    }

    if (!current_user_can('manage_paquetes_funerarios')) {
        wp_die(__('Error: You do not have permission to perform this action.', 'funeraria'));
        return;
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

    $post_data = array(
        'ID'           => $post_id, // Set the ID for updates
        'post_title'   => sanitize_text_field($_POST['post_title']),
        'post_content' => $_POST['post_content'],
        'post_status'  => 'publish', // or 'draft' if you want to save as draft
        'post_type'    => 'paquetes_funerarios',
        'post_author'  => get_current_user_id()
    );

    // Update or insert the post 
    if ($post_id) {
        $result = wp_update_post($post_data);
        if (is_wp_error($result)) {
            wp_die(__('Error: Post update failed.', 'funeraria') . ' ' . $result->get_error_message());
        }
    } else {
        $result = wp_insert_post($post_data);
        if (is_wp_error($result)) {
            wp_die(__('Error: Post creation failed.', 'funeraria') . ' ' . $result->get_error_message());
        } else {
            $post_id = $result;
        }
    }

    // Save the meta fields 
    if ($post_id) {
        
        if (isset($_POST['comuna'])) {  
            $comuna_string = $_POST['comuna'];
            $comunas = explode(',', $comuna_string);
            $sanitized_comunas = array_map('sanitize_text_field', $comunas);
    
            update_post_meta($post_id, 'comuna', $sanitized_comunas);
        }
        //$comuna = isset($_POST['comuna']) ? sanitize_text_field($_POST['comuna']) : '';
        $price = isset($_POST['price']) ? floatval(sanitize_text_field($_POST['price'])) : '';
        $tipo_servicio = isset($_POST['tipo_servicio']) ? sanitize_text_field($_POST['tipo_servicio']) : '';
        $images_gallery = isset($_POST['images_gallery']) ? array_map('intval', $_POST['images_gallery']) : array();
        $funeraria_id = get_current_user_id();
        $not_included = isset($_POST['not_included']) ? sanitize_text_field($_POST['not_included']) : '';
        $sala_velatorio = isset($_POST['sala_velatorio']) ? sanitize_text_field($_POST['sala_velatorio']) : '';

        if ( isset( $_POST['featured_image'] ) ) {
            $featured_image_id = absint( $_POST['featured_image'] );
            set_post_thumbnail( $post_id, $featured_image_id );
        }

        // Update or delete meta values based on whether they are empty or not
        //update_post_meta($post_id, 'comuna', $comuna);
        update_post_meta($post_id, 'price', $price);
        update_post_meta($post_id, 'tipo_servicio', $tipo_servicio);
        update_post_meta($post_id, 'not_included', $not_included);
        update_post_meta($post_id, 'sala_velatorio', $sala_velatorio);
        update_post_meta($post_id, 'images_gallery', $images_gallery);
        update_post_meta($post_id, 'funeraria', $funeraria_id); 

        // Guardar servicios
        $taxonomy = 'servicios';
        $term_ids = isset($_POST['servicio_terms']) ? $_POST['servicio_terms'] : array();

        $term_ids = array_map('intval', (array)$term_ids);

        if (current_user_can($taxonomy . '_assign_terms')) {
            wp_set_object_terms($post_id, $term_ids, $taxonomy);
        } else {
            error_log('User does not have permission to assign "servicios" terms.');
        }
    
    }

    // Redirect after saving
    $dashboard_page = get_page_by_title('Funeraria Dashboard');
    wp_safe_redirect(get_permalink($dashboard_page->ID));
    exit;
}

add_action( 'admin_post_funeraria_save_paquete', 'funeraria_save_paquete_form' );
add_action( 'admin_post_nopriv_funeraria_save_paquete', 'funeraria_save_paquete_form' );

// Función para eliminar el paquete desde el dashboard
function funeraria_delete_paquete() {

    if (!wp_doing_ajax() || !is_user_logged_in()) {
        wp_send_json_error('Invalid request or unauthorized.');
    }

    if (!isset($_POST['post_id']) || !isset($_POST['nonce'])) {
        wp_send_json_error('Missing data.');
    }

    if (!wp_verify_nonce($_POST['nonce'], 'funeraria_delete_paquete')) {
        wp_send_json_error('Invalid nonce.');
    }

    $post_id = absint($_POST['post_id']);
    $funeraria_id = get_post_meta( $post_id, 'funeraria', true );
    if ($funeraria_id == absint($_POST['userId'])) {
        wp_send_json_error('You do not have permission to delete this post.');
    }

    $result = wp_delete_post($post_id, true);

    if ($result) {
        wp_send_json_success('Paquete Funerario eliminado correctamente.');
    } else {
        wp_send_json_error('Error al eliminar el Paquete Funerario.');
    }
}

add_action('wp_ajax_funeraria_delete_paquete', 'funeraria_delete_paquete');
add_action('wp_ajax_nopriv_funeraria_delete_paquete', 'funeraria_delete_paquete');


function funeraria_multistep_form_shortcode( $atts ) {
    wp_enqueue_script( 'jquery' ); 
    wp_enqueue_script( 'funeraria-multistep', plugins_url( 'js/funeraria-multistep.js', __FILE__ ), array( 'jquery' ), '1.0', true );

    global $wpdb;
    $table_name = $wpdb->prefix . 'listado_comunas';

    $regions = $wpdb->get_results( "SELECT DISTINCT codigo_region, nombre_region FROM $table_name ORDER BY nombre_region ASC" );
    $comunas_by_region = array();
    foreach ( $regions as $region ) {
        $comunas_by_region[$region->codigo_region] = $wpdb->get_results( $wpdb->prepare( 
            "SELECT codigo_comuna, nombre_comuna FROM $table_name WHERE codigo_region = %d ORDER BY nombre_comuna ASC", 
            $region->codigo_region 
        ) );
    }

    wp_localize_script( 'funeraria-multistep', 'funeraria_vars', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce_obtener_comunas' => wp_create_nonce( 'obtener_comunas_por_region' ),
        'nonce_buscar_paquetes' => wp_create_nonce( 'buscar_paquetes_funerarios' ),
        'regions' => $regions,
        'comunas_data' => $comunas_by_region,
    ) );

    ob_start();
    ?>
    <div id="funeraria-multistep-form">
        <div class="step-buttons">
            <?php
            $steps = array(
                '1' => 'Inicio',
                '2' => 'Tipo de servicio',
                '3' => 'Sala Velatorio',
                '4' => 'Región',
                '5' => 'Comuna'
            );
            $index = 1;
            foreach ( $steps as $stepNumber => $stepTitle ) {
                echo '<button type="button" class="step-button" data-step="' . esc_attr( $stepNumber ) . '"><span>'.$index.'</span><span>' . esc_html( $stepTitle ) . '</span></button>';
                $index++;
            }
            ?>
        </div>
        <div class="step" data-step="1">
            <h2>El comparador de servicios funerarios</h2>
            <p>Compare de forma rápida entre una gran variedad de servicios funerarios y ahorre en su contratación. Sin presupuestos ni esperas.</p>
            <button type="button" class="next-step">Comparar servicios</button>
        </div>

        <div class="step" data-step="2">
            <h3>¿Qué tipo de servicio funerario le interesa?</h3>
            <div class="options">
                <?php
                $servicios = array(
                    'inhumacion' => 'Inhumación',
                    'cremacion' => 'Cremación',
                    'traslado_nacional' => 'Traslado Nacional',
                    'repatriacion' => 'Repatriación'
                );
                foreach ($servicios as $value => $label) {
                    echo '<label><input type="radio" name="tipo_servicio" value="' . esc_attr($value) . '" required> ' . esc_html($label) . '</label>';
                }
                ?>
            </div>
        </div>

        <div class="step" data-step="3">
            <h3>¿Quiere que el servicio incluya Sala Velatorio?</h3>
            <div class="options">
            <?php
            $sala_velatorio_options = array(
                'si' => 'Sí',
                'no' => 'No',
                'indiferente' => 'Indiferente'
            );
            foreach ($sala_velatorio_options as $value => $label) {
                echo '<label><input type="radio" name="sala_velatorio" value="' . esc_attr($value) . '" required> ' . esc_html($label) . '</label>';
            }
            ?>
            </div>
        </div>

        <div class="step" data-step="4">
            <h3>Selecciona la región desde la que solicita el servicio</h3>
            <select id="region" name="region" required>
                <option value="">Seleccionar Región</option>
                <?php foreach ($regions as $region): ?>
                    <option value="<?php echo esc_attr($region->codigo_region); ?>"><?php echo esc_html($region->nombre_region); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="step" data-step="5">
            <h3>Seleccione la comuna desde la que solicita el servicio</h3>
            <select id="comuna" name="comuna" required>
                <option value="">Seleccionar Comuna</option>
            </select>
        </div>

        <div class="step step-results" data-step="6">
            <h3>Sugerencias sobre la búsqueda</h3>
            <div id="funeraria-resultados"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode( 'funeraria_multistep_form', 'funeraria_multistep_form_shortcode' );


function funeraria_get_comunas_by_region() {
    check_ajax_referer('obtener_comunas_por_region', 'nonce');

    $region_id = intval($_POST['region_id']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'listado_comunas';
    $comunas = $wpdb->get_results($wpdb->prepare("SELECT codigo_comuna, nombre_comuna FROM $table_name WHERE codigo_region = %d ORDER BY nombre_comuna ASC", $region_id));

    $options = '<option value="">Seleccionar Comuna</option>';
    foreach ($comunas as $comuna) {
        $options .= '<option value="' . esc_attr($comuna->codigo_comuna) . '">' . esc_html($comuna->nombre_comuna) . '</option>';
    }

    wp_send_json_success($options);
}
add_action('wp_ajax_obtener_comunas_por_region', 'funeraria_get_comunas_by_region');
add_action('wp_ajax_nopriv_obtener_comunas_por_region', 'funeraria_get_comunas_by_region');


function buscar_paquetes_funerarios() {
    check_ajax_referer('buscar_paquetes_funerarios', 'nonce');

    $tipo_servicio = sanitize_text_field($_POST['tipo_servicio']);
    $sala_velatorio    = sanitize_text_field($_POST['sala_velatorio']);
    $region            = sanitize_text_field($_POST['region']);
    $comuna            = sanitize_text_field($_POST['comuna']);
    

    if (!empty($comuna)) {
        $comuna_ids = explode(',', $comuna);
        $comuna_ids = array_map('intval', $comuna_ids); // Sanitize and cast to integers
    } else {
        $comuna_ids = array(); // Empty array if no comunas selected
    }

    $meta_query = array(
        'relation' => 'AND', 
        array(
            'key'     => 'tipo_servicio',
            'value'   => $tipo_servicio,
            'compare' => '='
        ),
        array(
            'key'     => 'comuna',
            'value'   => $comuna,
            'compare' => 'LIKE'
        ),
        array(
            'key'     => 'destacado',  
            'value'   => '1',      
            'compare' => '='
        )
    );

    if ( $sala_velatorio !== 'indiferente' ) {
        $meta_query[] = array(
            'key'     => 'sala_velatorio', 
            'value'   => $sala_velatorio,
            'compare' => '='
        );
    }

    $args = array(
        'post_type'      => 'paquetes_funerarios',
        'posts_per_page' => -1, 
        'meta_query'     => $meta_query
    );

    $query = new WP_Query( $args );

    $results_html = ''; 
    $results_html .= '<div class="featured-paquetes">'; 
    if ( $query->have_posts() ) :
        // Output results
        while ( $query->have_posts() ) : $query->the_post();
            $featured_image_id = get_post_thumbnail_id( get_the_ID() );
            $featured_image_url = $featured_image_id ? wp_get_attachment_image_url( $featured_image_id, 'full' ) : '';
            
            $content = get_the_content();
            $excerpt = wp_trim_words( $content, 20, '...' );
            $results_html .= '<div class="featured-paquete-funerario">';
            $results_html .= '<div class="img-price">';
            // Featured Image
            if ( $featured_image_url ) {
                $results_html .= '<img src="' . esc_url( $featured_image_url ) . '" alt="' . esc_attr( get_the_title() ) . '" />';
            }
            $results_html .= '<div class="price-box">';
            $results_html .= '<span class="price">$' . esc_html( get_post_meta( get_the_ID(), 'price', true ) ) . '</span>';
            $results_html .= '<span class="iva-text">IVA incluido</span>';
            $results_html .= '<img class="stars" src ="'. plugins_url( 'assets/images/'.esc_html( get_post_meta( get_the_ID(), 'rate', true ) ).'-stars.svg', __FILE__ ) .'">';
            $results_html .= '</div>';
            $results_html .= '</div>'; 
            $results_html .= '<h3>' . get_the_title() . '</h3>'; 
            $results_html .= '<p>' . esc_html($excerpt). '</p>';
            $results_html .= '<div class="buttons">';
            $results_html .= '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#funeraria-contact-modal" data-paquete-id="' . esc_attr( get_the_ID() ) . '">Me interesa</button>';
            $results_html .= '<a href="#" class="archive-paquete-link" data-postid="'.get_the_ID().'">Ver detalles</a>';
            $results_html .= '</div>';
            $results_html .= '</div>'; 
        endwhile; 
    else :
        $results_html .= '<p>' . __( 'No se encontraron paquetes funerarios que coincidan con su selección.', 'funeraria' ) . '</p>';
    endif;
    $results_html .= '</div>'; 
    ob_start();
    funeraria_render_contact_modal(); 
    funeraria_render_paquete_modal();
    $results_html .= ob_get_clean();
    wp_reset_postdata();

    // Add button to go to the archive page with filters
    if ( $query->found_posts > 0 ) {  // Only show button if there are results
        $archive_url = get_post_type_archive_link( 'paquetes_funerarios' );
        $filtered_archive_url = add_query_arg( $_POST, $archive_url ); 
        $results_html .= '<a href="' . esc_url( $filtered_archive_url ) . '" class="button button-primary">' . __( 'Ver todos los resultados', 'funeraria' ) . '</a>';
    }

    wp_send_json_success( $results_html );  
}
add_action( 'wp_ajax_buscar_paquetes_funerarios', 'buscar_paquetes_funerarios' );
add_action( 'wp_ajax_nopriv_buscar_paquetes_funerarios', 'buscar_paquetes_funerarios' );

function funeraria_render_contact_modal() {
    ?>
    <div class="modal fade" id="funeraria-contact-modal" tabindex="-1" aria-labelledby="funerariaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="funeraria-contact-form" class="row">
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function funeraria_render_paquete_modal() {
    ?>
    <div class="modal fade" id="modalPaqueteContent" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog modal-fullscreen" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="" id="myModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div>
                    <div class="modal-body" id="modal-content">
                        
                    </div>
                </div>
            </div>
        </div>
    <?php
}


function funeraria_filter_archive_query($query) {
    if ($query->is_main_query() && is_post_type_archive('paquetes_funerarios')) {

        // Check for query parameters
        if (isset($_GET['servicio_funerario'])) {
            $query->set('meta_key', 'tipo_servicio');
            $query->set('meta_value', sanitize_text_field($_GET['servicio_funerario']));
        }

        if (isset($_GET['sala_velatorio']) && $_GET['sala_velatorio'] !== 'indiferente') {
            $query->set('meta_key', 'sala_velatorio');
            $query->set('meta_value', sanitize_text_field($_GET['sala_velatorio']));
        }

        if (isset($_GET['comuna'])) {
            $comuna = sanitize_text_field($_GET['comuna']);

            // Check if $comuna is a valid serialized array
            if (is_serialized($comuna)) {
                $comuna_array = unserialize($comuna);
                $query->set('meta_query', array(
                    array(
                        'key'     => 'comuna',
                        'value'   => $comuna_array,
                        'compare' => 'IN'  
                    )
                ));
            } else {
                // If not serialized, search for a partial match within the serialized array
                $query->set('meta_query', array(
                    array(
                        'key'     => 'comuna',
                        'value'   => $comuna,
                        'compare' => 'LIKE'
                    )
                ));
            }
        }
        
    }
}
add_action('pre_get_posts', 'funeraria_filter_archive_query');

function funeraria_custom_excerpt_length( $length ) {
    return 25;
}
add_filter( 'excerpt_length', 'funeraria_custom_excerpt_length' );

function funeraria_filter_paquetes_funerarios() {
    check_ajax_referer('filter_paquetes_funerarios', 'nonce');

    $servicio_funerario = sanitize_text_field($_POST['servicio_funerario']);
    $sala_velatorio    = sanitize_text_field($_POST['sala_velatorio']);
    $region            = sanitize_text_field($_POST['region']);
    $comuna            = sanitize_text_field($_POST['comuna']);
    $rate            = sanitize_text_field($_POST['rate']);

    $args = array(
        'post_type'      => 'paquetes_funerarios',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
        )
    );

    if (isset($_POST['orderby'])) {
        $orderby = sanitize_text_field($_POST['orderby']);

        switch ($orderby) {
            case 'price_asc':
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                $args['meta_key'] = 'price';
                break;
            case 'price_desc':
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                $args['meta_key'] = 'price';
                break;
            case 'rate_desc':
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                $args['meta_key'] = 'rate';
                break;
            case 'rate_asc':
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                $args['meta_key'] = 'rate';
                break;
        }
    }

    if ( isset( $_POST['min_price'] ) && ! empty( $_POST['min_price'] ) ) {
        $args['meta_query'][] = array(
            'key'     => 'price',
            'value'   => floatval( sanitize_text_field( $_POST['min_price'] ) ),
            'compare' => '>='
        );
    }

    if ( isset( $_POST['max_price'] ) && ! empty( $_POST['max_price'] ) ) {
        $args['meta_query'][] = array(
            'key'     => 'price',
            'value'   => floatval( sanitize_text_field( $_POST['max_price'] ) ),
            'compare' => '<='
        );
    }

    if (isset($_POST['servicios']) && is_array($_POST['servicios'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'servicios',
                'field'    => 'slug', 
                'terms'    => $_POST['servicios'], 
            ),
        );
    }

    if ( ! empty( $comuna ) ) {
        $args['meta_query'][] = array(
            'key'     => 'comuna',
            'value'   => $comuna,
            'compare' => 'LIKE'
        );
    }

    if ( ! empty( $servicio_funerario ) ) {
        $args['meta_query'][] = array(
            'key'     => 'tipo_servicio',
            'value'   => $servicio_funerario,
            'compare' => '='
        );
    }

    if ( ! empty( $sala_velatorio ) ) {
        $args['meta_query'][] = array(
            'key'     => 'sala_velatorio',
            'value'   => $sala_velatorio,
            'compare' => '='
        );
    }

    if ( ! empty( $rate ) ) {
        $args['meta_query'][] = array(
            'key'     => 'rate',
            'value'   => $rate,
            'compare' => '='
        );
    }
    
    $query = new WP_Query( $args );

    $results_html = ''; 

    ob_start();

    if ($query->have_posts()) : ?>
        <div class="paquete-funerarios-grid"> 
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <article id="post-<?php the_ID(); ?>" class="paquete-item row py-4 border-bottom">
                <div class="paquete-img col-md-3">
                    <a href="#" class="archive-paquete-link" data-postid="<?php the_ID(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>    
                </div>    
                <div class="paquete-content col-md-7">
                    <h3 class="title"><a href="#" class="archive-paquete-link" data-postid="<?php the_ID(); ?>"><?php the_title(); ?></a></h3>
                    <?php the_excerpt(); ?>
                    <?php
                    $terms = get_the_terms(get_the_ID(), 'servicios');

                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo '<div class="related-services mb-3">';
                        foreach ($terms as $term) {
                            $icon_id = get_term_meta($term->term_id, 'icon', true); 
                            $icon_url = wp_get_attachment_image_url($icon_id, 'thumbnail');
                            if ($icon_url) {
                                echo '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($term->name) . '" title="Incluye ' . esc_attr($term->name) . '" width="24" height="24" /> ';
                            }
                        }
                        echo '</div>';
                    }
                    ?>
                </div> 
                <div class="price-box col-md-2">
                    <span class="price"> <?php echo esc_html(get_post_meta(get_the_ID(), 'price', true)); ?></span>
                    <span class="iva-text">IVA incluido</span>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#funeraria-contact-modal" data-paquete-id="<?php echo get_the_ID() ?>">Me interesa</button>
                </div>   
            </article>
        <?php endwhile; ?>
        </div> <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e( 'No se encontraron paquetes funerarios.', 'funeraria' ); ?></p>
    <?php 
    endif;
    $results_html = ob_get_clean();
    $total_posts = $query->found_posts;

    $response_data = array(
        'html' => $results_html,
        'total_posts' => $total_posts
    );

    wp_reset_postdata();
    wp_send_json_success( $response_data );
}
add_action( 'wp_ajax_filter_paquetes_funerarios', 'funeraria_filter_paquetes_funerarios' );
add_action( 'wp_ajax_nopriv_filter_paquetes_funerarios', 'funeraria_filter_paquetes_funerarios' );


function funeraria_get_contact_form_fields() {
    // Check for nonce and permissions
    check_ajax_referer( 'modal_form', 'nonce' );
    
    $paquete_id = isset( $_POST['paquete_id'] ) ? absint( $_POST['paquete_id'] ) : 0;
    $comuna_id = get_post_meta( $paquete_id, 'comuna', true );
    $price = get_post_meta($paquete_id, 'price', true); // Get the price of the package
    // Form fields HTML
    ob_start();
    ?>
        <h3 class="modal-title"><?php echo get_the_title($paquete_id); ?> -  $<?php echo $price ?></h3>
        <p class="modal-desc">Si está interesado en solicitar más información sin compromiso de este servicio, envíe el siguiente formulario o llame al 900 433 094 y le atenderemos de inmediato.</p>
        <div class="mb-3 col-md-6">
            <label for="nombre" class="mb-2 fw-semibold text-start"><?php _e('Nombre:', 'funeraria'); ?></label>
            <input type="text" id="nombre" name="nombre" class="form-control" required>
        </div>
        <div class="mb-3 col-md-6">
            <label for="comuna" class="mb-2 fw-semibold text-start"><?php _e('Comuna:', 'funeraria'); ?></label>
            <select id="comuna" name="comuna" class="form-control" disabled>
                <?php echo funeraria_get_comunas_options( $comuna_id ); ?> 
            </select>
        </div>
        <div class="mb-3 col-md-6">
            <label for="email" class="mb-2 fw-semibold text-start"><?php _e('Email:', 'funeraria'); ?></label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3 col-md-6">
            <label for="telefono" class="mb-2 fw-semibold text-start"><?php _e('Teléfono:', 'funeraria'); ?></label>
            <input type="tel" id="telefono" name="telefono" class="form-control" required>
        </div>
        <div class="mb-3 col-md-12">
            <label for="comentarios" class="mb-2 fw-semibold text-start"><?php _e('Comentarios:', 'funeraria'); ?></label>
            <textarea id="comentarios" name="comentarios" class="form-control"></textarea>
        </div>
        <input id="paquete-id" type="hidden" name="paquete_id" value="<?php echo $paquete_id; ?>">

        <div id="request_info_privacy">
            Los datos que aquí introduce serán utilizados para notificar a la empresa responsable de la prestación del servicio, así como para contactar con Vd. si fuera necesario con el fin de valorar la prestación del mismo. Si quiere más información al respecto puede consultar nuestra <a href="/docs/lopd-es.pdf" target="_blank">política de privacidad</a><br><br>Para aceptar estas condiciones confirme la solicitud y en breve contactaremos con Vd.<br><br>
        </div>

        <?php
        // Servicios Complementarios (Checkbox List)
        $terms = get_terms(array(
            'taxonomy' => 'servicios_complementarios', // Get terms from the correct taxonomy
            'hide_empty' => false,
        ));
        ?>
        <div class="form-group mb-4">
            <label class="fw-bold fs-5 mb-3 mt-4"><?php esc_html_e('Servicios Complementarios:', 'funeraria'); ?></label>
            <div class="row gap-2">
                <?php foreach ($terms as $term): ?>
                    <div class="form-check form-switch col-md-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="<?php echo esc_attr($term->slug); ?>" value="<?php echo esc_attr($term->term_id); ?>" name="servicio_complementarios_terms[]"> 
                        <label class="form-check-label" for="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <input type="submit" value="<?php _e('Solicitar información', 'funeraria'); ?>" class="mx-auto">
    <?php
    $form_fields = ob_get_clean();
    wp_send_json_success( $form_fields );
    wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_get_contact_form_fields', 'funeraria_get_contact_form_fields' );
add_action( 'wp_ajax_nopriv_get_contact_form_fields', 'funeraria_get_contact_form_fields' );

function funeraria_save_solicitud() {
    check_ajax_referer( 'modal_form', 'nonce' );

    // Sanitize form data
    $nombre       = sanitize_text_field( $_POST['nombre'] );
    $email        = sanitize_email( $_POST['email'] );
    $telefono     = sanitize_text_field( $_POST['telefono'] );
    $comentario   = sanitize_textarea_field( $_POST['comentarios'] ); 
    $paquete_id   = isset( $_POST['paquete_id'] ) ? absint( $_POST['paquete_id'] ) : 0;
    $funeraria_id = get_post_meta( $paquete_id, 'funeraria', true );

    // Validate email address
    if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
        wp_send_json_error( 'Por favor, introduce una dirección de correo electrónico válida.' );
        wp_die(); // Terminate further execution
    }

    // Sanitize servicio complementarios terms
    $servicio_complementarios_terms = isset($_POST['servicio_complementarios_terms']) && is_array($_POST['servicio_complementarios_terms'])
    ? array_map('absint', $_POST['servicio_complementarios_terms']) // Sanitize as integers
    : array();  // Set to empty array if not set or not an array


    // Prepare data for database insertion
    global $wpdb;
    $table_name = $wpdb->prefix . 'solicitudes';
    $data = array(
        'funeraria_id' => $funeraria_id,
        'paquete_id' => $paquete_id,
        'fecha' => current_time( 'mysql' ),
        'nombre' => $nombre,
        'email' => $email,
        'telefono' => $telefono,
        'comentario' => $comentario,
        'servicios_complementarios' => implode( ',', $servicio_complementarios_terms ) // Store as comma-separated string
    );

    // Insert data into database with error handling
    $result = $wpdb->insert( $table_name, $data, array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );
    
    if ( $result ) {
        // Retrieve funeraria user and package post for email content
        $funeraria_user = get_userdata( $funeraria_id );
        $paquete_post = get_post( $paquete_id );

        // Send email to funeraria
        $to = $funeraria_user->user_email;
        $subject = 'Nueva Solicitud de Información - Paquete Funerario';
        $message = "Hola " . esc_html( $funeraria_user->display_name ) . ",\n\n";
        $message .= "Ha recibido una nueva solicitud de información para el paquete funerario:\n\n";
        $message .= "Paquete: " . esc_html( $paquete_post->post_title ) . "\n";
        $message .= "Nombre: " . esc_html( $nombre ) . "\n";
        $message .= "Email: " . esc_html( $email ) . "\n";
        $message .= "Teléfono: " . esc_html( $telefono ) . "\n";
        $message .= "Comentario: " . esc_html( $comentario ) . "\n\n";
        $message .= "Servicios complementarios: " . esc_html( implode( ', ', $servicio_complementarios_terms ) ) . "\n\n"; // Add this line
        $message .= "Puede ver los detalles del paquete en: " . get_permalink( $paquete_id ) . "\n\n";
        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail( $to, $subject, $message, $headers );

        // Send confirmation email to the client
        $to = $email;
        $subject = 'Gracias por su Interés - ' . esc_html( $paquete_post->post_title );
        $message = "Estimado/a " . esc_html( $nombre ) . ",\n\n";
        $message .= "Gracias por su interés en nuestro paquete funerario:\n\n";
        $message .= "Paquete: " . esc_html( $paquete_post->post_title ) . "\n";
        $message .= "Pronto nos pondremos en contacto con usted para brindarle más información.\n\n";
        $message .= "Atentamente,\n";
        $message .= esc_html( $funeraria_user->display_name ) . "\n"; 
        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail( $to, $subject, $message, $headers );

        wp_send_json_success( 'Su solicitud ha sido enviada exitosamente. La funeraria y usted recibirán una notificación por correo electrónico.' );
    } else {
        $error_message = $wpdb->last_error; // Get the specific database error message
        wp_send_json_error("Error al guardar la solicitud: $error_message"); 
    }

    wp_die(); 
}


add_action( 'wp_ajax_funeraria_save_solicitud', 'funeraria_save_solicitud' );
add_action( 'wp_ajax_nopriv_funeraria_save_solicitud', 'funeraria_save_solicitud' ); 

function funeraria_create_solicitudes_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'solicitudes';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          funeraria_id mediumint(9) UNSIGNED NOT NULL, 
          paquete_id mediumint(9) UNSIGNED NOT NULL,  
          fecha datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,  
          nombre varchar(255) NOT NULL,   
          email varchar(100) NOT NULL,       
          telefono varchar(20) NOT NULL,     
          comentario text,              
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function funeraria_get_single_paquete_content($post_id) {
    $post = get_post($post_id); 

    if ($post && $post->post_type === 'paquetes_funerarios') {
        ob_start();

        ?>
        <div id="paquete-content" class="content-area"> 
            <main id="main" class="site-main container">

                <div id="post-<?php echo $post->ID; ?>" class="row py-5">
                    <div class="paquete-img col-md-3">
                        <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                    </div>    
                    <div class="paquete-content col-md-7">
                        <h3 class="title"><a href="<?php echo esc_url(get_permalink($post->ID)); ?>"><?php echo esc_html($post->post_title); ?></a></h3>
                        <?php echo apply_filters('the_content', $post->post_content); ?>
                    </div> 
                    <div class="price-box col-md-2">
                        <span class="price"> <?php echo esc_html(get_post_meta($post->ID, 'price', true)); ?></span>
                        <span class="iva-text">IVA incluido</span>
                        <button class="btn" data-bs-toggle="modal" data-bs-target="#funeraria-contact-modal" data-paquete-id="<?php echo esc_attr($post->ID); ?>">
                            Me interesa
                        </button>
                    </div>                    
                </div>

                <?php 
                // Servicios incluidos
                $terms = get_the_terms($post->ID, 'servicios'); 
                if (!empty($terms) && !is_wp_error($terms)) {
                    echo '<h3 class="paquete-section-heading">Incluido en el precio</h3>';
                    echo '<div class="related-services-paquete">';
                    foreach ($terms as $term) {
                        echo '<div class="service-item">';
                        $icon_id = get_term_meta($term->term_id, 'icon', true);
                        $icon_url = wp_get_attachment_image_url($icon_id, 'thumbnail');
                        if ($icon_url) {
                            echo '<div class="service-icon-wrapper"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($term->name) . '" title="Incluye ' . esc_attr($term->name) . '" width="24" height="24" /> </div>';
                        }
                        echo  '<span class="service-title">'. esc_html( $term->name ) .'</span>';
                        echo '<p class="service-description">' . esc_html( $term->description ) . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>

                <div class="" style="margin-bottom:40px">
                    <h3 class="paquete-section-heading" style="color:#d63638;">No incluido</h3>
                    <?php echo esc_html(get_post_meta($post->ID, 'not_included', true)); ?>
                </div>

                <h3 class="paquete-section-heading">Galería de Imágenes</h3>
                <?php 
                $image_ids = get_post_meta($post->ID, 'images_gallery', true); 
                if (!empty($image_ids)) {
                    echo '<div class="paquete-gallery">';
                    foreach ($image_ids as $image_id) {
                        $image_url = wp_get_attachment_image_url($image_id, 'full');
                        echo '<a href="' . esc_url($image_url) . '"><img src="' . esc_url($image_url) . '" alt="Imagen de la galería"></a>';
                    }
                    echo '</div>';
                }
                ?>

            </main> 
        </div> 

        <?php
        $content = ob_get_clean(); 
        return $content;
    } else {
        return ''; // Return an empty string if the post isn't found
    }
}


add_action( 'wp_ajax_funeraria_get_paquete_content', 'funeraria_get_paquete_content_callback' );
add_action( 'wp_ajax_nopriv_funeraria_get_paquete_content', 'funeraria_get_paquete_content_callback' );

function funeraria_get_paquete_content_callback() {
    // Check for nonce and post ID
    check_ajax_referer( 'funeraria_get_paquete_content_nonce', 'nonce' ); 
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

    if ( $post_id ) {
        $content = funeraria_get_single_paquete_content( $post_id );
        wp_send_json_success( $content );
    } else {
        wp_send_json_error( 'Invalid post ID' );
    }

    wp_die();
}