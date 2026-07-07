<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'fightrank_register_post_types' );
function fightrank_register_post_types() {

    /* ---- PELEAS ---- */
    register_post_type( 'fight', [
        'labels' => [
            'name'               => 'Peleas',
            'singular_name'      => 'Pelea',
            'add_new'            => 'Añadir pelea',
            'add_new_item'       => 'Añadir nueva pelea',
            'edit_item'          => 'Editar pelea',
            'search_items'       => 'Buscar peleas',
            'not_found'          => 'No se encontraron peleas',
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => [ 'slug' => 'peleas' ],
        'menu_icon'    => 'dashicons-awards',
        'supports'     => [ 'title', 'thumbnail', 'excerpt' ],
        'show_in_rest' => true,
    ]);

    /* ---- PELEADORES ---- */
    register_post_type( 'fighter', [
        'labels' => [
            'name'               => 'Peleadores',
            'singular_name'      => 'Peleador',
            'add_new'            => 'Añadir peleador',
            'add_new_item'       => 'Añadir nuevo peleador',
            'edit_item'          => 'Editar peleador',
            'search_items'       => 'Buscar peleadores',
            'not_found'          => 'No se encontraron peleadores',
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => [ 'slug' => 'peleadores' ],
        'menu_icon'    => 'dashicons-businessman',
        'supports'     => [ 'title', 'thumbnail', 'excerpt' ],
        'show_in_rest' => true,
    ]);

    /* ---- EVENTOS ---- */
    register_post_type( 'ufc_event', [
        'labels' => [
            'name'               => 'Eventos',
            'singular_name'      => 'Evento',
            'add_new'            => 'Añadir evento',
            'add_new_item'       => 'Añadir nuevo evento',
            'edit_item'          => 'Editar evento',
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => [ 'slug' => 'eventos' ],
        'menu_icon'    => 'dashicons-calendar-alt',
        'supports'     => [ 'title', 'thumbnail', 'excerpt' ],
        'show_in_rest' => true,
    ]);

    /* ---- TAXONOMÍAS ---- */
    register_taxonomy( 'weight_class', [ 'fight', 'fighter' ], [
        'label'        => 'División de peso',
        'rewrite'      => [ 'slug' => 'division' ],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);

    register_taxonomy( 'fight_method', 'fight', [
        'label'        => 'Método de victoria',
        'rewrite'      => [ 'slug' => 'metodo' ],
        'hierarchical' => true,
        'show_in_rest' => true,
    ]);
}
