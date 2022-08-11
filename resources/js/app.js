require('./bootstrap');
import Chart from 'chart.js/auto';

window.Chart = Chart;

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

window.Alpine = Alpine;

Alpine.start();