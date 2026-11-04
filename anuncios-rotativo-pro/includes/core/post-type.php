<?php
if (!defined('ABSPATH')) {
    exit;
}

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
