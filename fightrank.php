<?php
/**
 * Plugin Name: FightRank
 * Plugin URI:  https://github.com/
 * Description: Registro de peleas UFC con puntuación automática y votación de usuarios.
 * Version:     1.0.0
 * Author:      Adrian Serrano
 * Text Domain: fightrank
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FIGHTRANK_VERSION', '1.0.0' );
define( 'FIGHTRANK_PATH', plugin_dir_path( __FILE__ ) );
define( 'FIGHTRANK_URL',  plugin_dir_url( __FILE__ ) );

require_once FIGHTRANK_PATH . 'includes/post-types.php';
require_once FIGHTRANK_PATH . 'includes/meta-fields.php';
require_once FIGHTRANK_PATH . 'includes/scoring.php';
require_once FIGHTRANK_PATH . 'includes/ratings.php';
require_once FIGHTRANK_PATH . 'includes/ajax.php';
require_once FIGHTRANK_PATH . 'includes/shortcodes.php';

if ( is_admin() ) {
    require_once FIGHTRANK_PATH . 'includes/importer.php';
    require_once FIGHTRANK_PATH . 'includes/admin-ui.php';
}

/* ---------- Assets ---------- */
add_action( 'wp_enqueue_scripts', 'fightrank_enqueue_assets' );
function fightrank_enqueue_assets() {
    wp_enqueue_style(
        'fightrank-style',
        FIGHTRANK_URL . 'assets/css/fightrank.css',
        [],
        FIGHTRANK_VERSION
    );
    wp_enqueue_script(
        'fightrank-script',
        FIGHTRANK_URL . 'assets/js/fightrank.js',
        [ 'jquery' ],
        FIGHTRANK_VERSION,
        true
    );
    wp_localize_script( 'fightrank-script', 'fightrank_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'fightrank_rating_nonce' ),
        'logged_in' => is_user_logged_in(),
        'login_url' => wp_login_url( get_permalink() ),
    ]);
}

/* ---------- Template loader ---------- */
add_filter( 'template_include', 'fightrank_template_loader' );
function fightrank_template_loader( $template ) {
    if ( is_singular( 'fight' ) ) {
        $custom = FIGHTRANK_PATH . 'templates/single-fight.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    if ( is_singular( 'fighter' ) ) {
        $custom = FIGHTRANK_PATH . 'templates/single-fighter.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    if ( is_post_type_archive( 'fight' ) ) {
        $custom = FIGHTRANK_PATH . 'templates/archive-fight.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    if ( is_singular( 'ufc_event' ) ) {
        $custom = FIGHTRANK_PATH . 'templates/single-ufc_event.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    if ( is_post_type_archive( 'ufc_event' ) ) {
        $custom = FIGHTRANK_PATH . 'templates/archive-ufc_event.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    if ( is_post_type_archive( 'fighter' ) ) {
        $custom = FIGHTRANK_PATH . 'templates/archive-fighter.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    if ( is_front_page() ) {
        $custom = FIGHTRANK_PATH . 'templates/front-page.php';
        return file_exists( $custom ) ? $custom : $template;
    }
    return $template;
}

/* ---------- Navegación frontend ---------- */
add_action( 'wp_body_open', 'fightrank_render_nav' );
function fightrank_render_nav() {
    $is_fr_page = is_singular( [ 'fight', 'fighter', 'ufc_event' ] )
               || is_post_type_archive( [ 'fight', 'fighter', 'ufc_event' ] )
               || is_front_page();
    if ( ! $is_fr_page ) return;

    $links = [
        'Peleas'      => get_post_type_archive_link( 'fight' ),
        'Rankings'    => get_post_type_archive_link( 'fighter' ),
        'Eventos'     => get_post_type_archive_link( 'ufc_event' ),
    ];
    ?>
    <nav class="fr-nav" id="fr-nav">
        <div class="fr-nav__inner">
            <a href="<?= home_url() ?>" class="fr-nav__logo">FIGHT<span>RANK</span></a>

            <ul class="fr-nav__links" id="fr-nav-links">
                <?php foreach ( $links as $label => $url ) : ?>
                    <li><a href="<?= esc_url( $url ) ?>"><?= $label ?></a></li>
                <?php endforeach; ?>
            </ul>

            <div class="fr-nav__user">
                <?php if ( is_user_logged_in() ) :
                    $user = wp_get_current_user();
                    ?>
                    <span class="fr-nav__username"><?= esc_html( $user->display_name ) ?></span>
                    <a href="<?= wp_logout_url( home_url() ) ?>" class="fr-nav__logout">Salir</a>
                <?php else : ?>
                    <a href="<?= wp_login_url( get_permalink() ) ?>" class="fr-btn fr-btn--outline fr-btn--sm">
                        Entrar
                    </a>
                <?php endif; ?>
            </div>

            <button class="fr-nav__burger" id="fr-burger" aria-label="Menú">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>
    <?php
}

/* ---------- Ocultar header del tema en páginas FightRank ---------- */
add_action( 'wp_head', 'fightrank_hide_theme_header' );
function fightrank_hide_theme_header() {
    $is_fr_page = is_singular( [ 'fight', 'fighter', 'ufc_event' ] )
               || is_post_type_archive( [ 'fight', 'fighter', 'ufc_event' ] )
               || is_front_page();
    if ( ! $is_fr_page ) return;
    echo '<style>
        header, .site-header, #masthead, #header,
        .ast-desktop-header, .ast-mobile-header-wrap,
        .ast-above-header-wrap, .ast-below-header-wrap,
        .ast-main-header-wrap, .main-header-bar-wrap,
        .ast-header-break-point, .ast-primary-header-bar,
        .ast-site-identity, #ast-fixed-header,
        .site-branding { display:none!important; }
        .ast-page-builder-template .hfeed, .ast-container { max-width:100%!important; padding:0!important; }
        #page, .site { padding-top:0!important; margin-top:0!important; }
        .entry-content, .ast-article-single { padding:0!important; margin:0!important; }
        .site-content, #content { padding:0!important; }
    </style>';
}

/* ---------- Admin columns: Peleas ---------- */
add_filter( 'manage_fight_posts_columns', 'fightrank_fight_columns' );
function fightrank_fight_columns( $cols ) {
    unset( $cols['date'] );
    $cols['fr_fight_date'] = 'Fecha';
    $cols['fr_method']     = 'Método';
    $cols['fr_score']      = 'Nota FR';
    $cols['fr_votes']      = 'Votos';
    return $cols;
}

add_action( 'manage_fight_posts_custom_column', 'fightrank_fight_column_content', 10, 2 );
function fightrank_fight_column_content( $col, $post_id ) {
    $d = fightrank_get_fight_data( $post_id );
    if ( $col === 'fr_fight_date' ) {
        echo $d['fight_date'] ? esc_html( date_i18n( 'd/m/Y', strtotime( $d['fight_date'] ) ) ) : '—';
    } elseif ( $col === 'fr_method' ) {
        echo $d['method'] ? esc_html( $d['method'] ) : '—';
    } elseif ( $col === 'fr_score' ) {
        $s = $d['auto_score'];
        echo $s !== '' ? '<strong style="color:#FFD700">' . esc_html( $s ) . '</strong>' : '—';
    } elseif ( $col === 'fr_votes' ) {
        $stats = fightrank_get_rating_stats( $post_id );
        echo $stats['total'] > 0 ? esc_html( $stats['avg'] ) . ' <small>(' . $stats['total'] . ')</small>' : '—';
    }
}

add_filter( 'manage_edit-fight_sortable_columns', 'fightrank_fight_sortable_columns' );
function fightrank_fight_sortable_columns( $cols ) {
    $cols['fr_fight_date'] = 'fr_fight_date';
    $cols['fr_score']      = 'fr_score';
    return $cols;
}

/* ---------- Admin columns: Eventos ---------- */
add_filter( 'manage_ufc_event_posts_columns', 'fightrank_event_columns' );
function fightrank_event_columns( $cols ) {
    unset( $cols['date'] );
    $cols['fr_event_date']   = 'Fecha';
    $cols['fr_location']     = 'Lugar';
    $cols['fr_fight_count']  = 'Peleas';
    return $cols;
}

add_action( 'manage_ufc_event_posts_custom_column', 'fightrank_event_column_content', 10, 2 );
function fightrank_event_column_content( $col, $post_id ) {
    $d = fightrank_get_event_data( $post_id );
    if ( $col === 'fr_event_date' ) {
        echo $d['event_date'] ? esc_html( date_i18n( 'd/m/Y', strtotime( $d['event_date'] ) ) ) : '—';
    } elseif ( $col === 'fr_location' ) {
        echo $d['location'] ? esc_html( $d['location'] ) : '—';
    } elseif ( $col === 'fr_fight_count' ) {
        $count = count( get_posts([
            'post_type'   => 'fight',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => [[ 'key' => 'fr_event_id', 'value' => $post_id ]],
        ]) );
        echo $count ?: '0';
    }
}

/* ---------- Admin columns: Peleadores ---------- */
add_filter( 'manage_fighter_posts_columns', 'fightrank_fighter_columns' );
function fightrank_fighter_columns( $cols ) {
    unset( $cols['date'] );
    $cols['fr_record']   = 'Récord';
    $cols['fr_champion'] = 'Campeón';
    return $cols;
}

add_action( 'manage_fighter_posts_custom_column', 'fightrank_fighter_column_content', 10, 2 );
function fightrank_fighter_column_content( $col, $post_id ) {
    $d = fightrank_get_fighter_data( $post_id );
    if ( $col === 'fr_record' ) {
        echo esc_html( $d['wins'] . '-' . $d['losses'] . '-' . $d['draws'] );
    } elseif ( $col === 'fr_champion' ) {
        echo $d['is_champion'] === '1' ? '&#127881; Sí' : '—';
    }
}

/* ---------- Activation: crear tabla + flush permalinks ---------- */
register_activation_hook( __FILE__, 'fightrank_activate' );
function fightrank_activate() {
    fightrank_register_post_types();
    flush_rewrite_rules();
    fightrank_create_tables();
}

register_deactivation_hook( __FILE__, fn() => flush_rewrite_rules() );

function fightrank_create_tables() {
    global $wpdb;
    $table   = $wpdb->prefix . 'fightrank_ratings';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        fight_id   BIGINT UNSIGNED NOT NULL,
        user_id    BIGINT UNSIGNED NOT NULL,
        rating     TINYINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY fight_user (fight_id, user_id)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
