<?php
/*
Template Name: Archive - Paquetes Funerarios
*/

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
    <div class="container">
        <div class="row">
            <div class="bg-light col-md-3">
                <form id="funeraria-filter-form">

                    <div class="filter-group">
                        <div class="filter-label">Región/Comuna</div>
                        <select id="filter-region" name="region">
                            <option value="">Seleccionar Región</option>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'listado_comunas';
                            $regions = $wpdb->get_results("SELECT DISTINCT codigo_region, nombre_region FROM $table_name ORDER BY nombre_region ASC");
                            foreach ($regions as $region):
                                echo '<option value="' . esc_attr($region->codigo_region) . '">' . esc_html($region->nombre_region) . '</option>';
                            endforeach;
                            ?>
                        </select>
                        <select id="filter-comuna" name="comuna">
                            <option value="">Seleccionar Comuna</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <div class="filter-label">Precio</div>
                        <div id="price-range"></div>
                        <div class="price-range-values">
                            <span id="min-value">0</span> - <span id="max-value">0</span>  
                        </div>
                        <input type="hidden" value="0" id="min_price"/>
                        <input type="hidden" value="0" id="max_price" />
                    </div>

                    <div class="filter-group">
                        <div class="filter-label">Servicios</div>
                        <div class="services-container">
                            <?php
                            $terms = get_terms(array(
                                'taxonomy' => 'servicios',
                                'hide_empty' => false,
                            ));

                            foreach ($terms as $term) {
                                $icon_id = get_term_meta($term->term_id, 'icon', true);
                                $icon_url = wp_get_attachment_image_url($icon_id, 'thumbnail');
                                echo '<div class="service-item" data-slug="' . esc_attr($term->slug) . '">'; 
                                if ($icon_url) {
                                    echo '<div class="service-icon"><img src="' . esc_url($icon_url) . '" alt="' . esc_attr($term->name) . '" width="24" height="24" /> </div>';
                                }
                                echo esc_html($term->name);
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="filter-group">
                        <div class="filter-label">Valoración</div>
                        <div class="rate-container d-flex flex-wrap gap-2">
                            <div class="btn btn-light border btn-rate" data-rate="5">
                                <img src="<?php echo plugins_url( '../assets/images/5-stars.svg', __FILE__ ) ?>" alt="" width="90">
                            </div>
                            <div class="btn btn-light border btn-rate" data-rate="4">
                                <img src="<?php echo plugins_url( '../assets/images/4-stars.svg', __FILE__ ) ?>" alt="" width="90">
                            </div>
                            <div class="btn btn-light border btn-rate" data-rate="3">
                                <img src="<?php echo plugins_url( '../assets/images/3-stars.svg', __FILE__ ) ?>" alt="" width="90">
                            </div>
                            <div class="btn btn-light border btn-rate" data-rate="2">
                                <img src="<?php echo plugins_url( '../assets/images/2-stars.svg', __FILE__ ) ?>" alt="" width="90">
                            </div>
                            <div class="btn btn-light border btn-rate" data-rate="1">
                                <img src="<?php echo plugins_url( '../assets/images/1-stars.svg', __FILE__ ) ?>" alt="" width="90">
                            </div>
                            <div class="btn btn-light border btn-rate" data-rate="0">
                                <img src="<?php echo plugins_url( '../assets/images/0-stars.svg', __FILE__ ) ?>" alt="" width="90">
                            </div>
                        </div>
                    </div>
                    <a href="#" class="btn btn-light border w-100 my-3 d-none" id="reset-filters">Limpiar Filtros</a>

                </form>
            </div>

            <div class="paquetes-list col-md-9"> 
                <div class="d-flex justify-content-between align-items-center my-4">
                    <?php 
                        global $wp_query;
                        $total_posts = $wp_query->found_posts;
                    ?>
                    <span><span id="number-of-results"><?php echo $total_posts ?></span> Servicios encontrados</span>
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-nowrap" for="#sort-by">Ordenar por:</label>
                        <select id="sort-by" name="sort_by" class="form-control">
                            <option value="rate_desc">Valoración (Mayor a Menor)</option>
                            <option value="rate_asc">Valoración (Menor a Mayor)</option>
                            <option value="price_asc">Precio (Menor a Mayor)</option>
                            <option value="price_desc">Precio (Mayor a Menor)</option>
                        </select>
                    </div>
                </div>
                <div id="funeraria-package-results" >
                    <?php if ( have_posts() ) : ?>
                        <div class="paquete-funerarios-grid"> 
                        <?php while ( have_posts() ) : the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" class="paquete-item row py-4 border-bottom">
                            <div class="paquete-img col-md-3">
                                <a href="#" class="archive-paquete-link" data-postid="<?php echo esc_attr( get_the_ID() ); ?>">
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                </a>    
                            </div>    
                            <div class="paquete-content col-md-7">
                                <h3 class="title"><a href="#" class="archive-paquete-link" data-postid="<?php echo esc_attr( get_the_ID() ); ?>"><?php the_title(); ?></a></h3>
                                <?php the_excerpt(); ?>
                                <?php
                                // Get the terms for this post
                                $terms = get_the_terms( get_the_ID(), 'servicios' ); 

                                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                                    echo '<div class="related-services mb-3">'; // Wrap services in a container
                                    foreach ( $terms as $term ) {
                                        $icon_id = get_term_meta($term->term_id, 'icon', true); // Get the icon for the service
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
                                <span class="price"> <?php echo esc_html( get_post_meta( get_the_ID(), 'price', true ) ); ?></span>
                                <span class="iva-text">IVA incluido</span>
                                <button class="btn" data-bs-toggle="modal" data-bs-target="#funeraria-contact-modal" data-paquete-id="<?php echo esc_attr( get_the_ID() ); ?>">
                                    Me interesa
                                </button>
                            </div>   
                                
                                    
                        </article>
                        <?php endwhile; ?>
                        </div> <?php the_posts_pagination(); ?>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No se encontraron paquetes funerarios.', 'funeraria' ); ?></p>
                    <?php endif; ?> 
                </div>
            </div>
        </div>

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


        </div>
    </main>
</div>

<?php


get_footer();
?>
