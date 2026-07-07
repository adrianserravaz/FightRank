(function ($) {
    'use strict';

    /* ================================================================
       Burger menu — mobile nav toggle
       ================================================================ */
    const $burger = $('#fr-burger');
    const $navLinks = $('#fr-nav-links');

    $burger.on('click', function () {
        const open = $navLinks.hasClass('fr-nav__links--open');
        $navLinks.toggleClass('fr-nav__links--open', !open);
        $burger.toggleClass('fr-nav__burger--open', !open);
    });

    /* Cerrar menú al hacer clic fuera */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#fr-nav').length) {
            $navLinks.removeClass('fr-nav__links--open');
            $burger.removeClass('fr-nav__burger--open');
        }
    });

    /* Nav activo según la sección actual */
    const $navAnchors = $('.fr-nav__links a');
    const currentUrl  = window.location.pathname;
    $navAnchors.each(function () {
        const href = new URL($(this).attr('href'), window.location.origin).pathname;
        if (href !== '/' && currentUrl.startsWith(href)) {
            $(this).addClass('fr-nav__link--active');
        }
    });

    /* ================================================================
       Sistema de valoración: estrellas interactivas
       ================================================================ */
    const $widget = $('#fr-rating-widget');
    if (!$widget.length) return;

    const $stars  = $('.fr-star-btn');
    const $label  = $('#fr-selected-label');
    const $btn    = $('#fr-submit-rating');
    const $msg    = $('#fr-rating-message');
    const fightId = $('.fr-single-fight').data('fight-id');

    let selectedRating = parseInt(
        $stars.filter('.fr-star-btn--active').last().data('value') || 0
    );

    /* Hover: resaltar hasta la estrella sobre la que pasamos */
    $stars.on('mouseenter', function () {
        const val = parseInt($(this).data('value'));
        $stars.each(function () {
            $(this).toggleClass('fr-star-btn--hover', parseInt($(this).data('value')) <= val);
        });
    });

    $stars.on('mouseleave', function () {
        $stars.removeClass('fr-star-btn--hover');
    });

    /* Click: seleccionar puntuación */
    $stars.on('click', function () {
        selectedRating = parseInt($(this).data('value'));

        $stars.each(function () {
            $(this).toggleClass('fr-star-btn--active', parseInt($(this).data('value')) <= selectedRating);
        });

        $label.text('Tu nota: ' + selectedRating + '/10');
        $btn.prop('disabled', false).text('Enviar valoración');
        $msg.text('').removeClass('fr-rating-message--success fr-rating-message--error');
    });

    /* Envío */
    $btn.on('click', function () {
        if (!selectedRating) return;

        $btn.prop('disabled', true).text('Enviando…');

        $.post(fightrank_ajax.ajax_url, {
            action:   'fightrank_rate',
            nonce:    fightrank_ajax.nonce,
            fight_id: fightId,
            rating:   selectedRating,
        })
        .done(function (res) {
            if (res.success) {
                const d = res.data;

                $('.fr-user-avg__value').text(d.avg);
                $('.fr-user-avg__meta').text(d.total + ' voto' + (d.total !== 1 ? 's' : ''));
                $('#fr-rating-count').text(d.total);
                $('.fr-rating-counter__label').text('valoracion' + (d.total !== 1 ? 'es' : '') + ' de usuarios');

                const total = d.total;
                $.each(d.distribution, function (score, cnt) {
                    const pct = total > 0 ? Math.round(cnt / total * 100) : 0;
                    $('.fr-dist__row').filter(function () {
                        return parseInt($('.fr-dist__label', this).text()) === parseInt(score);
                    }).find('.fr-dist__bar').css('width', pct + '%');
                    $('.fr-dist__row').filter(function () {
                        return parseInt($('.fr-dist__label', this).text()) === parseInt(score);
                    }).find('.fr-dist__count').text(cnt);
                });

                $msg.text('¡Valoración enviada! (' + d.user_rating + '/10)')
                    .addClass('fr-rating-message--success');
                $btn.text('Actualizar valoración').prop('disabled', false);

            } else {
                $msg.text(res.data.message || 'Error al enviar.')
                    .addClass('fr-rating-message--error');
                $btn.prop('disabled', false).text('Enviar valoración');
            }
        })
        .fail(function () {
            $msg.text('Error de conexión. Inténtalo de nuevo.')
                .addClass('fr-rating-message--error');
            $btn.prop('disabled', false).text('Enviar valoración');
        });
    });

}(jQuery));
