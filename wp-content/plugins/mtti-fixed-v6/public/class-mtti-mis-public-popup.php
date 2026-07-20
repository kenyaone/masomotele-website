<?php
/**
 * Online-enrollment popup CTA — shown site-wide on the front end to drive
 * prospective online learners to /enroll-online/ ([mtti_course_checkout]).
 * Skipped inside the student/lecturer portal (existing students don't need
 * to see an ad for something they've already done) and in wp-admin.
 */

if (!defined('ABSPATH')) exit;

class MTTI_MIS_Public_Popup {

    public function __construct() {
        add_action('wp_footer', array($this, 'render'));
    }

    public function render() {
        if (is_admin()) return;

        global $post;
        if ($post instanceof WP_Post) {
            foreach (array('mtti_learner_portal', 'mtti_student_portal', 'mtti_lecturer_portal', 'mtti_course_checkout', 'mtti_admission_form') as $sc) {
                if (has_shortcode($post->post_content, $sc)) return;
            }
        }

        $checkout_url = home_url('/enroll-online/');
        ?>
        <div id="mtti-online-promo-panel" class="mtti-online-promo-panel" aria-hidden="true">
            <div class="mtti-online-promo-card" role="dialog" aria-modal="false" aria-labelledby="mtti-online-promo-title">
                <div class="mtti-online-promo-ribbon"><span>SAVE 50%</span></div>
                <div class="mtti-online-promo-head">
                    <button type="button" class="mtti-online-promo-close" id="mtti-online-promo-close" aria-label="Close">&times;</button>
                    <div class="mtti-online-promo-badges">
                        <div class="mtti-online-promo-badge">🎓 TVETA Accredited &middot; 100% Online</div>
                        <div class="mtti-online-promo-badge mtti-online-promo-badge-amber">💰 Lipa Mdogo Mdogo</div>
                    </div>
                    <h2 id="mtti-online-promo-title" class="mtti-online-promo-title">Save up to <span>50%</span> on Tuition Fees</h2>
                </div>
                <div class="mtti-online-promo-body">
                    <div class="mtti-online-promo-savings">
                        <span class="mtti-online-promo-savings-icon">🔥</span>
                        <div>
                            <div class="mtti-online-promo-savings-headline">Online learners pay up to 50% less</div>
                            <div class="mtti-online-promo-savings-sub">than the same course on our Eldoret campus</div>
                        </div>
                    </div>
                    <ul class="mtti-online-promo-list">
                        <li><span class="chk">&check;</span> Lipa Mdogo Mdogo — enroll with a small deposit, clear the rest as you learn</li>
                        <li><span class="chk">&check;</span> No commuting or missed classes — learn from your phone, anytime</li>
                        <li><span class="chk">&check;</span> Same TVETA-accredited certificate as our Eldoret campus</li>
                    </ul>
                    <a href="<?php echo esc_url($checkout_url); ?>" class="mtti-online-promo-cta" id="mtti-online-promo-cta">
                        Claim Your Discount Now <span class="mtti-online-promo-cta-arrow">&rarr;</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/online-admission/')); ?>" class="mtti-online-promo-alt">Prefer campus classes in Eldoret? Apply here</a>
                </div>
            </div>
        </div>

        <style>
            .mtti-online-promo-panel {
                position: fixed; z-index: 999999;
                bottom: 20px; right: 20px;
                width: 380px; max-width: calc(100vw - 32px);
                display: none;
                opacity: 0;
                transform: translateY(24px) scale(.96);
                transition: opacity .4s ease, transform .4s cubic-bezier(.2,.8,.2,1);
                pointer-events: none;
            }
            .mtti-online-promo-panel.mtti-online-promo-visible {
                display: block; opacity: 1;
                transform: translateY(0) scale(1);
                pointer-events: auto;
            }
            @media (max-width: 560px) {
                .mtti-online-promo-panel { left: 12px; right: 12px; bottom: 12px; width: auto; }
            }
            .mtti-online-promo-card {
                position: relative;
                background: #fff;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 20px 50px -14px rgba(0,0,0,.4), 0 0 0 1px rgba(0,0,0,.05);
                font-family: 'Poppins', -apple-system, "Segoe UI", sans-serif;
            }
            .mtti-online-promo-ribbon {
                position: absolute; top: 14px; left: -40px;
                width: 90px;
                transform: rotate(-45deg);
                text-align: center;
                z-index: 3;
                pointer-events: none;
            }
            .mtti-online-promo-ribbon span {
                display: block;
                background: linear-gradient(135deg, #EF5350 0%, #B71C1C 100%);
                color: #fff;
                font-weight: 800;
                font-size: 10.5px;
                letter-spacing: .05em;
                padding: 5px 0;
                box-shadow: 0 3px 10px rgba(0,0,0,.35);
                animation: mtti-online-promo-ribbon-glow 2.2s ease-in-out infinite;
            }
            @keyframes mtti-online-promo-ribbon-glow {
                0%, 100% { filter: brightness(1); }
                50% { filter: brightness(1.18); }
            }
            .mtti-online-promo-head {
                position: relative;
                background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
                padding: 34px 44px 16px 20px;
            }
            .mtti-online-promo-close {
                position: absolute; top: 10px; right: 10px;
                width: 26px; height: 26px; border-radius: 50%;
                background: rgba(255,255,255,.2); border: none; cursor: pointer;
                font-size: 17px; line-height: 1; color: #fff;
                display: flex; align-items: center; justify-content: center;
                transition: background .2s;
            }
            .mtti-online-promo-close:hover { background: rgba(255,255,255,.34); }
            .mtti-online-promo-badges {
                display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px;
            }
            .mtti-online-promo-badge {
                display: inline-block;
                background: rgba(255,255,255,.16); color: #fff;
                font-size: 11px; font-weight: 700; letter-spacing: .04em;
                padding: 4px 10px; border-radius: 999px;
            }
            .mtti-online-promo-badge-amber {
                background: #FFD54F; color: #5c3d00;
            }
            .mtti-online-promo-title {
                font-size: 19px; font-weight: 800; line-height: 1.28;
                color: #fff; margin: 0;
            }
            .mtti-online-promo-title span { color: #FFEB3B; }
            .mtti-online-promo-body { padding: 18px 20px 20px; }
            .mtti-online-promo-savings {
                display: flex; align-items: center; gap: 10px;
                background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
                border: 1.5px solid #EF5350;
                border-radius: 12px;
                padding: 10px 14px;
                margin: 0 0 14px;
            }
            .mtti-online-promo-savings-icon { font-size: 22px; line-height: 1; flex: 0 0 auto; }
            .mtti-online-promo-savings-headline {
                font-size: 14px; font-weight: 800; color: #B71C1C; line-height: 1.25;
            }
            .mtti-online-promo-savings-sub {
                font-size: 11px; font-weight: 600; color: #8a4a4a; margin-top: 1px;
            }
            .mtti-online-promo-list {
                list-style: none; margin: 0 0 16px; padding: 0;
                display: flex; flex-direction: column; gap: 9px;
            }
            .mtti-online-promo-list li {
                display: flex; align-items: flex-start; gap: 8px;
                font-size: 13.5px; line-height: 1.42; color: #3a4238;
            }
            .mtti-online-promo-list .chk {
                flex: 0 0 auto; width: 18px; height: 18px; margin-top: 1px;
                border-radius: 50%; background: #e8f5e9; color: #2E7D32;
                font-size: 11px; font-weight: 800;
                display: flex; align-items: center; justify-content: center;
            }
            .mtti-online-promo-cta {
                display: flex; align-items: center; justify-content: center; gap: 8px;
                width: 100%; box-sizing: border-box;
                background: #FF9800;
                color: #fff; text-decoration: none;
                font-weight: 800; font-size: 14.5px;
                padding: 13px 18px; border-radius: 12px;
                box-shadow: 0 8px 24px -6px rgba(255,152,0,.55);
                animation: mtti-online-promo-pulse 2.4s ease-in-out infinite;
                transition: transform .15s ease, background .15s ease;
            }
            .mtti-online-promo-cta:hover { transform: translateY(-2px); background: #F57C00; color: #fff; }
            .mtti-online-promo-cta-arrow { transition: transform .2s ease; }
            .mtti-online-promo-cta:hover .mtti-online-promo-cta-arrow { transform: translateX(3px); }
            @keyframes mtti-online-promo-pulse {
                0%, 100% { box-shadow: 0 8px 24px -6px rgba(255,152,0,.55); }
                50% { box-shadow: 0 10px 30px -4px rgba(255,152,0,.8); }
            }
            .mtti-online-promo-alt {
                display: block; text-align: center; margin-top: 12px;
                font-size: 11.5px; color: #8a9186; text-decoration: underline;
            }
            .mtti-online-promo-alt:hover { color: #5a6459; }
            @media (prefers-reduced-motion: reduce) {
                .mtti-online-promo-cta { animation: none; }
                .mtti-online-promo-panel { transition: none; }
            }
        </style>

        <script>
        (function () {
            function showPopup() {
                var overlay = document.getElementById('mtti-online-promo-panel');
                if (!overlay) return;
                overlay.classList.add('mtti-online-promo-visible');
            }

            function dismissPopup() {
                var overlay = document.getElementById('mtti-online-promo-panel');
                if (overlay) overlay.classList.remove('mtti-online-promo-visible');
                // Deliberately no persistence — closing only affects this
                // page view. Shows again on every fresh page load by design.
            }

            document.addEventListener('DOMContentLoaded', function () {
                var closeBtn = document.getElementById('mtti-online-promo-close');
                if (closeBtn) closeBtn.addEventListener('click', dismissPopup);
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') dismissPopup(); });

                // A small corner card, not a full-screen overlay, so it never
                // covers the rest of the page. Always shows on load now —
                // no session/cooldown persistence.
                showPopup();
            });
        })();
        </script>
        <?php
    }
}

add_action('init', function () {
    new MTTI_MIS_Public_Popup();
});
