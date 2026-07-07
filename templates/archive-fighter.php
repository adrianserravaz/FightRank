<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

$weight_class = isset( $_GET['weight_class'] ) ? sanitize_key( $_GET['weight_class'] ) : '';
$orderby_opt  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'wins';
$search = isset( $_GET['fighter_search'] ) ? sanitize_text_field( $_GET['fighter_search'] ) : '';
$paged        = max( 1, get_query_var( 'paged' ) ?: ( $_GET['paged'] ?? 1 ) );
$per_page     = 50;

$meta_key = $orderby_opt === 'losses' ? 'fr_losses' : ( $orderby_opt === 'ranking' ? 'fr_current_rank' : 'fr_wins' );

$args = [
    'post_type'      => 'fighter',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'meta_key'       => $meta_key,
    's' => $search,
    'orderby'        => 'meta_value_num',
    'order' => $orderby_opt === 'ranking' ? 'ASC' : 'DESC',
];

if ( $weight_class ) {
    $args['tax_query'] = [[
        'taxonomy' => 'weight_class',
        'field'    => 'slug',
        'terms'    => $weight_class,
    ]];
}

$query    = new WP_Query( $args );
$fighters = $query->posts;
$total    = $query->found_posts;
$pages    = ceil( $total / $per_page );
$offset   = ( $paged - 1 ) * $per_page;
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

<div class="fr-archive fr-archive--fighters">
    <div class="fr-archive__inner">

        <header class="fr-archive__header">
            <h1 class="fr-archive__title">Rankings</h1>
            <p class="fr-archive__subtitle"><?= number_format( $total ) ?> peleadores registrados</p>
        </header>

        <form class="fr-filters" method="get" action="<?= esc_url( get_post_type_archive_link( 'fighter' ) ) ?>">
            <div class="fr-filters__group">
                <label>División</label>
                <select name="weight_class">
                  <option value="">Todas las divisiones</option>
<?php
$allowed_weights = [
    'flyweight',
    'bantamweight',
    'featherweight',
    'lightweight',
    'welterweight',
    'middleweight',
    'light-heavyweight',
    'heavyweight',
    'womens-strawweight',
    'womens-flyweight',
    'womens-bantamweight',
];

$terms = get_terms([
    'taxonomy'   => 'weight_class',
    'hide_empty' => true,
    'slug'       => $allowed_weights,
    'orderby'    => 'name',
]);

if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
    foreach ( $terms as $term ) {
        echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $weight_class, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
    }
}
?>
                </select>
            </div>
            <div class="fr-filters__group">
                <label>Ordenar por</label>
                <select name="orderby">
                    <option value="wins"   <?= $orderby_opt === 'wins'   ? 'selected' : '' ?>>Victorias</option>
                    <option value="losses" <?= $orderby_opt === 'losses' ? 'selected' : '' ?>>Derrotas</option>
                    <option value="ranking" <?= $orderby_opt === 'ranking' ? 'selected' : '' ?>>Ranking actual</option>
                </select>
            </div>
            <div class="fr-filters__group">
    <label>Buscar peleador</label>
    <input 
        type="text" 
        name="fighter_search" 
        value="<?= esc_attr( $search ) ?>" 
        placeholder="Nombre del peleador"
    >
</div>
            <button type="submit" class="fr-filters__btn">Filtrar</button>
        </form>

        <?php if ( empty( $fighters ) ) : ?>
            <p class="fr-no-results">No se encontraron peleadores.</p>
        <?php else : ?>

        <div class="fr-rankings-table-wrap">
            <table class="fr-rankings-table">
                <thead>
                    <tr>
                        <th class="fr-rank-col">#</th>
                        <th>Peleador</th>
                        <th>División</th>
                        <th>Récord</th>
                        <th>Estilo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $fighters as $i => $fighter ) :
                        $fd       = fightrank_get_fighter_data( $fighter->ID );
                        $divs     = get_the_terms( $fighter->ID, 'weight_class' );
                        $div_name = ( $divs && ! is_wp_error( $divs ) ) ? $divs[0]->name : '—';
                    ?>
                    <tr class="fr-rankings-row <?= $fd['is_champion'] === '1' ? 'fr-rankings-row--champion' : '' ?>">
                        <td class="fr-rank-col">
                            <span class="fr-rank-num"><?= $offset + $i + 1 ?></span>
                        </td>
                        <td>
                            <a href="<?= get_permalink( $fighter->ID ) ?>" class="fr-rankings-fighter">
                                <?php if ( has_post_thumbnail( $fighter->ID ) ) : ?>
                                    <div class="fr-rankings-photo"><?= get_the_post_thumbnail( $fighter->ID, [ 48, 48 ] ) ?></div>
                                <?php else : ?>
                                    <div class="fr-rankings-photo fr-rankings-photo--placeholder">
                                        <?= strtoupper( substr( $fighter->post_title, 0, 1 ) ) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="fr-rankings-fighter__info">
                                    <span class="fr-rankings-fighter__name">
                                        <?= esc_html( $fighter->post_title ) ?>
                                        <?php if ( $fd['is_champion'] === '1' ) : ?>
                                            <span class="fr-champion-badge fr-champion-badge--sm">C</span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ( $fd['nickname'] ) : ?>
                                        <span class="fr-rankings-fighter__nick">"<?= esc_html( $fd['nickname'] ) ?>"</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </td>
                        <td class="fr-rankings-division"><?= esc_html( $div_name ) ?></td>
                        <td class="fr-rankings-record">
                            <strong><?= (int) $fd['wins'] ?></strong>-<?= (int) $fd['losses'] ?>-<?= (int) $fd['draws'] ?>
                        </td>
                        <td class="fr-rankings-style"><?= $fd['style'] ? esc_html( $fd['style'] ) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ( $pages > 1 ) :
            $base_url = strtok( $_SERVER['REQUEST_URI'], '?' );
            $params   = array_filter([
                'weight_class' => $weight_class,
                'orderby'      => $orderby_opt !== 'wins' ? $orderby_opt : '',
            ]);
        ?>
        <div class="fr-pagination">
            <div class="nav-links">
                <?php if ( $paged > 1 ) : ?>
                    <a class="page-numbers" href="<?= esc_url( $base_url . '?' . http_build_query( array_merge( $params, [ 'paged' => $paged - 1 ] ) ) ) ?>">&laquo; Anterior</a>
                <?php endif; ?>

                <?php
                $start = max( 1, $paged - 2 );
                $end   = min( $pages, $paged + 2 );
                for ( $p = $start; $p <= $end; $p++ ) :
                    $class = $p === (int) $paged ? 'page-numbers current' : 'page-numbers';
                ?>
                    <a class="<?= $class ?>" href="<?= esc_url( $base_url . '?' . http_build_query( array_merge( $params, [ 'paged' => $p ] ) ) ) ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ( $paged < $pages ) : ?>
                    <a class="page-numbers" href="<?= esc_url( $base_url . '?' . http_build_query( array_merge( $params, [ 'paged' => $paged + 1 ] ) ) ) ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
