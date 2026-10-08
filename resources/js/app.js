import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import * as htmlToImage from 'html-to-image';

window.Chart = Chart;
window.ChartDataLabels = ChartDataLabels;
window.htmlToImage = htmlToImage;

// Unduh PNG laporan & grafik berukuran F4 tetap (lihat resources/js/f4-export.js).
import * as f4Export from './f4-export';
window.f4Export = f4Export;

// Peringatan setoran ulangan di form input & spreadsheet (lihat resources/js/repeat-check.js).
import * as repeatCheck from './repeat-check';
window.repeatCheck = repeatCheck;

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const components = import.meta.glob('./components/**/*.js', { eager: true });
Object.entries(components).forEach(([path, definition]) => {
    const name = path.split('/').pop().replace('.js', '');
    Alpine.data(name, definition.default);
});

Alpine.start();
