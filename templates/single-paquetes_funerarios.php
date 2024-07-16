<?php
/*
Template Name: Single Paquete Funerario
Template Post Type: paquetes_funerarios
*/
get_header();
?>

<div id="paquete-content" class="content-area">
    <main id="main" class="site-main container">
        <?php while ( have_posts() ) : the_post(); ?>
        <div id="post-<?php the_ID(); ?>" class="row py-5">
            <div class="paquete-img col-md-3">
                <?php the_post_thumbnail( 'medium' ); ?>
            </div>    
            <div class="paquete-content col-md-7">
                <h3 class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php the_content(); ?>
            </div> 
            <div class="price-box col-md-2">
                <span class="price"> <?php echo esc_html( get_post_meta( get_the_ID(), 'price', true ) ); ?></span>
                <span class="iva-text">IVA incluido</span>
                <button class="btn" data-bs-toggle="modal" data-bs-target="#funeraria-contact-modal" data-paquete-id="<?php echo esc_attr( get_the_ID() ); ?>">
                    Me interesa
                </button>
            </div>                    
        </div>
        <?php
        $terms = get_the_terms( get_the_ID(), 'servicios' ); 

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            echo '<h3 class="paquete-section-heading">Incluido en el precio</h3>';
            echo '<div class="related-services-paquete">';
            foreach ( $terms as $term ) {
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
            <?php
            echo esc_html( get_post_meta( get_the_ID(), 'not_included', true ) );
            ?>
        </div>

        <h3 class="paquete-section-heading">Galería de Imágenes</h3>
        <?php 
        $image_ids = get_post_meta(get_the_ID(), 'images_gallery', true); 
        if (!empty($image_ids)) {
            echo '<div class="paquete-gallery">';
            foreach ($image_ids as $image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'full');
                echo '<a href="' . esc_url($image_url) . '"><img src="' . esc_url($image_url) . '" alt="Imagen de la galería"></a>';
            }
            echo '</div>';
        }
        ?>
        <div class="d-flex justify-content-center gap-2 my-4">
            <button class="btn" data-bs-toggle="modal" data-bs-target="#funeraria-contact-modal" data-paquete-id="<?php echo esc_attr( get_the_ID() ); ?>">Me interesa</button>
            <a class="btn btn-light border" href="<?php echo esc_url( get_post_type_archive_link( 'paquetes_funerarios' ) ); ?>">Regresar</a>
        </div>
        <?php endwhile; ?>
        
    </main>
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

<?php
get_footer();
?>
