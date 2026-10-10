    {{-- Global Premium Loading Overlay --}}
<div id="ofuqGlobalLoader" class="ofuq-loader-overlay" aria-hidden="true">
    <div class="ofuq-loader-card">
        <div class="ofuq-loader-orb">
            <div class="ofuq-loader-ring"></div>
            <div class="ofuq-loader-ring second"></div>

            <div class="ofuq-loader-core">
                <span class="material-icons-round">psychology</span>
            </div>
        </div>

        <h3 id="ofuqLoaderTitle">نجهّز تجربتك الذكية</h3>

        <p id="ofuqLoaderText">
            نحلّل بياناتك بهدوء لبناء مسار يناسبك.
        </p>

        <div class="ofuq-loader-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>

<style>
    .ofuq-loader-overlay {
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background:
            radial-gradient(circle at 30% 20%, rgba(139, 92, 246, 0.16), transparent 34%),
            radial-gradient(circle at 70% 80%, rgba(96, 165, 250, 0.16), transparent 34%),
            rgba(248, 250, 252, 0.58);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.28s ease, visibility 0.28s ease;
    }

    .ofuq-loader-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: all;
    }

    .ofuq-loader-card {
        width: min(390px, 92vw);
        padding: 34px 28px 30px;
        border-radius: 34px;
        text-align: center;
        direction: rtl;
        background:
            linear-gradient(145deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.66));
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow:
            0 30px 90px rgba(79, 70, 229, 0.18),
            0 12px 35px rgba(15, 23, 42, 0.08),
            inset 0 0 30px rgba(255, 255, 255, 0.65);
        transform: translateY(14px) scale(0.96);
        opacity: 0;
        transition: transform 0.32s cubic-bezier(.2,.9,.2,1.15), opacity 0.28s ease;
        position: relative;
        overflow: hidden;
    }

    .ofuq-loader-overlay.active .ofuq-loader-card {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .ofuq-loader-card::before {
        content: "";
        position: absolute;
        width: 170px;
        height: 170px;
        border-radius: 50%;
        background: rgba(139, 92, 246, 0.12);
        filter: blur(25px);
        top: -70px;
        right: -50px;
        pointer-events: none;
    }

    .ofuq-loader-card::after {
        content: "";
        position: absolute;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: rgba(96, 165, 250, 0.12);
        filter: blur(25px);
        bottom: -65px;
        left: -50px;
        pointer-events: none;
    }

    .ofuq-loader-orb {
        position: relative;
        width: 108px;
        height: 108px;
        margin: 0 auto 22px;
        display: grid;
        place-items: center;
    }

    .ofuq-loader-ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2px solid transparent;
        border-top-color: #8b5cf6;
        border-right-color: rgba(139, 92, 246, 0.55);
        animation: ofuqSpin 1.15s linear infinite;
        filter: drop-shadow(0 8px 18px rgba(139, 92, 246, 0.22));
    }

    .ofuq-loader-ring.second {
        inset: 11px;
        border-top-color: #60a5fa;
        border-left-color: rgba(96, 165, 250, 0.55);
        animation-duration: 1.8s;
        animation-direction: reverse;
        opacity: 0.9;
    }

    .ofuq-loader-core {
        width: 68px;
        height: 68px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        background:
            linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(237, 233, 254, 0.9));
        border: 1px solid rgba(255, 255, 255, 0.92);
        box-shadow:
            0 16px 35px rgba(124, 58, 237, 0.16),
            inset 0 0 22px rgba(255, 255, 255, 0.75);
        animation: ofuqPulse 1.8s ease-in-out infinite;
        position: relative;
        z-index: 2;
    }

    .ofuq-loader-core .material-icons-round {
        font-size: 32px;
        background: linear-gradient(135deg, #8b5cf6, #60a5fa);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .ofuq-loader-card h3 {
        position: relative;
        z-index: 2;
        margin: 0 0 9px;
        font-size: 1.28rem;
        font-weight: 900;
        color: #172554;
        letter-spacing: -0.3px;
    }

    .ofuq-loader-card p {
        position: relative;
        z-index: 2;
        margin: 0 auto;
        max-width: 290px;
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.9;
        font-weight: 600;
    }

    .ofuq-loader-dots {
        position: relative;
        z-index: 2;
        margin-top: 22px;
        display: flex;
        justify-content: center;
        gap: 8px;
    }

    .ofuq-loader-dots span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8b5cf6, #60a5fa);
        opacity: 0.35;
        animation: ofuqDots 1.2s ease-in-out infinite;
    }

    .ofuq-loader-dots span:nth-child(2) {
        animation-delay: 0.18s;
    }

    .ofuq-loader-dots span:nth-child(3) {
        animation-delay: 0.36s;
    }

    @keyframes ofuqSpin {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes ofuqPulse {
        0%, 100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.055);
        }
    }

    @keyframes ofuqDots {
        0%, 100% {
            transform: translateY(0);
            opacity: 0.35;
        }

        50% {
            transform: translateY(-7px);
            opacity: 1;
        }
    }

    @media (max-width: 600px) {
        .ofuq-loader-card {
            padding: 30px 22px 26px;
            border-radius: 28px;
        }

        .ofuq-loader-orb {
            width: 96px;
            height: 96px;
        }

        .ofuq-loader-core {
            width: 62px;
            height: 62px;
            border-radius: 22px;
        }

        .ofuq-loader-card h3 {
            font-size: 1.12rem;
        }

        .ofuq-loader-card p {
            font-size: 0.88rem;
        }
    }
</style>

<script>
    window.showOfuqLoader = function (
        title = 'نجهّز تجربتك الذكية',
        text = 'نحلّل بياناتك بهدوء لبناء مسار يناسبك.'
    ) {
        const overlay = document.getElementById('ofuqGlobalLoader');
        const titleElement = document.getElementById('ofuqLoaderTitle');
        const textElement = document.getElementById('ofuqLoaderText');

        if (!overlay) {
            return;
        }

        if (titleElement) {
            titleElement.textContent = title;
        }

        if (textElement) {
            textElement.textContent = text;
        }

        overlay.classList.add('active');
        overlay.setAttribute('aria-hidden', 'false');
    };

    window.hideOfuqLoader = function () {
        const overlay = document.getElementById('ofuqGlobalLoader');

        if (!overlay) {
            return;
        }

        overlay.classList.remove('active');
        overlay.setAttribute('aria-hidden', 'true');
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-ofuq-loading]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const title = form.getAttribute('data-loading-title') || 'نجهّز تجربتك الذكية';
                const text = form.getAttribute('data-loading-text') || 'يرجى الانتظار قليلًا، يتم تجهيز البيانات الآن.';

                window.showOfuqLoader(title, text);
            });
        });

        document.querySelectorAll('[data-ofuq-loading-click]').forEach(function (element) {
            element.addEventListener('click', function () {
                const title = element.getAttribute('data-loading-title') || 'نجهّز تجربتك الذكية';
                const text = element.getAttribute('data-loading-text') || 'يرجى الانتظار قليلًا، يتم تجهيز البيانات الآن.';

                window.showOfuqLoader(title, text);
            });
        });
    });
</script>
