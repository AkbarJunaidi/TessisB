/* Kartu Keuangan Dashboard: dropdown periode mengganti angka dan grafik dari data-periods. */
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('dashFinance');
    const canvas = document.getElementById('dashTrendChart');
    const select = document.getElementById('dashPeriod');
    if (!root || !canvas || !select || typeof Chart === 'undefined') {
        return;
    }

    const periods = JSON.parse(root.dataset.periods || '{}');
    const rp = (v) => 'Rp ' + Math.round(Math.abs(v)).toLocaleString('id-ID');
    const $ = (name) => root.querySelector('[data-f="' + name + '"]');

    // Sumbu Y diringkas supaya tidak memakan lebar grafik.
    const short = function (v) {
        const a = Math.abs(v);
        if (a >= 1e9) return (a / 1e9).toFixed(1).replace('.0', '') + ' M';
        if (a >= 1e6) return (a / 1e6).toFixed(1).replace('.0', '') + ' jt';
        if (a >= 1e3) return Math.round(a / 1e3) + ' rb';
        return a;
    };

    const line = (label, color) => ({
        label: label, data: [], borderColor: color, backgroundColor: color,
        borderWidth: 2, tension: 0.3, pointRadius: 2, pointHoverRadius: 5
    });

    const chart = new Chart(canvas, {
        type: 'line',
        data: { labels: [], datasets: [line('Pemasukan', '#198754'), line('Pengeluaran', '#dc3545')] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : {},
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + rp(c.parsed.y) } }
            },
            scales: {
                x: { ticks: { maxTicksLimit: 8, maxRotation: 0 } },
                y: { beginAtZero: true, ticks: { callback: (v) => short(v) } }
            }
        }
    });

    // goodWhenUp: pemasukan naik baik (hijau), pengeluaran naik buruk (merah).
    const delta = function (el, value, goodWhenUp) {
        if (value === null || value === undefined) {
            el.className = 'small text-muted';
            el.textContent = 'Tanpa pembanding';
            return;
        }
        const good = goodWhenUp ? value >= 0 : value <= 0;
        el.className = 'small fw-semibold ' + (good ? 'text-success' : 'text-danger');
        el.innerHTML = '<i class="bi bi-arrow-' + (value >= 0 ? 'up' : 'down') + '-right"></i> ' + Math.abs(value) +
            '% <span class="text-muted fw-normal">vs periode sebelumnya</span>';
    };

    const render = function (key) {
        const p = periods[key];
        if (!p) {
            return;
        }

        $('income').textContent = rp(p.income);
        $('expense').textContent = rp(p.expense);
        delta($('income-delta'), p.income_delta, true);
        delta($('expense-delta'), p.expense_delta, false);

        const loss = p.net < 0;
        $('net').textContent = rp(p.net);
        $('net').className = 'dash-value ' + (loss ? 'text-danger' : 'text-success');
        $('net-label').textContent = loss ? 'Rugi' : 'Laba';
        $('net-icon').className = 'bi me-1 ' + (loss ? 'bi-graph-down-arrow text-danger' : 'bi-graph-up-arrow text-success');
        $('net-tile').className = 'rounded-3 p-3 h-100 ' + (loss ? 'bg-danger-subtle' : 'bg-success-subtle');
        $('net-meta').textContent = p.income > 0 ? 'Margin ' + ((p.net / p.income) * 100).toFixed(1).replace('.', ',') + '%' : 'Tanpa pemasukan';

        chart.data.labels = p.trend.labels;
        chart.data.datasets[0].data = p.trend.income;
        chart.data.datasets[1].data = p.trend.expense;
        chart.update();
    };

    select.addEventListener('change', () => render(select.value));
    render(select.value);
});
