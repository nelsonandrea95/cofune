<?php
/* 
Template Name: Funeraria Dashboard 
*/

if ( ! is_user_logged_in() || ! in_array( 'funeraria', (array) wp_get_current_user()->roles ) ) {
    wp_redirect( home_url( '/funeraria-login/' ) );
    exit;
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main container">

        <div class="d-flex gap-3 align-items-start my-4">
            <div class="nav flex-column me-3 gap-2" role="tablist" aria-orientation="vertical" style="min-width: 185px;">
                <a href="#" class="nav-link border-bottom active" id="paquetes-tab" data-bs-toggle="pill" data-bs-target="#paquetes" role="tab" aria-controls="paquetes" aria-selected="true">Paquetes Funerarios</a>
                <a href="#" class="nav-link border-bottom" id="solicitudes-tab" data-bs-toggle="pill" data-bs-target="#solicitudes" role="tab" aria-controls="solicitudes" aria-selected="false">Solicitudes</a>
                <a 
                href="#"
                class="nav-link border-bottom"
                id="funeraria-logout-button"
                data-nonce="<?php echo wp_create_nonce( 'funeraria_logout' ); ?>">
                    Cerrar sesión
                </a>
            </div>
            <div class="tab-content" id="v-pills-tabContent">
                <div class="tab-pane fade show active" id="paquetes" role="tabpanel" aria-labelledby="paquetes-tab" tabindex="0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h3><?php esc_html_e( 'Mis Paquetes Funerarios', 'funeraria' ); ?></h3>
                        <div class="d-flex gap-2">
                            <a 
                                href="<?php echo esc_url( get_permalink( get_page_by_title( 'Paquete Funerario Form' ) ) ); ?>" 
                                class="btn btn-primary text-white d-flex align-items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                                </svg>
                                <?php esc_html_e( 'Crear Nuevo Paquete', 'funeraria' ); ?>
                            </a>
                        </div>
                    </div>

                    <?php
                    // Get the current user
                    $current_user = wp_get_current_user();

                    // Query for Paquetes Funerarios associated with the current funeraria user
                    $args = array(
                        'post_type'      => 'paquetes_funerarios',
                        'posts_per_page' => -1, // Show all posts
                        'meta_query'     => array(
                            array(
                                'key'     => 'funeraria',
                                'value'   => $current_user->ID,  // Use the current user's ID
                                'compare' => '='
                            )
                        )
                    );
                    $query = new WP_Query( $args );

                    if ( $query->have_posts() ) : ?>
                        <table class="wp-list-table widefat fixed striped w-100">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Título', 'funeraria' ); ?></th>
                                    <!--<th><?php esc_html_e( 'Comuna', 'funeraria' ); ?></th>-->
                                    <th><?php esc_html_e( 'Precio', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Tipo de Servicio', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Acciones', 'funeraria' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                                <tr>
                                    <td><?php the_title(); ?></td>
                                    <!--<td>
                                        <?php 
                                        /*$codigo_comuna = get_post_meta( get_the_ID(), 'comuna', true );
                                        global $wpdb;
                                        $nombre_comuna = $wpdb->get_var( $wpdb->prepare( "SELECT nombre_comuna FROM {$wpdb->prefix}listado_comunas WHERE codigo_comuna = %d", $codigo_comuna ) );
                                        echo esc_html( $nombre_comuna ); */
                                        ?>
                                    </td>-->
                                    <td><?php echo esc_html( get_post_meta( get_the_ID(), 'price', true ) ); ?></td>
                                    <td><?php echo esc_html( get_post_meta( get_the_ID(), 'tipo_servicio', true ) ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( add_query_arg( 'edit', get_the_ID(), get_permalink( get_page_by_title( 'Paquete Funerario Form' ) ) ) ); ?>" class="button button-secondary">
                                            <?php esc_html_e( 'Editar', 'funeraria' ); ?>
                                        </a>
                                        |
                                        <a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank" class="button button-secondary">
                                            <?php esc_html_e( 'Ver', 'funeraria' ); ?>
                                        </a>
                                        |
                                        <a 
                                            href="#" 
                                            class="button button-danger funeraria-delete-button" 
                                            data-post-id="<?php echo esc_attr( get_the_ID() ); ?>" 
                                            data-nonce="<?php echo wp_create_nonce( 'funeraria_delete_paquete' ); ?>"
                                            data-userId="<?php echo get_current_user_id() ?>">
                                                <?php esc_html_e( 'Eliminar', 'funeraria' ); ?>
                                        </a>

                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No tienes paquetes funerarios creados.', 'funeraria' ); ?></p>
                    <?php endif; wp_reset_postdata(); ?>

                </div>
                <div class="tab-pane fade" id="solicitudes" role="tabpanel" aria-labelledby="solicitudes-tab" tabindex="0">

                    <h2><?php esc_html_e( 'Solicitudes de Información', 'funeraria' ); ?></h2>

                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'solicitudes';
                    $current_user_id = get_current_user_id();

                    // Query solicitudes for the current funeraria
                    $solicitudes = $wpdb->get_results( $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE funeraria_id = %d ORDER BY fecha DESC",
                        $current_user_id
                    ) );

                    if ( $solicitudes ) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Fecha', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Paquete', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Servicios Complementarios', 'funeraria' ); ?></th> 
                                    <th><?php esc_html_e( 'Nombre', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Email', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Teléfono', 'funeraria' ); ?></th>
                                    <th><?php esc_html_e( 'Comentario', 'funeraria' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $solicitudes as $solicitud ) : 
                                    $paquete = get_post( $solicitud->paquete_id ); // Get the package post
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $solicitud->fecha ); ?></td>
                                        <td>
                                            <?php if ( $paquete ) : ?>
                                                <a href="<?php echo esc_url( get_permalink( $paquete->ID ) ); ?>">
                                                    <?php echo esc_html( $paquete->post_title ); ?>
                                                </a>
                                            <?php else : ?>
                                                <?php esc_html_e( 'Paquete no encontrado', 'funeraria' ); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                        <?php 
                                                // Obtén los servicios complementarios relacionados con la solicitud
                                                $servicios_complementarios = get_post_meta( $solicitud->ID, 'servicios_complementarios', false ); 
                                                
                                                if ( ! empty( $servicios_complementarios ) ) : 
                                                    echo esc_html( implode( ', ', $servicios_complementarios ) ); 
                                                else :
                                                    esc_html_e( 'Ninguno', 'funeraria' );
                                                endif; 
                                            ?>
                                        </td>
                                        <td><?php echo esc_html( $solicitud->nombre ); ?></td>
                                        <td><?php echo esc_html( $solicitud->email ); ?></td>
                                        <td><?php echo esc_html( $solicitud->telefono ); ?></td>
                                        <td><?php echo esc_html( $solicitud->comentario ); ?></td> 
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No hay solicitudes de información todavía.', 'funeraria' ); ?></p>
                    <?php endif; ?>    

                </div>
            </div>
        </div>
    </main>
</div>
<script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    jQuery(document).ready(function($){
        $('.funeraria-delete-button').click(function(e){
            e.preventDefault();
            var postId = $(this).data('post-id');
            if (confirm('¿Estás seguro de que deseas eliminar este paquete funerario?')) {
                var data = {
                    'action': 'funeraria_delete_paquete',
                    'post_id': postId,
                    'nonce': $(this).data('nonce'),
                    'userId': $(this).data('userId'),
                };
                $.post(ajaxurl, data, function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload(); // Reload the page to reflect the changes
                    } else {
                        alert(response.data);
                    }
                });
            }
        });

        $('#funeraria-logout-button').click(function(e) {
            e.preventDefault();

            var data = {
                'action': 'funeraria_logout',
                'nonce': $(this).data('nonce'),
            };

            $.post(funeraria_script_vars.ajaxurl, data, function (response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data.message);
                }
            });
        });
    });


</script>

<?php
get_footer();
