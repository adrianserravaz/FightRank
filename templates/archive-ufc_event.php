<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
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

<div class="fr-archive fr-archive--events">
    <div class="fr-archive__inner">

        <header class="fr-archive__header">
            <h1 class="fr-archive__title">Eventos UFC</h1>
            <p class="fr-archive__subtitle">Todos los eventos registrados en FightRank</p>
        </header>

        <?php if ( have_posts() ) : ?>
        <div class="fr-event-grid">
            <?php while ( have_posts() ) : the_post();
                $id = get_the_ID();
                $ev = fightrank_get_event_data( $id );
                $fight_count = count( get_posts([
                    'post_type'   => 'fight',
                    'numberposts' => -1,
                    'fields'      => 'ids',
                    'meta_query'  => [[ 'key' => 'fr_event_id', 'value' => $id ]],
                ]) );
            ?>
            <article class="fr-event-card">
                <a href="<?= get_permalink() ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="fr-event-card__thumb"><?= get_the_post_thumbnail( $id, 'medium_large' ) ?></div>
                    <?php else : ?>
                        <div class="fr-event-card__thumb fr-event-card__thumb--empty">
                            <span class="fr-event-card__octagon">&#11042;</span>
                        </div>
                    <?php endif; ?>

                    <div class="fr-event-card__body">
                        <?php if ( $ev['event_number'] ) : ?>
                            <span class="fr-event-number fr-event-number--sm"><?= esc_html( $ev['event_number'] ) ?></span>
                        <?php endif; ?>
                        <h2 class="fr-event-card__title"><?= get_the_title() ?></h2>
                        <div class="fr-event-card__meta">
                            <?php if ( $ev['event_date'] ) : ?>
                                <span>&#128197; <?= date_i18n( 'd M Y', strtotime( $ev['event_date'] ) ) ?></span>
                            <?php endif; ?>
                            <?php if ( $ev['location'] ) : ?>
                                <span>&#128205; <?= esc_html( $ev['location'] ) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fr-event-card__footer">
                            <span class="fr-event-fights-count">
                                <?= $fight_count ?> pelea<?= $fight_count !== 1 ? 's' : '' ?>
                            </span>
                            <?php if ( $ev['arena'] ) : ?>
                                <span class="fr-event-arena"><?= esc_html( $ev['arena'] ) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php endwhile; ?>
        </div>

        <div class="fr-pagination">
            <?php the_posts_pagination([ 'prev_text' => '&laquo; Anterior', 'next_text' => 'Siguiente &raquo;' ]); ?>
        </div>

        <?php else : ?>
            <p class="fr-no-results">No hay eventos registrados todavía.</p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
