<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_fightrank_rate',         'fightrank_ajax_rate' );
add_action( 'wp_ajax_nopriv_fightrank_rate',  'fightrank_ajax_rate_nopriv' );

function fightrank_ajax_rate_nopriv() {
    wp_send_json_error([ 'message' => 'Debes iniciar sesión para valorar.' ]);
}

function fightrank_ajax_rate() {
    check_ajax_referer( 'fightrank_rating_nonce', 'nonce' );

    $fight_id = absint( $_POST['fight_id'] ?? 0 );
    $rating   = absint( $_POST['rating']   ?? 0 );
    $user_id  = get_current_user_id();

    if ( ! $fight_id || $rating < 1 || $rating > 10 ) {
        wp_send_json_error([ 'message' => 'Datos inválidos.' ]);
    }

    if ( get_post_type( $fight_id ) !== 'fight' ) {
        wp_send_json_error([ 'message' => 'Pelea no encontrada.' ]);
    }

    $stats = fightrank_save_user_rating( $fight_id, $user_id, $rating );

    wp_send_json_success([
        'avg'          => $stats['avg'],
        'total'        => $stats['total'],
        'user_rating'  => $rating,
        'distribution' => fightrank_get_rating_distribution( $fight_id ),
    ]);
}
