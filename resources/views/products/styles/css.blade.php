<style>
    .ar-model-popup .swal2-popup {
        padding: 0;
    }

    .ar-model-popup .swal2-html-container {
        padding: 0;
        margin: 0;
    }

    /* Custom styles for model viewer */
    model-viewer {
        --poster-color: transparent;
        --progress-bar-color: #007bff;
        --progress-bar-height: 4px;
    }

    model-viewer::part(default-progress-bar) {
        background-color: #007bff;
        height: 4px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .ar-model-popup {
            width: 95% !important;
        }

        .ar-model-popup model-viewer {
            height: 400px !important;
        }
    }
</style>
