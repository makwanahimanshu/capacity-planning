import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

$(document).ajaxStart(function () {
    $('#global-loader').fadeIn(150);
});

$(document).ajaxStop(function () {
    $('#global-loader').fadeOut(150);
});