<script>
    function viewArModel(productId, arModelUrl, productName) {
        // Check device capabilities and show warnings
        const performanceWarning = getDevicePerformanceWarning();

        Swal.fire({
            title: `<h3 style="color: #2c3e50;">🥽 AR Model - ${productName}</h3>`,
            html: `
                <div style="padding: 20px;">
                    ${performanceWarning}

                    <div style="margin-bottom: 20px; text-align: center;">
                        <p style="color: #7f8c8d; margin-bottom: 15px;">
                            Use your mouse to rotate, zoom, and interact with the 3D model.
                        </p>
                        <div style="background: #e8f4f8; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                            <small style="color: #2c3e50;">
                                📱 <strong>Mobile tip:</strong> Use touch gestures to rotate and pinch to zoom<br>
                                🖥️ <strong>Desktop tip:</strong> Click and drag to rotate, scroll to zoom
                            </small>
                        </div>
                    </div>

                    <div style="width: 100%; height: 500px; border: 2px solid #e8f4f8; border-radius: 10px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <model-viewer
                            id="ar-model-viewer"
                            src="${arModelUrl}"
                            alt="3D model of ${productName}"
                            auto-rotate
                            camera-controls
                            interaction-policy="always-allow"
                            style="width: 100%; height: 100%;"
                            loading="eager"
                            reveal="auto"
                            environment-image="neutral"
                            shadow-intensity="1"
                            camera-orbit="0deg 75deg 105%"
                            min-camera-orbit="auto auto auto"
                            max-camera-orbit="auto auto auto"
                            min-field-of-view="30deg"
                            max-field-of-view="120deg">

                            <!-- Loading indicator -->
                            <div slot="poster" style="display: flex; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <div style="text-align: center;">
                                    <div class="spinner-border text-light mb-3" role="status"></div>
                                    <p>Loading 3D Model...</p>
                                    <small>This may take a moment on slower devices</small>
                                </div>
                            </div>

                            <!-- Error fallback -->
                            <div slot="error" style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d; text-align: center; padding: 20px;">
                                <div>
                                    <h4 style="color: #e74c3c;">❌ Unable to load 3D model</h4>
                                    <p style="margin: 15px 0;">The AR model file may be:</p>
                                    <ul style="text-align: left; margin: 15px auto; max-width: 300px; color: #7f8c8d;">
                                        <li>Corrupted or incompatible</li>
                                        <li>Too large for your device</li>
                                        <li>Not supported by your browser</li>
                                        <li>Network connection issue</li>
                                    </ul>
                                    <button type="button" id="retry-model" class="btn btn-primary btn-sm mt-2">
                                        🔄 Try Again
                                    </button>
                                </div>
                            </div>
                        </model-viewer>
                    </div>

                    <!-- Performance monitor -->
                    <div id="performance-monitor" style="display: none; margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <small style="color: #856404;">
                            <strong>⚠️ Performance Notice:</strong>
                            <span id="performance-message">Model is using significant resources</span>
                        </small>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 15px;">
                            <button type="button" id="reset-camera" class="btn btn-outline-primary btn-sm">
                                📷 Reset View
                            </button>
                            <button type="button" id="toggle-autorotate" class="btn btn-outline-secondary btn-sm">
                                🔄 Toggle Rotation
                            </button>
                            <button type="button" id="fullscreen-model" class="btn btn-outline-success btn-sm">
                                🔍 Fullscreen
                            </button>
                            <button type="button" id="reduce-quality" class="btn btn-outline-warning btn-sm" style="display: none;">
                                🚀 Reduce Quality
                            </button>
                        </div>
                        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                            <small style="color: #6c757d;">
                                <strong>Controls:</strong> Left click + drag to rotate • Right click + drag to pan • Scroll to zoom
                            </small>
                        </div>
                    </div>
                </div>
            `,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                popup: 'ar-model-popup'
            },
            didOpen: () => {
                initializeArModelControls();
                startPerformanceMonitoring();
            },
            willClose: () => {
                // Clean up model viewer and performance monitoring
                const modelViewer = document.getElementById('ar-model-viewer');
                if (modelViewer) {
                    modelViewer.autoRotate = false;
                }
                stopPerformanceMonitoring();
            }
        });
    }

    function getDevicePerformanceWarning() {
        const userAgent = navigator.userAgent.toLowerCase();
        const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
        const isOldBrowser = !window.WebGLRenderingContext;
        const ram = navigator.deviceMemory || 0; // Available in some browsers
        const cores = navigator.hardwareConcurrency || 0;

        let warningLevel = 'none';
        let warningMessage = '';

        // Detect potentially slow devices
        if (isMobile) {
            warningLevel = 'medium';
            warningMessage = 'Mobile devices may experience slower performance with complex 3D models.';
        }

        if (ram > 0 && ram < 4) {
            warningLevel = 'high';
            warningMessage = 'Your device has limited memory. The 3D model may load slowly or cause performance issues.';
        }

        if (cores > 0 && cores < 4) {
            warningLevel = 'medium';
            warningMessage = 'Your device has limited processing power. Complex models may run slowly.';
        }

        if (isOldBrowser) {
            warningLevel = 'critical';
            warningMessage = 'Your browser may not support 3D models. Please update your browser for the best experience.';
        }

        // Check for known slow devices
        if (userAgent.includes('android') && userAgent.includes('chrome/') &&
            parseInt(userAgent.split('chrome/')[1]) < 70) {
            warningLevel = 'high';
            warningMessage = 'Your browser version may have performance issues with 3D models. Consider updating Chrome.';
        }

        if (warningLevel === 'none') return '';

        const colors = {
            'medium': { bg: '#fff3cd', border: '#ffc107', text: '#856404' },
            'high': { bg: '#f8d7da', border: '#f5c6cb', text: '#721c24' },
            'critical': { bg: '#f5c6cb', border: '#f1b0b7', text: '#721c24' }
        };

        const icons = {
            'medium': '⚠️',
            'high': '🚨',
            'critical': '❌'
        };

        const color = colors[warningLevel];
        const icon = icons[warningLevel];

        return `
            <div style="margin-bottom: 20px; padding: 15px; background: ${color.bg}; border: 1px solid ${color.border}; border-radius: 8px; border-left: 4px solid ${color.border};">
                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 1.2em; margin-right: 10px;">${icon}</span>
                    <strong style="color: ${color.text};">Performance Warning</strong>
                </div>
                <p style="color: ${color.text}; margin: 0; font-size: 0.9em;">
                    ${warningMessage}
                </p>
                ${warningLevel === 'critical' ?
            `<div style="margin-top: 10px;">
                        <small style="color: ${color.text};">
                            <strong>Recommendation:</strong> Update your browser or try on a different device.
                        </small>
                    </div>` : ''
        }
            </div>
        `;
    }

    let performanceMonitor = {
        interval: null,
        startTime: null,
        frameCount: 0,
        lastFrameTime: 0,
        suppressed: false, // added: suppress further warnings after reduce quality
    };

    function startPerformanceMonitoring() {
        const modelViewer = document.getElementById('ar-model-viewer');
        if (!modelViewer) return;

        performanceMonitor.startTime = performance.now();
        performanceMonitor.frameCount = 0;
        performanceMonitor.suppressed = false; // reset suppression when opening viewer

        // Monitor FPS and performance
        performanceMonitor.interval = setInterval(() => {
            const currentTime = performance.now();
            const deltaTime = currentTime - performanceMonitor.lastFrameTime;

            if (deltaTime > 100) { // More than 100ms between frames (< 10 FPS)
                showPerformanceWarning('low-fps', 'Low frame rate detected. Consider reducing quality for better performance.');
            }

            performanceMonitor.lastFrameTime = currentTime;
            performanceMonitor.frameCount++;

            // Check memory usage if available
            if (performance.memory) {
                const memoryUsage = performance.memory.usedJSHeapSize / performance.memory.jsHeapSizeLimit;
                if (memoryUsage > 0.8) {
                    showPerformanceWarning('high-memory', 'High memory usage detected. The model may cause your browser to slow down.');
                }
            }
        }, 1000);

        // Listen for model viewer events
        modelViewer.addEventListener('error', handleModelError);
        modelViewer.addEventListener('load', handleModelLoad);
        modelViewer.addEventListener('progress', handleModelProgress);
    }

    function stopPerformanceMonitoring() {
        if (performanceMonitor.interval) {
            clearInterval(performanceMonitor.interval);
            performanceMonitor.interval = null;
        }
    }

    function showPerformanceWarning(type, message) {
        // added: short-circuit when suppressed
        if (performanceMonitor.suppressed) return;

        const monitor = document.getElementById('performance-monitor');
        const messageEl = document.getElementById('performance-message');
        const reduceQualityBtn = document.getElementById('reduce-quality');

        if (monitor && messageEl) {
            messageEl.textContent = message;
            monitor.style.display = 'block';

            if (type === 'low-fps' || type === 'high-memory') {
                reduceQualityBtn.style.display = 'inline-block';
            }
        }
    }

    function handleModelError(event) {
        console.error('Model Viewer Error:', event.detail);

        // Show user-friendly error message
        const errorSlot = document.querySelector('model-viewer [slot="error"]');
        if (errorSlot) {
            let errorMessage = 'An unknown error occurred while loading the 3D model.';

            if (event.detail && event.detail.type) {
                switch (event.detail.type) {
                    case 'webgl-unsupported':
                        errorMessage = 'Your device does not support WebGL, which is required for 3D models.';
                        break;
                    case 'fetch-failure':
                        errorMessage = 'Failed to download the 3D model. Please check your internet connection.';
                        break;
                    case 'parse-failure':
                        errorMessage = 'The 3D model file is corrupted or in an unsupported format.';
                        break;
                    case 'out-of-memory':
                        errorMessage = 'Your device does not have enough memory to load this 3D model.';
                        break;
                }
            }

            errorSlot.querySelector('p').textContent = errorMessage;
        }

        // Log error for debugging
        Swal.showValidationMessage(`Model loading failed: ${event.detail?.message || 'Unknown error'}`);
    }

    function handleModelLoad(event) {
        console.log('3D model loaded successfully');

        // Hide performance warning if model loads successfully
        const monitor = document.getElementById('performance-monitor');
        if (monitor && monitor.style.display === 'block') {
            setTimeout(() => {
                monitor.style.display = 'none';
            }, 3000);
        }
    }

    function handleModelProgress(event) {
        const progress = event.detail.totalProgress;
        console.log(`Loading progress: ${Math.round(progress * 100)}%`);

        // Show progress in loading indicator
        const poster = document.querySelector('model-viewer [slot="poster"] p');
        if (poster && progress < 1) {
            poster.textContent = `Loading 3D Model... ${Math.round(progress * 100)}%`;
        }
    }

    function initializeArModelControls() {
        const modelViewer = document.getElementById('ar-model-viewer');

        if (!modelViewer) return;

        // Reset camera button
        document.getElementById('reset-camera').addEventListener('click', function() {
            try {
                modelViewer.cameraOrbit = '0deg 75deg 105%';
                modelViewer.fieldOfView = '45deg';
            } catch (error) {
                console.error('Error resetting camera:', error);
                Swal.showValidationMessage('Failed to reset camera view');
            }
        });

        // Toggle auto-rotate button
        document.getElementById('toggle-autorotate').addEventListener('click', function() {
            try {
                modelViewer.autoRotate = !modelViewer.autoRotate;
                this.textContent = modelViewer.autoRotate ? '⏸️ Stop Rotation' : '🔄 Start Rotation';
                this.classList.toggle('btn-outline-warning', modelViewer.autoRotate);
                this.classList.toggle('btn-outline-secondary', !modelViewer.autoRotate);
            } catch (error) {
                console.error('Error toggling auto-rotate:', error);
                Swal.showValidationMessage('Failed to toggle rotation');
            }
        });

        // Fullscreen button
        document.getElementById('fullscreen-model').addEventListener('click', function() {
            try {
                if (modelViewer.requestFullscreen) {
                    modelViewer.requestFullscreen();
                } else if (modelViewer.webkitRequestFullscreen) {
                    modelViewer.webkitRequestFullscreen();
                } else if (modelViewer.msRequestFullscreen) {
                    modelViewer.msRequestFullscreen();
                } else {
                    throw new Error('Fullscreen not supported');
                }
            } catch (error) {
                console.error('Error entering fullscreen:', error);
                Swal.showValidationMessage('Fullscreen mode is not supported on your device');
            }
        });

        // Reduce quality button
        document.getElementById('reduce-quality').addEventListener('click', function() {
            try {
                // Disable auto-rotate and shadows for better performance
                modelViewer.autoRotate = false;
                modelViewer.shadowIntensity = 0;
                modelViewer.environmentImage = 'legacy';

                this.textContent = '✅ Quality Reduced';
                this.disabled = true;

                // hide the performance warning and suppress further warnings
                const monitor = document.getElementById('performance-monitor');
                if (monitor) {
                    monitor.style.display = 'none';
                }
                performanceMonitor.suppressed = true;
            } catch (error) {
                console.error('Error reducing quality:', error);
            }
        });

        // Retry button for error state
        const retryBtn = document.getElementById('retry-model');
        if (retryBtn) {
            retryBtn.addEventListener('click', function() {
                try {
                    // Force reload the model
                    const currentSrc = modelViewer.src;
                    modelViewer.src = '';
                    setTimeout(() => {
                        modelViewer.src = currentSrc;
                    }, 100);
                } catch (error) {
                    console.error('Error retrying model load:', error);
                    Swal.showValidationMessage('Failed to retry loading the model');
                }
            });
        }

        // Model viewer event listeners with error handling
        modelViewer.addEventListener('load', handleModelLoad);
        modelViewer.addEventListener('error', handleModelError);
        modelViewer.addEventListener('progress', handleModelProgress);

        // Camera change event with throttling
        let cameraChangeTimeout;
        modelViewer.addEventListener('camera-change', function(event) {
            clearTimeout(cameraChangeTimeout);
            cameraChangeTimeout = setTimeout(() => {
                // Optional: Log camera changes for debugging
                // console.log('Camera changed:', event.detail);
            }, 100);
        });
    }
</script>
