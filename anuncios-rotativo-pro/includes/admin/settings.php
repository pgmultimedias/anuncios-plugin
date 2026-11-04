<?php
if (!defined('ABSPATH')) {
    exit;
}

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
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_anuncio_activado', array('default' => 'on') );
    register_setting( 'arp_modo_anuncio_grupo', 'arp_modo_anuncio_tipo', array('default' => array('popup', 'sticky', 'interstitial', 'inarticle')) );
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
    $activado = get_option('arp_modo_anuncio_activado', 'on');
    ?>
    <label>
        <input type="checkbox" name="arp_modo_anuncio_activado" value="on" <?php checked($activado, 'on'); ?>>
        Activar el modo anuncio en el sitio
    </label>
    <p class="description">Cuando está activado, se mostrará el anuncio según el tipo seleccionado.</p>
    <?php
}

function arp_campo_modo_tipo_callback() {
    $tipos = arp_obtener_tipos_modo_anuncio();
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
    <p class="description">Puede seleccionar varios modos. Cada lugar especial (Pop-up, Sticky, Interstitial o In-Article) también activa automáticamente su modo cuando tiene un anuncio publicado.</p>
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
