<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$current_wc      = isset( $_GET['weight_class'] ) ? sanitize_text_field( $_GET['weight_class'] ) : '';
$current_orderby = isset( $_GET['orderby'] )     ? sanitize_key( $_GET['orderby'] )             : 'date';
$current_method  = isset( $_GET['method'] )      ? sanitize_text_field( $_GET['method'] )       : '';
$current_year_from = isset( $_GET['year_from'] ) ? (int) $_GET['year_from']                     : '';
$current_year_to   = isset( $_GET['year_to'] )   ? (int) $_GET['year_to']                       : '';
$current_title_only = ! empty( $_GET['title_only'] );
$paged           = max( 1, (int) ( $_GET['fr_page'] ?? 1 ) );
$per_page        = 24;

$args = [
    'post_type'      => 'fight',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
];

if ( $current_wc ) {
    $args['tax_query'] = [[
        'taxonomy' => 'weight_class',
        'field'    => 'slug',
        'terms'    => $current_wc,
    ]];
}

$meta_query = [ 'relation' => 'AND' ];

if ( $current_orderby === 'score' ) {
    $args['meta_key'] = 'fr_auto_score';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'DESC';
    $meta_query[]     = [ 'key' => 'fr_auto_score', 'compare' => 'EXISTS' ];
} else {
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
}

if ( $current_method ) {
    $meta_query[] = [ 'key' => 'fr_method', 'value' => $current_method, 'compare' => '=' ];
}

if ( $current_title_only ) {
    $meta_query[] = [ 'key' => 'fr_title_fight', 'value' => '1', 'compare' => '=' ];
}

if ( $current_year_from ) {
    $meta_query[] = [ 'key' => 'fr_fight_date', 'value' => $current_year_from . '-01-01', 'compare' => '>=', 'type' => 'DATE' ];
}

if ( $current_year_to ) {
    $meta_query[] = [ 'key' => 'fr_fight_date', 'value' => $current_year_to . '-12-31', 'compare' => '<=', 'type' => 'DATE' ];
}

if ( count( $meta_query ) > 1 ) {
    $args['meta_query'] = $meta_query;
}

$query  = new WP_Query( $args );
$total  = $query->found_posts;
$pages  = ceil( $total / $per_page );
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
.fr-logo-link{color:#fff;font-size:22px;font-weight:900;text-transform:uppercase;letter-spacing:1px;text-decoration:none;}
.fr-nav-links{
    display:flex;
    gap:28px;
    position:absolute;
    right:48px;
}
.fr-nav-links a{color:#fff;text-decoration:none;font-weight:700;text-transform:uppercase;font-size:14px;}
</style>

<div class="fr-archive">
    <div class="fr-archive__inner">

        <header class="fr-archive__header">
            <h1 class="fr-archive__title">Registro de Peleas</h1>
            <p class="fr-archive__subtitle"><?= number_format( $total ) ?> peleas en FightRank</p>
        </header>

        <form class="fr-filters" method="get" action="<?= esc_url( get_post_type_archive_link( 'fight' ) ) ?>">
            <div class="fr-filters__group">
                <label>División</label>
                <select name="weight_class">
                    <option value="">Todas las divisiones</option>
                    <?php
                    $terms = get_terms([ 'taxonomy' => 'weight_class', 'hide_empty' => true, 'orderby' => 'name' ]);
                    foreach ( $terms as $term ) :
                    ?>
                        <option value="<?= esc_attr( $term->slug ) ?>" <?= $current_wc === $term->slug ? 'selected' : '' ?>>
                            <?= esc_html( $term->name ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fr-filters__group">
                <label>Método</label>
                <select name="method">
                    <option value="">Todos los métodos</option>
                    <option value="KO/TKO"      <?= $current_method === 'KO/TKO'      ? 'selected' : '' ?>>KO / TKO</option>
                    <option value="Submission"   <?= $current_method === 'Submission'   ? 'selected' : '' ?>>Sumisión</option>
                    <option value="Decision-U"   <?= $current_method === 'Decision-U'   ? 'selected' : '' ?>>Decisión Unánime</option>
                    <option value="Decision-S"   <?= $current_method === 'Decision-S'   ? 'selected' : '' ?>>Decisión Dividida</option>
                    <option value="Decision-M"   <?= $current_method === 'Decision-M'   ? 'selected' : '' ?>>Decisión Mayoritaria</option>
                </select>
            </div>
            <div class="fr-filters__group">
                <label>Año desde</label>
                <select name="year_from">
                    <option value="">Desde siempre</option>
                    <?php for ( $y = 2026; $y >= 1994; $y-- ) : ?>
                        <option value="<?= $y ?>" <?= (int)$current_year_from === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="fr-filters__group">
                <label>Año hasta</label>
                <select name="year_to">
                    <option value="">Hasta hoy</option>
                    <?php for ( $y = 2026; $y >= 1994; $y-- ) : ?>
                        <option value="<?= $y ?>" <?= (int)$current_year_to === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="fr-filters__group">
                <label>Ordenar por</label>
                <select name="orderby">
                    <option value="date"  <?= $current_orderby === 'date'  ? 'selected' : '' ?>>Fecha (reciente)</option>
                    <option value="score" <?= $current_orderby === 'score' ? 'selected' : '' ?>>Puntuación FightRank</option>
                </select>
            </div>
            <div class="fr-filters__group fr-filters__group--checkbox">
                <label class="fr-filters__checkbox-label">
                    <input type="checkbox" name="title_only" value="1" <?= $current_title_only ? 'checked' : '' ?>>
                    Solo peleas por título
                </label>
            </div>
            <button type="submit" class="fr-filters__btn">Filtrar</button>
        </form>

        <?php if ( $query->have_posts() ) : ?>
        <div class="fr-fight-grid">
            <?php while ( $query->have_posts() ) : $query->the_post();
                $id    = get_the_ID();
                $data  = fightrank_get_fight_data( $id );
                $score = $data['auto_score'] !== '' ? (float) $data['auto_score'] : null;
                $label = fightrank_score_label( $score );
                $stats = fightrank_get_rating_stats( $id );
                $f1    = $data['fighter1_id'] ? get_post( $data['fighter1_id'] ) : null;
                $f2    = $data['fighter2_id'] ? get_post( $data['fighter2_id'] ) : null;
            ?>
            <article class="fr-fight-card fr-fight-card--grid">
                <a href="<?= get_permalink() ?>">
                    <div class="fr-fight-card__body">
                        <div class="fr-fight-card__matchup">
                            <span class="<?= $data['winner'] === 'fighter1' ? 'fr-name--winner' : '' ?>">
                                <?= $f1 ? esc_html( $f1->post_title ) : '—' ?>
                            </span>
                            <span class="fr-vs-small">VS</span>
                            <span class="<?= $data['winner'] === 'fighter2' ? 'fr-name--winner' : '' ?>">
                                <?= $f2 ? esc_html( $f2->post_title ) : '—' ?>
                            </span>
                        </div>

                        <div class="fr-fight-card__details">
                            <?php if ( $data['method'] ) : ?>
                                <span class="fr-tag"><?= esc_html( $data['method'] ) ?></span>
                            <?php endif; ?>
                            <?php if ( $data['title_fight'] === '1' ) : ?>
                                <span class="fr-tag fr-tag--title">Título</span>
                            <?php endif; ?>
                            <?php if ( $data['fight_date'] ) : ?>
                                <span class="fr-tag fr-tag--date"><?= date_i18n( 'd/m/Y', strtotime( $data['fight_date'] ) ) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="fr-fight-card__scores">
                            <div class="fr-score-pill <?= $label['class'] ?>">
                                FR: <?= $score !== null ? $score : '—' ?>
                            </div>
                            <?php if ( $stats['total'] > 0 ) : ?>
                            <div class="fr-score-pill fr-score-pill--user">
                                &#9733; <?= $stats['avg'] ?> <small>(<?= $stats['total'] ?>)</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <?php if ( $pages > 1 ) :
            $base_url = get_post_type_archive_link( 'fight' );
            $params   = array_filter([
                'weight_class' => $current_wc,
                'method'       => $current_method,
                'year_from'    => $current_year_from ?: '',
                'year_to'      => $current_year_to   ?: '',
                'orderby'      => $current_orderby !== 'date' ? $current_orderby : '',
                'title_only'   => $current_title_only ? '1' : '',
            ]);
        ?>
        <div class="fr-pagination">
            <div class="nav-links">
                <?php if ( $paged > 1 ) : ?>
                    <a class="page-numbers" href="<?= esc_url( $base_url . '?' . http_build_query( array_merge( $params, [ 'fr_page' => $paged - 1 ] ) ) ) ?>">&laquo; Anterior</a>
                <?php endif; ?>
                <?php
                $start = max( 1, $paged - 2 );
                $end   = min( $pages, $paged + 2 );
                for ( $p = $start; $p <= $end; $p++ ) :
                    $class = $p === $paged ? 'page-numbers current' : 'page-numbers';
                ?>
                    <a class="<?= $class ?>" href="<?= esc_url( $base_url . '?' . http_build_query( array_merge( $params, [ 'fr_page' => $p ] ) ) ) ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ( $paged < $pages ) : ?>
                    <a class="page-numbers" href="<?= esc_url( $base_url . '?' . http_build_query( array_merge( $params, [ 'fr_page' => $paged + 1 ] ) ) ) ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else : ?>
            <p class="fr-no-results">No se encontraron peleas con los filtros seleccionados.</p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
