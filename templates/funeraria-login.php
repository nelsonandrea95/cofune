<?php
/*
Template Name: Funeraria Login
*/

if ( is_user_logged_in() ) {
    wp_redirect( get_permalink( get_page_by_title( 'Funeraria Dashboard' ) ) );
    exit;
}

get_header();

$logo = plugin_dir_url( __FILE__ ) . 'assets/images/logo-deluto.png';

wp_enqueue_script('funeraria-login', plugins_url('../assets/js/funeraria-login.js', __FILE__), array('jquery'), '1.0', true); // Corrected path
wp_localize_script(
    'funeraria-login',
    'funeraria_vars',
    array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'login_nonce' => wp_create_nonce('funeraria-login-nonce'), // Nonce for comuna AJAX
    )
);
?>

<div id="primary" class="content-area container">
    <main id="main" class="site-main">
        <img src="<?php echo esc_url( $logo ); ?>" alt="DeLuto logo" class="logo-login">

        <div class="login-form">
            <h2><?php esc_html_e( 'Accede a tu cuenta', 'funeraria' ); ?></h2>
            <div id="login-error-message"></div> 

            <form id="funeraria-login-form" method="post" action="">
                <div class="form-group">
                    <label for="user_login"><?php esc_html_e( 'Usuario o correo', 'funeraria' ); ?></label>
                    <input type="text" class="form-control" name="log" id="user_login" required>
                </div>
                <div class="form-group">
                    <label for="user_pass"><?php esc_html_e( 'Contraseña', 'funeraria' ); ?></label>
                    <input type="password" class="form-control" name="pwd" id="user_pass" required>
                </div>
                <input type="hidden" name="funeraria_login_nonce" value="<?php echo wp_create_nonce('funeraria-login-nonce'); ?>">
                <input type="hidden" name="action" value="funeraria_login"> 
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink( get_page_by_title( 'Funeraria Dashboard' ) ) ); ?>">  
                <button type="submit" class="btn btn-primary"><?php esc_attr_e( 'Acceder', 'funeraria' ); ?></button>
            </form>
        </div>
    </main>
</div>
<?php get_footer(); ?>
