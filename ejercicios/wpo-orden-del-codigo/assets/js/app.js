/* Un solo fichero, cargado con defer al final del <head>.
   Se descarga en paralelo (el preload scanner lo ve) y se ejecuta
   cuando el DOM ya está completo, así que todos los elementos existen. */

(function () {
    'use strict';

    /* ---------------------------------------------------------------
       Cuenta atrás
       Original: setInterval reescribiendo innerHTML cada segundo, con
       el script por encima del elemento y una fecha sin zona horaria.
       --------------------------------------------------------------- */

    var countdown = document.getElementById('countdowncarlos');

    if (countdown) {
        var target = new Date('2026-10-14T16:00:00+02:00').getTime();
        var output = countdown.querySelector('time');
        var timer = null;

        var tick = function () {
            var left = target - Date.now();

            if (left <= 0) {
                countdown.textContent = '¡Ha comenzado la formación!';
                clearInterval(timer);
                return;
            }

            var d = Math.floor(left / 86400000);
            var h = Math.floor((left % 86400000) / 3600000);
            var m = Math.floor((left % 3600000) / 60000);
            var s = Math.floor((left % 60000) / 1000);

            /* textContent sobre un nodo que ya existe: sin reconstruir el DOM */
            output.textContent = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
        };

        var start = function () {
            if (timer === null) {
                tick();
                timer = setInterval(tick, 1000);
            }
        };

        var stop = function () {
            clearInterval(timer);
            timer = null;
        };

        /* Si la pestaña no está a la vista, no hay nada que contar */
        document.addEventListener('visibilitychange', function () {
            document.hidden ? stop() : start();
        });

        start();
    }

    /* ---------------------------------------------------------------
       Barra fija al hacer scroll
       Original: $(window).scroll() con jQuery sin cargar → ReferenceError
       que abortaba el bloque y dejaba .fixed-navbar sin aplicarse nunca.
       --------------------------------------------------------------- */

    var navbar = document.querySelector('.navbar-header');

    if (navbar) {
        var ticking = false;

        window.addEventListener('scroll', function () {
            if (ticking) {
                return;
            }
            ticking = true;

            /* Agrupa la lectura de scrollY con el siguiente frame para
               no forzar un reflow en cada evento */
            window.requestAnimationFrame(function () {
                navbar.classList.toggle('fixed-navbar', window.scrollY >= 30);
                ticking = false;
            });
        }, { passive: true });
    }

    /* ---------------------------------------------------------------
       Menú móvil
       Original: onclick="" en el HTML + className += " clase" y una
       expresión regular para quitarla.
       --------------------------------------------------------------- */

    var mobileNav = document.getElementById('mobile-nav');
    var toggle = document.getElementById('menu-toggle');
    var close = document.getElementById('close-menu');

    if (mobileNav && toggle) {
        var setMenu = function (open) {
            mobileNav.classList.toggle('expand-menu', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', function () {
            setMenu(!mobileNav.classList.contains('expand-menu'));
        });

        if (close) {
            close.addEventListener('click', function () {
                setMenu(false);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                setMenu(false);
            }
        });
    }
}());
