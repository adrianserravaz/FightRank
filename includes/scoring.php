<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Algoritmo de puntuación automática FightRank (escala 0–10)
 *
 * Factores considerados:
 *  1. Método de victoria  → base
 *  2. Round en el que acabó → modificador (antes = mejor)
 *  3. Knockdowns totales  → bonus de emoción
 *  4. Diferencial de golpes significativos → bonus de dominio
 *  5. Intentos de sumisión totales → bonus técnico
 *  6. Pelea por título    → bonus de importancia
 */
function fightrank_calculate_score( $post_id ) {
    $d = fightrank_get_fight_data( $post_id );

    /* Sin resultado aún */
    if ( empty( $d['method'] ) || empty( $d['winner'] ) || $d['winner'] === '' ) {
        return null;
    }

    /* 1. Puntuación base según método */
    $base = match( $d['method'] ) {
        'KO/TKO'      => 10.0,
        'Submission'  => 9.5,
        'Decision-U'  => 8.5,
        'Decision-M'  => 8.0,
        'Decision-S'  => 7.5,
        'DQ'          => 6.0,
        default       => 7.0,
    };

    /* 2. Modificador por round (cuanto antes acabe, más emocionante) */
    $round = (int) $d['round'];
    $round_mod = match( true ) {
        $round === 1 => 1.0,
        $round === 2 => 0.5,
        $round === 3 => 0.0,
        $round === 4 => -0.2,
        $round >= 5  => -0.4,
        default      => 0.0,
    };

    /* Decisiones no tienen bonus de round (siempre van al final) */
    if ( str_starts_with( $d['method'], 'Decision' ) ) {
        $round_mod = 0.0;
    }

    /* 3. Bonus por knockdowns (emoción) */
    $total_kd   = (int) $d['kd_f1'] + (int) $d['kd_f2'];
    $kd_bonus   = min( 1.0, $total_kd * 0.25 );

    /* 4. Bonus por diferencial de golpes significativos */
    $diff_strikes = abs( (int) $d['sig_strikes_f1'] - (int) $d['sig_strikes_f2'] );
    $strike_bonus = match( true ) {
        $diff_strikes > 50 => 0.3,
        $diff_strikes > 25 => 0.2,
        default            => 0.0,
    };

    /* 5. Bonus técnico por intentos de sumisión */
    $total_subs = (int) $d['sub_attempts_f1'] + (int) $d['sub_attempts_f2'];
    $sub_bonus  = min( 0.5, $total_subs * 0.1 );

    /* 6. Bonus pelea por título */
    $title_bonus = ( $d['title_fight'] === '1' ) ? 0.3 : 0.0;

    /* Empate o NC reducen la nota */
    $result_penalty = 0.0;
    if ( $d['winner'] === 'draw' ) $result_penalty = -0.5;
    if ( $d['winner'] === 'nc'   ) $result_penalty = -1.0;

    $score = $base + $round_mod + $kd_bonus + $strike_bonus + $sub_bonus + $title_bonus + $result_penalty;

    return round( min( 10.0, max( 0.0, $score ) ), 1 );
}

/* Devuelve el desglose del score para mostrarlo en frontend */
function fightrank_score_breakdown( $post_id ) {
    $d = fightrank_get_fight_data( $post_id );
    if ( empty( $d['method'] ) ) return [];

    $base = match( $d['method'] ) {
        'KO/TKO'     => 10.0, 'Submission' => 9.5,
        'Decision-U' => 8.5,  'Decision-M' => 8.0,
        'Decision-S' => 7.5,  'DQ'         => 6.0,
        default      => 7.0,
    };
    $round = (int) $d['round'];
    $round_mod = ( str_starts_with( $d['method'], 'Decision' ) ) ? 0.0 : match( true ) {
        $round === 1 => 1.0, $round === 2 => 0.5,
        $round === 3 => 0.0, $round === 4 => -0.2,
        $round >= 5  => -0.4, default => 0.0,
    };
    $total_kd   = (int) $d['kd_f1'] + (int) $d['kd_f2'];
    $diff_str   = abs( (int) $d['sig_strikes_f1'] - (int) $d['sig_strikes_f2'] );
    $total_subs = (int) $d['sub_attempts_f1'] + (int) $d['sub_attempts_f2'];

    return [
        'Método de victoria'        => $base,
        'Bonus por round'           => $round_mod,
        'Knockdowns'                => min( 1.0, $total_kd * 0.25 ),
        'Dominio en golpes'         => $diff_str > 50 ? 0.3 : ( $diff_str > 25 ? 0.2 : 0.0 ),
        'Intentos de sumisión'      => min( 0.5, $total_subs * 0.1 ),
        'Pelea por título'          => $d['title_fight'] === '1' ? 0.3 : 0.0,
    ];
}

/* Devuelve label y color CSS para una puntuación */
function fightrank_score_label( $score ) {
    if ( $score === null ) return [ 'label' => 'Sin puntuar', 'class' => 'fr-score--none' ];
    return match( true ) {
        $score >= 9.5 => [ 'label' => 'Obra maestra',   'class' => 'fr-score--masterpiece' ],
        $score >= 9.0 => [ 'label' => 'Clásico',        'class' => 'fr-score--classic' ],
        $score >= 8.0 => [ 'label' => 'Excelente',      'class' => 'fr-score--excellent' ],
        $score >= 7.0 => [ 'label' => 'Muy buena',      'class' => 'fr-score--great' ],
        $score >= 6.0 => [ 'label' => 'Buena',          'class' => 'fr-score--good' ],
        $score >= 5.0 => [ 'label' => 'Correcta',       'class' => 'fr-score--average' ],
        default       => [ 'label' => 'Por debajo de la media', 'class' => 'fr-score--below' ],
    };
}
