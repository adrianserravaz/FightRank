<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* =====================================================================
   Meta boxes para PELEAS
   ===================================================================== */
add_action( 'add_meta_boxes', 'fightrank_add_meta_boxes' );
function fightrank_add_meta_boxes() {
    add_meta_box(
        'fightrank_fight_data',
        'Datos de la pelea',
        'fightrank_fight_meta_box_cb',
        'fight',
        'normal',
        'high'
    );
    add_meta_box(
        'fightrank_fighter_data',
        'Datos del peleador',
        'fightrank_fighter_meta_box_cb',
        'fighter',
        'normal',
        'high'
    );
    add_meta_box(
        'fightrank_event_data',
        'Datos del evento',
        'fightrank_event_meta_box_cb',
        'ufc_event',
        'normal',
        'high'
    );
}

/* ---------- Meta box: Pelea ---------- */
function fightrank_fight_meta_box_cb( $post ) {
    wp_nonce_field( 'fightrank_save_fight', 'fightrank_fight_nonce' );
    $d = fightrank_get_fight_data( $post->ID );

    /* Obtener peleadores y eventos para los selectores */
    $fighters = get_posts([ 'post_type' => 'fighter', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ]);
    $events   = get_posts([ 'post_type' => 'ufc_event',  'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ]);
    ?>
    <style>
        .fr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .fr-field { margin-bottom: 12px; }
        .fr-field label { display: block; font-weight: 600; margin-bottom: 4px; }
        .fr-field input, .fr-field select { width: 100%; }
        .fr-section { margin-top: 20px; border-top: 1px solid #ddd; padding-top: 16px; }
        .fr-section h3 { margin: 0 0 12px; font-size: 14px; text-transform: uppercase; color: #c0392b; }
    </style>

    <div class="fr-grid">
        <!-- Evento -->
        <div class="fr-field">
            <label>Evento</label>
            <select name="fr_event_id">
                <option value="">-- Seleccionar --</option>
                <?php foreach ( $events as $ev ) : ?>
                    <option value="<?= $ev->ID ?>" <?= selected( $d['event_id'], $ev->ID, false ) ?>><?= esc_html( $ev->post_title ) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Fecha -->
        <div class="fr-field">
            <label>Fecha de la pelea</label>
            <input type="date" name="fr_fight_date" value="<?= esc_attr( $d['fight_date'] ) ?>">
        </div>

        <!-- Peleador 1 -->
        <div class="fr-field">
            <label>Peleador 1 (Ganador si aplica)</label>
            <select name="fr_fighter1_id">
                <option value="">-- Seleccionar --</option>
                <?php foreach ( $fighters as $f ) : ?>
                    <option value="<?= $f->ID ?>" <?= selected( $d['fighter1_id'], $f->ID, false ) ?>><?= esc_html( $f->post_title ) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Peleador 2 -->
        <div class="fr-field">
            <label>Peleador 2</label>
            <select name="fr_fighter2_id">
                <option value="">-- Seleccionar --</option>
                <?php foreach ( $fighters as $f ) : ?>
                    <option value="<?= $f->ID ?>" <?= selected( $d['fighter2_id'], $f->ID, false ) ?>><?= esc_html( $f->post_title ) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Resultado -->
    <div class="fr-section">
        <h3>Resultado</h3>
        <div class="fr-grid">
            <div class="fr-field">
                <label>Ganador</label>
                <select name="fr_winner">
                    <option value="">-- Sin resultado --</option>
                    <option value="fighter1" <?= selected( $d['winner'], 'fighter1', false ) ?>>Peleador 1</option>
                    <option value="fighter2" <?= selected( $d['winner'], 'fighter2', false ) ?>>Peleador 2</option>
                    <option value="draw" <?= selected( $d['winner'], 'draw', false ) ?>>Empate</option>
                    <option value="nc"     <?= selected( $d['winner'], 'nc',     false ) ?>>Sin Concurso</option>
                </select>
            </div>

            <div class="fr-field">
                <label>Método</label>
                <select name="fr_method">
                    <option value="">-- Seleccionar --</option>
                    <option value="KO/TKO"       <?= selected( $d['method'], 'KO/TKO',       false ) ?>>KO / TKO</option>
                    <option value="Submission"    <?= selected( $d['method'], 'Submission',    false ) ?>>Sumisión</option>
                    <option value="Decision-U"    <?= selected( $d['method'], 'Decision-U',    false ) ?>>Decisión Unánime</option>
                    <option value="Decision-S"    <?= selected( $d['method'], 'Decision-S',    false ) ?>>Decisión Dividida</option>
                    <option value="Decision-M"    <?= selected( $d['method'], 'Decision-M',    false ) ?>>Decisión Mayoritaria</option>
                    <option value="DQ"            <?= selected( $d['method'], 'DQ',            false ) ?>>Descalificación</option>
                </select>
            </div>

            <div class="fr-field">
                <label>Round</label>
                <input type="number" name="fr_round" value="<?= esc_attr( $d['round'] ) ?>" min="1" max="5">
            </div>

            <div class="fr-field">
                <label>Tiempo (MM:SS)</label>
                <input type="text" name="fr_time" value="<?= esc_attr( $d['time'] ) ?>" placeholder="4:35">
            </div>

            <div class="fr-field">
                <label>¿Pelea por título?</label>
                <select name="fr_title_fight">
                    <option value="0" <?= selected( $d['title_fight'], '0', false ) ?>>No</option>
                    <option value="1" <?= selected( $d['title_fight'], '1', false ) ?>>Sí</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="fr-section">
        <h3>Estadísticas de la pelea</h3>
        <div class="fr-grid">
            <div class="fr-field">
                <label>Knockdowns P1</label>
                <input type="number" name="fr_kd_f1" value="<?= esc_attr( $d['kd_f1'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Knockdowns P2</label>
                <input type="number" name="fr_kd_f2" value="<?= esc_attr( $d['kd_f2'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Golpes significativos P1</label>
                <input type="number" name="fr_sig_strikes_f1" value="<?= esc_attr( $d['sig_strikes_f1'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Golpes significativos P2</label>
                <input type="number" name="fr_sig_strikes_f2" value="<?= esc_attr( $d['sig_strikes_f2'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Derribos P1</label>
                <input type="number" name="fr_td_f1" value="<?= esc_attr( $d['td_f1'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Derribos P2</label>
                <input type="number" name="fr_td_f2" value="<?= esc_attr( $d['td_f2'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Intentos de sumisión P1</label>
                <input type="number" name="fr_sub_attempts_f1" value="<?= esc_attr( $d['sub_attempts_f1'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Intentos de sumisión P2</label>
                <input type="number" name="fr_sub_attempts_f2" value="<?= esc_attr( $d['sub_attempts_f2'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Tiempo total en pie (seg)</label>
                <input type="number" name="fr_time_standing" value="<?= esc_attr( $d['time_standing'] ) ?>" min="0">
            </div>
            <div class="fr-field">
                <label>Tiempo en suelo (seg)</label>
                <input type="number" name="fr_time_ground" value="<?= esc_attr( $d['time_ground'] ) ?>" min="0">
            </div>
        </div>
    </div>
    <?php
}

/* ---------- Meta box: Peleador ---------- */
function fightrank_fighter_meta_box_cb( $post ) {
    wp_nonce_field( 'fightrank_save_fighter', 'fightrank_fighter_nonce' );
    $d = fightrank_get_fighter_data( $post->ID );
    ?>
    <div class="fr-grid">
        <div class="fr-field">
            <label>Nombre completo</label>
            <input type="text" name="fr_full_name" value="<?= esc_attr( $d['full_name'] ) ?>">
        </div>
        <div class="fr-field">
            <label>Apodo</label>
            <input type="text" name="fr_nickname" value="<?= esc_attr( $d['nickname'] ) ?>">
        </div>
        <div class="fr-field">
            <label>Nacionalidad</label>
            <input type="text" name="fr_nationality" value="<?= esc_attr( $d['nationality'] ) ?>">
        </div>
        <div class="fr-field">
            <label>Fecha de nacimiento</label>
            <input type="date" name="fr_dob" value="<?= esc_attr( $d['dob'] ) ?>">
        </div>
        <div class="fr-field">
            <label>Victorias</label>
            <input type="number" name="fr_wins" value="<?= esc_attr( $d['wins'] ) ?>" min="0">
        </div>
        <div class="fr-field">
            <label>Derrotas</label>
            <input type="number" name="fr_losses" value="<?= esc_attr( $d['losses'] ) ?>" min="0">
        </div>
        <div class="fr-field">
            <label>Empates</label>
            <input type="number" name="fr_draws" value="<?= esc_attr( $d['draws'] ) ?>" min="0">
        </div>
        <div class="fr-field">
            <label>Estilo de lucha</label>
            <select name="fr_style">
                <option value="">-- Seleccionar --</option>
                <?php foreach ( ['Boxing','Muay Thai','Wrestling','BJJ','Kickboxing','MMA','Judo','Karate'] as $s ) : ?>
                    <option value="<?= $s ?>" <?= selected( $d['style'], $s, false ) ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fr-field">
            <label>¿Es campeón actualmente?</label>
            <select name="fr_is_champion">
                <option value="0" <?= selected( $d['is_champion'], '0', false ) ?>>No</option>
                <option value="1" <?= selected( $d['is_champion'], '1', false ) ?>>Sí</option>
            </select>
        </div>
       <div class="fr-field">
    <label>Ranking actual</label>
    <input type="number" name="fr_current_rank" value="<?= esc_attr( get_post_meta( $post->ID, 'fr_current_rank', true ) ) ?>" min="0" max="15" placeholder="0 campeón, 1-15 ranking">
</div>
        <div class="fr-field">
            <label>Instagram (usuario)</label>
            <input type="text" name="fr_instagram" value="<?= esc_attr( $d['instagram'] ) ?>" placeholder="@usuario">
        </div>
    </div>
    <?php
}

/* =====================================================================
   Guardar meta datos
   ===================================================================== */
add_action( 'save_post_fight',    'fightrank_save_fight_meta' );
add_action( 'save_post_fighter',  'fightrank_save_fighter_meta' );

function fightrank_save_fight_meta( $post_id ) {
    if ( ! isset( $_POST['fightrank_fight_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['fightrank_fight_nonce'], 'fightrank_save_fight' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    $fields = [
        'fr_event_id', 'fr_fight_date', 'fr_fighter1_id', 'fr_fighter2_id',
        'fr_winner', 'fr_method', 'fr_round', 'fr_time', 'fr_title_fight',
        'fr_kd_f1', 'fr_kd_f2', 'fr_sig_strikes_f1', 'fr_sig_strikes_f2',
        'fr_td_f1', 'fr_td_f2', 'fr_sub_attempts_f1', 'fr_sub_attempts_f2',
        'fr_time_standing', 'fr_time_ground',
    ];

    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }

    /* Recalcular puntuación automática al guardar */
    $score = fightrank_calculate_score( $post_id );
    update_post_meta( $post_id, 'fr_auto_score', $score );
}

function fightrank_save_fighter_meta( $post_id ) {
    if ( ! isset( $_POST['fightrank_fighter_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['fightrank_fighter_nonce'], 'fightrank_save_fighter' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    $fields = [
        'fr_full_name', 'fr_nickname', 'fr_nationality', 'fr_dob',
        'fr_wins', 'fr_losses', 'fr_draws', 'fr_style',
        'fr_is_champion', 'fr_current_rank', 'fr_instagram',
    ];

    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }
}

/* =====================================================================
   Helpers de lectura
   ===================================================================== */
function fightrank_get_fight_data( $post_id ) {
    $keys = [
        'event_id', 'fight_date', 'fighter1_id', 'fighter2_id',
        'winner', 'method', 'round', 'time', 'title_fight',
        'kd_f1', 'kd_f2', 'sig_strikes_f1', 'sig_strikes_f2',
        'td_f1', 'td_f2', 'sub_attempts_f1', 'sub_attempts_f2',
        'time_standing', 'time_ground', 'auto_score',
    ];
    $data = [];
    foreach ( $keys as $k ) {
        $data[ $k ] = get_post_meta( $post_id, "fr_$k", true );
    }
    return $data;
}

function fightrank_get_fighter_data( $post_id ) {
    $keys = [ 'full_name', 'nickname', 'nationality', 'dob', 'wins', 'losses', 'draws', 'style', 'is_champion', 'instagram' ];
    $data = [];
    foreach ( $keys as $k ) {
        $data[ $k ] = get_post_meta( $post_id, "fr_$k", true );
    }
    return $data;
}

function fightrank_get_event_data( $post_id ) {
    $keys = [ 'event_date', 'location', 'arena', 'event_number' ];
    $data = [];
    foreach ( $keys as $k ) {
        $data[ $k ] = get_post_meta( $post_id, "fr_$k", true );
    }
    return $data;
}

/* =====================================================================
   Meta box: Evento
   ===================================================================== */
function fightrank_event_meta_box_cb( $post ) {
    wp_nonce_field( 'fightrank_save_event', 'fightrank_event_nonce' );
    $d = fightrank_get_event_data( $post->ID );
    ?>
    <div class="fr-grid">
        <div class="fr-field">
            <label>Nombre/Número del evento</label>
            <input type="text" name="fr_event_number" value="<?= esc_attr( $d['event_number'] ) ?>" placeholder="UFC 300">
        </div>
        <div class="fr-field">
            <label>Fecha del evento</label>
            <input type="date" name="fr_event_date" value="<?= esc_attr( $d['event_date'] ) ?>">
        </div>
        <div class="fr-field">
            <label>Arena / Recinto</label>
            <input type="text" name="fr_arena" value="<?= esc_attr( $d['arena'] ) ?>" placeholder="T-Mobile Arena">
        </div>
        <div class="fr-field">
            <label>Ciudad y país</label>
            <input type="text" name="fr_location" value="<?= esc_attr( $d['location'] ) ?>" placeholder="Las Vegas, Nevada, USA">
        </div>
    </div>
    <?php
}

add_action( 'save_post_ufc_event', 'fightrank_save_event_meta' );
function fightrank_save_event_meta( $post_id ) {
    if ( ! isset( $_POST['fightrank_event_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['fightrank_event_nonce'], 'fightrank_save_event' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    $fields = [ 'fr_event_number', 'fr_event_date', 'fr_arena', 'fr_location' ];
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }
}
