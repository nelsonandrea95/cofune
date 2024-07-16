<?php
/*
Template Name: Paquete Funerario Form
*/

if (!is_user_logged_in() || !in_array('funeraria', (array)wp_get_current_user()->roles)) {
    wp_redirect(home_url('/funeraria-login/'));
    exit;
}

$post = null;
$post_id = null;

if (isset($_GET['edit'])) {
    $post_id = absint($_GET['edit']);

    if (   get_post_meta( $post_id, 'funeraria', true ) != get_current_user_id()  ) {
        wp_die( __( 'Error: You do not have permission to perform this action.', 'funeraria' ) );
        return;
    }

    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'paquetes_funerarios') {
        wp_die(__('Error: Invalid post.', 'funeraria'));
    }
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main container pt-5">

        <h2 class="mb-3"><?php echo ($post_id) ? esc_html__('Editar Paquete Funerario', 'funeraria') : esc_html__('Crear Nuevo Paquete Funerario', 'funeraria'); ?></h2>

        <form id="funeraria-paquete-form" class="row" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('funeraria_save_paquete', 'funeraria_paquete_nonce'); ?>

            <?php if ($post_id) : ?>
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <?php endif; ?>

            <div class="form-group  mb-3">
                <label for="post_title" class="mb-1 fw-semibold"><?php esc_html_e('Título', 'funeraria'); ?></label>
                <input type="text" class="form-control" id="post_title" name="post_title" value="<?php echo ($post) ? esc_attr($post->post_title) : ''; ?>">
            </div>

            <div class="form-group  mb-3">
                <label for="post_content" class="mb-1 fw-semibold"><?php esc_html_e('Contenido', 'funeraria'); ?></label>
                <textarea class="form-control" id="post_content" name="post_content"><?php echo ($post) ? esc_textarea($post->post_content) : ''; ?></textarea>
            </div>

            <div class="form-group  mb-3">
            <?php $not_included = ($post) ? get_post_meta( $post->ID, 'not_included', true ) : ''; ?>
                <label for="post_not_included" class="mb-1 fw-semibold"><?php esc_html_e('No incluye', 'funeraria'); ?></label>
                <textarea class="form-control" id="post_not_included" name="not_included" value="<?php echo ($post) ? esc_textarea($post->not_included) : ''; ?>"><?php echo ($post) ? esc_textarea($post->not_included) : ''; ?></textarea>
            </div>
            
            <?php
            // Price
            $price = ($post) ? get_post_meta( $post->ID, 'price', true ) : '';
            echo '<div class="form-group col-md-3 mb-3">'; 
            echo '<label for="price" class="mb-1 fw-semibold">' . __('Precio:', 'funeraria') . '</label> ';
            echo '<input type="text" class="form-control" id="price" name="price" value="' . esc_attr($price) . '" />';
            echo '</div>'; 

            

            // Tipo de Servicio
            $tipo_servicio = ($post) ? get_post_meta($post->ID, 'tipo_servicio', true ) : '';
            $options = array(
                '' => __('Seleccionar Tipo de Servicio', 'funeraria'),
                'inhumacion' => __('Inhumación', 'funeraria'),
                'cremacion' => __('Cremación', 'funeraria'),
                'traslado_nacional' => __('Traslado Nacional', 'funeraria'),
                'repatriacion' => __('Repatración', 'funeraria'),
            );

            echo '<div class="form-group col-md-3 mb-3">';
            echo '<label for="tipo_servicio" class="mb-1 fw-semibold">' . __('Tipo de Servicio:', 'funeraria') . '</label> ';
            echo '<select class="form-control" id="tipo_servicio" name="tipo_servicio">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($tipo_servicio, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '</div>';

            // Incluye sala velatorio?
            $sala_velatorio = ($post) ? get_post_meta($post->ID, 'sala_velatorio', true ) : '';
            $options = array(
                '' => __('Incluye sala velatorio?', 'funeraria'),
                'si' => __('Si', 'funeraria'),
                'no' => __('No', 'funeraria'),
            );

            echo '<div class="form-group col-md-3 mb-3">';
            echo '<label for="sala_velatorio" class="mb-1 fw-semibold">' . __('Tipo de Servicio:', 'funeraria') . '</label> ';
            echo '<select class="form-control" id="sala_velatorio" name="sala_velatorio">';
            foreach ($options as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected($sala_velatorio, $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select>';
            echo '</div>';





            // Comuna Fields (Multi-Select)
            $selected_comunas = get_post_meta( $post->ID, 'comuna', true );
            $selected_comunas = is_array( $selected_comunas ) ? $selected_comunas : array();
            $comunas = funeraria_get_comunas_options();
            echo '<label for="comuna-available" style="font-weight:bold;">' . __( 'Comunas Disponibles:', 'funeraria' ) . '</label>';
            echo '<select id="comuna-available" name="comuna-available[]" multiple="multiple" style="width: 100%; height:150px;" class="my-2">';
            foreach ( $comunas as $index => $comuna ) { // <-- Add $index for tracking original order
                // Exclude comunas already selected
                if ( ! in_array( $comuna->codigo_comuna, $selected_comunas ) ) {
                    echo '<option value="' . esc_attr( $comuna->codigo_comuna ) . '" data-original-index="' . $index . '">' // <-- Add data-original-index
                        . esc_html( $comuna->nombre_region ) . ' - ' . esc_html( $comuna->nombre_comuna ) . '</option>';
                }
            }
            echo '</select>';

            echo '<button type="button" id="add-comuna-button" class="button button-secondary mb-3">'. __( 'Agregar >>', 'funeraria' ) .'</button>';

            echo '<label for="comuna-selected" style="font-weight:bold;">' . __( 'Comunas Seleccionadas:', 'funeraria' ) . '</label>';
            echo '<select id="comuna-selected" name="selected-comuna" multiple="multiple" style="width: 100%; height:150px;" class="my-2">'; // This is the field that will be saved
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




            // Funeraria Field (hidden)
            $funeraria_id = ($post) ? get_post_meta($post->ID, 'funeraria', true) : get_current_user_id();
            echo '<input type="hidden" name="funeraria" value="' . esc_attr($funeraria_id) . '">';

            // Servicios (Checkbox List)
            $terms = get_terms(array(
                'taxonomy' => 'servicios',
                'hide_empty' => false,
            ));
            $post_terms = ($post) ? get_the_terms($post->ID, 'servicios') : array();
            $post_term_slugs = !is_wp_error($post_terms) ? wp_list_pluck($post_terms, 'slug') : array();
            ?>
            <div class="form-group mb-4">
                <label class="fw-bold fs-5 mb-3 mt-4"><?php esc_html_e('Servicios que incluye el paquete', 'funeraria'); ?></label>
                <div class="row gap-2">
                    <?php foreach ($terms as $term): ?>
                        <div class="form-check form-switch col-md-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="<?php echo esc_attr($term->slug); ?>" value="<?php echo esc_attr( $term->term_id ); ?>" name="servicio_terms[]"  <?php checked(in_array($term->slug, $post_term_slugs)); ?>>
                            <label class="form-check-label" for="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            // Servicios (Checkbox List)
            $terms = get_terms(array(
                'taxonomy' => 'servicios_complementarios',
                'hide_empty' => false,
            ));
            $post_terms = ($post) ? get_the_terms($post->ID, 'servicios_complementarios') : array();
            $post_term_slugs = !is_wp_error($post_terms) ? wp_list_pluck($post_terms, 'slug') : array();
            ?>
            <div class="form-group mb-4">
                <label class="fw-bold fs-5 mb-3 mt-4"><?php esc_html_e('Servicios que incluye el paquete', 'funeraria'); ?></label>
                <div class="row gap-2">
                    <?php foreach ($terms as $term): ?>
                        <div class="form-check form-switch col-md-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="<?php echo esc_attr($term->slug); ?>" value="<?php echo esc_attr( $term->term_id ); ?>" name="servicios_complementarios[]"  <?php checked(in_array($term->slug, $post_term_slugs)); ?>>
                            <label class="form-check-label" for="<?php echo esc_attr($term->slug); ?>"><?php echo esc_html($term->name); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="featured_image" class="mb-1 fw-semibold"><?php esc_html_e('Imagen Destacada:', 'funeraria'); ?></label>
                <input type="hidden" id="featured_image" name="featured_image" value="<?php echo esc_attr( get_post_thumbnail_id( $post->ID ) ); ?>">
                <div id="featured-image-preview">
                    <?php if (has_post_thumbnail( $post->ID) ): 
                        echo get_the_post_thumbnail($post->ID, 'thumbnail');
                    endif; ?>
                </div>
                <button type="button" class="btn btn-secondary mt-2" id="featured-image-upload">
                    <?php esc_html_e('Cargar Imagen', 'funeraria'); ?>
                </button>
            </div>

            <label class="fw-bold fs-5 mb-3 mt-4"><?php esc_html_e('Galería de imágenes', 'funeraria'); ?></label>
            <?php
            // Image Gallery
            
            funeraria_paquetes_gallery_metabox_callback($post);
            
            ?>


            <input type="hidden" name="action" value="funeraria_save_paquete">
            <input type="submit" class="button button-primary d-none save-paquete" value="<?php echo ($post_id) ? esc_attr__('Guardar Cambios', 'funeraria') : esc_attr__('Crear Paquete', 'funeraria'); ?>">
        </form>

    </main>
    
    <div class="fixed-bottom bg-white shadow-lg p-2 d-flex justify-content-center gap-2">
        <a href="#" class="btn btn-primary text-white btn-save"><?php echo ($post_id) ? esc_attr__('Guardar Cambios', 'funeraria') : esc_attr__('Crear Paquete', 'funeraria'); ?></a>
        <a class="btn btn-secondary text-white" href="/funeraria-dashboard/">Regresar</a>
    </div>
</div>


<script>
        jQuery(document).ready(function ($) {

            // Add original index data attribute to each option
            $('#comuna-available option').each(function(index) {
                $(this).data('original-index', index);
            });

            // Add selected comunas
            $('#add-comuna-button').on('click', function () {
                $('#comuna-available option:selected').appendTo('#comuna-selected');
                $('#comuna-available option:selected').remove();
                updateHiddenInput();
            });

            // Remove selected comunas
            $('#remove-comuna-button').on('click', function () {
                $('#comuna-selected option:selected').each(function () {
                    var option = $(this);
                    var originalIndex = option.data('original-index');

                    // Find the correct position in the "available" select and insert there
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

            // Function to update the hidden input with selected values
            function updateHiddenInput() {
                var selectedValues = $('#comuna-selected option').map(function () {
                    return this.value;
                }).get();
                $('#selected-comunas-input').val(selectedValues.join(','));
            }

            $('.btn-save').click(function(e){
                e.preventDefault();
                $('.save-paquete').click();
            })

        });

    </script>

<?php
get_footer();
