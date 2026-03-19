// PWA Install Prompt Handler
(function() {
    let deferredPrompt;
    let installBanner = null;

    // Check if already installed or dismissed
    function isInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true;
    }

    function wasDismissed() {
        const dismissed = localStorage.getItem('pwa-install-dismissed');
        if (dismissed) {
            const dismissedDate = new Date(dismissed);
            const now = new Date();
            // Show again after 7 days
            if ((now - dismissedDate) > 7 * 24 * 60 * 60 * 1000) {
                localStorage.removeItem('pwa-install-dismissed');
                return false;
            }
            return true;
        }
        return false;
    }

    function createInstallBanner() {
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.innerHTML = `
            <div class="pwa-banner-content">
                <img src="/images/Cornerstone.jpg" alt="Cornerstone Baptist" class="pwa-banner-icon">
                <div class="pwa-banner-text">
                    <strong>Install Our App</strong>
                    <span>Add to your home screen for quick access</span>
                </div>
            </div>
            <div class="pwa-banner-buttons">
                <button id="pwa-install-btn" class="pwa-btn-install">Install</button>
                <button id="pwa-dismiss-btn" class="pwa-btn-dismiss">&times;</button>
            </div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            #pwa-install-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
                color: white;
                padding: 12px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 10000;
                box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
                border-top: 2px solid #14AFB1;
                animation: slideUp 0.3s ease-out;
            }
            @keyframes slideUp {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }
            .pwa-banner-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .pwa-banner-icon {
                width: 45px;
                height: 45px;
                border-radius: 10px;
            }
            .pwa-banner-text {
                display: flex;
                flex-direction: column;
            }
            .pwa-banner-text strong {
                font-size: 15px;
                color: #14AFB1;
            }
            .pwa-banner-text span {
                font-size: 12px;
                opacity: 0.8;
            }
            .pwa-banner-buttons {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .pwa-btn-install {
                background: #14AFB1;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 25px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .pwa-btn-install:hover {
                background: #0d8a8c;
            }
            .pwa-btn-dismiss {
                background: transparent;
                color: white;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 5px 10px;
                opacity: 0.7;
            }
            .pwa-btn-dismiss:hover {
                opacity: 1;
            }
            @media (max-width: 400px) {
                .pwa-banner-text span {
                    display: none;
                }
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(banner);

        return banner;
    }

    function showInstallBanner() {
        if (isInstalled() || wasDismissed()) return;

        installBanner = createInstallBanner();

        // Install button click
        document.getElementById('pwa-install-btn').addEventListener('click', function() {
            if (deferredPrompt) {
                // Android/Chrome - use native prompt
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted install');
                    }
                    deferredPrompt = null;
                    hideBanner();
                });
            } else if (isIOS()) {
                // iOS - show instructions
                showIOSInstructions();
            }
        });

        // Dismiss button click
        document.getElementById('pwa-dismiss-btn').addEventListener('click', function() {
            localStorage.setItem('pwa-install-dismissed', new Date().toISOString());
            hideBanner();
        });
    }

    function hideBanner() {
        if (installBanner) {
            installBanner.style.animation = 'slideDown 0.3s ease-out forwards';
            installBanner.style.setProperty('--slideDown', 'translateY(100%)');
            setTimeout(function() {
                installBanner.remove();
            }, 300);
        }
    }

    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }

    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    function showIOSInstructions() {
        const modal = document.createElement('div');
        modal.id = 'ios-install-modal';
        modal.innerHTML = `
            <div class="ios-modal-content">
                <button class="ios-modal-close">&times;</button>
                <img src="/images/Cornerstone.jpg" alt="Cornerstone Baptist" style="width:60px;margin-bottom:15px;border-radius:12px;">
                <h3>Install Cornerstone Baptist App</h3>
                <p>To install this app on your iPhone:</p>
                <ol>
                    <li>Tap the <strong>Share</strong> button <span style="font-size:18px;">&#x1F4E4;</span> at the bottom of your screen</li>
                    <li>Scroll down and tap <strong>"Add to Home Screen"</strong></li>
                    <li>Tap <strong>"Add"</strong> in the top right</li>
                </ol>
                <button class="ios-modal-ok">Got it!</button>
            </div>
        `;

        const style = document.createElement('style');
        style.textContent = `
            #ios-install-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10001;
                padding: 20px;
            }
            .ios-modal-content {
                background: white;
                padding: 30px;
                border-radius: 16px;
                max-width: 320px;
                text-align: center;
                position: relative;
            }
            .ios-modal-content h3 {
                color: #0a0a0a;
                margin-bottom: 10px;
            }
            .ios-modal-content p {
                color: #555;
                margin-bottom: 15px;
            }
            .ios-modal-content ol {
                text-align: left;
                padding-left: 20px;
                color: #333;
                line-height: 1.8;
            }
            .ios-modal-close {
                position: absolute;
                top: 10px;
                right: 15px;
                background: none;
                border: none;
                font-size: 28px;
                cursor: pointer;
                color: #999;
            }
            .ios-modal-ok {
                background: #14AFB1;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 25px;
                font-weight: 600;
                margin-top: 20px;
                cursor: pointer;
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(modal);

        modal.querySelector('.ios-modal-close').addEventListener('click', function() {
            modal.remove();
            hideBanner();
        });
        modal.querySelector('.ios-modal-ok').addEventListener('click', function() {
            modal.remove();
            hideBanner();
        });
    }

    // Listen for beforeinstallprompt (Android/Chrome)
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;

        // Show banner on mobile only
        if (isMobile()) {
            showInstallBanner();
        }
    });

    // For iOS - show banner after page loads
    window.addEventListener('load', function() {
        if (isIOS() && !isInstalled() && !wasDismissed()) {
            setTimeout(showInstallBanner, 2000); // Show after 2 seconds
        }
    });

    // Handle successful install
    window.addEventListener('appinstalled', function() {
        console.log('App installed successfully');
        hideBanner();
    });

    // ========================================
    // UPDATE NOTIFICATION SYSTEM
    // ========================================

    function showUpdateBanner() {
        // Remove existing update banner if any
        var existingBanner = document.getElementById('pwa-update-banner');
        if (existingBanner) existingBanner.remove();

        var banner = document.createElement('div');
        banner.id = 'pwa-update-banner';
        banner.innerHTML =
            '<div class="pwa-update-content">' +
                '<i class="fas fa-sync-alt" style="font-size:20px;margin-right:12px;"></i>' +
                '<span><strong>Update Available!</strong> A new version is ready.</span>' +
            '</div>' +
            '<button id="pwa-update-btn" class="pwa-btn-update">Refresh</button>';

        var style = document.createElement('style');
        style.textContent =
            '#pwa-update-banner {' +
                'position: fixed;' +
                'top: 0;' +
                'left: 0;' +
                'right: 0;' +
                'background: linear-gradient(135deg, #14AFB1 0%, #0d8a8c 100%);' +
                'color: white;' +
                'padding: 12px 20px;' +
                'display: flex;' +
                'justify-content: space-between;' +
                'align-items: center;' +
                'z-index: 10002;' +
                'box-shadow: 0 4px 20px rgba(0,0,0,0.3);' +
                'animation: slideDown 0.3s ease-out;' +
            '}' +
            '@keyframes slideDown {' +
                'from { transform: translateY(-100%); }' +
                'to { transform: translateY(0); }' +
            '}' +
            '.pwa-update-content {' +
                'display: flex;' +
                'align-items: center;' +
            '}' +
            '.pwa-btn-update {' +
                'background: white;' +
                'color: #14AFB1;' +
                'border: none;' +
                'padding: 8px 20px;' +
                'border-radius: 20px;' +
                'font-weight: 600;' +
                'cursor: pointer;' +
                'transition: all 0.2s;' +
            '}' +
            '.pwa-btn-update:hover {' +
                'background: #f0f0f0;' +
                'transform: scale(1.05);' +
            '}';

        document.head.appendChild(style);
        document.body.appendChild(banner);

        document.getElementById('pwa-update-btn').addEventListener('click', function() {
            window.location.reload();
        });
    }

    // Listen for service worker update messages
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'SW_UPDATED') {
                console.log('App updated to version:', event.data.version);
                showUpdateBanner();
            }
        });

        // Also check for new service worker on page load
        navigator.serviceWorker.ready.then(function(registration) {
            registration.addEventListener('updatefound', function() {
                var newWorker = registration.installing;
                newWorker.addEventListener('statechange', function() {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New service worker installed, show update notification
                        showUpdateBanner();
                    }
                });
            });
        });
    }
})();


