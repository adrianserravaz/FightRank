<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$post_id    = get_the_ID();
$data       = fightrank_get_fight_data( $post_id );
$f1         = $data['fighter1_id'] ? get_post( $data['fighter1_id'] ) : null;
$f2         = $data['fighter2_id'] ? get_post( $data['fighter2_id'] ) : null;
$f1d        = $f1 ? fightrank_get_fighter_data( $f1->ID ) : [];
$f2d        = $f2 ? fightrank_get_fighter_data( $f2->ID ) : [];
$auto_score = isset( $data['auto_score'] ) && $data['auto_score'] !== '' ? (float) $data['auto_score'] : null;
$label      = fightrank_score_label( $auto_score );
$stats      = fightrank_get_rating_stats( $post_id );
$dist       = fightrank_get_rating_distribution( $post_id );
$user_id    = get_current_user_id();
$my_rating  = $user_id ? fightrank_get_user_rating( $post_id, $user_id ) : null;
$event      = $data['event_id'] ? get_post( $data['event_id'] ) : null;
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

<div class="fr-single-fight" data-fight-id="<?= $post_id ?>">

    <!-- ====== HERO ====== -->
    <section class="fr-fight-hero">
        <div class="fr-fight-hero__inner">

            <!-- Peleador 1 -->
            <div class="fr-fight-hero__fighter fr-fight-hero__fighter--1 <?= $data['winner'] === 'fighter1' ? 'fr-fight-hero__fighter--winner' : '' ?>">
                <?php if ( $f1 && has_post_thumbnail( $f1->ID ) ) : ?>
                    <div class="fr-fighter-photo"><?= get_the_post_thumbnail( $f1->ID, 'large' ) ?></div>
                <?php else : ?>
                    <div class="fr-fighter-photo fr-fighter-photo--placeholder"><span><?= $f1 ? strtoupper( substr( $f1->post_title, 0, 1 ) ) : '?' ?></span></div>
                <?php endif; ?>
                <?php if ( $f1 ) : ?>
                    <a href="<?= get_permalink( $f1->ID ) ?>" class="fr-fighter-hero-name">
                        <?= esc_html( $f1->post_title ) ?>
                    </a>
                <?php else : ?>
                    <span class="fr-fighter-hero-name">—</span>
                <?php endif; ?>
                <?php if ( ! empty( $f1d['nickname'] ) ) : ?>
                    <p class="fr-fighter-hero-nickname">"<?= esc_html( $f1d['nickname'] ) ?>"</p>
                <?php endif; ?>
                <div class="fr-fighter-record">
                    <?= esc_html( ($f1d['wins'] ?? '0') . '-' . ($f1d['losses'] ?? '0') . '-' . ($f1d['draws'] ?? '0') ) ?>
                </div>
                <?php if ( $data['winner'] === 'fighter1' ) : ?>
                    <span class="fr-winner-badge">GANADOR</span>
                <?php endif; ?>
            </div>

            <!-- Centro -->
            <div class="fr-fight-hero__center">
                <?php if ( $data['title_fight'] === '1' ) : ?>
                    <div class="fr-title-belt">&#127881; Pelea por título</div>
                <?php endif; ?>
                <div class="fr-vs-text">VS</div>
                <?php if ( ! empty( $data['method'] ) ) : ?>
                <div class="fr-fight-result">
                    <span class="fr-method-badge"><?= esc_html( $data['method'] ) ?></span>
                    <span class="fr-round-info">R<?= esc_html( $data['round'] ) ?> · <?= esc_html( $data['time'] ) ?></span>
                </div>
                <?php endif; ?>
                <?php if ( $event ) : ?>
                    <a href="<?= get_permalink( $event->ID ) ?>" class="fr-event-link"><?= esc_html( $event->post_title ) ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $data['fight_date'] ) ) : ?>
                    <time class="fr-fight-date"><?= date_i18n( 'd M Y', strtotime( $data['fight_date'] ) ) ?></time>
                <?php endif; ?>
            </div>

            <!-- Peleador 2 -->
            <div class="fr-fight-hero__fighter fr-fight-hero__fighter--2 <?= $data['winner'] === 'fighter2' ? 'fr-fight-hero__fighter--winner' : '' ?>">
                <?php if ( $f2 && has_post_thumbnail( $f2->ID ) ) : ?>
                    <div class="fr-fighter-photo"><?= get_the_post_thumbnail( $f2->ID, 'large' ) ?></div>
                <?php else : ?>
                    <div class="fr-fighter-photo fr-fighter-photo--placeholder"><span><?= $f2 ? strtoupper( substr( $f2->post_title, 0, 1 ) ) : '?' ?></span></div>
                <?php endif; ?>
                <?php if ( $f2 ) : ?>
                    <a href="<?= get_permalink( $f2->ID ) ?>" class="fr-fighter-hero-name">
                        <?= esc_html( $f2->post_title ) ?>
                    </a>
                <?php else : ?>
                    <span class="fr-fighter-hero-name">—</span>
                <?php endif; ?>
                <?php if ( ! empty( $f2d['nickname'] ) ) : ?>
                    <p class="fr-fighter-hero-nickname">"<?= esc_html( $f2d['nickname'] ) ?>"</p>
                <?php endif; ?>
                <div class="fr-fighter-record">
                    <?= esc_html( ($f2d['wins'] ?? '0') . '-' . ($f2d['losses'] ?? '0') . '-' . ($f2d['draws'] ?? '0') ) ?>
                </div>
                <?php if ( $data['winner'] === 'fighter2' ) : ?>
                    <span class="fr-winner-badge">GANADOR</span>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ====== ESTADÍSTICAS COMPARATIVAS ====== -->
    <?php
    $has_stats = (
        (int)$data['kd_f1']           + (int)$data['kd_f2']           +
        (int)$data['sig_strikes_f1']  + (int)$data['sig_strikes_f2']  +
        (int)$data['td_f1']           + (int)$data['td_f2']           +
        (int)$data['sub_attempts_f1'] + (int)$data['sub_attempts_f2'] +
        (int)$data['time_standing']   + (int)$data['time_ground']
    ) > 0;
    if ( $has_stats ) :
        $f1_name = $f1 ? esc_html( $f1->post_title ) : 'Peleador 1';
        $f2_name = $f2 ? esc_html( $f2->post_title ) : 'Peleador 2';

        $rows = [
            [ 'label' => 'Golpes significativos', 'v1' => (int)$data['sig_strikes_f1'],  'v2' => (int)$data['sig_strikes_f2']  ],
            [ 'label' => 'Knockdowns',             'v1' => (int)$data['kd_f1'],           'v2' => (int)$data['kd_f2']           ],
            [ 'label' => 'Derribos',               'v1' => (int)$data['td_f1'],           'v2' => (int)$data['td_f2']           ],
            [ 'label' => 'Int. sumisión',          'v1' => (int)$data['sub_attempts_f1'], 'v2' => (int)$data['sub_attempts_f2'] ],
        ];

        $time_total = (int)$data['time_standing'] + (int)$data['time_ground'];
        $pct_standing = $time_total > 0 ? round( (int)$data['time_standing'] / $time_total * 100 ) : null;
        $pct_ground   = $time_total > 0 ? 100 - $pct_standing : null;
    ?>
    <section class="fr-stats-section">
        <div class="fr-stats-section__inner">
            <h2 class="fr-stats-section__title">Estadísticas del combate</h2>

            <div class="fr-stats-header">
                <span class="fr-stats-header__name fr-stats-header__name--left <?= $data['winner'] === 'fighter1' ? 'fr-stats-header__name--winner' : '' ?>"><?= $f1_name ?></span>
                <span class="fr-stats-header__name fr-stats-header__name--right <?= $data['winner'] === 'fighter2' ? 'fr-stats-header__name--winner' : '' ?>"><?= $f2_name ?></span>
            </div>

            <div class="fr-stats-rows">
                <?php foreach ( $rows as $row ) :
                    $total = $row['v1'] + $row['v2'];
                    $pct1  = $total > 0 ? round( $row['v1'] / $total * 100 ) : 50;
                    $pct2  = $total > 0 ? 100 - $pct1 : 50;
                    $dominant = $row['v1'] > $row['v2'] ? 'left' : ( $row['v2'] > $row['v1'] ? 'right' : 'none' );
                ?>
                <div class="fr-stat-row">
                    <span class="fr-stat-row__val fr-stat-row__val--left <?= $dominant === 'left' ? 'fr-stat-row__val--lead' : '' ?>"><?= $row['v1'] ?></span>
                    <div class="fr-stat-row__center">
                        <span class="fr-stat-row__label"><?= $row['label'] ?></span>
                        <div class="fr-stat-bar">
                            <div class="fr-stat-bar__fill fr-stat-bar__fill--left"  style="width:<?= $pct1 ?>%"></div>
                            <div class="fr-stat-bar__fill fr-stat-bar__fill--right" style="width:<?= $pct2 ?>%"></div>
                        </div>
                    </div>
                    <span class="fr-stat-row__val fr-stat-row__val--right <?= $dominant === 'right' ? 'fr-stat-row__val--lead' : '' ?>"><?= $row['v2'] ?></span>
                </div>
                <?php endforeach; ?>

                <?php if ( $pct_standing !== null ) : ?>
                <div class="fr-stat-row fr-stat-row--time">
                    <span class="fr-stat-row__val fr-stat-row__val--left"><?= gmdate( 'i:s', (int)$data['time_standing'] ) ?></span>
                    <div class="fr-stat-row__center">
                        <span class="fr-stat-row__label">Tiempo en pie / suelo</span>
                        <div class="fr-stat-bar fr-stat-bar--split">
                            <div class="fr-stat-bar__segment fr-stat-bar__segment--standing" style="width:<?= $pct_standing ?>%">
                                <span class="fr-stat-bar__seg-label">En pie <?= $pct_standing ?>%</span>
                            </div>
                            <div class="fr-stat-bar__segment fr-stat-bar__segment--ground" style="width:<?= $pct_ground ?>%">
                                <span class="fr-stat-bar__seg-label">Suelo <?= $pct_ground ?>%</span>
                            </div>
                        </div>
                    </div>
                    <span class="fr-stat-row__val fr-stat-row__val--right"><?= gmdate( 'i:s', (int)$data['time_ground'] ) ?></span>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <style>
    .fr-stats-section {
        background: #1a1a1a;
        padding: 48px 0;
        border-top: 1px solid rgba(200,16,46,0.2);
    }
    .fr-stats-section__inner {
        max-width: 780px;
        margin: 0 auto;
        padding: 0 24px;
    }
    .fr-stats-section__title {
        text-align: center;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 3px;
        color: #888;
        margin: 0 0 28px;
    }
    .fr-stats-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .fr-stats-header__name {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #aaa;
        max-width: 45%;
    }
    .fr-stats-header__name--left  { text-align: left; }
    .fr-stats-header__name--right { text-align: right; }
    .fr-stats-header__name--winner { color: #FFD700; }

    .fr-stats-rows {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .fr-stat-row {
        display: grid;
        grid-template-columns: 44px 1fr 44px;
        align-items: center;
        gap: 12px;
    }
    .fr-stat-row__center {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .fr-stat-row__label {
        text-align: center;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #666;
    }
    .fr-stat-row__val {
        font-size: 22px;
        font-weight: 800;
        color: #ccc;
    }
    .fr-stat-row__val--left  { text-align: right; }
    .fr-stat-row__val--right { text-align: left; }
    .fr-stat-row__val--lead  { color: #fff; }

    .fr-stat-bar {
        display: flex;
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
        background: #2a2a2a;
        gap: 2px;
    }
    .fr-stat-bar__fill--left {
        background: #C8102E;
        border-radius: 3px 0 0 3px;
        transition: width 0.6s ease;
    }
    .fr-stat-bar__fill--right {
        background: #444;
        border-radius: 0 3px 3px 0;
        transition: width 0.6s ease;
        margin-left: auto;
    }

    .fr-stat-bar--split {
        height: 28px;
        border-radius: 4px;
        gap: 2px;
        background: #111;
    }
    .fr-stat-bar__segment {
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transition: width 0.6s ease;
    }
    .fr-stat-bar__segment--standing {
        background: #2980b9;
        border-radius: 4px 0 0 4px;
    }
    .fr-stat-bar__segment--ground {
        background: #795548;
        border-radius: 0 4px 4px 0;
    }
    .fr-stat-bar__seg-label {
        font-size: 10px;
        font-weight: 700;
        color: rgba(255,255,255,0.85);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 6px;
    }
    .fr-stat-row--time .fr-stat-row__val {
        font-size: 14px;
        color: #888;
    }

    @media (max-width: 480px) {
        .fr-stat-row { grid-template-columns: 36px 1fr 36px; gap: 8px; }
        .fr-stat-row__val { font-size: 18px; }
        .fr-stats-header__name { font-size: 11px; }
    }
    </style>
    <?php endif; ?>

    <!-- ====== PUNTUACIÓN + VALORACIÓN ====== -->
    <section class="fr-scores-section">
        <div class="fr-scores-section__inner">

            <!-- Puntuación automática FightRank -->
            <div class="fr-score-block fr-score-block--auto">
                <h3 class="fr-score-block__title">Puntuación FightRank</h3>
                <div class="fr-score-display <?= $label['class'] ?>">
                    <span class="fr-score-display__value"><?= $auto_score !== null ? $auto_score : '—' ?></span>
                    <span class="fr-score-display__label"><?= $label['label'] ?></span>
                </div>
                <?php if ( $stats['total'] > 0 ) : ?>
                <div class="fr-user-avg">
                    <span class="fr-user-avg__value"><?= $stats['avg'] ?></span>
                    <span class="fr-user-avg__meta">&#9733; <?= $stats['total'] ?> voto<?= $stats['total'] !== 1 ? 's' : '' ?> de usuarios</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Valoración de usuarios -->
            <div class="fr-score-block fr-score-block--users">
                <h3 class="fr-score-block__title">Tu valoración</h3>

                <div class="fr-rating-counter">
                    <span class="fr-rating-counter__num" id="fr-rating-count"><?= $stats['total'] ?></span>
                    <span class="fr-rating-counter__label">valoracion<?= $stats['total'] !== 1 ? 'es' : '' ?> de usuarios</span>
                </div>

                <div class="fr-rating-widget" id="fr-rating-widget">
                    <?php if ( is_user_logged_in() ) : ?>
                        <p class="fr-rating-instruction">
                            <?= $my_rating ? 'Tu nota actual: <strong>' . $my_rating . '/10</strong>' : 'Puntúa esta pelea del 1 al 10:' ?>
                        </p>
                        <div class="fr-rating-stars" data-fight="<?= $post_id ?>">
                            <?php for ( $i = 1; $i <= 10; $i++ ) : ?>
                                <button class="fr-star-btn <?= $my_rating >= $i ? 'fr-star-btn--active' : '' ?>"
                                        data-value="<?= $i ?>" title="<?= $i ?>/10">
                                    &#9733;
                                </button>
                            <?php endfor; ?>
                        </div>
                        <p class="fr-rating-selected" id="fr-selected-label">
                            <?= $my_rating ? "Tu nota: $my_rating/10" : 'Selecciona una puntuación' ?>
                        </p>
                        <button class="fr-submit-rating" id="fr-submit-rating" <?= ! $my_rating ? 'disabled' : '' ?>>
                            <?= $my_rating ? 'Actualizar valoración' : 'Enviar valoración' ?>
                        </button>
                        <p class="fr-rating-message" id="fr-rating-message"></p>
                    <?php else : ?>
                        <p class="fr-no-votes">
                            <a href="<?= wp_login_url( get_permalink() ) ?>" class="fr-login-link">Inicia sesión</a>
                            para valorar esta pelea.
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ( $stats['total'] > 0 ) : ?>
                <div class="fr-dist">
                    <?php for ( $i = 10; $i >= 1; $i-- ) :
                        $cnt = $dist[ $i ] ?? 0;
                        $pct = $stats['total'] > 0 ? round( $cnt / $stats['total'] * 100 ) : 0;
                    ?>
                    <div class="fr-dist__row">
                        <span class="fr-dist__label"><?= $i ?></span>
                        <div class="fr-dist__bar-wrap">
                            <div class="fr-dist__bar" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="fr-dist__count"><?= $cnt ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

</div>

<?php get_footer(); ?>
