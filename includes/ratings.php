<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sistema de valoración de usuarios (1–10)
 * Almacenado en la tabla wp_fightrank_ratings
 */

function fightrank_get_user_rating( $fight_id, $user_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'fightrank_ratings';
    return $wpdb->get_var( $wpdb->prepare(
        "SELECT rating FROM $table WHERE fight_id = %d AND user_id = %d",
        $fight_id, $user_id
    ));
}

function fightrank_save_user_rating( $fight_id, $user_id, $rating ) {
    global $wpdb;
    $table  = $wpdb->prefix . 'fightrank_ratings';
    $rating = max( 1, min( 10, (int) $rating ) );

    $existing = fightrank_get_user_rating( $fight_id, $user_id );

    if ( $existing !== null ) {
        $wpdb->update(
            $table,
            [ 'rating' => $rating ],
            [ 'fight_id' => $fight_id, 'user_id' => $user_id ],
            [ '%d' ], [ '%d', '%d' ]
        );
    } else {
        $wpdb->insert(
            $table,
            [ 'fight_id' => $fight_id, 'user_id' => $user_id, 'rating' => $rating ],
            [ '%d', '%d', '%d' ]
        );
    }

    return fightrank_get_rating_stats( $fight_id );
}

function fightrank_get_rating_stats( $fight_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'fightrank_ratings';
    $row   = $wpdb->get_row( $wpdb->prepare(
        "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM $table WHERE fight_id = %d",
        $fight_id
    ));
    return [
        'avg'   => $row ? round( (float) $row->avg_rating, 1 ) : null,
        'total' => $row ? (int) $row->total : 0,
    ];
}

/* Distribución de votos por puntuación (para gráfico) */
function fightrank_get_rating_distribution( $fight_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'fightrank_ratings';
    $rows  = $wpdb->get_results( $wpdb->prepare(
        "SELECT rating, COUNT(*) AS cnt FROM $table WHERE fight_id = %d GROUP BY rating ORDER BY rating",
        $fight_id
    ), ARRAY_A );

    $dist = array_fill( 1, 10, 0 );
    foreach ( $rows as $r ) {
        $dist[ (int) $r['rating'] ] = (int) $r['cnt'];
    }
    return $dist;
}
