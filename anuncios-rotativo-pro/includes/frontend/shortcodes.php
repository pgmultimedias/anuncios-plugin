<?php
if (!defined('ABSPATH')) {
    exit;
}

// 4. LÓGICA DEL SHORTCODE Y VISUALIZACIÓN - ACTUALIZADO
// =========================================================================
function arp_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'lugar' => '' ), $atts, 'anuncio_rotativo' );
    if ( empty( $atts['lugar'] ) ) return '';

    $anuncios_validos = arp_obtener_anuncios_validos($atts['lugar']);

    if ( empty($anuncios_validos) ) return '';

    // Aplicar rotación por refresco si está activada
    $anuncios_validos = arp_seleccionar_anuncio_rotativo( $anuncios_validos, $atts['lugar'] );

    // Opciones de configuración
    $tiempos = get_option('arp_tiempos_rotacion');
    $dimensiones = get_option('arp_dimensiones_lugar');
    $tiempo_rotacion = isset($tiempos[$atts['lugar']]) ? intval($tiempos[$atts['lugar']]) : 5000;
    $ancho = isset($dimensiones[$atts['lugar']]['ancho']) && !empty($dimensiones[$atts['lugar']]['ancho']) ? intval($dimensiones[$atts['lugar']]['ancho']) . 'px' : 'auto';
    $alto = isset($dimensiones[$atts['lugar']]['alto']) && !empty($dimensiones[$atts['lugar']]['alto']) ? intval($dimensiones[$atts['lugar']]['alto']) . 'px' : 'auto';

    $container_id = 'arp-container-' . uniqid();
    $style = "style='width: {$ancho}; height: {$alto};'";
    $output = "<div id='{$container_id}' class='arp-container' data-rotation-speed='{$tiempo_rotacion}' {$style}>";

    foreach ($anuncios_validos as $index => $anuncio_id) {
        $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
        // El primer banner queda visible desde el HTML, incluso si el JavaScript del tema falla.
        $slide_class = ($index === 0) ? 'arp-slide active' : 'arp-slide';
        $output .= "<div class='{$slide_class}'>";

        if ($ad_type === 'code') {
            $ad_code = get_post_meta($anuncio_id, '_arp_ad_code', true);
            $output .= do_shortcode($ad_code); // Usar do_shortcode para procesar shortcodes dentro del código de anuncio
        } elseif ($ad_type === 'video') {
            $video_url = get_post_meta($anuncio_id, '_arp_video_url', true);
            $video_id = arp_extract_youtube_id($video_url);

            if ($video_id) {
                // URL de embed con autoplay, mute, loop, sin controles ni info
                $embed_url = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&rel=0&modestbranding=1&iv_load_policy=3&disablekb=1&fs=0";
                $output .= "<div class='arp-video-container' style='width: 100%; height: 100%; position: relative;'>";
                $output .= "<iframe src='" . esc_url($embed_url) . "' frameborder='0' allow='autoplay; encrypted-media; picture-in-picture' allowfullscreen style='width: 100%; height: 100%; border: none;'></iframe>";
                $output .= "</div>";
            } else {
                $output .= "<p>Error: URL de YouTube no válida</p>";
            }
        } else { // Por defecto, es 'image'
            $link = get_post_meta($anuncio_id, '_arp_link', true);
            $nombre_empresa = get_post_meta($anuncio_id, '_arp_nombre_empresa', true);
            $imagen_url = get_the_post_thumbnail_url($anuncio_id, 'full');

            if (!empty($link)) $output .= "<a href='" . esc_url($link) . "' target='_blank' rel='noopener noreferrer'>";
            $output .= "<img src='" . esc_url($imagen_url) . "' alt='" . esc_attr($nombre_empresa) . "'>";
            if (!empty($link)) $output .= "</a>";
        }

        $output .= "</div>";
    }
    $output .= "</div>";

    return $output;
}
add_shortcode( 'anuncio_rotativo', 'arp_shortcode' );


// =========================================================================
// 5. SHORTCODE PARA STICKY FOOTER - ACTUALIZADO CON VIDEO
// =========================================================================
function arp_shortcode_sticky( $atts ) {
    $atts = shortcode_atts( array( 'lugar' => '' ), $atts, 'anuncio_sticky' );

    // Si no se especifica lugar, usar el configurado en modo anuncio para sticky
    if ( empty( $atts['lugar'] ) ) {
        $atts['lugar'] = get_option('arp_modo_sticky_lugar', 'lugar_sticky_footer');
    }

    $anuncios_validos = arp_obtener_anuncios_validos($atts['lugar']);

    if (empty($anuncios_validos)) {
        return '';
    }

    // Configuración
    $ancho = get_option('arp_modo_sticky_ancho', '80') . '%';
    $alto = get_option('arp_modo_sticky_alto', '100') . 'px';

    // Obtener configuraciones personalizadas para sticky
    $boton_color = get_option('arp_sticky_boton_color', '#ffffff');
    $boton_fondo = get_option('arp_sticky_boton_fondo', '#f44336');
    $margen = get_option('arp_sticky_margen', '0');
    $sin_margen = get_option('arp_sticky_sin_margen', 'off');
    $padding_style = ($sin_margen === 'on') ? 'padding: 0;' : 'padding: ' . esc_attr($margen) . 'px;';

    $output = '<div id="arp-sticky-shortcode" class="arp-sticky-footer" style="position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: ' . esc_attr($ancho) . '; height: ' . esc_attr($alto) . '; background: white; border-top: 2px solid #ccc; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 99997; max-width: 100%;">';
    $output .= '<button class="arp-sticky-close" style="position: absolute; top: -30px; right: 10px; background: ' . esc_attr($boton_fondo) . '; color: ' . esc_attr($boton_color) . '; border: 2px solid ' . esc_attr($boton_fondo) . '; border-radius: 5px 5px 0 0; padding: 5px 15px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2);" onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.3)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.2)\';">× Cerrar</button>';
    $output .= '<div class="arp-sticky-content" style="width: 100%; height: 100%; position: relative; ' . $padding_style . '">';

    // Contenido rotativo
    $output .= '<div class="arp-sticky-rotativo" style="width: 100%; height: 100%; position: relative;">';
    foreach ($anuncios_validos as $index => $anuncio_id) {
        $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
        $style = $index === 0 ? 'display: block;' : 'display: none;';

        $output .= '<div class="arp-sticky-slide" data-index="' . $index . '" style="width: 100%; height: 100%; ' . $style . '">';

        if ($ad_type === 'code') {
            $ad_code = get_post_meta($anuncio_id, '_arp_ad_code', true);
            $output .= do_shortcode($ad_code);
        } elseif ($ad_type === 'video') {
            $video_url = get_post_meta($anuncio_id, '_arp_video_url', true);
            $video_id = arp_extract_youtube_id($video_url);

            if ($video_id) {
                // URL de embed con autoplay, mute, loop, sin controles ni info
                $embed_url = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&rel=0&modestbranding=1&iv_load_policy=3&disablekb=1&fs=0";
                $output .= "<div class='arp-video-container' style='width: 100%; height: 100%; position: relative;'>";
                $output .= "<iframe src='" . esc_url($embed_url) . "' frameborder='0' allow='autoplay; encrypted-media; picture-in-picture' allowfullscreen style='width: 100%; height: 100%; border: none;'></iframe>";
                $output .= "</div>";
            } else {
                $output .= "<p>Error: URL de YouTube no válida</p>";
            }
        } else {
            $link = get_post_meta($anuncio_id, '_arp_link', true);
            $nombre_empresa = get_post_meta($anuncio_id, '_arp_nombre_empresa', true);
            $imagen_url = get_the_post_thumbnail_url($anuncio_id, 'full');

            if (!empty($link)) $output .= '<a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer">';
            $output .= '<img src="' . esc_url($imagen_url) . '" alt="' . esc_attr($nombre_empresa) . '" style="width: 100%; height: 100%; object-fit: contain;">';
            if (!empty($link)) $output .= '</a>';
        }

        $output .= '</div>';
    }
    $output .= '</div></div></div>';

    return $output;
}
add_shortcode( 'anuncio_sticky', 'arp_shortcode_sticky' );


// =========================================================================
