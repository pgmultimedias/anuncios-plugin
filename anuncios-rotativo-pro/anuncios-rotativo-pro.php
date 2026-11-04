<?php
/**
 * Plugin Name:       Anuncios Rotativo Pro
 * Description:       Gestiona banners de anuncios rotativos con CPT, shortcodes, fechas de vencimiento, dimensiones y soporte para AdSense.
 * Version:           1.7.1
 * Author:            PG Multimedias
 * Author URI:        https://pgmultimedias.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       anuncios-rotativo-pro
 */

// Evitar el acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =========================================================================
// 1. REGISTRO DEL CUSTOM POST TYPE 'ANUNCIO' (ACTUALIZADO CON NUEVOS LUGARES)
// =========================================================================
function arp_registrar_post_type() {
    $labels = array(
        'name'                  => _x( 'Anuncios', 'Post Type General Name', 'anuncios-rotativo-pro' ),
        'singular_name'         => _x( 'Anuncio', 'Post Type Singular Name', 'anuncios-rotativo-pro' ),
        'menu_name'             => __( 'Anuncios Rotativos', 'anuncios-rotativo-pro' ),
        'name_admin_bar'        => __( 'Anuncio', 'anuncios-rotativo-pro' ),
        'archives'              => __( 'Archivo de Anuncios', 'anuncios-rotativo-pro' ),
        'attributes'            => __( 'Atributos del Anuncio', 'anuncios-rotativo-pro' ),
        'parent_item_colon'     => __( 'Anuncio Padre:', 'anuncios-rotativo-pro' ),
        'all_items'             => __( 'Todos los Anuncios', 'anuncios-rotativo-pro' ),
        'add_new_item'          => __( 'Añadir Nuevo Anuncio', 'anuncios-rotativo-pro' ),
        'add_new'               => __( 'Añadir Nuevo', 'anuncios-rotativo-pro' ),
        'new_item'              => __( 'Nuevo Anuncio', 'anuncios-rotativo-pro' ),
        'edit_item'             => __( 'Editar Anuncio', 'anuncios-rotativo-pro' ),
        'update_item'           => __( 'Actualizar Anuncio', 'anuncios-rotativo-pro' ),
        'view_item'             => __( 'Ver Anuncio', 'anuncios-rotativo-pro' ),
        'view_items'            => __( 'Ver Anuncios', 'anuncios-rotativo-pro' ),
        'search_items'          => __( 'Buscar Anuncio', 'anuncios-rotativo-pro' ),
        'not_found'             => __( 'No encontrado', 'anuncios-rotativo-pro' ),
        'not_found_in_trash'    => __( 'No encontrado en la papelera', 'anuncios-rotativo-pro' ),
        'featured_image'        => __( 'Banner del Anuncio', 'anuncios-rotativo-pro' ),
        'set_featured_image'    => __( 'Establecer Banner (gif, jpg, png)', 'anuncios-rotativo-pro' ),
        'remove_featured_image' => __( 'Quitar Banner', 'anuncios-rotativo-pro' ),
        'use_featured_image'    => __( 'Usar como Banner', 'anuncios-rotativo-pro' ),
    );
    $args = array(
        'label'                 => __( 'Anuncio', 'anuncios-rotativo-pro' ),
        'description'           => __( 'Anuncios para el carrusel', 'anuncios-rotativo-pro' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-megaphone',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
    );
    register_post_type( 'anuncio', $args );
}
add_action( 'init', 'arp_registrar_post_type', 0 );

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
// 2. AÑADIR META BOXES (CAMPOS PERSONALIZADOS) - ACTUALIZADO CON NUEVOS LUGARES
// =========================================================================
function arp_agregar_meta_boxes() {
    add_meta_box(
        'arp_anuncio_meta_box',
        'Detalles del Anuncio',
        'arp_render_meta_box',
        'anuncio',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'arp_agregar_meta_boxes' );

function arp_render_meta_box( $post ) {
    wp_nonce_field( 'arp_guardar_meta_box_data', 'arp_meta_box_nonce' );

    // Recuperar datos guardados
    $ad_type = get_post_meta( $post->ID, '_arp_ad_type', true );
    $ad_code = get_post_meta( $post->ID, '_arp_ad_code', true );
    $video_url = get_post_meta( $post->ID, '_arp_video_url', true );
    $nombre_empresa = get_post_meta( $post->ID, '_arp_nombre_empresa', true );
    $link = get_post_meta( $post->ID, '_arp_link', true );
    $fecha_inicio = get_post_meta( $post->ID, '_arp_fecha_inicio', true );
    $fecha_vencimiento = get_post_meta( $post->ID, '_arp_fecha_vencimiento', true );
    $auto_despublicar = get_post_meta( $post->ID, '_arp_auto_despublicar', true );
    $lugar = get_post_meta( $post->ID, '_arp_lugar', true );
    $programacion = get_post_meta( $post->ID, '_arp_programacion', true );

    $dias = isset($programacion['dias']) ? $programacion['dias'] : [];
    $hora_inicio = isset($programacion['hora_inicio']) ? $programacion['hora_inicio'] : '';
    $hora_fin = isset($programacion['hora_fin']) ? $programacion['hora_fin'] : '';
    ?>
    <style>
        .arp-campo { margin-bottom: 20px; }
        .arp-campo label { font-weight: bold; display: block; margin-bottom: 5px; }
        .arp-campo input[type="text"], .arp-campo input[type="url"], .arp-campo input[type="date"], .arp-campo input[type="time"], .arp-campo select, .arp-campo textarea { width: 100%; max-width: 600px; padding: 8px; }
        .arp-campo textarea { min-height: 150px; font-family: monospace; }
        .arp-campo-programacion div { display: inline-block; margin-right: 15px; }
        .arp-campo-programacion label, .arp-campo-radio label { font-weight: normal; margin-left: 5px; }
        .arp-campo small { color: #666; display: block; margin-top: 5px; }
    </style>

    <div class="arp-campo arp-campo-radio">
        <label>Tipo de Anuncio:</label>
        <input type="radio" id="arp_ad_type_image" name="arp_ad_type" value="image" <?php checked( $ad_type, 'image', true ); checked(empty($ad_type), true, true); ?>>
        <label for="arp_ad_type_image">Banner con Imagen (Subir archivo)</label>
        <br>
        <input type="radio" id="arp_ad_type_code" name="arp_ad_type" value="code" <?php checked( $ad_type, 'code', true ); ?>>
        <label for="arp_ad_type_code">Código de Anuncio (AdSense, etc.)</label>
        <br>
        <input type="radio" id="arp_ad_type_video" name="arp_ad_type" value="video" <?php checked( $ad_type, 'video', true ); ?>>
        <label for="arp_ad_type_video">Video (YouTube)</label>
    </div>
    <hr>
    
    <div id="arp_fields_for_image">
        <div class="arp-campo">
            <label for="arp_nombre_empresa">Nombre de la Empresa o Anunciante:</label>
            <input type="text" id="arp_nombre_empresa" name="arp_nombre_empresa" value="<?php echo esc_attr( $nombre_empresa ); ?>">
            <small>Este nombre se usará como texto alternativo (alt) de la imagen.</small>
        </div>

        <div class="arp-campo">
            <label for="arp_link">URL del Anunciante (Link):</label>
            <input type="url" id="arp_link" name="arp_link" value="<?php echo esc_url( $link ); ?>" placeholder="https://www.ejemplo.com">
            <small>La imagen enlazará a esta URL. Déjelo vacío si no quiere que tenga link.</small>
        </div>
        <small><strong>Importante:</strong> La imagen del banner se sube en la sección "Banner del Anuncio" en la columna derecha.</small>
    </div>

    <div id="arp_fields_for_code" style="display:none;">
        <div class="arp-campo">
            <label for="arp_ad_code">Pegar Código de Anuncio:</label>
            <textarea id="arp_ad_code" name="arp_ad_code"><?php echo esc_textarea( $ad_code ); ?></textarea>
            <small>Pegue aquí el código completo proporcionado por su red de anuncios (ej. AdSense). Incluya las etiquetas &lt;script&gt; si son necesarias.</small>
        </div>
    </div>

    <div id="arp_fields_for_video" style="display:none;">
        <div class="arp-campo">
            <label for="arp_video_url">URL del Video de YouTube:</label>
            <input type="url" id="arp_video_url" name="arp_video_url" value="<?php echo esc_url( $video_url ); ?>" placeholder="https://www.youtube.com/watch?v=... o https://youtu.be/...">
            <small>Pegue la URL completa del video de YouTube. El video se reproducirá automáticamente sin sonido, en bucle infinito y sin mostrar controles ni información de YouTube.</small>
        </div>
    </div>

    <hr>

    <div class="arp-campo">
        <label for="arp_lugar">Lugar de Visualización:</label>
        <select id="arp_lugar" name="arp_lugar">
            <option value="">-- Seleccionar un lugar --</option>
            
            <!-- Lugares numerados originales (1-30) -->
            <?php for ($i = 1; $i <= 30; $i++): 
                $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $lugar_nombre = arp_obtener_nombre_lugar( $lugar_id );
            ?>
                <option value="<?php echo $lugar_id; ?>" <?php selected( $lugar, $lugar_id ); ?>><?php echo esc_html( $lugar_nombre ); ?></option>
            <?php endfor; ?>
            
            <!-- Nuevos lugares específicos -->
            <option value="lugar_popup" <?php selected( $lugar, 'lugar_popup' ); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_popup' ) ); ?></option>
            <option value="lugar_sticky_footer" <?php selected( $lugar, 'lugar_sticky_footer' ); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_sticky_footer' ) ); ?></option>
            <option value="lugar_interstitial" <?php selected( $lugar, 'lugar_interstitial' ); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_interstitial' ) ); ?></option>
            <option value="lugar_inarticle" <?php selected( $lugar, 'lugar_inarticle' ); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_inarticle' ) ); ?></option>
        </select>
    </div>
    <hr>
    
    <div class="arp-campo">
        <label for="arp_fecha_inicio">Fecha de Inicio:</label>
        <input type="date" id="arp_fecha_inicio" name="arp_fecha_inicio" value="<?php echo esc_attr( $fecha_inicio ); ?>">
    </div>

    <div class="arp-campo">
        <label for="arp_fecha_vencimiento">Fecha de Vencimiento:</label>
        <input type="date" id="arp_fecha_vencimiento" name="arp_fecha_vencimiento" value="<?php echo esc_attr( $fecha_vencimiento ); ?>">
    </div>

    <div class="arp-campo">
        <label>
            <input type="checkbox" name="arp_auto_despublicar" value="on" <?php checked( $auto_despublicar, 'on' ); ?>>
            Despublicar automáticamente al vencer.
        </label>
    </div>
    <hr>

    <h3>Programación Avanzada (Opcional)</h3>
    <div class="arp-campo arp-campo-programacion">
        <label>Días de la semana:</label>
        <?php 
        $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        foreach ($dias_semana as $key => $dia) {
            $checked = is_array($dias) && in_array($key + 1, $dias) ? 'checked' : '';
            echo '<div><input type="checkbox" id="dia_'.($key+1).'" name="arp_programacion_dias[]" value="'.($key+1).'" '.$checked.'><label for="dia_'.($key+1).'">'.$dia.'</label></div>';
        }
        ?>
    </div>
    <div class="arp-campo arp-campo-programacion">
        <label>Rango de Horas:</label>
        <div><label for="arp_programacion_hora_inicio">Desde:</label><input type="time" id="arp_programacion_hora_inicio" name="arp_programacion_hora_inicio" value="<?php echo esc_attr($hora_inicio); ?>"></div>
        <div><label for="arp_programacion_hora_fin">Hasta:</label><input type="time" id="arp_programacion_hora_fin" name="arp_programacion_hora_fin" value="<?php echo esc_attr($hora_fin); ?>"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function toggleAdFields() {
            var selectedType = $('input[name="arp_ad_type"]:checked').val();
            
            // Ocultar todos los campos primero
            $('#arp_fields_for_image').hide();
            $('#arp_fields_for_code').hide();
            $('#arp_fields_for_video').hide();
            
            if (selectedType === 'code') {
                $('#arp_fields_for_code').show();
                $('#postimagediv').hide(); // Oculta la caja de imagen destacada
            } else if (selectedType === 'video') {
                $('#arp_fields_for_video').show();
                $('#postimagediv').hide(); // Oculta la caja de imagen destacada
            } else {
                // Por defecto, es 'image'
                $('#arp_fields_for_image').show();
                $('#postimagediv').show(); // Muestra la caja de imagen destacada
            }
        }
        
        $('input[name="arp_ad_type"]').on('change', toggleAdFields);
        toggleAdFields(); // Ejecutar al cargar la página
    });
    </script>
    <?php
}

// Función para extraer el ID de video de YouTube
function arp_extract_youtube_id($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}

function arp_guardar_meta_box_data( $post_id ) {
    if ( ! isset( $_POST['arp_meta_box_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['arp_meta_box_nonce'], 'arp_guardar_meta_box_data' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Guardar tipo de anuncio
    $ad_type = sanitize_text_field( $_POST['arp_ad_type'] );
    update_post_meta( $post_id, '_arp_ad_type', $ad_type );
    
    // Guardar datos según el tipo
    if ( $ad_type === 'code' ) {
        // Para AdSense y otros, necesitamos guardar el código tal cual, incluyendo scripts.
        update_post_meta( $post_id, '_arp_ad_code', $_POST['arp_ad_code'] );
        update_post_meta( $post_id, '_arp_video_url', '' ); // Limpiar video
    } elseif ( $ad_type === 'video' ) {
        update_post_meta( $post_id, '_arp_video_url', esc_url_raw( $_POST['arp_video_url'] ) );
        update_post_meta( $post_id, '_arp_ad_code', '' ); // Limpiar código
    } else {
        // Para imagen, limpiar otros campos
        update_post_meta( $post_id, '_arp_ad_code', '' );
        update_post_meta( $post_id, '_arp_video_url', '' );
    }

    update_post_meta( $post_id, '_arp_nombre_empresa', sanitize_text_field( $_POST['arp_nombre_empresa'] ) );
    update_post_meta( $post_id, '_arp_link', esc_url_raw( $_POST['arp_link'] ) );
    update_post_meta( $post_id, '_arp_fecha_inicio', sanitize_text_field( $_POST['arp_fecha_inicio'] ) );
    update_post_meta( $post_id, '_arp_fecha_vencimiento', sanitize_text_field( $_POST['arp_fecha_vencimiento'] ) );
    update_post_meta( $post_id, '_arp_lugar', sanitize_text_field( $_POST['arp_lugar'] ) );
    update_post_meta( $post_id, '_arp_auto_despublicar', isset($_POST['arp_auto_despublicar']) ? 'on' : 'off' );

    // Guardar programación
    $programacion = [];
    if (!empty($_POST['arp_programacion_dias']) && !empty($_POST['arp_programacion_hora_inicio']) && !empty($_POST['arp_programacion_hora_fin'])) {
        $programacion['dias'] = array_map('intval', $_POST['arp_programacion_dias']);
        $programacion['hora_inicio'] = sanitize_text_field($_POST['arp_programacion_hora_inicio']);
        $programacion['hora_fin'] = sanitize_text_field($_POST['arp_programacion_hora_fin']);
    }
    update_post_meta($post_id, '_arp_programacion', $programacion);
}
add_action( 'save_post', 'arp_guardar_meta_box_data' );


// =========================================================================
// 3. PANEL DE CONFIGURACIÓN - ACTUALIZADO CON NUEVAS FUNCIONES
// =========================================================================
function arp_agregar_pagina_admin() {
    add_submenu_page( 'edit.php?post_type=anuncio', 'Configuración de Anuncios', 'Configuración', 'manage_options', 'arp_configuracion', 'arp_render_pagina_configuracion' );
}
add_action( 'admin_menu', 'arp_agregar_pagina_admin' );

function arp_render_pagina_configuracion() {
    ?>
    <div class="wrap">
        <h1>Configuración de Anuncios Rotativo Pro</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?post_type=anuncio&page=arp_configuracion&tab=config" class="nav-tab <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'config' ? 'nav-tab-active' : ''; ?>">Configuración General</a>
            <a href="?post_type=anuncio&page=arp_configuracion&tab=shortcodes" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'shortcodes' ? 'nav-tab-active' : ''; ?>">Información de Shortcodes</a>
            <a href="?post_type=anuncio&page=arp_configuracion&tab=modo" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'modo' ? 'nav-tab-active' : ''; ?>">Modo Anuncio</a>
            <a href="?post_type=anuncio&page=arp_configuracion&tab=personalizacion" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'personalizacion' ? 'nav-tab-active' : ''; ?>">Personalización</a>
            <a href="?post_type=anuncio&page=arp_configuracion&tab=nombres_lugares" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'nombres_lugares' ? 'nav-tab-active' : ''; ?>">Personalizar nombres de lugares</a>
        </h2>

        <?php
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'config';
        if($active_tab == 'config') { ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'arp_opciones_grupo' );
                do_settings_sections( 'arp_configuracion' );
                submit_button();
                ?>
            </form>
        <?php } elseif($active_tab == 'shortcodes') { ?>
            <h3>Códigos para mostrar los anuncios</h3>
            <p>Usa estos shortcodes para mostrar los carruseles de anuncios en cualquier parte de tu web.</p>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Lugar</th><th>Shortcode</th></tr></thead>
                <tbody>
                <?php for ($i = 1; $i <= 30; $i++): 
                    $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                    $lugar_nombre = arp_obtener_nombre_lugar( $lugar_id );
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $lugar_nombre ); ?></strong></td>
                        <td><input type="text" value="[anuncio_rotativo lugar='<?php echo esc_attr($lugar_id); ?>']" readonly onfocus="this.select();" style="width: 100%;"></td>
                    </tr>
                <?php endfor; ?>
                <!-- Nuevos lugares específicos -->
                <tr>
                    <td><strong><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_popup' ) ); ?></strong></td>
                    <td><input type="text" value="[anuncio_rotativo lugar='lugar_popup']" readonly onfocus="this.select();" style="width: 100%;"></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_sticky_footer' ) ); ?></strong></td>
                    <td><input type="text" value="[anuncio_rotativo lugar='lugar_sticky_footer']" readonly onfocus="this.select();" style="width: 100%;"></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_interstitial' ) ); ?></strong></td>
                    <td><input type="text" value="[anuncio_rotativo lugar='lugar_interstitial']" readonly onfocus="this.select();" style="width: 100%;"></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_inarticle' ) ); ?></strong></td>
                    <td><input type="text" value="[anuncio_rotativo lugar='lugar_inarticle']" readonly onfocus="this.select();" style="width: 100%;"></td>
                </tr>
                </tbody>
            </table>
            <h3 style="margin-top: 30px;">Shortcode para Sticky Footer</h3>
            <p>Usa este shortcode para mostrar el anuncio sticky en cualquier parte de tu web:</p>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Descripción</th><th>Shortcode</th></tr></thead>
                <tbody>
                    <tr>
                        <td><strong>Sticky Footer</strong></td>
                        <td><input type="text" value="[anuncio_sticky]" readonly onfocus="this.select();" style="width: 100%;"></td>
                    </tr>
                </tbody>
            </table>
        <?php } elseif($active_tab == 'modo') { ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'arp_modo_anuncio_grupo' );
                do_settings_sections( 'arp_modo_anuncio' );
                submit_button();
                ?>
            </form>
        <?php } elseif($active_tab == 'personalizacion') { ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'arp_personalizacion_grupo' );
                do_settings_sections( 'arp_personalizacion' );
                submit_button();
                ?>
            </form>
        <?php } elseif($active_tab == 'nombres_lugares') { ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'arp_nombres_lugares_grupo' );
                do_settings_sections( 'arp_nombres_lugares' );
                submit_button();
                ?>
            </form>
        <?php } ?>
    </div>
    <?php
}

function arp_registrar_opciones() {
    // Configuración original de lugares
    register_setting( 'arp_opciones_grupo', 'arp_tiempos_rotacion' );
    register_setting( 'arp_opciones_grupo', 'arp_dimensiones_lugar' );
    register_setting( 'arp_opciones_grupo', 'arp_rotacion_refresco' );

    add_settings_section( 'arp_seccion_config', 'Configuración por Lugar', null, 'arp_configuracion' );

    // Lugares originales (1-30)
    for ($i = 1; $i <= 30; $i++) {
        $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
        add_settings_field(
            'arp_config_' . $lugar_id,
            'Lugar ' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'arp_campo_config_callback',
            'arp_configuracion',
            'arp_seccion_config',
            ['id' => $lugar_id]
        );
    }
    
    // Configuración para los nuevos lugares específicos
    $lugares_especiales = [
        'lugar_popup' => 'Lugar Pop-up',
        'lugar_sticky_footer' => 'Lugar Sticky Footer',
        'lugar_interstitial' => 'Lugar Anuncio Intersticial',
        'lugar_inarticle' => 'Lugar Anuncio In-Article'
    ];
    
    foreach ($lugares_especiales as $lugar_id => $lugar_nombre) {
        add_settings_field(
            'arp_config_' . $lugar_id,
            $lugar_nombre,
            'arp_campo_config_callback',
            'arp_configuracion',
            'arp_seccion_config',
            ['id' => $lugar_id]
        );
    }
    
    // Nueva configuración para modo anuncio
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_anuncio_activado' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_anuncio_tipo', array('default' => array('popup')) );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_anuncio_lugar' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_sticky_lugar' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_interstitial_lugar' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_inarticle_lugar' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_popup_ancho' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_popup_alto' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_sticky_ancho' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_sticky_alto' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_sticky_duracion' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_inarticle_ancho' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_inarticle_alto' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_inarticle_posicion' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_delay' );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_duracion' );

    add_settings_section( 'arp_modo_anuncio_seccion', 'Configuración del Modo Anuncio', null, 'arp_modo_anuncio' );
    
    add_settings_field(
        'arp_modo_anuncio_activado',
        'Activar Modo Anuncio',
        'arp_campo_modo_activado_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_anuncio_tipo',
        'Tipo de Modo (Puede seleccionar varios)',
        'arp_campo_modo_tipo_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_anuncio_lugar',
        'Lugar a Mostrar (Pop-up)',
        'arp_campo_modo_lugar_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_sticky_lugar',
        'Lugar a Mostrar (Sticky Footer)',
        'arp_campo_modo_sticky_lugar_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_interstitial_lugar',
        'Lugar a Mostrar (Interstitial Ad)',
        'arp_campo_modo_interstitial_lugar_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_inarticle_lugar',
        'Lugar a Mostrar (Anuncio In-Article)',
        'arp_campo_modo_inarticle_lugar_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_popup_config',
        'Configuración Pop-up',
        'arp_campo_popup_config_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_sticky_config',
        'Configuración Sticky Footer',
        'arp_campo_sticky_config_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_inarticle_config',
        'Configuración Anuncio In-Article',
        'arp_campo_inarticle_config_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    add_settings_field(
        'arp_modo_tiempos',
        'Tiempos de Visualización',
        'arp_campo_modo_tiempos_callback',
        'arp_modo_anuncio',
        'arp_modo_anuncio_seccion'
    );
    
    // Configuración de personalización
    register_setting( 'arp_personalizacion_grupo', 'arp_popup_boton_color' );
    register_setting( 'arp_personalizacion_grupo', 'arp_popup_boton_fondo' );
    register_setting( 'arp_personalizacion_grupo', 'arp_popup_margen' );
    register_setting( 'arp_personalizacion_grupo', 'arp_popup_sin_margen' );
    register_setting( 'arp_personalizacion_grupo', 'arp_interstitial_boton_color' );
    register_setting( 'arp_personalizacion_grupo', 'arp_interstitial_boton_fondo' );
    register_setting( 'arp_personalizacion_grupo', 'arp_interstitial_margen' );
    register_setting( 'arp_personalizacion_grupo', 'arp_interstitial_sin_margen' );
    register_setting( 'arp_personalizacion_grupo', 'arp_sticky_boton_color' );
    register_setting( 'arp_personalizacion_grupo', 'arp_sticky_boton_fondo' );
    register_setting( 'arp_personalizacion_grupo', 'arp_sticky_margen' );
    register_setting( 'arp_personalizacion_grupo', 'arp_sticky_sin_margen' );
    
    add_settings_section( 'arp_personalizacion_seccion', 'Personalización de Diseño', null, 'arp_personalizacion' );
    
    add_settings_field(
        'arp_personalizacion_popup',
        'Personalización Pop-up',
        'arp_campo_personalizacion_popup_callback',
        'arp_personalizacion',
        'arp_personalizacion_seccion'
    );
    
    add_settings_field(
        'arp_personalizacion_interstitial',
        'Personalización Anuncio Intersticial',
        'arp_campo_personalizacion_interstitial_callback',
        'arp_personalizacion',
        'arp_personalizacion_seccion'
    );
    
    add_settings_field(
        'arp_personalizacion_sticky',
        'Personalización Sticky Footer Ad',
        'arp_campo_personalizacion_sticky_callback',
        'arp_personalizacion',
        'arp_personalizacion_seccion'
    );
    
    // Configuración de nombres personalizados de lugares
    register_setting( 'arp_nombres_lugares_grupo', 'arp_nombres_lugares' );
    
    add_settings_section( 'arp_nombres_lugares_seccion', 'Personalizar Nombres de Lugares', null, 'arp_nombres_lugares' );
    
    add_settings_field(
        'arp_nombres_lugares_campos',
        'Nombres Personalizados',
        'arp_campo_nombres_lugares_callback',
        'arp_nombres_lugares',
        'arp_nombres_lugares_seccion'
    );
}
add_action( 'admin_init', 'arp_registrar_opciones' );

function arp_campo_config_callback( $args ) {
    $tiempos = get_option('arp_tiempos_rotacion');
    $dimensiones = get_option('arp_dimensiones_lugar');
    $rotacion_refresco = get_option('arp_rotacion_refresco', array());
    $id = $args['id'];
    
    $tiempo_val = isset($tiempos[$id]) ? $tiempos[$id] : '5000';
    $ancho_val = isset($dimensiones[$id]['ancho']) ? $dimensiones[$id]['ancho'] : '';
    $alto_val = isset($dimensiones[$id]['alto']) ? $dimensiones[$id]['alto'] : '';
    $rotacion_activa = isset($rotacion_refresco[$id]) && $rotacion_refresco[$id] === 'on' ? 'on' : 'off';
    
    echo "Rotación: <input type='number' name='arp_tiempos_rotacion[$id]' value='" . esc_attr($tiempo_val) . "' placeholder='5000' style='width: 80px;' /> ms ";
    echo "Ancho: <input type='number' name='arp_dimensiones_lugar[$id][ancho]' value='" . esc_attr($ancho_val) . "' placeholder='450' style='width: 70px;' /> px ";
    echo "Alto: <input type='number' name='arp_dimensiones_lugar[$id][alto]' value='" . esc_attr($alto_val) . "' placeholder='300' style='width: 70px;' /> px";
    
    // Mostrar opción de rotación por refresco para todos los lugares (numerados y especiales)
    echo "<br><label style='margin-top: 10px; display: block;'><input type='checkbox' name='arp_rotacion_refresco[$id]' value='on' " . checked($rotacion_activa, 'on', false) . "> Rotación por refresco (intercalar banners en cada carga de página)</label>";
}

// Nuevos callbacks para el modo anuncio
function arp_campo_modo_activado_callback() {
    $activado = get_option('arp_modo_anuncio_activado', 'off');
    ?>
    <label>
        <input type="checkbox" name="arp_modo_anuncio_activado" value="on" <?php checked($activado, 'on'); ?>>
        Activar el modo anuncio en el sitio
    </label>
    <p class="description">Cuando está activado, se mostrará el anuncio según el tipo seleccionado.</p>
    <?php
}

function arp_campo_modo_tipo_callback() {
    $tipos = get_option('arp_modo_anuncio_tipo', array('popup'));
    if (!is_array($tipos)) {
        $tipos = array($tipos);
    }
    ?>
    <label>
        <input type="checkbox" name="arp_modo_anuncio_tipo[]" value="popup" <?php checked(in_array('popup', $tipos), true); ?>>
        Pop-up (Ventana emergente)
    </label><br>
    <label>
        <input type="checkbox" name="arp_modo_anuncio_tipo[]" value="sticky" <?php checked(in_array('sticky', $tipos), true); ?>>
        Sticky Footer Ad (Anuncio fijo al pie)
    </label><br>
    <label>
        <input type="checkbox" name="arp_modo_anuncio_tipo[]" value="interstitial" <?php checked(in_array('interstitial', $tipos), true); ?>>
        Anuncio Intersticial (Interstitial Ad)
    </label><br>
    <label>
        <input type="checkbox" name="arp_modo_anuncio_tipo[]" value="inarticle" <?php checked(in_array('inarticle', $tipos), true); ?>>
        Anuncio In-Article (En medio del contenido)
    </label>
    <p class="description">Puede seleccionar varios modos para que funcionen simultáneamente.</p>
    <?php
}

function arp_campo_modo_lugar_callback() {
    $lugar_seleccionado = get_option('arp_modo_anuncio_lugar', 'lugar_popup');
    ?>
    <select name="arp_modo_anuncio_lugar">
        <?php for ($i = 1; $i <= 30; $i++): 
            $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $lugar_nombre = arp_obtener_nombre_lugar( $lugar_id );
        ?>
            <option value="<?php echo $lugar_id; ?>" <?php selected($lugar_seleccionado, $lugar_id); ?>><?php echo esc_html( $lugar_nombre ); ?></option>
        <?php endfor; ?>
        <option value="lugar_popup" <?php selected($lugar_seleccionado, 'lugar_popup'); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_popup' ) ); ?></option>
    </select>
    <p class="description">Selecciona el lugar cuyos anuncios se mostrarán en el pop-up.</p>
    <?php
}

function arp_campo_modo_sticky_lugar_callback() {
    $lugar_seleccionado = get_option('arp_modo_sticky_lugar', 'lugar_sticky_footer');
    ?>
    <select name="arp_modo_sticky_lugar">
        <?php for ($i = 1; $i <= 30; $i++): 
            $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $lugar_nombre = arp_obtener_nombre_lugar( $lugar_id );
        ?>
            <option value="<?php echo $lugar_id; ?>" <?php selected($lugar_seleccionado, $lugar_id); ?>><?php echo esc_html( $lugar_nombre ); ?></option>
        <?php endfor; ?>
        <option value="lugar_sticky_footer" <?php selected($lugar_seleccionado, 'lugar_sticky_footer'); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_sticky_footer' ) ); ?></option>
    </select>
    <p class="description">Selecciona el lugar cuyos anuncios se mostrarán en el sticky footer.</p>
    <?php
}

function arp_campo_modo_interstitial_lugar_callback() {
    $lugar_seleccionado = get_option('arp_modo_interstitial_lugar', 'lugar_interstitial');
    ?>
    <select name="arp_modo_interstitial_lugar">
        <?php for ($i = 1; $i <= 30; $i++): 
            $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $lugar_nombre = arp_obtener_nombre_lugar( $lugar_id );
        ?>
            <option value="<?php echo $lugar_id; ?>" <?php selected($lugar_seleccionado, $lugar_id); ?>><?php echo esc_html( $lugar_nombre ); ?></option>
        <?php endfor; ?>
        <option value="lugar_interstitial" <?php selected($lugar_seleccionado, 'lugar_interstitial'); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_interstitial' ) ); ?></option>
    </select>
    <p class="description">Selecciona el lugar cuyos anuncios se mostrarán en el anuncio intersticial.</p>
    <?php
}

function arp_campo_modo_inarticle_lugar_callback() {
    $lugar_seleccionado = get_option('arp_modo_inarticle_lugar', 'lugar_inarticle');
    ?>
    <select name="arp_modo_inarticle_lugar">
        <?php for ($i = 1; $i <= 30; $i++): 
            $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $lugar_nombre = arp_obtener_nombre_lugar( $lugar_id );
        ?>
            <option value="<?php echo $lugar_id; ?>" <?php selected($lugar_seleccionado, $lugar_id); ?>><?php echo esc_html( $lugar_nombre ); ?></option>
        <?php endfor; ?>
        <option value="lugar_inarticle" <?php selected($lugar_seleccionado, 'lugar_inarticle'); ?>><?php echo esc_html( arp_obtener_nombre_lugar( 'lugar_inarticle' ) ); ?></option>
    </select>
    <p class="description">Selecciona el lugar cuyos anuncios se insertarán en el medio del contenido.</p>
    <?php
}

function arp_campo_popup_config_callback() {
    $ancho = get_option('arp_modo_popup_ancho', '600');
    $alto = get_option('arp_modo_popup_alto', '400');
    ?>
    <div>
        <label>Ancho: <input type="number" name="arp_modo_popup_ancho" value="<?php echo esc_attr($ancho); ?>" style="width: 80px;"> px</label>
    </div>
    <div style="margin-top: 10px;">
        <label>Alto: <input type="number" name="arp_modo_popup_alto" value="<?php echo esc_attr($alto); ?>" style="width: 80px;"> px</label>
    </div>
    <?php
}

function arp_campo_sticky_config_callback() {
    $ancho = get_option('arp_modo_sticky_ancho', '80');
    $alto = get_option('arp_modo_sticky_alto', '100');
    $duracion = get_option('arp_modo_sticky_duracion', '0');
    ?>
    <div>
        <label>Ancho: <input type="number" name="arp_modo_sticky_ancho" value="<?php echo esc_attr($ancho); ?>" min="1" max="100" style="width: 80px;"> %</label>
    </div>
    <div style="margin-top: 10px;">
        <label>Alto: <input type="number" name="arp_modo_sticky_alto" value="<?php echo esc_attr($alto); ?>" style="width: 80px;"> px</label>
    </div>
    <div style="margin-top: 10px;">
        <label>Duración visible (ms): <input type="number" name="arp_modo_sticky_duracion" value="<?php echo esc_attr($duracion); ?>" min="0" style="width: 100px;"></label>
        <p class="description">Tiempo que el Sticky Footer permanece visible antes de cerrarse automáticamente (en milisegundos). Use 0 para que permanezca visible indefinidamente hasta que el usuario lo cierre manualmente.</p>
    </div>
    <?php
}

function arp_campo_inarticle_config_callback() {
    $ancho = get_option('arp_modo_inarticle_ancho', '300');
    $alto = get_option('arp_modo_inarticle_alto', '250');
    $posicion = get_option('arp_modo_inarticle_posicion', 'despues_parrafo_3');
    ?>
    <div>
        <label>Ancho: <input type="number" name="arp_modo_inarticle_ancho" value="<?php echo esc_attr($ancho); ?>" style="width: 80px;"> px</label>
    </div>
    <div style="margin-top: 10px;">
        <label>Alto: <input type="number" name="arp_modo_inarticle_alto" value="<?php echo esc_attr($alto); ?>" style="width: 80px;"> px</label>
    </div>
    <div style="margin-top: 10px;">
        <label>Posición en el contenido:</label>
        <select name="arp_modo_inarticle_posicion" style="width: 200px;">
            <option value="despues_parrafo_1" <?php selected($posicion, 'despues_parrafo_1'); ?>>Después del 1er párrafo</option>
            <option value="despues_parrafo_2" <?php selected($posicion, 'despues_parrafo_2'); ?>>Después del 2do párrafo</option>
            <option value="despues_parrafo_3" <?php selected($posicion, 'despues_parrafo_3'); ?>>Después del 3er párrafo</option>
            <option value="despues_parrafo_4" <?php selected($posicion, 'despues_parrafo_4'); ?>>Después del 4to párrafo</option>
            <option value="despues_parrafo_5" <?php selected($posicion, 'despues_parrafo_5'); ?>>Después del 5to párrafo</option>
            <option value="mitad_contenido" <?php selected($posicion, 'mitad_contenido'); ?>>En la mitad del contenido</option>
            <option value="antes_comentarios" <?php selected($posicion, 'antes_comentarios'); ?>>Antes de los comentarios</option>
        </select>
    </div>
    <?php
}

function arp_campo_modo_tiempos_callback() {
    $delay = get_option('arp_modo_delay', '3000');
    $duracion = get_option('arp_modo_duracion', '10000');
    ?>
    <div>
        <label>Delay inicial (ms): <input type="number" name="arp_modo_delay" value="<?php echo esc_attr($delay); ?>" style="width: 100px;"></label>
        <p class="description">Tiempo de espera antes de mostrar el anuncio (en milisegundos). Solo aplica a Pop-up, Sticky e Interstitial.</p>
    </div>
    <div style="margin-top: 10px;">
        <label>Duración visible (ms): <input type="number" name="arp_modo_duracion" value="<?php echo esc_attr($duracion); ?>" style="width: 100px;"></label>
        <p class="description">Tiempo que el anuncio permanece visible (en milisegundos). Para el popup, se cierra automáticamente después de este tiempo.</p>
    </div>
    <?php
}

function arp_campo_personalizacion_popup_callback() {
    $boton_color = get_option('arp_popup_boton_color', '#ffffff');
    $boton_fondo = get_option('arp_popup_boton_fondo', '#f44336');
    $margen = get_option('arp_popup_margen', '20');
    $sin_margen = get_option('arp_popup_sin_margen', 'off');
    ?>
    <div style="margin-bottom: 20px;">
        <h4>Botón de Cerrar</h4>
        <div style="margin-top: 10px;">
            <label>Color del texto: <input type="color" name="arp_popup_boton_color" value="<?php echo esc_attr($boton_color); ?>" style="width: 80px; height: 35px; vertical-align: middle;"></label>
        </div>
        <div style="margin-top: 10px;">
            <label>Color de fondo: <input type="color" name="arp_popup_boton_fondo" value="<?php echo esc_attr($boton_fondo); ?>" style="width: 80px; height: 35px; vertical-align: middle;"></label>
        </div>
    </div>
    <div style="margin-bottom: 20px;">
        <h4>Márgenes del Contenedor</h4>
        <div style="margin-top: 10px;">
            <label>
                <input type="checkbox" name="arp_popup_sin_margen" value="on" <?php checked($sin_margen, 'on'); ?>>
                Sin márgenes (el banner ocupará todo el espacio del contenedor)
            </label>
        </div>
        <div style="margin-top: 10px;">
            <label>Márgenes (px): <input type="number" name="arp_popup_margen" value="<?php echo esc_attr($margen); ?>" min="0" style="width: 80px;" <?php echo $sin_margen === 'on' ? 'disabled' : ''; ?>></label>
            <p class="description">Márgenes internos del contenedor del anuncio. Se aplica solo si "Sin márgenes" está desactivado.</p>
        </div>
    </div>
    <?php
}

function arp_campo_personalizacion_interstitial_callback() {
    $boton_color = get_option('arp_interstitial_boton_color', '#ffffff');
    $boton_fondo = get_option('arp_interstitial_boton_fondo', '#f44336');
    $margen = get_option('arp_interstitial_margen', '20');
    $sin_margen = get_option('arp_interstitial_sin_margen', 'off');
    ?>
    <div style="margin-bottom: 20px;">
        <h4>Botón de Cerrar</h4>
        <div style="margin-top: 10px;">
            <label>Color del texto: <input type="color" name="arp_interstitial_boton_color" value="<?php echo esc_attr($boton_color); ?>" style="width: 80px; height: 35px; vertical-align: middle;"></label>
        </div>
        <div style="margin-top: 10px;">
            <label>Color de fondo: <input type="color" name="arp_interstitial_boton_fondo" value="<?php echo esc_attr($boton_fondo); ?>" style="width: 80px; height: 35px; vertical-align: middle;"></label>
        </div>
    </div>
    <div style="margin-bottom: 20px;">
        <h4>Márgenes del Contenedor</h4>
        <div style="margin-top: 10px;">
            <label>
                <input type="checkbox" name="arp_interstitial_sin_margen" value="on" <?php checked($sin_margen, 'on'); ?>>
                Sin márgenes (el banner ocupará todo el espacio del contenedor)
            </label>
        </div>
        <div style="margin-top: 10px;">
            <label>Márgenes (px): <input type="number" name="arp_interstitial_margen" value="<?php echo esc_attr($margen); ?>" min="0" style="width: 80px;" <?php echo $sin_margen === 'on' ? 'disabled' : ''; ?>></label>
            <p class="description">Márgenes internos del contenedor del anuncio. Se aplica solo si "Sin márgenes" está desactivado.</p>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('input[name="arp_popup_sin_margen"]').on('change', function() {
            $('input[name="arp_popup_margen"]').prop('disabled', $(this).is(':checked'));
        });
        $('input[name="arp_interstitial_sin_margen"]').on('change', function() {
            $('input[name="arp_interstitial_margen"]').prop('disabled', $(this).is(':checked'));
        });
        $('input[name="arp_sticky_sin_margen"]').on('change', function() {
            $('input[name="arp_sticky_margen"]').prop('disabled', $(this).is(':checked'));
        });
    });
    </script>
    <?php
}

function arp_campo_nombres_lugares_callback() {
    $nombres_personalizados = get_option( 'arp_nombres_lugares', array() );
    ?>
    <p class="description">Personaliza los nombres de los lugares. Si dejas un campo vacío, se usará el nombre por defecto.</p>
    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
            <tr>
                <th style="width: 200px;">ID del Lugar</th>
                <th>Nombre por Defecto</th>
                <th>Nombre Personalizado</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i <= 30; $i++): 
                $lugar_id = 'lugar_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $nombre_default = 'Lugar ' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $nombre_personalizado = isset($nombres_personalizados[$lugar_id]) ? $nombres_personalizados[$lugar_id] : '';
            ?>
                <tr>
                    <td><strong><?php echo esc_html($lugar_id); ?></strong></td>
                    <td><?php echo esc_html($nombre_default); ?></td>
                    <td>
                        <input type="text" name="arp_nombres_lugares[<?php echo esc_attr($lugar_id); ?>]" value="<?php echo esc_attr($nombre_personalizado); ?>" style="width: 100%; max-width: 400px;" placeholder="<?php echo esc_attr($nombre_default); ?>">
                    </td>
                </tr>
            <?php endfor; ?>
            
            <!-- Lugares especiales -->
            <tr>
                <td><strong>lugar_popup</strong></td>
                <td>Lugar Pop-up</td>
                <td>
                    <input type="text" name="arp_nombres_lugares[lugar_popup]" value="<?php echo isset($nombres_personalizados['lugar_popup']) ? esc_attr($nombres_personalizados['lugar_popup']) : ''; ?>" style="width: 100%; max-width: 400px;" placeholder="Lugar Pop-up">
                </td>
            </tr>
            <tr>
                <td><strong>lugar_sticky_footer</strong></td>
                <td>Lugar Sticky Footer</td>
                <td>
                    <input type="text" name="arp_nombres_lugares[lugar_sticky_footer]" value="<?php echo isset($nombres_personalizados['lugar_sticky_footer']) ? esc_attr($nombres_personalizados['lugar_sticky_footer']) : ''; ?>" style="width: 100%; max-width: 400px;" placeholder="Lugar Sticky Footer">
                </td>
            </tr>
            <tr>
                <td><strong>lugar_interstitial</strong></td>
                <td>Lugar Anuncio Intersticial</td>
                <td>
                    <input type="text" name="arp_nombres_lugares[lugar_interstitial]" value="<?php echo isset($nombres_personalizados['lugar_interstitial']) ? esc_attr($nombres_personalizados['lugar_interstitial']) : ''; ?>" style="width: 100%; max-width: 400px;" placeholder="Lugar Anuncio Intersticial">
                </td>
            </tr>
            <tr>
                <td><strong>lugar_inarticle</strong></td>
                <td>Lugar Anuncio In-Article</td>
                <td>
                    <input type="text" name="arp_nombres_lugares[lugar_inarticle]" value="<?php echo isset($nombres_personalizados['lugar_inarticle']) ? esc_attr($nombres_personalizados['lugar_inarticle']) : ''; ?>" style="width: 100%; max-width: 400px;" placeholder="Lugar Anuncio In-Article">
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

function arp_campo_personalizacion_sticky_callback() {
    $boton_color = get_option('arp_sticky_boton_color', '#ffffff');
    $boton_fondo = get_option('arp_sticky_boton_fondo', '#f44336');
    $margen = get_option('arp_sticky_margen', '0');
    $sin_margen = get_option('arp_sticky_sin_margen', 'off');
    ?>
    <div style="margin-bottom: 20px;">
        <h4>Botón de Cerrar</h4>
        <div style="margin-top: 10px;">
            <label>Color del texto: <input type="color" name="arp_sticky_boton_color" value="<?php echo esc_attr($boton_color); ?>" style="width: 80px; height: 35px; vertical-align: middle;"></label>
        </div>
        <div style="margin-top: 10px;">
            <label>Color de fondo: <input type="color" name="arp_sticky_boton_fondo" value="<?php echo esc_attr($boton_fondo); ?>" style="width: 80px; height: 35px; vertical-align: middle;"></label>
        </div>
    </div>
    <div style="margin-bottom: 20px;">
        <h4>Márgenes del Contenedor</h4>
        <div style="margin-top: 10px;">
            <label>
                <input type="checkbox" name="arp_sticky_sin_margen" value="on" <?php checked($sin_margen, 'on'); ?>>
                Sin márgenes (el banner ocupará todo el espacio del contenedor)
            </label>
        </div>
        <div style="margin-top: 10px;">
            <label>Márgenes (px): <input type="number" name="arp_sticky_margen" value="<?php echo esc_attr($margen); ?>" min="0" style="width: 80px;" <?php echo $sin_margen === 'on' ? 'disabled' : ''; ?>></label>
            <p class="description">Márgenes internos del contenedor del anuncio. Se aplica solo si "Sin márgenes" está desactivado.</p>
        </div>
    </div>
    <?php
}


// =========================================================================
// 4. LÓGICA DEL SHORTCODE Y VISUALIZACIÓN - ACTUALIZADO
// =========================================================================
function arp_shortcode( $atts ) {
    $atts = shortcode_atts( array( 'lugar' => '' ), $atts, 'anuncio_rotativo' );
    if ( empty( $atts['lugar'] ) ) return '';

    $hoy = current_time('Y-m-d');
    $args = array(
        'post_type'      => 'anuncio', 'posts_per_page' => -1, 'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array('key' => '_arp_lugar', 'value' => $atts['lugar'], 'compare' => '='),
            // Las fechas son opcionales: si no se indicaron, el anuncio sigue vigente.
            array(
                'relation' => 'OR',
                array('key' => '_arp_fecha_inicio', 'compare' => 'NOT EXISTS'),
                array('key' => '_arp_fecha_inicio', 'value' => '', 'compare' => '='),
                array('key' => '_arp_fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE'),
            ),
            array(
                'relation' => 'OR',
                array('key' => '_arp_fecha_vencimiento', 'compare' => 'NOT EXISTS'),
                array('key' => '_arp_fecha_vencimiento', 'value' => '', 'compare' => '='),
                array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE'),
            ),
        ),
    );
    $query = new WP_Query( $args );
    $anuncios_validos = [];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();
            $programacion = get_post_meta($post_id, '_arp_programacion', true);
            if (empty($programacion) || (empty($programacion['dias']) && empty($programacion['hora_inicio']))) {
                $anuncios_validos[] = $post_id;
                continue;
            }
            $dia_actual = (int) current_time('N');
            $hora_actual = current_time('H:i');
            if (isset($programacion['dias']) && in_array($dia_actual, $programacion['dias']) && isset($programacion['hora_inicio']) && $hora_actual >= $programacion['hora_inicio'] && isset($programacion['hora_fin']) && $hora_actual <= $programacion['hora_fin']) {
                $anuncios_validos[] = $post_id;
            }
        }
        wp_reset_postdata();
    }
    
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
    
    foreach ($anuncios_validos as $anuncio_id) {
        $ad_type = get_post_meta($anuncio_id, '_arp_ad_type', true);
        $output .= "<div class='arp-slide'>";
        
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
    
    $hoy = current_time('Y-m-d');
    $args = array(
        'post_type'      => 'anuncio',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array('key' => '_arp_lugar', 'value' => $atts['lugar'], 'compare' => '='),
            // Las fechas son opcionales: si no se indicaron, el anuncio sigue vigente.
            array(
                'relation' => 'OR',
                array('key' => '_arp_fecha_inicio', 'compare' => 'NOT EXISTS'),
                array('key' => '_arp_fecha_inicio', 'value' => '', 'compare' => '='),
                array('key' => '_arp_fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE'),
            ),
            array(
                'relation' => 'OR',
                array('key' => '_arp_fecha_vencimiento', 'compare' => 'NOT EXISTS'),
                array('key' => '_arp_fecha_vencimiento', 'value' => '', 'compare' => '='),
                array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE'),
            ),
        ),
    );
    
    $query = new WP_Query($args);
    $anuncios_validos = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $programacion = get_post_meta($post_id, '_arp_programacion', true);
            if (empty($programacion) || (empty($programacion['dias']) && empty($programacion['hora_inicio']))) {
                $anuncios_validos[] = $post_id;
                continue;
            }
            $dia_actual = (int) current_time('N');
            $hora_actual = current_time('H:i');
            if (isset($programacion['dias']) && in_array($dia_actual, $programacion['dias']) && 
                isset($programacion['hora_inicio']) && $hora_actual >= $programacion['hora_inicio'] && 
                isset($programacion['hora_fin']) && $hora_actual <= $programacion['hora_fin']) {
                $anuncios_validos[] = $post_id;
            }
        }
        wp_reset_postdata();
    }
    
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
// 6. IMPLEMENTACIÓN DEL MODO ANUNCIO (POPUP, STICKY, INTERSTITIAL, IN-ARTICLE)
// =========================================================================
function arp_implementar_modo_anuncio() {
    // Verificar si el modo anuncio está activado
    $modo_activado = get_option('arp_modo_anuncio_activado', 'off');
    if ($modo_activado !== 'on') {
        return;
    }

    // Obtener configuración
    $tipos = get_option('arp_modo_anuncio_tipo', array('popup'));
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
        $hoy = current_time('Y-m-d');
        $args = array(
            'post_type'      => 'anuncio',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => '_arp_lugar', 'value' => $lugar, 'compare' => '='),
                // Las fechas son opcionales: si no se indicaron, el anuncio sigue vigente.
                array(
                    'relation' => 'OR',
                    array('key' => '_arp_fecha_inicio', 'compare' => 'NOT EXISTS'),
                    array('key' => '_arp_fecha_inicio', 'value' => '', 'compare' => '='),
                    array('key' => '_arp_fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE'),
                ),
                array(
                    'relation' => 'OR',
                    array('key' => '_arp_fecha_vencimiento', 'compare' => 'NOT EXISTS'),
                    array('key' => '_arp_fecha_vencimiento', 'value' => '', 'compare' => '='),
                    array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE'),
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $anuncios_validos = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $programacion = get_post_meta($post_id, '_arp_programacion', true);
                if (empty($programacion) || (empty($programacion['dias']) && empty($programacion['hora_inicio']))) {
                    $anuncios_validos[] = $post_id;
                    continue;
                }
                $dia_actual = (int) current_time('N');
                $hora_actual = current_time('H:i');
                if (isset($programacion['dias']) && in_array($dia_actual, $programacion['dias']) && 
                    isset($programacion['hora_inicio']) && $hora_actual >= $programacion['hora_inicio'] && 
                    isset($programacion['hora_fin']) && $hora_actual <= $programacion['hora_fin']) {
                    $anuncios_validos[] = $post_id;
                }
            }
            wp_reset_postdata();
        }
        
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
        $hoy = current_time('Y-m-d');
        $args = array(
            'post_type'      => 'anuncio',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => '_arp_lugar', 'value' => $lugar, 'compare' => '='),
                // Las fechas son opcionales: si no se indicaron, el anuncio sigue vigente.
                array(
                    'relation' => 'OR',
                    array('key' => '_arp_fecha_inicio', 'compare' => 'NOT EXISTS'),
                    array('key' => '_arp_fecha_inicio', 'value' => '', 'compare' => '='),
                    array('key' => '_arp_fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE'),
                ),
                array(
                    'relation' => 'OR',
                    array('key' => '_arp_fecha_vencimiento', 'compare' => 'NOT EXISTS'),
                    array('key' => '_arp_fecha_vencimiento', 'value' => '', 'compare' => '='),
                    array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE'),
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $anuncios_validos = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $programacion = get_post_meta($post_id, '_arp_programacion', true);
                if (empty($programacion) || (empty($programacion['dias']) && empty($programacion['hora_inicio']))) {
                    $anuncios_validos[] = $post_id;
                    continue;
                }
                $dia_actual = (int) current_time('N');
                $hora_actual = current_time('H:i');
                if (isset($programacion['dias']) && in_array($dia_actual, $programacion['dias']) && 
                    isset($programacion['hora_inicio']) && $hora_actual >= $programacion['hora_inicio'] && 
                    isset($programacion['hora_fin']) && $hora_actual <= $programacion['hora_fin']) {
                    $anuncios_validos[] = $post_id;
                }
            }
            wp_reset_postdata();
        }
        
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
        $hoy = current_time('Y-m-d');
        $args = array(
            'post_type'      => 'anuncio',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'AND',
                array('key' => '_arp_lugar', 'value' => $lugar, 'compare' => '='),
                // Las fechas son opcionales: si no se indicaron, el anuncio sigue vigente.
                array(
                    'relation' => 'OR',
                    array('key' => '_arp_fecha_inicio', 'compare' => 'NOT EXISTS'),
                    array('key' => '_arp_fecha_inicio', 'value' => '', 'compare' => '='),
                    array('key' => '_arp_fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE'),
                ),
                array(
                    'relation' => 'OR',
                    array('key' => '_arp_fecha_vencimiento', 'compare' => 'NOT EXISTS'),
                    array('key' => '_arp_fecha_vencimiento', 'value' => '', 'compare' => '='),
                    array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE'),
                ),
            ),
        );
        
        $query = new WP_Query($args);
        $anuncios_validos = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $programacion = get_post_meta($post_id, '_arp_programacion', true);
                if (empty($programacion) || (empty($programacion['dias']) && empty($programacion['hora_inicio']))) {
                    $anuncios_validos[] = $post_id;
                    continue;
                }
                $dia_actual = (int) current_time('N');
                $hora_actual = current_time('H:i');
                if (isset($programacion['dias']) && in_array($dia_actual, $programacion['dias']) && 
                    isset($programacion['hora_inicio']) && $hora_actual >= $programacion['hora_inicio'] && 
                    isset($programacion['hora_fin']) && $hora_actual <= $programacion['hora_fin']) {
                    $anuncios_validos[] = $post_id;
                }
            }
            wp_reset_postdata();
        }
        
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
    $modo_activado = get_option('arp_modo_anuncio_activado', 'off');
    if ($modo_activado !== 'on') {
        return $content;
    }
    
    // Verificar si el modo inarticle está activado
    $tipos = get_option('arp_modo_anuncio_tipo', array('popup'));
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
    
    // Obtener anuncios válidos para el lugar seleccionado
    $hoy = current_time('Y-m-d');
    $args = array(
        'post_type'      => 'anuncio',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array('key' => '_arp_lugar', 'value' => $lugar, 'compare' => '='),
            // Las fechas son opcionales: si no se indicaron, el anuncio sigue vigente.
            array(
                'relation' => 'OR',
                array('key' => '_arp_fecha_inicio', 'compare' => 'NOT EXISTS'),
                array('key' => '_arp_fecha_inicio', 'value' => '', 'compare' => '='),
                array('key' => '_arp_fecha_inicio', 'value' => $hoy, 'compare' => '<=', 'type' => 'DATE'),
            ),
            array(
                'relation' => 'OR',
                array('key' => '_arp_fecha_vencimiento', 'compare' => 'NOT EXISTS'),
                array('key' => '_arp_fecha_vencimiento', 'value' => '', 'compare' => '='),
                array('key' => '_arp_fecha_vencimiento', 'value' => $hoy, 'compare' => '>=', 'type' => 'DATE'),
            ),
        ),
    );
    
    $query = new WP_Query($args);
    $anuncios_validos = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $programacion = get_post_meta($post_id, '_arp_programacion', true);
            if (empty($programacion) || (empty($programacion['dias']) && empty($programacion['hora_inicio']))) {
                $anuncios_validos[] = $post_id;
                continue;
            }
            $dia_actual = (int) current_time('N');
            $hora_actual = current_time('H:i');
            if (isset($programacion['dias']) && in_array($dia_actual, $programacion['dias']) && 
                isset($programacion['hora_inicio']) && $hora_actual >= $programacion['hora_inicio'] && 
                isset($programacion['hora_fin']) && $hora_actual <= $programacion['hora_fin']) {
                $anuncios_validos[] = $post_id;
            }
        }
        wp_reset_postdata();
    }
    
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
// 8. SCRIPTS Y ESTILOS (JAVASCRIPT Y CSS) - ACTUALIZADO CON IN-ARTICLE
// =========================================================================
function arp_agregar_scripts_y_estilos() {
    // Estilos base existentes
    $css = "
    .arp-container { position: relative; overflow: hidden; display: block; line-height: 0; }
    .arp-container .arp-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.5s ease-in-out; display: flex; align-items: center; justify-content: center; }
    .arp-container .arp-slide.active { opacity: 1; z-index: 1; position: relative; }
    .arp-container .arp-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .arp-container .arp-slide iframe, .arp-container .arp-slide ins { max-width: 100%; max-height: 100%; }
    
    /* Estilos para video */
    .arp-video-container { width: 100%; height: 100%; position: relative; }
    .arp-video-container iframe { width: 100%; height: 100%; border: none; }
    
    /* Estilos para sticky footer */
    .arp-sticky-footer { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); background: white; border-top: 2px solid #ccc; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 99997; max-width: 100%; }
    .arp-sticky-footer img { width: 100%; height: 100%; object-fit: contain; }
    
    /* Estilos para intersticial */
    #arp-modointerstitial-overlay { display: none !important; }
    #arp-modointerstitial-overlay.active { display: block !important; }
    
    /* Estilos para in-article */
    .arp-inarticle-ad { margin: 30px auto; text-align: center; max-width: 100%; }
    .arp-inarticle-container { width: 100%; position: relative; overflow: hidden; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
    .arp-inarticle-slide img { width: 100%; height: 100%; object-fit: contain; }
    ";
    
    wp_register_style( 'arp-inline-css', false );
    wp_enqueue_style( 'arp-inline-css' );
    wp_add_inline_style( 'arp-inline-css', $css );

    // JavaScript base existente
    $js = "
    document.addEventListener('DOMContentLoaded', function() {
        const containers = document.querySelectorAll('.arp-container');
        containers.forEach(function(container) {
            const slides = container.querySelectorAll('.arp-slide');
            if (slides.length > 1) {
                let currentSlide = 0;
                const rotationSpeed = parseInt(container.getAttribute('data-rotation-speed'), 10) || 5000;
                slides[0].classList.add('active');
                setInterval(function() {
                    slides[currentSlide].classList.remove('active');
                    currentSlide = (currentSlide + 1) % slides.length;
                    slides[currentSlide].classList.add('active');
                }, rotationSpeed);
            } else if (slides.length === 1) {
                slides[0].classList.add('active');
            }
        });
    });
    ";
    
    wp_register_script( 'arp-inline-js', false, [], null, true );
    wp_enqueue_script( 'arp-inline-js' );
    wp_add_inline_script( 'arp-inline-js', $js );
    
    // JavaScript para el modo anuncio (popup/sticky/interstitial/inarticle)
    $modo_activado = get_option('arp_modo_anuncio_activado', 'off');
    if ($modo_activado === 'on') {
        $tipos = get_option('arp_modo_anuncio_tipo', array('popup'));
        if (!is_array($tipos)) {
            $tipos = array($tipos);
        }
        $delay = get_option('arp_modo_delay', '3000');
        $duracion = get_option('arp_modo_duracion', '10000');
        
        $modo_js = "
        document.addEventListener('DOMContentLoaded', function() {";
        
        if (in_array('popup', $tipos)) {
            $modo_js .= "
            // POPUP - Mostrar en cada visita/recarga
            const popupOverlay = document.getElementById('arp-modopopup-overlay');
            const popupClose = document.getElementById('arp-modopopup-close');
            const popupSlides = document.querySelectorAll('.arp-modopopup-slide');
            
            if (popupOverlay) {
                setTimeout(function() {
                    popupOverlay.style.display = 'flex';
                    
                    // Rotación de slides
                    if (popupSlides.length > 1) {
                        let currentSlide = 0;
                        const rotationInterval = setInterval(function() {
                            popupSlides[currentSlide].style.display = 'none';
                            currentSlide = (currentSlide + 1) % popupSlides.length;
                            popupSlides[currentSlide].style.display = 'block';
                        }, 3000);
                        
                        // Detener rotación cuando se cierra
                        popupClose.addEventListener('click', function() {
                            clearInterval(rotationInterval);
                        });
                    }
                    
                    // Cerrar popup
                    popupClose.addEventListener('click', function() {
                        popupOverlay.style.display = 'none';
                    });
                    
                    // Cerrar al hacer clic fuera
                    popupOverlay.addEventListener('click', function(e) {
                        if (e.target.id === 'arp-modopopup-overlay') {
                            popupOverlay.style.display = 'none';
                        }
                    });
                    
                    // Auto cerrar después de la duración
                    setTimeout(function() {
                        if (popupOverlay.style.display !== 'none') {
                            popupOverlay.style.display = 'none';
                        }
                    }, " . esc_js($duracion) . ");
                }, " . esc_js($delay) . ");
            }";
        }
        
        if (in_array('sticky', $tipos)) {
            $sticky_duracion = get_option('arp_modo_sticky_duracion', '0');
            $modo_js .= "
            // STICKY FOOTER - Mostrar en cada visita/recarga
            const stickyContainer = document.getElementById('arp-modosticky-container');
            const stickyClose = document.getElementById('arp-modosticky-close');
            const stickySlides = document.querySelectorAll('.arp-modosticky-slide');
            
            if (stickyContainer) {
                setTimeout(function() {
                    stickyContainer.style.display = 'block';
                    
                    // Rotación de slides
                    let rotationInterval = null;
                    if (stickySlides.length > 1) {
                        let currentSlide = 0;
                        rotationInterval = setInterval(function() {
                            stickySlides[currentSlide].style.display = 'none';
                            currentSlide = (currentSlide + 1) % stickySlides.length;
                            stickySlides[currentSlide].style.display = 'block';
                        }, 3000);
                    }
                    
                    // Función para cerrar sticky
                    function cerrarSticky() {
                        if (rotationInterval) {
                            clearInterval(rotationInterval);
                        }
                        stickyContainer.style.display = 'none';
                    }
                    
                    // Cerrar sticky al hacer clic en el botón
                    stickyClose.addEventListener('click', cerrarSticky);
                    
                    // Auto cerrar después de la duración configurada (si es mayor a 0)
                    var stickyDuracion = " . intval($sticky_duracion) . ";
                    if (stickyDuracion > 0) {
                        setTimeout(function() {
                            if (stickyContainer.style.display !== 'none') {
                                cerrarSticky();
                            }
                        }, stickyDuracion);
                    }
                }, " . esc_js($delay) . ");
            }";
        }
        
        if (in_array('interstitial', $tipos)) {
            $modo_js .= "
            // INTERSTITIAL AD - Mostrar en cada visita/recarga
            const interstitialOverlay = document.getElementById('arp-modointerstitial-overlay');
            const interstitialClose = document.getElementById('arp-modointerstitial-close');
            const interstitialSlides = document.querySelectorAll('.arp-modointerstitial-slide');
            
            if (interstitialOverlay) {
                // Bloquear el scroll del body
                document.body.style.overflow = 'hidden';
                
                setTimeout(function() {
                    interstitialOverlay.style.display = 'block';
                    interstitialOverlay.classList.add('active');
                    
                    // Rotación de slides
                    if (interstitialSlides.length > 1) {
                        let currentSlide = 0;
                        const rotationInterval = setInterval(function() {
                            interstitialSlides[currentSlide].style.display = 'none';
                            currentSlide = (currentSlide + 1) % interstitialSlides.length;
                            interstitialSlides[currentSlide].style.display = 'block';
                        }, 3000);
                        
                        // Detener rotación cuando se cierra
                        interstitialClose.addEventListener('click', function() {
                            clearInterval(rotationInterval);
                        });
                    }
                    
                    // Cerrar interstitial
                    interstitialClose.addEventListener('click', function() {
                        interstitialOverlay.style.display = 'none';
                        interstitialOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                }, " . esc_js($delay) . ");
            }";
        }
        
        // JavaScript para rotación de anuncios in-article
        $modo_js .= "
            // Rotación para anuncios in-article
            const inarticleContainers = document.querySelectorAll('.arp-inarticle-rotativo');
            inarticleContainers.forEach(function(container) {
                const slides = container.querySelectorAll('.arp-inarticle-slide');
                if (slides.length > 1) {
                    let currentSlide = 0;
                    setInterval(function() {
                        slides[currentSlide].style.display = 'none';
                        currentSlide = (currentSlide + 1) % slides.length;
                        slides[currentSlide].style.display = 'block';
                    }, 3000);
                }
            });
        ";
        
        // JavaScript para rotación y cierre del sticky del shortcode
        $sticky_duracion_shortcode = get_option('arp_modo_sticky_duracion', '0');
        $modo_js .= "
            // Rotación para sticky del shortcode
            const stickyShortcode = document.getElementById('arp-sticky-shortcode');
            if (stickyShortcode) {
                const stickyShortcodeSlides = stickyShortcode.querySelectorAll('.arp-sticky-slide');
                const stickyShortcodeClose = stickyShortcode.querySelector('.arp-sticky-close');
                
                let stickyRotationInterval = null;
                if (stickyShortcodeSlides.length > 1) {
                    let currentStickySlide = 0;
                    stickyRotationInterval = setInterval(function() {
                        stickyShortcodeSlides[currentStickySlide].style.display = 'none';
                        currentStickySlide = (currentStickySlide + 1) % stickyShortcodeSlides.length;
                        stickyShortcodeSlides[currentStickySlide].style.display = 'block';
                    }, 3000);
                }
                
                // Función para cerrar sticky del shortcode
                function cerrarStickyShortcode() {
                    if (stickyRotationInterval) {
                        clearInterval(stickyRotationInterval);
                    }
                    stickyShortcode.style.display = 'none';
                }
                
                // Cerrar sticky del shortcode al hacer clic
                if (stickyShortcodeClose) {
                    stickyShortcodeClose.addEventListener('click', cerrarStickyShortcode);
                }
                
                // Auto cerrar después de la duración configurada (si es mayor a 0)
                var stickyDuracionShortcode = " . intval($sticky_duracion_shortcode) . ";
                if (stickyDuracionShortcode > 0) {
                    setTimeout(function() {
                        if (stickyShortcode.style.display !== 'none') {
                            cerrarStickyShortcode();
                        }
                    }, stickyDuracionShortcode);
                }
            }
        });";
        
        wp_add_inline_script('arp-inline-js', $modo_js);
    }
}
add_action( 'wp_enqueue_scripts', 'arp_agregar_scripts_y_estilos' );


// =========================================================================
// 9. CRON JOB PARA DESPUBLICAR ANUNCIOS VENCIDOS (Sin cambios)
// =========================================================================
function arp_programar_verificacion_vencidos() {
    if ( ! wp_next_scheduled( 'arp_verificar_anuncios_vencidos_hook' ) ) {
        wp_schedule_event( time(), 'daily', 'arp_verificar_anuncios_vencidos_hook' );
    }
}
register_activation_hook( __FILE__, 'arp_programar_verificacion_vencidos' );

function arp_desactivar_programacion() {
    wp_clear_scheduled_hook( 'arp_verificar_anuncios_vencidos_hook' );
}
register_deactivation_hook( __FILE__, 'arp_desactivar_programacion' );

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