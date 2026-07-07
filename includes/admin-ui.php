<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* =====================================================================
   Limpiar menú lateral
   ===================================================================== */
add_action( 'admin_menu', 'fightrank_clean_menu', 999 );
function fightrank_clean_menu() {
    $remove = [
        'edit.php',                    // Posts
        'edit.php?post_type=page',     // Páginas
        'edit-comments.php',           // Comentarios
        'themes.php',                  // Apariencia
        'plugins.php',                 // Plugins
        'users.php',                   // Usuarios
        'options-general.php',         // Ajustes
    ];
    foreach ( $remove as $slug ) {
        remove_menu_page( $slug );
    }

    // Dejar Tools solo con el importador
    remove_submenu_page( 'tools.php', 'tools.php' );
    remove_submenu_page( 'tools.php', 'import.php' );
    remove_submenu_page( 'tools.php', 'export.php' );
    remove_submenu_page( 'tools.php', 'site-health.php' );
    remove_submenu_page( 'tools.php', 'export-personal-data.php' );
    remove_submenu_page( 'tools.php', 'erase-personal-data.php' );
}

/* =====================================================================
   Dashboard limpio con estadísticas FightRank
   ===================================================================== */
add_action( 'wp_dashboard_setup', 'fightrank_dashboard_setup' );
function fightrank_dashboard_setup() {
    // Eliminar widgets por defecto
    $remove = [
        'dashboard_quick_press', 'dashboard_recent_drafts',
        'dashboard_primary',     'dashboard_site_health',
        'dashboard_right_now',   'dashboard_activity',
    ];
    foreach ( $remove as $id ) {
        remove_meta_box( $id, 'dashboard', 'side' );
        remove_meta_box( $id, 'dashboard', 'normal' );
        remove_meta_box( $id, 'dashboard', 'core' );
    }

    add_meta_box( 'fightrank_stats', '📊 FightRank — Resumen', 'fightrank_dashboard_widget', 'dashboard', 'normal', 'high' );
}

function fightrank_dashboard_widget() {
    $fights   = wp_count_posts( 'fight' );
    $fighters = wp_count_posts( 'fighter' );
    $events   = wp_count_posts( 'ufc_event' );

    $n_fights   = (int) ( $fights->publish   ?? 0 );
    $n_fighters = (int) ( $fighters->publish ?? 0 );
    $n_events   = (int) ( $events->publish   ?? 0 );
    ?>
    <div style="display:flex;gap:24px;margin-bottom:24px">
        <?php foreach ( [
            [ $n_fights,   'Peleas',      admin_url( 'edit.php?post_type=fight' ) ],
            [ $n_fighters, 'Peleadores',  admin_url( 'edit.php?post_type=fighter' ) ],
            [ $n_events,   'Eventos',     admin_url( 'edit.php?post_type=ufc_event' ) ],
        ] as [ $n, $label, $url ] ) : ?>
        <a href="<?= esc_url( $url ) ?>" style="flex:1;background:#1a1a1a;color:#f0f0f0;text-align:center;padding:20px;border-radius:6px;text-decoration:none;border:1px solid #2e2e2e;transition:border-color .2s" onmouseover="this.style.borderColor='#C8102E'" onmouseout="this.style.borderColor='#2e2e2e'">
            <div style="font-size:2.5rem;font-weight:900;color:#C8102E;line-height:1"><?= number_format( $n ) ?></div>
            <div style="font-size:0.85rem;text-transform:uppercase;letter-spacing:2px;color:#888;margin-top:4px"><?= $label ?></div>
        </a>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:12px">
        <a href="<?= admin_url( 'post-new.php?post_type=fight' ) ?>" class="button button-primary">+ Nueva pelea</a>
        <a href="<?= admin_url( 'post-new.php?post_type=fighter' ) ?>" class="button">+ Nuevo peleador</a>
        <a href="<?= admin_url( 'post-new.php?post_type=ufc_event' ) ?>" class="button">+ Nuevo evento</a>
        <a href="<?= admin_url( 'tools.php?page=fightrank-importer' ) ?>" class="button">📥 Importar UFC</a>
    </div>
    <?php
}

/* =====================================================================
   Admin footer y título personalizados
   ===================================================================== */
add_filter( 'admin_footer_text', fn() => '<span>FightRank &mdash; Proyecto intermodular CESUR 25/26</span>' );
add_filter( 'update_footer',     fn() => '', 11 );

/* =====================================================================
   Ocultar barra de admin en el frontend
   ===================================================================== */
add_action( 'after_setup_theme', function () {
    if ( ! is_admin() ) {
        show_admin_bar( false );
    }
} );

/* =====================================================================
   Estilos extra del admin (logo en login)
   ===================================================================== */
add_action( 'login_enqueue_scripts', function () {
    echo '<style>
        body.login { background: #0D0D0D; }
        .login h1 a {
            background-image: none !important;
            font-family: "Barlow Condensed", Arial, sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: #C8102E;
            text-shadow: none;
            width: auto;
            height: auto;
            line-height: 1;
            text-indent: 0;
        }
        .login h1 a::before { content: "FIGHT"; color: #F0F0F0; }
        .login h1 a::after  { content: "RANK"; color: #C8102E; }
        #loginform { background: #1A1A1A; border: 1px solid #2E2E2E; border-radius: 6px; }
        .login label { color: #888; }
        #user_login, #user_pass { background: #242424; border-color: #2E2E2E; color: #F0F0F0; }
        #wp-submit { background: #C8102E; border-color: #9B0D23; font-weight: 700; letter-spacing: 1px; }
        #wp-submit:hover { background: #9B0D23; }
        #nav a, #backtoblog a { color: #888; }
    </style>';
} );
add_filter( 'login_headertext', fn() => 'FightRank' );
add_filter( 'login_headerurl',  fn() => home_url() );
