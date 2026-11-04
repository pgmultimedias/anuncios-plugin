<?php
if (!defined('ABSPATH')) {
    exit;
}

// Agregar columnas personalizadas en la lista de anuncios
function arp_agregar_columnas_anuncios( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['arp_lugares'] = __( 'Lugares', 'anuncios-rotativo-pro' );
    $new_columns['arp_fecha_inicio'] = __( 'Fecha de Inicio', 'anuncios-rotativo-pro' );
    $new_columns['arp_fecha_vencimiento'] = __( 'Fecha de Vencimiento', 'anuncios-rotativo-pro' );
    $new_columns['arp_estado'] = __( 'Estado', 'anuncios-rotativo-pro' );
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}
add_filter( 'manage_anuncio_posts_columns', 'arp_agregar_columnas_anuncios' );

// Mostrar contenido de las columnas personalizadas
function arp_mostrar_contenido_columnas( $column, $post_id ) {
    switch ( $column ) {
        case 'arp_lugares':
            $lugar = get_post_meta( $post_id, '_arp_lugar', true );
            if ( !empty( $lugar ) ) {
                echo esc_html( arp_obtener_nombre_lugar( $lugar ) );
            } else {
                echo '<span style="color: #999;">—</span>';
            }
            break;

        case 'arp_fecha_inicio':
            $fecha_inicio = get_post_meta( $post_id, '_arp_fecha_inicio', true );
            if ( !empty( $fecha_inicio ) ) {
                $fecha_formateada = date_i18n( get_option( 'date_format' ), strtotime( $fecha_inicio ) );
                echo esc_html( $fecha_formateada );
            } else {
                echo '<span style="color: #999;">—</span>';
            }
            break;

        case 'arp_fecha_vencimiento':
            $fecha_vencimiento = get_post_meta( $post_id, '_arp_fecha_vencimiento', true );
            if ( !empty( $fecha_vencimiento ) ) {
                $fecha_formateada = date_i18n( get_option( 'date_format' ), strtotime( $fecha_vencimiento ) );
                $hoy = current_time( 'Y-m-d' );
                $color = ( $fecha_vencimiento < $hoy ) ? '#d63638' : ( ( $fecha_vencimiento === $hoy ) ? '#dba617' : '#00a32a' );
                echo '<span style="color: ' . esc_attr( $color ) . ';">' . esc_html( $fecha_formateada ) . '</span>';
            } else {
                echo '<span style="color: #999;">—</span>';
            }
            break;

        case 'arp_estado':
            $post_status = get_post_status( $post_id );
            $fecha_vencimiento = get_post_meta( $post_id, '_arp_fecha_vencimiento', true );
            $hoy = current_time( 'Y-m-d' );

            if ( $post_status === 'publish' ) {
                if ( !empty( $fecha_vencimiento ) && $fecha_vencimiento < $hoy ) {
                    echo '<span style="color: #d63638; font-weight: bold;">Vencido</span>';
                } else {
                    echo '<span style="color: #00a32a; font-weight: bold;">Publicado</span>';
                }
            } else {
                echo '<span style="color: #646970;">Borrador</span>';
            }
            break;
    }
}
add_action( 'manage_anuncio_posts_custom_column', 'arp_mostrar_contenido_columnas', 10, 2 );

// Hacer las columnas ordenables (opcional, pero útil)
function arp_columnas_ordenables( $columns ) {
    $columns['arp_fecha_inicio'] = 'arp_fecha_inicio';
    $columns['arp_fecha_vencimiento'] = 'arp_fecha_vencimiento';
    return $columns;
}
add_filter( 'manage_edit-anuncio_sortable_columns', 'arp_columnas_ordenables' );


// =========================================================================
