(function () {
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    const clearBtn = document.getElementById('signatureClearBtn');
    const hiddenInput = document.getElementById('signatureCanvasData');
    const form = document.getElementById('signatureForm');
    const fileInput = document.getElementById('signatureFileInput');

    let isDrawing = false;
    let hasDrawn = false;

    // Kosongkan kanvas tanda tangan dan atur gaya goresan.
    function resetCanvas() {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#1a1a1a';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        hasDrawn = false;
    }
    resetCanvas();

    // Koordinat pointer dalam skala kanvas (mouse dan sentuh).
    function getPoint(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY,
        };
    }

    // Mulai goresan.
    function startDraw(e) {
        e.preventDefault();
        isDrawing = true;
        hasDrawn = true;
        const p = getPoint(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    // Lanjutkan goresan selama pointer ditekan.
    function moveDraw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const p = getPoint(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }

    // Akhiri goresan.
    function endDraw() {
        isDrawing = false;
    }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', moveDraw);
    canvas.addEventListener('mouseup', endDraw);
    canvas.addEventListener('mouseleave', endDraw);
    canvas.addEventListener('touchstart', startDraw);
    canvas.addEventListener('touchmove', moveDraw);
    canvas.addEventListener('touchend', endDraw);

    clearBtn.addEventListener('click', resetCanvas);

    form.addEventListener('submit', function () {
        const drawTabActive = document.getElementById('src-draw').classList.contains('active');

        if (drawTabActive && hasDrawn) {
            hiddenInput.value = canvas.toDataURL('image/png');
            fileInput.value = '';
        } else {
            hiddenInput.value = '';
        }
    });
})();
