<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$post_id = get_the_ID();
$data    = fightrank_get_fighter_data( $post_id );
$record  = $data['wins'] . '-' . $data['losses'] . '-' . $data['draws'];

/* Peleas donde aparece este peleador */
$fights_as_f1 = new WP_Query([
    'post_type'      => 'fight',
    'posts_per_page' => -1,
    'meta_query'     => [[ 'key' => 'fr_fighter1_id', 'value' => $post_id ]],
]);
$fights_as_f2 = new WP_Query([
    'post_type'      => 'fight',
    'posts_per_page' => -1,
    'meta_query'     => [[ 'key' => 'fr_fighter2_id', 'value' => $post_id ]],
]);

$all_fights = array_merge(
    $fights_as_f1->posts ?? [],
    $fights_as_f2->posts ?? []
);

usort( $all_fights, function( $a, $b ) {
    $da = get_post_meta( $a->ID, 'fr_fight_date', true );
    $db = get_post_meta( $b->ID, 'fr_fight_date', true );
    return strcmp( $db, $da );
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

<div class="fr-single-fighter">

    <!-- HERO -->
    <section class="fr-fighter-hero">
        <div class="fr-fighter-hero__inner">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="fr-fighter-hero__photo"><?= get_the_post_thumbnail( $post_id, 'large' ) ?></div>
            <?php endif; ?>
            <div class="fr-fighter-hero__info">
                <?php if ( $data['is_champion'] === '1' ) : ?>
                    <span class="fr-champion-badge">&#127881; Campeón</span>
                <?php endif; ?>
                <h1 class="fr-fighter-hero__name"><?= get_the_title() ?></h1>
                <?php if ( $data['nickname'] ) : ?>
                    <p class="fr-fighter-hero__nickname">"<?= esc_html( $data['nickname'] ) ?>"</p>
                <?php endif; ?>
                <div class="fr-fighter-hero__record"><?= esc_html( $record ) ?></div>
                <div class="fr-fighter-hero__meta">
                    <?php if ( $data['nationality'] ) : ?>
                        <span>&#127988; <?= esc_html( $data['nationality'] ) ?></span>
                    <?php endif; ?>
                    <?php if ( $data['style'] ) : ?>
                        <span>&#129354; <?= esc_html( $data['style'] ) ?></span>
                    <?php endif; ?>
                    <?php if ( $data['dob'] ) : ?>
                        <span>&#128197; <?= date_i18n( 'd M Y', strtotime( $data['dob'] ) ) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $data['instagram'] ) : ?>
                    <a href="https://instagram.com/<?= esc_attr( ltrim( $data['instagram'], '@' ) ) ?>"
                       target="_blank" rel="noopener" class="fr-instagram-btn">
                        Instagram
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- HISTORIAL -->
    <section class="fr-fighter-history">
        <div class="fr-fighter-history__inner">
            <h2 class="fr-section-title">Historial de peleas</h2>
            <?php if ( empty( $all_fights ) ) : ?>
                <p>No hay peleas registradas para este peleador.</p>
            <?php else : ?>
            <table class="fr-history-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Rival</th>
                        <th>Resultado</th>
                        <th>Método</th>
                        <th>Round</th>
                        <th>Nota FR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $all_fights as $fight ) :
                        $fd     = fightrank_get_fight_data( $fight->ID );
                        $is_f1  = (int) $fd['fighter1_id'] === $post_id;
                        $rival_id = $is_f1 ? $fd['fighter2_id'] : $fd['fighter1_id'];
                        $rival    = $rival_id ? get_post( $rival_id ) : null;

                        $result = '—';
                        $result_class = '';
                        if ( $fd['winner'] === ( $is_f1 ? 'fighter1' : 'fighter2' ) ) {
                            $result = 'Victoria'; $result_class = 'fr-result--win';
                        } elseif ( $fd['winner'] === 'draw' ) {
                            $result = 'Empate'; $result_class = 'fr-result--draw';
                        } elseif ( $fd['winner'] === 'nc' ) {
                            $result = 'Sin Concurso'; $result_class = 'fr-result--nc';
                        } elseif ( ! empty( $fd['winner'] ) ) {
                            $result = 'Derrota'; $result_class = 'fr-result--loss';
                        }
                    ?>
                    <tr>
                        <td><?= $fd['fight_date'] ? date_i18n( 'd/m/Y', strtotime( $fd['fight_date'] ) ) : '—' ?></td>
                        <td>
                            <?php if ( $rival ) : ?>
                                <a href="<?= get_permalink( $rival->ID ) ?>"><?= esc_html( $rival->post_title ) ?></a>
                            <?php else : ?>—<?php endif; ?>
                        </td>
                        <td class="<?= $result_class ?>"><?= $result ?></td>
                        <td><?= esc_html( $fd['method'] ?: '—' ) ?></td>
                        <td><?= esc_html( $fd['round'] ?: '—' ) ?></td>
                        <td>
                            <a href="<?= get_permalink( $fight->ID ) ?>" class="fr-score-link">
                                <?= $fd['auto_score'] !== '' ? esc_html( $fd['auto_score'] ) : '—' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php get_footer(); ?>
