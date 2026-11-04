<?php
if (!defined('ABSPATH')) {
    exit;
}

// 9. CRON JOB PARA DESPUBLICAR ANUNCIOS VENCIDOS (Sin cambios)
// =========================================================================
function arp_programar_verificacion_vencidos() {
    if ( ! wp_next_scheduled( 'arp_verificar_anuncios_vencidos_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'arp_verificar_anuncios_vencidos_hook' );
    }
}
register_activation_hook( ARP_PLUGIN_FILE, 'arp_programar_verificacion_vencidos' );

function arp_desactivar_programacion() {
    wp_clear_scheduled_hook( 'arp_verificar_anuncios_vencidos_hook' );
}
register_deactivation_hook( ARP_PLUGIN_FILE, 'arp_desactivar_programacion' );

add_action( 'arp_verificar_anuncios_vencidos_hook', 'arp_verificar_anuncios_vencidos' );
function arp_verificar_anuncios_vencidos() {
    $hoy = current_time('Y-m-d');
    $args = array(
        'post_type' => 'anuncio', 'posts_per_page' => -1, 'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'AND',
            array('key' => '_arp_auto_despublicar', 'value' => 'on', 'compare' => '='),
            array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '<', 'type' => 'DATE'),
        ),
    );
    $anuncios_vencidos = new WP_Query( $args );
    if ( $anuncios_vencidos->have_posts() ) {
        while ( $anuncios_vencidos->have_posts() ) {
            $anuncios_vencidos->the_post();
            wp_update_post( array('ID' => get_the_ID(), 'post_status' => 'draft') );
        }
    }
    wp_reset_postdata();
}
