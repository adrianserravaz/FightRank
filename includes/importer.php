<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FR_IMP_FIGHTERS_URL', 'https://raw.githubusercontent.com/Greco1899/scrape_ufc_stats/main/ufc_fighter_details.csv' );
define( 'FR_IMP_TOTT_URL',     'https://raw.githubusercontent.com/Greco1899/scrape_ufc_stats/main/ufc_fighter_tott.csv' );
define( 'FR_IMP_FIGHTS_URL',   'https://raw.githubusercontent.com/Greco1899/scrape_ufc_stats/main/ufc_fight_results.csv' );
define( 'FR_IMP_EVENTS_URL',   'https://raw.githubusercontent.com/Greco1899/scrape_ufc_stats/main/ufc_event_details.csv' );
define( 'FR_IMP_BATCH',        50 );

/* ---------- Menú admin ---------- */
add_action( 'admin_menu', function () {
    add_submenu_page( 'tools.php', 'Importar UFC', 'Importar UFC', 'manage_options', 'fightrank-importer', 'fightrank_importer_page' );
} );

/* ---------- Página admin ---------- */
function fightrank_importer_page() {
    $p    = get_option( 'fr_imp_progress', [] );
    $ready = ! empty( $p['ready'] );
    $ft   = (int) ( $p['fighters_total'] ?? 0 );
    $fd   = (int) ( $p['fighters_done']  ?? 0 );
    $fgt  = (int) ( $p['fights_total']   ?? 0 );
    $fgd  = (int) ( $p['fights_done']    ?? 0 );
    ?>
    <div class="wrap">
        <h1>📥 Importar datos UFC</h1>
        <p>Fuente: <a href="https://github.com/Greco1899/scrape_ufc_stats" target="_blank">Greco1899/scrape_ufc_stats</a>
           — datos de <a href="http://ufcstats.com" target="_blank">ufcstats.com</a> (actualizado nov. 2025)</p>

        <table class="widefat striped" style="max-width:750px;margin-bottom:24px">
            <thead>
                <tr><th>Paso</th><th>Acción</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>1</strong></td>
                <td>Descargar CSVs de GitHub</td>
                <td id="st-prepare">
                    <?= $ready
                        ? "✅ {$ft} peleadores / {$fgt} peleas"
                        : '⏳ Pendiente' ?>
                </td>
                <td>
                    <button id="btn-prepare" class="button button-primary">
                        <?= $ready ? 'Volver a descargar' : 'Descargar datos' ?>
                    </button>
                </td>
            </tr>
            <tr>
                <td><strong>2</strong></td>
                <td>Importar peleadores</td>
                <td id="st-fighters">
                    <?= $fd > 0 ? ( $fd >= $ft ? "✅ {$fd}/{$ft}" : "🔄 {$fd}/{$ft}" ) : '⏳ Pendiente' ?>
                </td>
                <td>
                    <button id="btn-fighters" class="button" <?= ! $ready ? 'disabled' : '' ?>>
                        <?= ( $fd > 0 && $fd >= $ft ) ? 'Reimportar' : 'Importar peleadores' ?>
                    </button>
                    <div id="bar-fighters-wrap" style="height:6px;background:#ddd;border-radius:3px;margin-top:6px;width:220px;display:none">
                        <div id="bar-fighters" style="height:100%;background:#2271b1;border-radius:3px;width:0%"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>3</strong></td>
                <td>Importar peleas y eventos</td>
                <td id="st-fights">
                    <?= $fgd > 0 ? ( $fgd >= $fgt ? "✅ {$fgd}/{$fgt}" : "🔄 {$fgd}/{$fgt}" ) : '⏳ Pendiente' ?>
                </td>
                <td>
                    <button id="btn-fights" class="button" <?= ! $ready ? 'disabled' : '' ?>>
                        <?= ( $fgd > 0 && $fgd >= $fgt ) ? 'Reimportar' : 'Importar peleas' ?>
                    </button>
                    <div id="bar-fights-wrap" style="height:6px;background:#ddd;border-radius:3px;margin-top:6px;width:220px;display:none">
                        <div id="bar-fights" style="height:100%;background:#00a32a;border-radius:3px;width:0%"></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>4</strong></td>
                <td>Fotos de peleadores activos <small style="color:#999">(Wikipedia)</small></td>
                <td id="st-photos">⏳ Pendiente</td>
                <td>
                    <button id="btn-photos" class="button" <?= $fgd < $fgt ? 'disabled' : '' ?>>Importar fotos</button>
                    <div id="bar-photos-wrap" style="height:6px;background:#ddd;border-radius:3px;margin-top:6px;width:220px;display:none">
                        <div id="bar-photos" style="height:100%;background:#9b59b6;border-radius:3px;width:0%"></div>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <details>
            <summary style="cursor:pointer;color:#d63638;font-weight:600;margin-bottom:8px">
                ⚠️ Zona peligrosa — eliminar datos importados
            </summary>
            <div style="padding:16px;border:1px solid #d63638;border-radius:4px;max-width:500px;margin-top:8px">
                <p style="margin-top:0">Elimina <strong>todos</strong> los posts de peleadores, peleas y eventos. No se puede deshacer.</p>
                <button id="btn-reset" class="button button-link-delete">Eliminar todos los datos importados</button>
                <span id="st-reset" style="margin-left:12px"></span>
            </div>
        </details>
    </div>

    <script>
    jQuery(function ($) {
        var ajaxUrl = '<?= esc_js( admin_url( 'admin-ajax.php' ) ) ?>';
        var nonce   = '<?= wp_create_nonce( 'fr_importer' ) ?>';

        /* Paso 1 — Descargar */
        $('#btn-prepare').on('click', function () {
            var $b = $(this).prop('disabled', true).text('Descargando...');
            $('#st-prepare').text('⏳ Conectando con GitHub...');
            $.post(ajaxUrl, { action: 'fr_imp_prepare', nonce: nonce })
                .done(function (r) {
                    if (r.success) {
                        $('#st-prepare').html('✅ ' + r.data.msg);
                        $('#btn-fighters, #btn-fights').prop('disabled', false);
                        $b.text('Volver a descargar');
                    } else {
                        $('#st-prepare').html('❌ ' + r.data);
                        $b.text('Reintentar');
                    }
                })
                .fail(function () { $('#st-prepare').text('❌ Error de red'); $b.text('Reintentar'); })
                .always(function () { $b.prop('disabled', false); });
        });

        /* Importación genérica en lotes */
        function runImport(type, $btn, $st, $barWrap, $bar) {
            $btn.prop('disabled', true);
            $barWrap.show();
            var run = function (offset) {
                $st.text('Procesando... ' + offset + ' completados');
                $.post(ajaxUrl, { action: 'fr_imp_batch', nonce: nonce, type: type, offset: offset })
                    .done(function (r) {
                        if (!r.success) {
                            $st.html('❌ ' + r.data);
                            $btn.prop('disabled', false);
                            return;
                        }
                        var d = r.data;
                        var pct = d.total ? Math.round(d.processed / d.total * 100) : 0;
                        $bar.css('width', pct + '%');
                        $st.text(d.processed + ' / ' + d.total + ' (' + pct + '%)');
                        if (d.done) {
                            $st.html('✅ ' + d.processed + ' procesados · ' + d.skipped + ' omitidos');
                            $btn.prop('disabled', false);
                        } else {
                            run(d.processed);
                        }
                    })
                    .fail(function () {
                        $st.html('❌ Error de red. <button class="button-link" id="retry-' + type + '">Reintentar</button>');
                        $('#retry-' + type).on('click', function () { run(offset); });
                        $btn.prop('disabled', false);
                    });
            };
            run(0);
        }

        $('#btn-fighters').on('click', function () {
            runImport('fighters', $(this), $('#st-fighters'), $('#bar-fighters-wrap'), $('#bar-fighters'));
        });
        $('#btn-fights').on('click', function () {
            runImport('fights', $(this), $('#st-fights'), $('#bar-fights-wrap'), $('#bar-fights'));
        });

        /* Paso 4 — Fotos Wikipedia */
        $('#btn-photos').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            var $st  = $('#st-photos');
            var $bw  = $('#bar-photos-wrap').show();
            var $bar = $('#bar-photos');
            var run  = function (offset) {
                $st.text('Buscando fotos... ' + offset + ' procesados');
                $.post(ajaxUrl, { action: 'fr_imp_photos', nonce: nonce, offset: offset })
                    .done(function (r) {
                        if (!r.success) { $st.html('❌ ' + r.data); $btn.prop('disabled', false); return; }
                        var d = r.data;
                        var pct = d.total ? Math.round(d.processed / d.total * 100) : 100;
                        $bar.css('width', pct + '%');
                        $st.text(d.processed + ' / ' + d.total + ' · ' + d.found + ' fotos encontradas');
                        if (d.done) {
                            $st.html('✅ ' + d.found + ' fotos importadas de ' + d.total + ' peleadores activos');
                            $btn.prop('disabled', false).text('Reimportar fotos');
                        } else {
                            run(d.processed);
                        }
                    })
                    .fail(function () { $st.html('❌ Error de red'); $btn.prop('disabled', false); });
            };
            run(0);
        });

        /* Reset */
        $('#btn-reset').on('click', function () {
            if (!confirm('¿Seguro? Se eliminarán todos los peleadores, peleas y eventos importados.')) return;
            var $b = $(this).prop('disabled', true);
            $('#st-reset').text('Eliminando...');
            $.post(ajaxUrl, { action: 'fr_imp_reset', nonce: nonce })
                .done(function (r) {
                    $('#st-reset').html(r.success ? '✅ ' + r.data.msg : '❌ ' + r.data);
                })
                .always(function () { $b.prop('disabled', false); });
        });
    });
    </script>
    <?php
}

/* ---------- AJAX: Preparar ---------- */
add_action( 'wp_ajax_fr_imp_prepare', 'fightrank_ajax_imp_prepare' );
function fightrank_ajax_imp_prepare() {
    check_ajax_referer( 'fr_importer', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos' );

    $fighters_csv = fightrank_imp_fetch( FR_IMP_FIGHTERS_URL );
    $tott_csv     = fightrank_imp_fetch( FR_IMP_TOTT_URL );
    $fights_csv   = fightrank_imp_fetch( FR_IMP_FIGHTS_URL );
    $events_csv   = fightrank_imp_fetch( FR_IMP_EVENTS_URL );

    if ( ! $fighters_csv || ! $fights_csv ) {
        wp_send_json_error( 'No se pudieron descargar los datos de GitHub. Comprueba la conexión a internet.' );
    }

    $fighters_raw = fightrank_imp_parse( $fighters_csv );
    $tott_raw     = $tott_csv ? fightrank_imp_parse( $tott_csv ) : [];
    $fights_raw   = fightrank_imp_parse( $fights_csv );
    $events_raw   = $events_csv ? fightrank_imp_parse( $events_csv ) : [];

    // Indexar TOTT por URL
    $tott = [];
    foreach ( $tott_raw as $row ) {
        if ( ! empty( $row['URL'] ) ) $tott[ trim( $row['URL'] ) ] = $row;
    }

    // Indexar fechas de eventos por nombre: EVENT → date Y-m-d
    $event_dates = [];
    foreach ( $events_raw as $row ) {
        $ev_name  = trim( $row['EVENT'] ?? '' );
        $raw_date = trim( $row['DATE']  ?? '' );
        if ( $ev_name && $raw_date ) {
            $ts = strtotime( $raw_date );
            if ( $ts ) $event_dates[ $ev_name ] = date( 'Y-m-d', $ts );
        }
    }

    // Combinar fighters + TOTT
    $fighters = [];
    foreach ( $fighters_raw as $f ) {
        $url = trim( $f['URL'] ?? '' );
        $t   = $tott[ $url ] ?? [];
        $fighters[] = [
            'name'     => trim( ( $f['FIRST'] ?? '' ) . ' ' . ( $f['LAST'] ?? '' ) ),
            'nickname' => trim( $f['NICKNAME'] ?? '' ),
            'height'   => trim( $t['HEIGHT']  ?? '' ),
            'weight'   => trim( $t['WEIGHT']  ?? '' ),
            'reach'    => trim( $t['REACH']   ?? '' ),
            'stance'   => trim( $t['STANCE']  ?? '' ),
            'dob'      => trim( $t['DOB']     ?? '' ),
        ];
    }

    set_transient( 'fr_imp_fighters',    $fighters,    DAY_IN_SECONDS );
    set_transient( 'fr_imp_fights',      $fights_raw,  DAY_IN_SECONDS );
    set_transient( 'fr_imp_event_dates', $event_dates, DAY_IN_SECONDS );

    update_option( 'fr_imp_progress', [
        'ready'          => true,
        'fighters_total' => count( $fighters ),
        'fighters_done'  => 0,
        'fights_total'   => count( $fights_raw ),
        'fights_done'    => 0,
    ] );

    wp_send_json_success( [
        'msg' => count( $fighters ) . ' peleadores y ' . count( $fights_raw ) . ' peleas listos para importar.',
    ] );
}

/* ---------- AJAX: Lote ---------- */
add_action( 'wp_ajax_fr_imp_batch', 'fightrank_ajax_imp_batch' );
function fightrank_ajax_imp_batch() {
    check_ajax_referer( 'fr_importer', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos' );

    $type   = sanitize_key( $_POST['type']   ?? 'fighters' );
    $offset = absint( $_POST['offset'] ?? 0 );

    if ( $type === 'fighters' ) {
        fightrank_imp_fighters_batch( $offset );
    } else {
        fightrank_imp_fights_batch( $offset );
    }
}

/* ---------- Importar peleadores ---------- */
function fightrank_imp_fighters_batch( $offset ) {
    $all = get_transient( 'fr_imp_fighters' );
    if ( ! is_array( $all ) ) wp_send_json_error( 'Datos no disponibles. Ejecuta el Paso 1.' );

    $total   = count( $all );
    $batch   = array_slice( $all, $offset, FR_IMP_BATCH );
    $created = $skipped = 0;

    foreach ( $batch as $f ) {
        $name = trim( $f['name'] );
        if ( ! $name ) { $skipped++; continue; }

        if ( fightrank_imp_post_exists( $name, 'fighter' ) ) { $skipped++; continue; }

        $id = wp_insert_post( [
            'post_type'   => 'fighter',
            'post_title'  => $name,
            'post_status' => 'publish',
        ], true );

        if ( is_wp_error( $id ) ) { $skipped++; continue; }

        if ( $f['nickname'] )                             update_post_meta( $id, 'fr_nickname', $f['nickname'] );
        if ( $f['height'] && $f['height'] !== '--' )      update_post_meta( $id, 'fr_height',   $f['height'] );
        if ( $f['reach']  && $f['reach']  !== '--' )      update_post_meta( $id, 'fr_reach',    $f['reach'] );
        if ( $f['stance'] )                               update_post_meta( $id, 'fr_style',    $f['stance'] );

        if ( $f['dob'] && $f['dob'] !== '--' ) {
            $ts = strtotime( $f['dob'] );
            if ( $ts ) update_post_meta( $id, 'fr_dob', date( 'Y-m-d', $ts ) );
        }

        update_post_meta( $id, 'fr_wins',   0 );
        update_post_meta( $id, 'fr_losses', 0 );
        update_post_meta( $id, 'fr_draws',  0 );

        $wc = fightrank_imp_weight_to_class( $f['weight'] );
        if ( $wc ) wp_set_object_terms( $id, $wc, 'weight_class' );

        $created++;
    }

    $processed = $offset + count( $batch );
    $done      = $processed >= $total;

    $p = get_option( 'fr_imp_progress', [] );
    $p['fighters_done'] = $done ? $total : $processed;
    update_option( 'fr_imp_progress', $p );

    wp_send_json_success( compact( 'processed', 'total', 'created', 'skipped', 'done' ) );
}

/* ---------- Importar peleas ---------- */
function fightrank_imp_fights_batch( $offset ) {
    $all         = get_transient( 'fr_imp_fights' );
    $event_dates = get_transient( 'fr_imp_event_dates' ) ?: [];
    if ( ! is_array( $all ) ) wp_send_json_error( 'Datos no disponibles. Ejecuta el Paso 1.' );

    $total = count( $all );
    $batch = array_slice( $all, $offset, FR_IMP_BATCH );

    // Lookup fighters y eventos (1 query por batch)
    global $wpdb;
    $f_rows = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='fighter' AND post_status='publish'",
        ARRAY_A
    );
    $fighter_lut = array_column( $f_rows, 'ID', 'post_title' );

    $e_rows = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='ufc_event' AND post_status='publish'",
        ARRAY_A
    );
    $event_lut = array_column( $e_rows, 'ID', 'post_title' );

    $created = $skipped = 0;

    foreach ( $batch as $row ) {
        $bout = trim( $row['BOUT'] ?? '' );
        if ( ! $bout ) { $skipped++; continue; }

        $parts   = explode( ' vs. ', $bout, 2 );
        $f1_name = trim( $parts[0] ?? '' );
        $f2_name = trim( $parts[1] ?? '' );
        if ( ! $f1_name || ! $f2_name ) { $skipped++; continue; }

        $title = $f1_name . ' vs ' . $f2_name;
        if ( fightrank_imp_post_exists( $title, 'fight' ) ) { $skipped++; continue; }

        // Fecha desde el lookup de eventos (CSV ufc_event_details.csv)
        $ev_name_tmp = trim( $row['EVENT'] ?? '' );
        $fight_date  = $event_dates[ $ev_name_tmp ] ?? '';
        $post_date   = $fight_date ? $fight_date . ' 00:00:00' : '';

        $id = wp_insert_post( [
            'post_type'   => 'fight',
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_date'       => $post_date ?: current_time( 'mysql' ),
            'post_date_gmt'   => $post_date ? get_gmt_from_date( $post_date ) : current_time( 'mysql', 1 ),
        ], true );

        if ( is_wp_error( $id ) ) { $skipped++; continue; }

        // Peleadores
        $f1_id = (int) ( $fighter_lut[ $f1_name ] ?? 0 );
        $f2_id = (int) ( $fighter_lut[ $f2_name ] ?? 0 );
        if ( $f1_id ) update_post_meta( $id, 'fr_fighter1_id', $f1_id );
        if ( $f2_id ) update_post_meta( $id, 'fr_fighter2_id', $f2_id );

        // Resultado
        $outcome = strtoupper( trim( $row['OUTCOME'] ?? '' ) );
        if      ( $outcome === 'W/L' )                 $winner = 'fighter1';
        elseif  ( $outcome === 'L/W' )                 $winner = 'fighter2';
        elseif  ( strpos( $outcome, 'D' )  !== false ) $winner = 'draw';
        elseif  ( strpos( $outcome, 'NC' ) !== false ) $winner = 'nc';
        else                                           $winner = '';
        update_post_meta( $id, 'fr_winner', $winner );

        // Método, round, tiempo, fecha
        update_post_meta( $id, 'fr_method',     fightrank_imp_map_method( $row['METHOD'] ?? '' ) );
        update_post_meta( $id, 'fr_round',      sanitize_text_field( $row['ROUND'] ?? '' ) );
        update_post_meta( $id, 'fr_time',       sanitize_text_field( $row['TIME']  ?? '' ) );
        if ( $fight_date ) update_post_meta( $id, 'fr_fight_date', $fight_date );

        // División y título
        $wc_raw     = $row['WEIGHTCLASS'] ?? '';
        $title_fight = strpos( $wc_raw, 'Title' ) !== false ? '1' : '0';
        update_post_meta( $id, 'fr_title_fight', $title_fight );
        $wc = fightrank_imp_extract_wc( $wc_raw );
        if ( $wc ) wp_set_object_terms( $id, $wc, 'weight_class' );

        // Evento (crear si no existe)
        $ev_name = trim( $row['EVENT'] ?? '' );
        if ( $ev_name ) {
            if ( ! isset( $event_lut[ $ev_name ] ) ) {
                $ev_id = wp_insert_post( [
                    'post_type'     => 'ufc_event',
                    'post_title'    => $ev_name,
                    'post_status'   => 'publish',
                    'post_date'     => $post_date ?: current_time( 'mysql' ),
                    'post_date_gmt' => $post_date ? get_gmt_from_date( $post_date ) : current_time( 'mysql', 1 ),
                ] );
                if ( ! is_wp_error( $ev_id ) ) {
                    $event_lut[ $ev_name ] = $ev_id;
                    if ( $fight_date ) update_post_meta( $ev_id, 'fr_event_date', $fight_date );
                }
            }
            if ( isset( $event_lut[ $ev_name ] ) ) {
                update_post_meta( $id, 'fr_event_id', (int) $event_lut[ $ev_name ] );
            }
        }

        // Puntuación automática
        $score = fightrank_calculate_score( $id );
        update_post_meta( $id, 'fr_auto_score', $score );

        // Actualizar récord W-L-D
        if ( $f1_id && $winner ) fightrank_imp_update_record( $f1_id, $winner === 'fighter1' ? 'w' : ( $winner === 'draw' ? 'd' : ( $winner === 'nc' ? '' : 'l' ) ) );
        if ( $f2_id && $winner ) fightrank_imp_update_record( $f2_id, $winner === 'fighter2' ? 'w' : ( $winner === 'draw' ? 'd' : ( $winner === 'nc' ? '' : 'l' ) ) );

        $created++;
    }

    $processed = $offset + count( $batch );
    $done      = $processed >= $total;

    $p = get_option( 'fr_imp_progress', [] );
    $p['fights_done'] = $done ? $total : $processed;
    update_option( 'fr_imp_progress', $p );

    wp_send_json_success( compact( 'processed', 'total', 'created', 'skipped', 'done' ) );
}

/* ---------- AJAX: Reset ---------- */
add_action( 'wp_ajax_fr_imp_reset', 'fightrank_ajax_imp_reset' );
function fightrank_ajax_imp_reset() {
    check_ajax_referer( 'fr_importer', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos' );

    $del = 0;
    foreach ( [ 'fighter', 'fight', 'ufc_event' ] as $type ) {
        $ids = get_posts( [ 'post_type' => $type, 'numberposts' => -1, 'fields' => 'ids' ] );
        foreach ( $ids as $pid ) { wp_delete_post( $pid, true ); $del++; }
    }
    delete_transient( 'fr_imp_fighters' );
    delete_transient( 'fr_imp_fights' );

    $p = get_option( 'fr_imp_progress', [] );
    $p['fighters_done'] = 0;
    $p['fights_done']   = 0;
    update_option( 'fr_imp_progress', $p );

    wp_send_json_success( [ 'msg' => "{$del} posts eliminados." ] );
}

/* ---------- Utilidades ---------- */
function fightrank_imp_fetch( $url ) {
    $r = wp_remote_get( $url, [ 'timeout' => 45 ] );
    if ( is_wp_error( $r ) || wp_remote_retrieve_response_code( $r ) !== 200 ) return false;
    return wp_remote_retrieve_body( $r );
}

function fightrank_imp_parse( $text ) {
    $text   = trim( str_replace( [ "\r\n", "\r" ], "\n", $text ) );
    $handle = fopen( 'php://temp', 'r+' );
    fwrite( $handle, $text );
    rewind( $handle );
    $headers = null;
    $rows    = [];
    while ( ( $row = fgetcsv( $handle ) ) !== false ) {
        if ( $headers === null ) { $headers = array_map( 'trim', $row ); continue; }
        if ( count( $row ) === count( $headers ) ) $rows[] = array_combine( $headers, $row );
    }
    fclose( $handle );
    return $rows;
}

function fightrank_imp_post_exists( $title, $post_type ) {
    global $wpdb;
    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type=%s AND post_status!='trash' LIMIT 1",
        $title, $post_type
    ) );
}

function fightrank_imp_map_method( $raw ) {
    $r = strtolower( trim( $raw ) );
    if ( strpos( $r, 'ko' ) !== false || strpos( $r, 'tko' ) !== false ) return 'KO/TKO';
    if ( strpos( $r, 'submission' ) !== false ) return 'Submission';
    if ( strpos( $r, 'unanimous' ) !== false )  return 'Decision-U';
    if ( strpos( $r, 'split' ) !== false )      return 'Decision-S';
    if ( strpos( $r, 'majority' ) !== false )   return 'Decision-M';
    if ( strpos( $r, 'disqualif' ) !== false )  return 'DQ';
    return '';
}

function fightrank_imp_extract_wc( $str ) {
    $s = preg_replace( '/^UFC\s+/i', '', trim( $str ) );
    $s = preg_replace( '/\s*Title\s+Bout\s*$/i', '', $s );
    $s = preg_replace( '/\s*Bout\s*$/i', '', $s );
    return trim( $s );
}

function fightrank_imp_weight_to_class( $w ) {
    static $map = [
        115 => "Women's Strawweight", 125 => 'Flyweight',         135 => 'Bantamweight',
        145 => 'Featherweight',       155 => 'Lightweight',       170 => 'Welterweight',
        185 => 'Middleweight',        205 => 'Light Heavyweight', 265 => 'Heavyweight',
    ];
    preg_match( '/(\d+)/', (string) $w, $m );
    return $map[ (int) ( $m[1] ?? 0 ) ] ?? '';
}

function fightrank_imp_update_record( $fighter_id, $type ) {
    $key = [ 'w' => 'fr_wins', 'l' => 'fr_losses', 'd' => 'fr_draws' ][ $type ] ?? null;
    if ( ! $key ) return;
    update_post_meta( $fighter_id, $key, (int) get_post_meta( $fighter_id, $key, true ) + 1 );
}

/* =====================================================================
   PASO 4 — Fotos de peleadores activos (Wikipedia)
   ===================================================================== */
add_action( 'wp_ajax_fr_imp_photos', 'fightrank_ajax_imp_photos' );
function fightrank_ajax_imp_photos() {
    check_ajax_referer( 'fr_importer', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Sin permisos' );

    @set_time_limit( 300 );

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $offset     = (int) ( $_POST['offset'] ?? 0 );
    $batch_size = 3;

    if ( $offset === 0 ) {
        $ids = fightrank_imp_active_fighter_ids();
        set_transient( 'fr_imp_photo_queue', $ids, DAY_IN_SECONDS );
    }

    $all_ids = get_transient( 'fr_imp_photo_queue' );
    if ( ! is_array( $all_ids ) || empty( $all_ids ) ) {
        wp_send_json_error( 'No se encontraron peleadores activos (peleas desde 2022). Comprueba que la importación está completa.' );
    }

    $total = count( $all_ids );
    $batch = array_slice( $all_ids, $offset, $batch_size );
    $found = 0;

    foreach ( $batch as $fighter_id ) {
        if ( has_post_thumbnail( $fighter_id ) ) continue;

        $name      = get_the_title( $fighter_id );
        $photo_url = fightrank_imp_wiki_photo( $name );
        if ( ! $photo_url ) continue;

        // Descargar a archivo temporal
        $tmp = download_url( $photo_url, 15 );
        if ( is_wp_error( $tmp ) ) continue;

        $ext  = pathinfo( strtok( $photo_url, '?' ), PATHINFO_EXTENSION ) ?: 'jpg';
        $file = [
            'name'     => sanitize_file_name( $name . '.' . $ext ),
            'tmp_name' => $tmp,
        ];

        $att_id = media_handle_sideload( $file, $fighter_id, $name );
        @unlink( $tmp );

        if ( ! is_wp_error( $att_id ) ) {
            set_post_thumbnail( $fighter_id, $att_id );
            $found++;
        }
    }

    $processed = $offset + count( $batch );
    $done      = $processed >= $total;

    wp_send_json_success( compact( 'processed', 'total', 'found', 'done' ) );
}

function fightrank_imp_active_fighter_ids() {
    global $wpdb;
    // Peleadores que aparecen en peleas desde 2022
    $rows = $wpdb->get_col( "
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE pm.meta_key IN ('fr_fighter1_id','fr_fighter2_id')
          AND p.post_type   = 'fight'
          AND p.post_status = 'publish'
          AND p.post_date  >= '2022-01-01'
          AND pm.meta_value > 0
    " );
    return array_values( array_unique( array_map( 'intval', array_filter( $rows ) ) ) );
}

function fightrank_imp_wiki_photo( $name ) {
    $attempts = [
        $name,
        $name . ' (fighter)',
        $name . ' (mixed martial artist)',
    ];

    foreach ( $attempts as $attempt ) {
        $slug     = str_replace( ' ', '_', $attempt );
        $url      = 'https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode( $slug );
        $response = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'FightRank/1.0 (educational project)',
            'sslverify'  => false,
        ]);

        if ( is_wp_error( $response ) ) continue;
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) continue;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['thumbnail']['source'] ) ) continue;
        if ( ( $data['type'] ?? '' ) === 'disambiguation' ) continue;

        return $data['thumbnail']['source'];
    }

    return null;
}
