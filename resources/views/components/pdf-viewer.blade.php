<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>
<style>
    .pdfjs-viewer-container {
        width: 100%;
        height: 100%;
        overflow: auto;
        background: #525659;
    }
    .pdfjs-viewer-container .pdf-page-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 0;
    }
    .pdfjs-viewer-container .pdf-page {
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    .pdfjs-viewer-container .pdf-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #fff;
        font-size: 1.1rem;
    }
    .pdfjs-viewer-container .pdf-error {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #ff6b6b;
        font-size: 1.1rem;
    }
    .pdfjs-viewer-container canvas {
        display: block;
    }
</style>

<script>
    window.pdfRenderQueue = [];
    window.pdfRendering = false;

    function renderPdfIntoContainer(containerId, url, options) {
        options = options || {};
        var container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<div class="pdf-loading"><i class="mdi mdi-loading mdi-spin me-2"></i>Memuat PDF...</div>';

        var httpHeaders = options.headers || {};

        fetch(url, {
            headers: httpHeaders,
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) throw new Error('Gagal memuat PDF');
            return response.arrayBuffer();
        })
        .then(function(arrayBuffer) {
            var loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            return loadingTask.promise;
        })
        .then(function(pdf) {
            container.innerHTML = '';
            var wrapper = document.createElement('div');
            wrapper.className = 'pdf-page-wrapper';
            container.appendChild(wrapper);

            var scale = options.scale || 1.5;
            var totalPages = pdf.numPages;

            for (var i = 1; i <= totalPages; i++) {
                (function(pageNum) {
                    pdf.getPage(pageNum).then(function(page) {
                        var viewport = page.getViewport({ scale: scale });
                        var canvas = document.createElement('canvas');
                        canvas.className = 'pdf-page';
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;

                        var ctx = canvas.getContext('2d');
                        var renderContext = {
                            canvasContext: ctx,
                            viewport: viewport
                        };

                        page.render(renderContext).promise.then(function() {
                            wrapper.appendChild(canvas);
                        });
                    });
                })(i);
            }
        })
        .catch(function(error) {
            container.innerHTML = '<div class="pdf-error"><i class="mdi mdi-alert-circle me-2"></i>' + error.message + '</div>';
        });
    }
</script>
