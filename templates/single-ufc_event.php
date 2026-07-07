<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$post_id = get_the_ID();
$ev      = fightrank_get_event_data( $post_id );

$event_fights = get_posts([
    'post_type'   => 'fight',
    'numberposts' => -1,
    'meta_query'  => [[ 'key' => 'fr_event_id', 'value' => $post_id ]],
]);

usort( $event_fights, function( $a, $b ) {
    $da = fightrank_get_fight_data( $a->ID );
    $db = fightrank_get_fight_data( $b->ID );
    $ta = (int) ( $da['title_fight'] ?? 0 );
    $tb = (int) ( $db['title_fight'] ?? 0 );
    if ( $ta !== $tb ) return $tb - $ta;
    return (float) ( $db['auto_score'] ?? 0 ) <=> (float) ( $da['auto_score'] ?? 0 );
});
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

<div class="fr-home">

<div class="fr-single-event">

    <!-- HERO -->
    <section class="fr-event-hero">
        <div class="fr-event-hero__inner">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="fr-event-hero__thumb"><?= get_the_post_thumbnail( $post_id, 'large' ) ?></div>
            <?php endif; ?>
            <div class="fr-event-hero__info">
                <?php if ( $ev['event_number'] ) : ?>
                    <span class="fr-event-number"><?= esc_html( $ev['event_number'] ) ?></span>
                <?php endif; ?>
                <h1 class="fr-event-hero__title"><?= get_the_title() ?></h1>
                <div class="fr-event-hero__meta">
                    <?php if ( $ev['event_date'] ) : ?>
                        <span>&#128197; <?= date_i18n( 'd M Y', strtotime( $ev['event_date'] ) ) ?></span>
                    <?php endif; ?>
                    <?php if ( $ev['arena'] ) : ?>
                        <span>&#127965; <?= esc_html( $ev['arena'] ) ?></span>
                    <?php endif; ?>
                    <?php if ( $ev['location'] ) : ?>
                        <span>&#128205; <?= esc_html( $ev['location'] ) ?></span>
                    <?php endif; ?>
                </div>
                <div class="fr-event-hero__stats">
                    <span class="fr-event-stat">
                        <strong><?= count( $event_fights ) ?></strong>
                        pelea<?= count( $event_fights ) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- CARD DE PELEAS -->
    <section class="fr-event-fights">
        <div class="fr-event-fights__inner">
            <h2 class="fr-section-title">Card del evento</h2>

            <?php if ( empty( $event_fights ) ) : ?>
                <p class="fr-no-results">No hay peleas registradas para este evento todavía.</p>
            <?php else : ?>
            <div class="fr-event-fight-list">
                <?php foreach ( $event_fights as $fight ) :
                    $fd    = fightrank_get_fight_data( $fight->ID );
                    $f1    = $fd['fighter1_id'] ? get_post( $fd['fighter1_id'] ) : null;
                    $f2    = $fd['fighter2_id'] ? get_post( $fd['fighter2_id'] ) : null;
                    $score = $fd['auto_score'] !== '' ? (float) $fd['auto_score'] : null;
                    $label = fightrank_score_label( $score );
                    $stats = fightrank_get_rating_stats( $fight->ID );
                ?>
                <a href="<?= get_permalink( $fight->ID ) ?>"
                   class="fr-event-fight-row <?= $fd['title_fight'] === '1' ? 'fr-event-fight-row--title' : '' ?>">

                    <?php if ( $fd['title_fight'] === '1' ) : ?>
                        <div class="fr-event-fight-row__banner">&#127881; PELEA ESTELAR — POR EL TÍTULO</div>
                    <?php endif; ?>

                    <div class="fr-event-fight-row__matchup">

                        <div class="fr-event-fighter <?= $fd['winner'] === 'fighter1' ? 'fr-event-fighter--winner' : '' ?> fr-event-fighter--left">
                            <div class="fr-event-fighter__photo">
                                <?php if ( $f1 && has_post_thumbnail( $f1->ID ) ) : ?>
                                    <?= get_the_post_thumbnail( $f1->ID, [ 64, 64 ] ) ?>
                                <?php else : ?>
                                    <span class="fr-event-fighter__initial"><?= $f1 ? strtoupper( substr( $f1->post_title, 0, 1 ) ) : '?' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="fr-event-fighter__info">
                                <span class="fr-event-fighter__name"><?= $f1 ? esc_html( $f1->post_title ) : '—' ?></span>
                                <?php if ( $fd['winner'] === 'fighter1' ) : ?>
                                    <span class="fr-win-tag">W</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="fr-event-center">
                            <span class="fr-event-vs">VS</span>
                            <?php if ( $fd['method'] ) : ?>
                                <span class="fr-event-method"><?= esc_html( $fd['method'] ) ?></span>
                                <span class="fr-event-round">R<?= esc_html( $fd['round'] ) ?> &middot; <?= esc_html( $fd['time'] ) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="fr-event-fighter <?= $fd['winner'] === 'fighter2' ? 'fr-event-fighter--winner' : '' ?> fr-event-fighter--right">
                            <div class="fr-event-fighter__info">
                                <?php if ( $fd['winner'] === 'fighter2' ) : ?>
                                    <span class="fr-win-tag">W</span>
                                <?php endif; ?>
                                <span class="fr-event-fighter__name"><?= $f2 ? esc_html( $f2->post_title ) : '—' ?></span>
                            </div>
                            <div class="fr-event-fighter__photo">
                                <?php if ( $f2 && has_post_thumbnail( $f2->ID ) ) : ?>
                                    <?= get_the_post_thumbnail( $f2->ID, [ 64, 64 ] ) ?>
                                <?php else : ?>
                                    <span class="fr-event-fighter__initial"><?= $f2 ? strtoupper( substr( $f2->post_title, 0, 1 ) ) : '?' ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <div class="fr-event-fight-row__scores">
                        <span class="fr-score-pill <?= $label['class'] ?>">FR: <?= $score !== null ? $score : '—' ?></span>
                        <?php if ( $stats['total'] > 0 ) : ?>
                            <span class="fr-score-pill fr-score-pill--user">&#9733; <?= $stats['avg'] ?> <small>(<?= $stats['total'] ?>)</small></span>
                        <?php endif; ?>
                    </div>

                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ( get_the_content() ) : ?>
    <section class="fr-content-section">
        <div class="fr-content-section__inner"><?php the_content(); ?></div>
    </section>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
