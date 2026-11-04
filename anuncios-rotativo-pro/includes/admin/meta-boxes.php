<?php
if (!defined('ABSPATH')) {
    exit;
}

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
