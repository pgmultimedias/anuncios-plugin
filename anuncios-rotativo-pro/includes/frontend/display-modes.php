<?php
if (!defined('ABSPATH')) {
    exit;
}

// 6. IMPLEMENTACIÓN DEL MODO ANUNCIO (POPUP, STICKY, INTERSTITIAL, IN-ARTICLE)
// =========================================================================
function arp_implementar_modo_anuncio() {
    // Verificar si el modo anuncio está activado
    $modo_activado = get_option('arp_modo_anuncio_activado', 'on');
    if ($modo_activado !== 'on') {
        return;
    }

    // Obtener configuración
    $tipos = arp_obtener_tipos_modo_anuncio();
    if (!is_array($tipos)) {
        $tipos = array($tipos);
    }
    $delay = get_option('arp_modo_delay', '3000');
    $duracion = get_option('arp_modo_duracion', '10000');

    // Función para generar contenido de anuncio
    function arp_generar_contenido_anuncio($anuncio_id, $ad_type) {
        $output = '';

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

        return $output;
    }

    // Preparar el HTML según los tipos seleccionados
    if (in_array('popup', $tipos)) {
        $lugar = get_option('arp_modo_anuncio_lugar', 'lugar_popup');
        $ancho = get_option('arp_modo_popup_ancho', '600') . 'px';
        $alto = get_option('arp_modo_popup_alto', '400') . 'px';

        // Obtener anuncios válidos para el lugar seleccionado (popup)
        $anuncios_validos = arp_obtener_anuncios_para_modo($lugar, 'lugar_popup');

        if (!empty($anuncios_validos)) {
            // Aplicar rotación por refresco si está activada
            $anuncios_validos = arp_seleccionar_anuncio_rotativo( $anuncios_validos, $lugar );

            // Obtener configuraciones personalizadas
            $boton_color = get_option('arp_popup_boton_color', '#ffffff');
            $boton_fondo = get_option('arp_popup_boton_fondo', '#f44336');
            $margen = get_option('arp_popup_margen', '20');
            $sin_margen = get_option('arp_popup_sin_margen', 'off');
            $padding_style = ($sin_margen === 'on') ? 'padding: 0;' : 'padding: ' . esc_attr($margen) . 'px;';

            $output = '<div id="arp-modopopup-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99998; align-items: center; justify-content: center;">';
            $output .= '<div id="arp-modopopup-container" style="position: relative; background: white; ' . $padding_style . ' border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); width: ' . esc_attr($ancho) . '; height: ' . esc_attr($alto) . '; max-width: 90%; max-height: 90%;">';
            $output .= '<button id="arp-modopopup-close" style="position: absolute; top: 15px; right: 15px; background: ' . esc_attr($boton_fondo) . '; color: ' . esc_attr($boton_color) . '; border: 2px solid ' . esc_attr($boton_fondo) . '; border-radius: 50%; width: 40px; height: 40px; font-size: 24px; line-height: 1; cursor: pointer; z-index: 1000; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2);" onmouseover="this.style.transform=\'scale(1.1)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.3)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.2)\';">×</button>';
            $output .= '<div id="arp-modopopup-content" style="width: 100%; height: 100%; overflow: hidden; position: relative;">';

            // Contenido rotativo
            $output .= '<div id="arp-modopopup-rotativo" style="width: 100%; height: 100%; position: relative;">';
            foreach ($anuncios_validos as $index => $anuncio_id) {
                $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
                $style = $index === 0 ? 'display: block;' : 'display: none;';

                $output .= '<div class="arp-modopopup-slide" data-index="' . $index . '" style="width: 100%; height: 100%; ' . $style . '">';
                $output .= arp_generar_contenido_anuncio($anuncio_id, $ad_type);
                $output .= '</div>';
            }
            $output .= '</div></div></div></div>';

            echo $output;
        }
    }

    if (in_array('sticky', $tipos)) {
        $lugar = get_option('arp_modo_sticky_lugar', 'lugar_sticky_footer');
        $ancho = get_option('arp_modo_sticky_ancho', '80') . '%';
        $alto = get_option('arp_modo_sticky_alto', '100') . 'px';

        // Obtener anuncios válidos para el lugar seleccionado (sticky)
        $anuncios_validos = arp_obtener_anuncios_para_modo($lugar, 'lugar_sticky_footer');

        if (!empty($anuncios_validos)) {
            // Aplicar rotación por refresco si está activada
            $anuncios_validos = arp_seleccionar_anuncio_rotativo( $anuncios_validos, $lugar );

            // Obtener configuraciones personalizadas para sticky
            $boton_color = get_option('arp_sticky_boton_color', '#ffffff');
            $boton_fondo = get_option('arp_sticky_boton_fondo', '#f44336');
            $margen = get_option('arp_sticky_margen', '0');
            $sin_margen = get_option('arp_sticky_sin_margen', 'off');
            $padding_style = ($sin_margen === 'on') ? 'padding: 0;' : 'padding: ' . esc_attr($margen) . 'px;';

            $output = '<div id="arp-modosticky-container" style="display: none; position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: ' . esc_attr($ancho) . '; height: ' . esc_attr($alto) . '; background: white; border-top: 2px solid #ccc; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 99997; max-width: 100%;">';
            $output .= '<button id="arp-modosticky-close" style="position: absolute; top: -30px; right: 10px; background: ' . esc_attr($boton_fondo) . '; color: ' . esc_attr($boton_color) . '; border: 2px solid ' . esc_attr($boton_fondo) . '; border-radius: 5px 5px 0 0; padding: 5px 15px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2);" onmouseover="this.style.transform=\'scale(1.05)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.3)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.2)\';">× Cerrar</button>';
            $output .= '<div id="arp-modosticky-content" style="width: 100%; height: 100%; position: relative; ' . $padding_style . '">';

            // Contenido rotativo
            $output .= '<div id="arp-modosticky-rotativo" style="width: 100%; height: 100%; position: relative;">';
            foreach ($anuncios_validos as $index => $anuncio_id) {
                $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
                $style = $index === 0 ? 'display: block;' : 'display: none;';

                $output .= '<div class="arp-modosticky-slide" data-index="' . $index . '" style="width: 100%; height: 100%; ' . $style . '">';
                $output .= arp_generar_contenido_anuncio($anuncio_id, $ad_type);
                $output .= '</div>';
            }
            $output .= '</div></div></div>';

            echo $output;
        }
    }

    if (in_array('interstitial', $tipos)) {
        $lugar = get_option('arp_modo_interstitial_lugar', 'lugar_interstitial');

        // Obtener anuncios válidos para el lugar seleccionado (interstitial)
        $anuncios_validos = arp_obtener_anuncios_para_modo($lugar, 'lugar_interstitial');

        if (!empty($anuncios_validos)) {
            // Aplicar rotación por refresco si está activada
            $anuncios_validos = arp_seleccionar_anuncio_rotativo( $anuncios_validos, $lugar );

            // Obtener configuraciones personalizadas
            $boton_color = get_option('arp_interstitial_boton_color', '#ffffff');
            $boton_fondo = get_option('arp_interstitial_boton_fondo', '#f44336');
            $margen = get_option('arp_interstitial_margen', '20');
            $sin_margen = get_option('arp_interstitial_sin_margen', 'off');
            $padding_style = ($sin_margen === 'on') ? 'padding: 0;' : 'padding: ' . esc_attr($margen) . 'px;';

            $output = '<div id="arp-modointerstitial-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: white; z-index: 99999; overflow: hidden;">';
            $output .= '<button id="arp-modointerstitial-close" style="position: absolute; top: 25px; right: 25px; background: ' . esc_attr($boton_fondo) . '; color: ' . esc_attr($boton_color) . '; border: 2px solid ' . esc_attr($boton_fondo) . '; border-radius: 50%; width: 45px; height: 45px; font-size: 28px; line-height: 1; cursor: pointer; z-index: 1000; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.2);" onmouseover="this.style.transform=\'scale(1.1)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.3)\';" onmouseout="this.style.transform=\'scale(1)\'; this.style.boxShadow=\'0 2px 8px rgba(0,0,0,0.2)\';">×</button>';
            $output .= '<div id="arp-modointerstitial-content" style="width: 100%; height: 100%; position: relative; ' . $padding_style . '">';

            // Contenido rotativo
            $output .= '<div id="arp-modointerstitial-rotativo" style="width: 100%; height: 100%; position: relative;">';
            foreach ($anuncios_validos as $index => $anuncio_id) {
                $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
                $style = $index === 0 ? 'display: block;' : 'display: none;';

                $output .= '<div class="arp-modointerstitial-slide" data-index="' . $index . '" style="width: 100%; height: 100%; ' . $style . '">';
                $output .= arp_generar_contenido_anuncio($anuncio_id, $ad_type);
                $output .= '</div>';
            }
            $output .= '</div></div></div>';

            echo $output;
        }
    }
}
add_action('wp_footer', 'arp_implementar_modo_anuncio');


// =========================================================================
// 7. IMPLEMENTACIÓN DE ANUNCIO IN-ARTICLE
// =========================================================================
function arp_insertar_anuncio_inarticle($content) {
    // Insertar solo en el contenido principal de una entrada o página singular.
    // Esto evita duplicados en widgets/loops secundarios y permite usar In-Article en páginas.
    if (is_admin() || !is_singular() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    // Verificar si el modo anuncio está activado
    $modo_activado = get_option('arp_modo_anuncio_activado', 'on');
    if ($modo_activado !== 'on') {
        return $content;
    }

    // Verificar si el modo inarticle está activado
    $tipos = arp_obtener_tipos_modo_anuncio();
    if (!is_array($tipos)) {
        $tipos = array($tipos);
    }

    if (!in_array('inarticle', $tipos)) {
        return $content;
    }

    // Obtener configuración
    $lugar = get_option('arp_modo_inarticle_lugar', 'lugar_inarticle');
    $posicion = get_option('arp_modo_inarticle_posicion', 'despues_parrafo_3');
    $ancho = get_option('arp_modo_inarticle_ancho', '300') . 'px';
    $alto = get_option('arp_modo_inarticle_alto', '250') . 'px';

    $anuncios_validos = arp_obtener_anuncios_para_modo($lugar, 'lugar_inarticle');

    if (empty($anuncios_validos)) {
        return $content;
    }

    // Aplicar rotación por refresco si está activada
    $anuncios_validos = arp_seleccionar_anuncio_rotativo( $anuncios_validos, $lugar );

    // Generar el anuncio
    $anuncio_html = '<div class="arp-inarticle-ad" style="margin: 30px auto; text-align: center; width: ' . esc_attr($ancho) . '; max-width: 100%;">';
    $anuncio_html .= '<div class="arp-inarticle-container" style="width: 100%; height: ' . esc_attr($alto) . '; position: relative; overflow: hidden; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9;">';

    // Contenido rotativo
    $anuncio_html .= '<div class="arp-inarticle-rotativo" style="width: 100%; height: 100%; position: relative;">';
    foreach ($anuncios_validos as $index => $anuncio_id) {
        $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
        $style = $index === 0 ? 'display: block;' : 'display: none;';

        $anuncio_html .= '<div class="arp-inarticle-slide" data-index="' . $index . '" style="width: 100%; height: 100%; ' . $style . '">';

        if ($ad_type === 'code') {
            $ad_code = get_post_meta($anuncio_id, '_arp_ad_code', true);
            $anuncio_html .= do_shortcode($ad_code);
        } elseif ($ad_type === 'video') {
            $video_url = get_post_meta($anuncio_id, '_arp_video_url', true);
            $video_id = arp_extract_youtube_id($video_url);

            if ($video_id) {
                // URL de embed con autoplay, mute, loop, sin controles ni info
                $embed_url = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=1&loop=1&playlist={$video_id}&controls=0&showinfo=0&rel=0&modestbranding=1&iv_load_policy=3&disablekb=1&fs=0";
                $anuncio_html .= "<div class='arp-video-container' style='width: 100%; height: 100%; position: relative;'>";
                $anuncio_html .= "<iframe src='" . esc_url($embed_url) . "' frameborder='0' allow='autoplay; encrypted-media; picture-in-picture' allowfullscreen style='width: 100%; height: 100%; border: none;'></iframe>";
                $anuncio_html .= "</div>";
            } else {
                $anuncio_html .= "<p style='padding: 20px;'>Error: URL de YouTube no válida</p>";
            }
        } else {
            $link = get_post_meta($anuncio_id, '_arp_link', true);
            $nombre_empresa = get_post_meta($anuncio_id, '_arp_nombre_empresa', true);
            $imagen_url = get_the_post_thumbnail_url($anuncio_id, 'full');

            if (!empty($link)) $anuncio_html .= '<a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer">';
            $anuncio_html .= '<img src="' . esc_url($imagen_url) . '" alt="' . esc_attr($nombre_empresa) . '" style="width: 100%; height: 100%; object-fit: contain;">';
            if (!empty($link)) $anuncio_html .= '</a>';
        }

        $anuncio_html .= '</div>';
    }
    $anuncio_html .= '</div></div></div>';

    // Insertar el anuncio según la posición configurada
    if ($posicion === 'despues_parrafo_1') {
        $content = arp_insertar_despues_parrafo($content, $anuncio_html, 1);
    } elseif ($posicion === 'despues_parrafo_2') {
        $content = arp_insertar_despues_parrafo($content, $anuncio_html, 2);
    } elseif ($posicion === 'despues_parrafo_3') {
        $content = arp_insertar_despues_parrafo($content, $anuncio_html, 3);
    } elseif ($posicion === 'despues_parrafo_4') {
        $content = arp_insertar_despues_parrafo($content, $anuncio_html, 4);
    } elseif ($posicion === 'despues_parrafo_5') {
        $content = arp_insertar_despues_parrafo($content, $anuncio_html, 5);
    } elseif ($posicion === 'mitad_contenido') {
        $content = arp_insertar_mitad_contenido($content, $anuncio_html);
    } elseif ($posicion === 'antes_comentarios') {
        $content = arp_insertar_antes_comentarios($content, $anuncio_html);
    }

    return $content;
}
add_filter('the_content', 'arp_insertar_anuncio_inarticle');

// Función para insertar después de un párrafo específico
function arp_insertar_despues_parrafo($content, $insertion, $paragraph_id) {
    $closing_p = '</p>';
    $paragraphs = explode($closing_p, $content);

    if (count($paragraphs) >= $paragraph_id) {
        foreach ($paragraphs as $index => $paragraph) {
            if (trim($paragraph)) {
                $paragraphs[$index] .= $closing_p;
            }
            if ($paragraph_id == $index + 1) {
                $paragraphs[$index] .= $insertion;
            }
        }
        return implode('', $paragraphs);
    }

    return $content . $insertion;
}

// Función para insertar en la mitad del contenido
function arp_insertar_mitad_contenido($content, $insertion) {
    $content_length = strlen($content);
    $half = $content_length / 2;

    // Encontrar la posición del espacio más cercano a la mitad
    $pos = strpos($content, ' ', $half);
    if ($pos !== false) {
        return substr_replace($content, $insertion . ' ', $pos, 1);
    }

    return $content . $insertion;
}

// Función para insertar antes de los comentarios
function arp_insertar_antes_comentarios($content, $insertion) {
    // Buscar el área de comentarios
    $comment_pos = strpos($content, '<div id="comments"');
    if ($comment_pos !== false) {
        return substr_replace($content, $insertion, $comment_pos, 0);
    }

    // Si no encuentra comentarios, insertar al final
    return $content . $insertion;
}


// =========================================================================
