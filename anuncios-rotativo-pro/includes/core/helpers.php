<?php
if (!defined('ABSPATH')) {
    exit;
}

// Función helper para seleccionar un anuncio rotativo por refresco
function arp_seleccionar_anuncio_rotativo( $anuncios_validos, $lugar_id ) {
    if ( empty( $anuncios_validos ) || count( $anuncios_validos ) <= 1 ) {
        return $anuncios_validos;
    }

    // Verificar si la rotación por refresco está activada para este lugar
    $rotacion_refresco = get_option( 'arp_rotacion_refresco', array() );
    if ( !isset( $rotacion_refresco[ $lugar_id ] ) || $rotacion_refresco[ $lugar_id ] !== 'on' ) {
        return $anuncios_validos;
    }

    // Crear una clave única basada en IP y User Agent para identificar la sesión del usuario
    $user_key = md5( ( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' ) . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' ) );
    $transient_key = 'arp_rotacion_' . md5( $lugar_id ) . '_' . $user_key;

    // Obtener el índice del último anuncio mostrado desde el transient
    $last_index = get_transient( $transient_key );
    if ( $last_index === false ) {
        $last_index = -1;
    } else {
        $last_index = intval( $last_index );
    }

    // Calcular el siguiente índice (rotación circular)
    $next_index = ( $last_index + 1 ) % count( $anuncios_validos );

    // Guardar el nuevo índice en el transient (válido por 30 días)
    set_transient( $transient_key, $next_index, 30 * DAY_IN_SECONDS );

    // Retornar solo el anuncio seleccionado
    return array( $anuncios_validos[ $next_index ] );
}

// Devuelve los anuncios publicados de un lugar y aplica vigencia/programación en PHP.
// Evita que un meta_query de fechas deje fuera anuncios con fechas vacías o antiguas.
function arp_obtener_anuncios_validos($lugar) {
    if (!is_string($lugar) || trim($lugar) === '') {
        return array();
    }

    $anuncios = get_posts(array(
        'post_type'      => 'anuncio',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_arp_lugar',
        'meta_value'     => $lugar,
        'orderby'        => 'date',
        'order'          => 'ASC',
    ));

    $hoy = current_time('Y-m-d');
    $dia_actual = (int) current_time('N');
    $hora_actual = current_time('H:i');
    $validos = array();

    foreach ($anuncios as $anuncio_id) {
        $fecha_inicio = trim((string) get_post_meta($anuncio_id, '_arp_fecha_inicio', true));
        $fecha_fin = trim((string) get_post_meta($anuncio_id, '_arp_fecha_vencimiento', true));

        if ($fecha_inicio !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) && $fecha_inicio > $hoy) {
            continue;
        }
        if ($fecha_fin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin) && $fecha_fin < $hoy) {
            continue;
        }

        $programacion = get_post_meta($anuncio_id, '_arp_programacion', true);
        if (is_array($programacion)) {
            $dias = isset($programacion['dias']) && is_array($programacion['dias'])
                ? array_map('intval', $programacion['dias'])
                : array();
            $hora_inicio = isset($programacion['hora_inicio']) ? trim($programacion['hora_inicio']) : '';
            $hora_fin = isset($programacion['hora_fin']) ? trim($programacion['hora_fin']) : '';

            // Solo restringir por horario cuando la programación está completa.
            if (!empty($dias) && $hora_inicio !== '' && $hora_fin !== '') {
                if (!in_array($dia_actual, $dias, true) || $hora_actual < $hora_inicio || $hora_actual > $hora_fin) {
                    continue;
                }
            }
        }

        $validos[] = (int) $anuncio_id;
    }

    return $validos;
}

// Activa automáticamente el modo correspondiente cuando hay anuncios en sus lugares especiales.
function arp_obtener_tipos_modo_anuncio() {
    $tipos = get_option('arp_modo_anuncio_tipo', array('popup', 'sticky', 'interstitial', 'inarticle'));
    if (!is_array($tipos)) {
        $tipos = array($tipos);
    }

    $lugares_especiales = array(
        'popup'        => 'lugar_popup',
        'sticky'       => 'lugar_sticky_footer',
        'interstitial' => 'lugar_interstitial',
        'inarticle'    => 'lugar_inarticle',
    );

    foreach ($lugares_especiales as $tipo => $lugar) {
        if (!empty(arp_obtener_anuncios_validos($lugar))) {
            $tipos[] = $tipo;
        }
    }

    return array_values(array_unique($tipos));
}

// Para cada modo, prioriza su lugar dedicado cuando hay anuncios asignados allí.
function arp_obtener_anuncios_para_modo(&$lugar, $lugar_especial) {
    $anuncios_especiales = arp_obtener_anuncios_validos($lugar_especial);
    if (!empty($anuncios_especiales)) {
        $lugar = $lugar_especial;
        return $anuncios_especiales;
    }

    return arp_obtener_anuncios_validos($lugar);
}

// Función helper para obtener el nombre personalizado de un lugar
function arp_obtener_nombre_lugar( $lugar_id ) {
    $nombres_personalizados = get_option( 'arp_nombres_lugares', array() );

    // Si existe un nombre personalizado, devolverlo
    if ( isset( $nombres_personalizados[ $lugar_id ] ) && !empty( $nombres_personalizados[ $lugar_id ] ) ) {
        return $nombres_personalizados[ $lugar_id ];
    }

    // Si no, devolver el nombre por defecto
    if ( preg_match( '/lugar_(\d+)/', $lugar_id, $matches ) ) {
        return 'Lugar ' . str_pad( $matches[1], 3, '0', STR_PAD_LEFT );
    } elseif ( $lugar_id === 'lugar_popup' ) {
        return 'Lugar Pop-up';
    } elseif ( $lugar_id === 'lugar_sticky_footer' ) {
        return 'Lugar Sticky Footer';
    } elseif ( $lugar_id === 'lugar_interstitial' ) {
        return 'Lugar Anuncio Intersticial';
    } elseif ( $lugar_id === 'lugar_inarticle' ) {
        return 'Lugar Anuncio In-Article';
    }

    return $lugar_id;
}

// Función para extraer el ID de video de YouTube
function arp_extract_youtube_id($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}
