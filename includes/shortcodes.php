<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [fightrank_fights limit="10" weight_class="Welterweight"]
 * Muestra una lista de peleas con su puntuación.
 */
add_shortcode( 'fightrank_fights', function( $atts ) {
    $atts = shortcode_atts([
        'limit'        => 10,
        'weight_class' => '',
        'method'       => '',
    ], $atts );

    $args = [
        'post_type'      => 'fight',
        'posts_per_page' => (int) $atts['limit'],
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'fr_fight_date',
        'order'          => 'DESC',
    ];

    if ( $atts['weight_class'] ) {
        $args['tax_query'] = [[
            'taxonomy' => 'weight_class',
            'field'    => 'name',
            'terms'    => $atts['weight_class'],
        ]];
    }

    $fights = new WP_Query( $args );
    if ( ! $fights->have_posts() ) return '<p>No se encontraron peleas.</p>';

    ob_start();
    echo '<div class="fr-fight-list">';
    while ( $fights->have_posts() ) {
        $fights->the_post();
        $id    = get_the_ID();
        $data  = fightrank_get_fight_data( $id );
        $score = $data['auto_score'];
        $label = fightrank_score_label( $score !== '' ? (float) $score : null );
        $stats = fightrank_get_rating_stats( $id );

        $f1 = $data['fighter1_id'] ? get_post( $data['fighter1_id'] ) : null;
        $f2 = $data['fighter2_id'] ? get_post( $data['fighter2_id'] ) : null;
        ?>
        <article class="fr-fight-card">
            <a href="<?= get_permalink() ?>" class="fr-fight-card__link">
                <div class="fr-fight-card__fighters">
                    <span class="fr-fighter-name <?= $data['winner'] === 'fighter1' ? 'fr-fighter-name--winner' : '' ?>">
                        <?= $f1 ? esc_html( $f1->post_title ) : '—' ?>
                    </span>
                    <span class="fr-vs">VS</span>
                    <span class="fr-fighter-name <?= $data['winner'] === 'fighter2' ? 'fr-fighter-name--winner' : '' ?>">
                        <?= $f2 ? esc_html( $f2->post_title ) : '—' ?>
                    </span>
                </div>
                <div class="fr-fight-card__meta">
                    <span class="fr-method"><?= esc_html( $data['method'] ?: '—' ) ?></span>
                    <span class="fr-round">R<?= esc_html( $data['round'] ?: '—' ) ?></span>
                    <?php if ( $data['title_fight'] === '1' ) : ?>
                        <span class="fr-badge fr-badge--title">Pelea por título</span>
                    <?php endif; ?>
                </div>
                <div class="fr-fight-card__scores">
                    <div class="fr-score-chip <?= $label['class'] ?>">
                        <span class="fr-score-chip__value"><?= $score !== '' ? $score : '—' ?></span>
                        <span class="fr-score-chip__label"><?= $label['label'] ?></span>
                    </div>
                    <?php if ( $stats['total'] > 0 ) : ?>
                    <div class="fr-user-score-chip">
                        <span class="fr-star">&#9733;</span>
                        <span><?= $stats['avg'] ?></span>
                        <small>(<?= $stats['total'] ?> votos)</small>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
        </article>
        <?php
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
});

/**
 * [fightrank_top_fights limit="5"]
 * Top peleas por puntuación automática.
 */
add_shortcode( 'fightrank_top_fights', function( $atts ) {
    $atts = shortcode_atts([ 'limit' => 5 ], $atts );
    $fights = new WP_Query([
        'post_type'      => 'fight',
        'posts_per_page' => (int) $atts['limit'],
        'meta_key'       => 'fr_auto_score',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => [[ 'key' => 'fr_auto_score', 'compare' => 'EXISTS' ]],
    ]);

    if ( ! $fights->have_posts() ) return '<p>No hay peleas puntuadas aún.</p>';

    ob_start();
    $i = 1;
    echo '<ol class="fr-top-list">';
    while ( $fights->have_posts() ) {
        $fights->the_post();
        $id   = get_the_ID();
        $data = fightrank_get_fight_data( $id );
        $f1   = $data['fighter1_id'] ? get_post( $data['fighter1_id'] ) : null;
        $f2   = $data['fighter2_id'] ? get_post( $data['fighter2_id'] ) : null;
        ?>
        <li class="fr-top-list__item">
            <span class="fr-top-list__rank">#<?= $i ?></span>
            <a href="<?= get_permalink() ?>" class="fr-top-list__fight">
                <?= $f1 ? esc_html( $f1->post_title ) : '—' ?> vs <?= $f2 ? esc_html( $f2->post_title ) : '—' ?>
            </a>
            <span class="fr-top-list__score"><?= esc_html( $data['auto_score'] ) ?></span>
        </li>
        <?php
        $i++;
    }
    echo '</ol>';
    wp_reset_postdata();
    return ob_get_clean();
});

/**
 * [fightrank_rankings limit="10" weight_class=""]
 * Tabla mini de top peleadores por victorias.
 */
add_shortcode( 'fightrank_rankings', function( $atts ) {
    $atts = shortcode_atts([ 'limit' => 10, 'weight_class' => '' ], $atts );

    $args = [
        'post_type'      => 'fighter',
        'posts_per_page' => (int) $atts['limit'],
        'meta_key'       => 'fr_wins',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ];

    if ( $atts['weight_class'] ) {
        $args['tax_query'] = [[
            'taxonomy' => 'weight_class',
            'field'    => 'name',
            'terms'    => $atts['weight_class'],
        ]];
    }

    $fighters = get_posts( $args );
    if ( empty( $fighters ) ) return '<p>No hay peleadores registrados.</p>';

    ob_start();
    echo '<div class="fr-rankings-mini">';
    foreach ( $fighters as $i => $fighter ) :
        $fd = fightrank_get_fighter_data( $fighter->ID );
        ?>
        <a href="<?= get_permalink( $fighter->ID ) ?>" class="fr-rankings-mini__row">
            <span class="fr-rankings-mini__rank">#<?= $i + 1 ?></span>
            <?php if ( has_post_thumbnail( $fighter->ID ) ) : ?>
                <div class="fr-rankings-mini__photo"><?= get_the_post_thumbnail( $fighter->ID, [ 40, 40 ] ) ?></div>
            <?php else : ?>
                <div class="fr-rankings-mini__photo fr-rankings-mini__photo--ph">
                    <?= strtoupper( substr( $fighter->post_title, 0, 1 ) ) ?>
                </div>
            <?php endif; ?>
            <span class="fr-rankings-mini__name">
                <?= esc_html( $fighter->post_title ) ?>
                <?php if ( $fd['is_champion'] === '1' ) : ?>
                    <span class="fr-champion-badge fr-champion-badge--sm">C</span>
                <?php endif; ?>
            </span>
            <span class="fr-rankings-mini__record"><?= (int) $fd['wins'] ?>-<?= (int) $fd['losses'] ?></span>
        </a>
        <?php
    endforeach;
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
});
