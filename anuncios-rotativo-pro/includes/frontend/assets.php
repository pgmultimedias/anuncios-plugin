<?php
if (!defined('ABSPATH')) {
    exit;
}

// 8. SCRIPTS Y ESTILOS (JAVASCRIPT Y CSS) - ACTUALIZADO CON IN-ARTICLE
// =========================================================================
function arp_agregar_scripts_y_estilos() {
    // Estilos base existentes
    wp_enqueue_style(
        'arp-frontend',
        ARP_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        ARP_VERSION
    );

    wp_enqueue_script(
        'arp-frontend',
        ARP_PLUGIN_URL . 'assets/js/frontend.js',
        array(),
        ARP_VERSION,
        true
    );


    // JavaScript para el modo anuncio (popup/sticky/interstitial/inarticle)
    $modo_activado = get_option('arp_modo_anuncio_activado', 'on');
    if ($modo_activado === 'on') {
        $tipos = arp_obtener_tipos_modo_anuncio();
        if (!is_array($tipos)) {
            $tipos = array($tipos);
        }
        $delay = get_option('arp_modo_delay', '3000');
        $duracion = get_option('arp_modo_duracion', '10000');

        $modo_js = "
        document.addEventListener('DOMContentLoaded', function() {";

        if (in_array('popup', $tipos)) {
            $modo_js .= "
            // POPUP - Mostrar en cada visita/recarga
            const popupOverlay = document.getElementById('arp-modopopup-overlay');
            const popupClose = document.getElementById('arp-modopopup-close');
            const popupSlides = document.querySelectorAll('.arp-modopopup-slide');

            if (popupOverlay) {
                setTimeout(function() {
                    popupOverlay.style.display = 'flex';

                    // Rotación de slides
                    if (popupSlides.length > 1) {
                        let currentSlide = 0;
                        const rotationInterval = setInterval(function() {
                            popupSlides[currentSlide].style.display = 'none';
                            currentSlide = (currentSlide + 1) % popupSlides.length;
                            popupSlides[currentSlide].style.display = 'block';
                        }, 3000);

                        // Detener rotación cuando se cierra
                        popupClose.addEventListener('click', function() {
                            clearInterval(rotationInterval);
                        });
                    }

                    // Cerrar popup
                    popupClose.addEventListener('click', function() {
                        popupOverlay.style.display = 'none';
                    });

                    // Cerrar al hacer clic fuera
                    popupOverlay.addEventListener('click', function(e) {
                        if (e.target.id === 'arp-modopopup-overlay') {
                            popupOverlay.style.display = 'none';
                        }
                    });

                    // Auto cerrar después de la duración
                    setTimeout(function() {
                        if (popupOverlay.style.display !== 'none') {
                            popupOverlay.style.display = 'none';
                        }
                    }, " . esc_js($duracion) . ");
                }, " . esc_js($delay) . ");
            }";
        }

        if (in_array('sticky', $tipos)) {
            $sticky_duracion = get_option('arp_modo_sticky_duracion', '0');
            $modo_js .= "
            // STICKY FOOTER - Mostrar en cada visita/recarga
            const stickyContainer = document.getElementById('arp-modosticky-container');
            const stickyClose = document.getElementById('arp-modosticky-close');
            const stickySlides = document.querySelectorAll('.arp-modosticky-slide');

            if (stickyContainer) {
                setTimeout(function() {
                    stickyContainer.style.display = 'block';

                    // Rotación de slides
                    let rotationInterval = null;
                    if (stickySlides.length > 1) {
                        let currentSlide = 0;
                        rotationInterval = setInterval(function() {
                            stickySlides[currentSlide].style.display = 'none';
                            currentSlide = (currentSlide + 1) % stickySlides.length;
                            stickySlides[currentSlide].style.display = 'block';
                        }, 3000);
                    }

                    // Función para cerrar sticky
                    function cerrarSticky() {
                        if (rotationInterval) {
                            clearInterval(rotationInterval);
                        }
                        stickyContainer.style.display = 'none';
                    }

                    // Cerrar sticky al hacer clic en el botón
                    stickyClose.addEventListener('click', cerrarSticky);

                    // Auto cerrar después de la duración configurada (si es mayor a 0)
                    var stickyDuracion = " . intval($sticky_duracion) . ";
                    if (stickyDuracion > 0) {
                        setTimeout(function() {
                            if (stickyContainer.style.display !== 'none') {
                                cerrarSticky();
                            }
                        }, stickyDuracion);
                    }
                }, " . esc_js($delay) . ");
            }";
        }

        if (in_array('interstitial', $tipos)) {
            $modo_js .= "
            // INTERSTITIAL AD - Mostrar en cada visita/recarga
            const interstitialOverlay = document.getElementById('arp-modointerstitial-overlay');
            const interstitialClose = document.getElementById('arp-modointerstitial-close');
            const interstitialSlides = document.querySelectorAll('.arp-modointerstitial-slide');

            if (interstitialOverlay) {
                // Bloquear el scroll del body
                document.body.style.overflow = 'hidden';

                setTimeout(function() {
                    interstitialOverlay.style.display = 'block';
                    interstitialOverlay.classList.add('active');

                    // Rotación de slides
                    if (interstitialSlides.length > 1) {
                        let currentSlide = 0;
                        const rotationInterval = setInterval(function() {
                            interstitialSlides[currentSlide].style.display = 'none';
                            currentSlide = (currentSlide + 1) % interstitialSlides.length;
                            interstitialSlides[currentSlide].style.display = 'block';
                        }, 3000);

                        // Detener rotación cuando se cierra
                        interstitialClose.addEventListener('click', function() {
                            clearInterval(rotationInterval);
                        });
                    }

                    // Cerrar interstitial
                    interstitialClose.addEventListener('click', function() {
                        interstitialOverlay.style.display = 'none';
                        interstitialOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                }, " . esc_js($delay) . ");
            }";
        }

        // JavaScript para rotación de anuncios in-article
        $modo_js .= "
            // Rotación para anuncios in-article
            const inarticleContainers = document.querySelectorAll('.arp-inarticle-rotativo');
            inarticleContainers.forEach(function(container) {
                const slides = container.querySelectorAll('.arp-inarticle-slide');
                if (slides.length > 1) {
                    let currentSlide = 0;
                    setInterval(function() {
                        slides[currentSlide].style.display = 'none';
                        currentSlide = (currentSlide + 1) % slides.length;
                        slides[currentSlide].style.display = 'block';
                    }, 3000);
                }
            });
        ";

        // JavaScript para rotación y cierre del sticky del shortcode
        $sticky_duracion_shortcode = get_option('arp_modo_sticky_duracion', '0');
        $modo_js .= "
            // Rotación para sticky del shortcode
            const stickyShortcode = document.getElementById('arp-sticky-shortcode');
            if (stickyShortcode) {
                const stickyShortcodeSlides = stickyShortcode.querySelectorAll('.arp-sticky-slide');
                const stickyShortcodeClose = stickyShortcode.querySelector('.arp-sticky-close');

                let stickyRotationInterval = null;
                if (stickyShortcodeSlides.length > 1) {
                    let currentStickySlide = 0;
                    stickyRotationInterval = setInterval(function() {
                        stickyShortcodeSlides[currentStickySlide].style.display = 'none';
                        currentStickySlide = (currentStickySlide + 1) % stickyShortcodeSlides.length;
                        stickyShortcodeSlides[currentStickySlide].style.display = 'block';
                    }, 3000);
                }

                // Función para cerrar sticky del shortcode
                function cerrarStickyShortcode() {
                    if (stickyRotationInterval) {
                        clearInterval(stickyRotationInterval);
                    }
                    stickyShortcode.style.display = 'none';
                }

                // Cerrar sticky del shortcode al hacer clic
                if (stickyShortcodeClose) {
                    stickyShortcodeClose.addEventListener('click', cerrarStickyShortcode);
                }

                // Auto cerrar después de la duración configurada (si es mayor a 0)
                var stickyDuracionShortcode = " . intval($sticky_duracion_shortcode) . ";
                if (stickyDuracionShortcode > 0) {
                    setTimeout(function() {
                        if (stickyShortcode.style.display !== 'none') {
                            cerrarStickyShortcode();
                        }
                    }, stickyDuracionShortcode);
                }
            }
        });";

        wp_add_inline_script('arp-frontend', $modo_js);
    }
}
add_action( 'wp_enqueue_scripts', 'arp_agregar_scripts_y_estilos' );


// =========================================================================
