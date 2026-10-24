/* Grafik garis Tren 12 Bulan (Ringkasan Keuangan): data dari data-trend. */
document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.getElementById('financeTrend');
    if (!wrap || typeof Chart === 'undefined') {
        return;
    }

    const trend = JSON.parse(wrap.dataset.trend || '{}');
    const rp = (v) => 'Rp ' + Math.round(Math.abs(v)).toLocaleString('id-ID');
    const muted = '#546680';

    // Sumbu Y diringkas supaya tidak memakan lebar grafik.
    const short = function (v) {
        const a = Math.abs(v);
        if (a >= 1e9) return (a / 1e9).toFixed(1).replace('.0', '') + ' M';
        if (a >= 1e6) return (a / 1e6).toFixed(1).replace('.0', '') + ' jt';
        if (a >= 1e3) return Math.round(a / 1e3) + ' rb';
        return a;
    };

    // Monotone mencegah garis melengkung di bawah 0 atau melewati titik data.
    const line = (label, data, color) => ({
        label: label, data: data, borderColor: color, backgroundColor: color,
        borderWidth: 2.5, cubicInterpolationMode: 'monotone', pointRadius: 0,
        pointHoverRadius: 5, pointHoverBorderWidth: 2, pointHoverBorderColor: '#fff'
    });

    new Chart(wrap.querySelector('canvas'), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [line('Pemasukan', trend.income, '#198754'), line('Pengeluaran', trend.expense, '#dc3545')]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : {},
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { align: 'end', labels: { usePointStyle: true, pointStyle: 'line', boxWidth: 24, color: muted } },
                tooltip: {
                    backgroundColor: '#0b2447',
                    padding: 10,
                    callbacks: {
                        label: (c) => c.dataset.label + ': ' + rp(c.parsed.y),
                        footer: function (items) {
                            const net = trend.net[items[0].dataIndex];
                            return (net < 0 ? 'Rugi: ' : 'Laba: ') + rp(net);
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: muted, maxRotation: 0, autoSkip: true } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#e3ebf5' },
                    ticks: { color: muted, maxTicksLimit: 5, callback: (v) => short(v) }
                }
            }
        }
    });
});
