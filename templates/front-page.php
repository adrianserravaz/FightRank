<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$n_fights   = wp_count_posts( 'fight' )->publish   ?? 0;
$n_fighters = wp_count_posts( 'fighter' )->publish ?? 0;
$n_events   = wp_count_posts( 'ufc_event' )->publish ?? 0;

$top_fights = new WP_Query([
    'post_type'      => 'fight',
    'posts_per_page' => 10,
    'meta_key'       => 'fr_auto_score',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
    'meta_query'     => [[ 'key' => 'fr_auto_score', 'compare' => 'EXISTS' ]],
]);

$top_fighters = get_posts([
    'post_type'      => 'fighter',
    'numberposts'    => 8,
    'meta_key'       => 'fr_wins',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
]);

$latest_events = get_posts([
    'post_type'   => 'ufc_event',
    'numberposts' => 4,
    'meta_key'    => 'fr_event_date',
    'orderby'     => 'meta_value',
    'order'       => 'DESC',
]);
?>

<nav class="fr-custom-navbar">
    <a class="fr-logo-link" href="<?php echo home_url(); ?>">FightRank</a>

    <div class="fr-nav-links">
        <a href="<?php echo home_url(); ?>">Inicio</a>
        <a href="<?php echo home_url('/peleas'); ?>">Peleas</a>
        <a href="<?php echo home_url('/peleadores'); ?>">Peleadores</a>
        <a href="<?php echo home_url('/eventos'); ?>">Eventos</a>
    </div>
</nav>

</nav>

<style>
.fr-custom-navbar{
    width:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:18px 48px;
    background:#111;
    border-bottom:1px solid rgba(200,16,46,0.35);
    position:sticky;
    top:32px;
    z-index:9999;
    box-sizing:border-box;
}

.fr-logo-link{
    color:#fff;
    font-size:22px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:1px;
    text-decoration:none;
}

.fr-nav-links{
    display:flex;
    gap:28px;
    position:absolute;
    right:48px;
}

.fr-nav-links a{
    color:#fff;
    text-decoration:none;
    font-weight:700;
    text-transform:uppercase;
    font-size:14px;
}
</style>

<div class="fr-home">

<div class="fr-home">

    <!-- ====== HERO ====== -->
<div class="fr-home-hero__inner">
    <span class="fr-home-hero__label">El ranking definitivo</span>
    <h1 class="fr-home-hero__title">FIGHT<span>RANK</span></h1>
    <p class="fr-home-hero__sub">
        Puntuación automática y valoración de cada pelea UFC.<br>
        Datos reales. Rankings actualizados.
    </p>
    <div class="fr-home-hero__btns">
        <a href="<?= get_post_type_archive_link( 'fight' ) ?>" class="fr-btn fr-btn--red">
            Ver peleas
        </a>
        <a href="<?= get_post_type_archive_link( 'fighter' ) ?>" class="fr-btn fr-btn--outline">
            Rankings
        </a>
    </div>
</div>
        <div class="fr-home-hero__scroll">&#8595;</div>
    </section>

    <!-- ====== STATS BAR ====== -->
    <section class="fr-home-stats">
        <div class="fr-home-stats__inner">
            <?php foreach ( [
                [ number_format( $n_fights ),   'Peleas',      get_post_type_archive_link( 'fight' ) ],
                [ number_format( $n_fighters ),  'Peleadores',  get_post_type_archive_link( 'fighter' ) ],
                [ number_format( $n_events ),    'Eventos',     get_post_type_archive_link( 'ufc_event' ) ],
            ] as [ $num, $label, $url ] ) : ?>
            <a href="<?= esc_url( $url ) ?>" class="fr-stat-box">
                <span class="fr-stat-box__num"><?= $num ?></span>
                <span class="fr-stat-box__label"><?= $label ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ====== TOP PELEAS ====== -->
    <section class="fr-home-section fr-home-section--dark">
        <div class="fr-home-section__inner">
            <header class="fr-home-section__header">
                <h2 class="fr-home-section__title">Top 10 Peleas</h2>
                <a href="<?= get_post_type_archive_link( 'fight' ) ?>?orderby=score" class="fr-home-section__more">Ver todas &rarr;</a>
            </header>

            <?php if ( $top_fights->have_posts() ) : ?>
            <ol class="fr-home-top-list">
                <?php $i = 1; while ( $top_fights->have_posts() ) : $top_fights->the_post();
                    $id    = get_the_ID();
                    $fd    = fightrank_get_fight_data( $id );
                    $f1    = $fd['fighter1_id'] ? get_post( $fd['fighter1_id'] ) : null;
                    $f2    = $fd['fighter2_id'] ? get_post( $fd['fighter2_id'] ) : null;
                    $score = (float) $fd['auto_score'];
                    $label = fightrank_score_label( $score );
                ?>
                <li class="fr-home-top-item">
                    <span class="fr-home-top-rank"><?= $i ?></span>
                    <a href="<?= get_permalink() ?>" class="fr-home-top-fight">
                        <div class="fr-home-top-fight__matchup">
                            <span class="<?= $fd['winner'] === 'fighter1' ? 'fr-name--gold' : '' ?>">
                                <?= $f1 ? esc_html( $f1->post_title ) : '—' ?>
                            </span>
                            <span class="fr-home-top-vs">vs</span>
                            <span class="<?= $fd['winner'] === 'fighter2' ? 'fr-name--gold' : '' ?>">
                                <?= $f2 ? esc_html( $f2->post_title ) : '—' ?>
                            </span>
                        </div>
                        <div class="fr-home-top-fight__meta">
                            <?php if ( $fd['method'] ) : ?>
                                <span class="fr-tag"><?= esc_html( $fd['method'] ) ?></span>
                            <?php endif; ?>
                            <?php if ( $fd['title_fight'] === '1' ) : ?>
                                <span class="fr-tag fr-tag--title">Título</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="fr-score-pill <?= $label['class'] ?>">
                        <?= $score ?>
                    </div>
                </li>
                <?php $i++; endwhile; wp_reset_postdata(); ?>
            </ol>
            <?php else : ?>
                <p class="fr-no-results">Importa datos para ver el ranking de peleas.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====== RANKINGS PELEADORES ====== -->
    <section class="fr-home-section">
        <div class="fr-home-section__inner">
            <header class="fr-home-section__header">
                <h2 class="fr-home-section__title">Rankings</h2>
                <a href="<?= get_post_type_archive_link( 'fighter' ) ?>" class="fr-home-section__more">Ver todos &rarr;</a>
            </header>

            <?php if ( ! empty( $top_fighters ) ) : ?>
            <div class="fr-home-fighters-grid">
                <?php foreach ( $top_fighters as $i => $fighter ) :
                    $fd = fightrank_get_fighter_data( $fighter->ID );
                    $divisions = get_the_terms( $fighter->ID, 'weight_class' );
                    $div = $divisions && ! is_wp_error( $divisions ) ? $divisions[0]->name : '';
                ?>
                <a href="<?= get_permalink( $fighter->ID ) ?>" class="fr-home-fighter-card">
                    <span class="fr-home-fighter-card__rank">#<?= $i + 1 ?></span>
                    <?php if ( has_post_thumbnail( $fighter->ID ) ) : ?>
                        <div class="fr-home-fighter-card__photo">
                            <?= get_the_post_thumbnail( $fighter->ID, [ 80, 80 ] ) ?>
                        </div>
                    <?php else : ?>
                        <div class="fr-home-fighter-card__photo fr-home-fighter-card__photo--ph">
                            <?= strtoupper( substr( $fighter->post_title, 0, 1 ) ) ?>
                        </div>
                    <?php endif; ?>
                    <div class="fr-home-fighter-card__info">
                        <span class="fr-home-fighter-card__name">
                            <?= esc_html( $fighter->post_title ) ?>
                            <?php if ( $fd['is_champion'] === '1' ) : ?>
                                <span class="fr-champion-badge fr-champion-badge--sm">C</span>
                            <?php endif; ?>
                        </span>
                        <?php if ( $div ) : ?>
                            <span class="fr-home-fighter-card__div"><?= esc_html( $div ) ?></span>
                        <?php endif; ?>
                        <span class="fr-home-fighter-card__record">
                            <?= (int)$fd['wins'] ?>-<?= (int)$fd['losses'] ?>-<?= (int)$fd['draws'] ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
                <p class="fr-no-results">Importa datos para ver el ranking de peleadores.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ====== ÚLTIMOS EVENTOS ====== -->
    <?php if ( ! empty( $latest_events ) ) : ?>
    <section class="fr-home-section fr-home-section--dark">
        <div class="fr-home-section__inner">
            <header class="fr-home-section__header">
                <h2 class="fr-home-section__title">Eventos recientes</h2>
                <a href="<?= get_post_type_archive_link( 'ufc_event' ) ?>" class="fr-home-section__more">Ver todos &rarr;</a>
            </header>
            <div class="fr-home-events-grid">
                <?php foreach ( $latest_events as $event ) :
                    $ed = fightrank_get_event_data( $event->ID );
                    $n_fights_ev = count( get_posts([
                        'post_type'   => 'fight',
                        'numberposts' => -1,
                        'fields'      => 'ids',
                        'meta_query'  => [[ 'key' => 'fr_event_id', 'value' => $event->ID ]],
                    ]) );
                ?>
                <a href="<?= get_permalink( $event->ID ) ?>" class="fr-home-event-card">
                    <?php if ( has_post_thumbnail( $event->ID ) ) : ?>
                        <div class="fr-home-event-card__thumb">
                            <?= get_the_post_thumbnail( $event->ID, 'medium' ) ?>
                        </div>
                    <?php else : ?>
                        <div class="fr-home-event-card__thumb fr-home-event-card__thumb--empty">
                            <span>UFC</span>
                        </div>
                    <?php endif; ?>
                    <div class="fr-home-event-card__body">
                        <?php if ( $ed['event_number'] ) : ?>
                            <span class="fr-event-number fr-event-number--sm"><?= esc_html( $ed['event_number'] ) ?></span>
                        <?php endif; ?>
                        <h3 class="fr-home-event-card__title"><?= esc_html( $event->post_title ) ?></h3>
                        <div class="fr-home-event-card__meta">
                            <?php if ( $ed['event_date'] ) : ?>
                                <span>&#128197; <?= date_i18n( 'd M Y', strtotime( $ed['event_date'] ) ) ?></span>
                            <?php endif; ?>
                            <?php if ( $ed['location'] ) : ?>
                                <span>&#128205; <?= esc_html( $ed['location'] ) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="fr-event-fights-count"><?= $n_fights_ev ?> peleas</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
