<style>

/* =========================================================*
    &#xA0;  GEN Z FUTURISTIC BUS HOMEPAGE*
    &#xA0;  ========================================================= */

:root {
    --neon-blue: #00e5ff;
    --neon-purple: #8b5cf6;
    --neon-pink: #ec4899;
    --dark: #070711;
    --dark-2: #0d0d1b;
    --glass: rgba(255,255,255,0.07);
    --border: rgba(255,255,255,0.12);
}

/* HERO */
.smart-hero {
    position: relative;
    min-height: 650px;
    overflow: hidden;
    display: flex;
    align-items: center;
    background:
        radial-gradient(circle at 15% 20%, rgba(0,229,255,.18), transparent 28%),
        radial-gradient(circle at 85% 25%, rgba(139,92,246,.20), transparent 30%),
        radial-gradient(circle at 70% 85%, rgba(236,72,153,.12), transparent 25%),
        linear-gradient(135deg, #05050c 0%, #0a0a18 50%, #070711 100%);
    color: white;
}

/* GRID */
.smart-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
    background-size: 45px 45px;
    mask-image: linear-gradient(to bottom, black, transparent);
}

/* GLOW ORBS */
.glow-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
}

.orb-1 {
    width: 280px;
    height: 280px;
    background: rgba(0,229,255,.12);
    top: 10%;
    left: -80px;
}

.orb-2 {
    width: 320px;
    height: 320px;
    background: rgba(139,92,246,.12);
    right: -100px;
    top: 15%;
}

.orb-3 {
    width: 250px;
    height: 250px;
    background: rgba(236,72,153,.08);
    bottom: -80px;
    left: 45%;
}

/* HERO CONTENT */
.hero-content {
    position: relative;
    z-index: 3;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 50px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.12);
    color: #b9f7ff;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .5px;
    backdrop-filter: blur(10px);
}

.hero-badge span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--neon-blue);
    box-shadow: 0 0 15px var(--neon-blue);
}

.hero-title {
    margin-top: 22px;
    font-size: clamp(48px, 6vw, 78px);
    line-height: .98;
    font-weight: 950;
    letter-spacing: -4px;
}

.hero-title .gradient-text {
    background: linear-gradient(
        90deg,
        var(--neon-blue),
        #7dd3fc,
        var(--neon-purple),
        var(--neon-pink)
    );
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-subtitle {
    max-width: 590px;
    margin-top: 25px;
    color: #9ca3af;
    font-size: 17px;
    line-height: 1.75;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 32px;
}

.btn-neon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 22px;
    border-radius: 14px;
    background: var(--neon-blue);
    color: #031014;
    font-weight: 900;
    border: none;
    text-decoration: none;
    box-shadow:
        0 0 25px rgba(0,229,255,.22),
        0 10px 30px rgba(0,0,0,.25);
    transition: .3s ease;
}

.btn-neon:hover {
    color: #031014;
    transform: translateY(-3px);
    box-shadow:
        0 0 35px rgba(0,229,255,.40),
        0 15px 35px rgba(0,0,0,.30);
}

.btn-glass {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 22px;
    border-radius: 14px;
    background: rgba(255,255,255,.05);
    color: white;
    font-weight: 800;
    border: 1px solid rgba(255,255,255,.14);
    text-decoration: none;
    backdrop-filter: blur(12px);
    transition: .3s ease;
}

.btn-glass:hover {
    color: white;
    transform: translateY(-3px);
    background: rgba(255,255,255,.09);
    border-color: rgba(0,229,255,.35);
}

.hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 32px;
    margin-top: 48px;
}

.hero-stat strong {
    display: block;
    color: white;
    font-size: 23px;
    font-weight: 900;
}

.hero-stat span {
    color: #6b7280;
    font-size: 12px;
}

/* CTA */
.hero-card {
    position: relative;
    z-index: 2;
    padding: 24px;
    border-radius: 26px;
    background: rgba(255,255,255,.055);
    border: 1px solid rgba(255,255,255,.11);
    backdrop-filter: blur(18px);
    box-shadow:
        0 30px 80px rgba(0,0,0,.30),
        inset 0 1px 0 rgba(255,255,255,.05);
}

/* STATS */
.hero-card-title {
    font-size: 14px;
    font-weight: 900;
    color: #ffffff;
}

.hero-card-subtitle {
    color: #7f8799;
    font-size: 12px;
}

/* BUS STAGE */
.bus-stage {
    position: relative;
    height: 520px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ROAD */
.road {
    position: absolute;
    width: 100%;
    height: 180px;
    bottom: 40px;
    background:
        linear-gradient(
            180deg,
            rgba(255,255,255,.02),
            rgba(255,255,255,.07)
        );
    transform: perspective(500px) rotateX(55deg);
    border-top: 1px solid rgba(255,255,255,.05);
}

.road::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 2px;
    background: repeating-linear-gradient(
        90deg,
        rgba(255,255,255,.35) 0 55px,
        transparent 55px 105px
    );
}

/* BUS BODY */
.bus {
    position: relative;
    width: 420px;
    height: 230px;
    border-radius: 38px 38px 25px 25px;
    background:
        linear-gradient(
            145deg,
            #222438,
            #0d0f20 60%,
            #171a30
        );
    border: 1px solid rgba(255,255,255,.13);
    box-shadow:
        0 40px 80px rgba(0,0,0,.55),
        0 0 45px rgba(0,229,255,.08);
    transform: perspective(700px) rotateY(-8deg);
    z-index: 5;
}

/* MAIN SHELL */
.bus::before {
    content: "";
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 28px;
    height: 5px;
    border-radius: 10px;
    background: linear-gradient(
        90deg,
        var(--neon-blue),
        var(--neon-purple),
        var(--neon-pink)
    );
    box-shadow:
        0 0 15px rgba(0,229,255,.65);
}

/* ROOF */
.bus-roof {
    position: absolute;
    left: 45px;
    right: 45px;
    top: -20px;
    height: 25px;
    border-radius: 30px 30px 0 0;
    background: #181a2b;
    border: 1px solid rgba(255,255,255,.10);
}

/* TOP LED */
.bus-roof::after {
    content: "";
    position: absolute;
    left: 30px;
    right: 30px;
    top: 8px;
    height: 2px;
    background: var(--neon-blue);
    box-shadow:
        0 0 12px var(--neon-blue);
}

/* WINDOWS */
.bus-window {
    position: absolute;
    top: 28px;
    height: 75px;
    border-radius: 14px;
    background:
        linear-gradient(
            135deg,
            rgba(0,229,255,.18),
            rgba(139,92,246,.14)
        );
    border: 1px solid rgba(255,255,255,.12);
    box-shadow:
        inset 0 0 20px rgba(0,229,255,.04);
}

.window-1 {
    left: 25px;
    width: 105px;
}

.window-2 {
    left: 140px;
    width: 105px;
}

.window-3 {
    left: 255px;
    width: 105px;
}

/* FRONT WINDSHIELD */
.front-glass {
    position: absolute;
    right: -1px;
    top: 31px;
    width: 65px;
    height: 70px;
    border-radius: 12px 28px 12px 12px;
    background:
        linear-gradient(
            135deg,
            rgba(0,229,255,.20),
            rgba(139,92,246,.14)
        );
    border-left: 1px solid rgba(255,255,255,.10);
}

/* SIDE BODY */
.bus-side-line {
    position: absolute;
    left: 25px;
    right: 25px;
    top: 120px;
    height: 1px;
    background: rgba(255,255,255,.08);
}

/* NEON SIDE STRIP */
.neon-strip {
    position: absolute;
    left: 35px;
    right: 35px;
    top: 145px;
    height: 3px;
    background:
        linear-gradient(
            90deg,
            var(--neon-blue),
            var(--neon-purple),
            var(--neon-pink)
        );
    box-shadow:
        0 0 14px rgba(0,229,255,.55);
}

/* DOOR */
.bus-door {
    position: absolute;
    left: 32px;
    bottom: 38px;
    width: 50px;
    height: 66px;
    border-radius: 7px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.025);
}

/* HEADLIGHTS */
.headlight {
    position: absolute;
    right: -10px;
    top: 126px;
    width: 18px;
    height: 12px;
    border-radius: 50%;
    background: #e8fdff;
    box-shadow:
        0 0 12px white,
        0 0 25px var(--neon-blue);
}

/* REAR LIGHT */
.rear-light {
    position: absolute;
    left: -8px;
    top: 128px;
    width: 14px;
    height: 10px;
    border-radius: 50%;
    background: #ff4d6d;
    box-shadow:
        0 0 12px #ff4d6d;
}

/* WHEELS */
.wheel {
    position: absolute;
    bottom: -35px;
    width: 82px;
    height: 82px;
    border-radius: 50%;
    background:
        radial-gradient(
            circle,
            #34384b 0 25%,
            #121423 26% 55%,
            #05060c 56%
        );
    border: 5px solid #111323;
    box-shadow:
        0 12px 25px rgba(0,0,0,.55);
}

.wheel-1 {
    left: 55px;
}

.wheel-2 {
    right: 55px;
}

/* BUS LOGO */
.bus-logo {
    position: absolute;
    right: 75px;
    bottom: 55px;
    font-size: 15px;
    font-weight: 950;
    letter-spacing: 1px;
    color: white;
}

.bus-logo span {
    color: var(--neon-blue);
}

/* BUS INFO CHIPS */
.bus-chip {
    position: absolute;
    z-index: 10;
    padding: 8px 12px;
    border-radius: 12px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.10);
    backdrop-filter: blur(10px);
    font-size: 11px;
    color: #dbeafe;
    white-space: nowrap;
}

.chip-1 {
    top: 80px;
    right: 5%;
}

.chip-2 {
    bottom: 110px;
    left: 0;
}

/* SPEED LINES */
.speed-line {
    position: absolute;
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(0,229,255,.55),
        transparent
    );
    animation: speed 2.5s linear infinite;
}

.speed-1 {
    width: 160px;
    left: 5%;
    bottom: 185px;
}

.speed-2 {
    width: 100px;
    right: 4%;
    bottom: 220px;
    animation-delay: .8s;
}

.speed-3 {
    width: 190px;
    right: 12%;
    bottom: 145px;
    animation-delay: 1.2s;
}

@keyframes speed {
    0% {
        opacity: 0;
        transform: translateX(-50px);
    }
    30% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translateX(80px);
    }
}

/* =========================================================
   QUICK SEARCH
   ========================================================= */

.quick-search {
    position: relative;
    z-index: 20;
    margin-top: -70px;
    margin-bottom: 20px;
}

.search-glass {
    padding: 25px;
    border-radius: 25px;
    background: rgba(13,13,27,.88);
    border: 1px solid rgba(255,255,255,.11);
    backdrop-filter: blur(20px);
    box-shadow:
        0 30px 70px rgba(0,0,0,.28),
        0 0 35px rgba(0,229,255,.035);
}

.search-label {
    color: #8d95a7;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-bottom: 8px;
}

.search-box {
    width: 100%;
    height: 48px;
    border-radius: 13px;
    background: rgba(255,255,255,.045);
    border: 1px solid rgba(255,255,255,.10);
    color: white;
    padding: 0 14px;
    outline: none;
}

.search-box:focus {
    border-color: rgba(0,229,255,.5);
    box-shadow: 0 0 0 3px rgba(0,229,255,.08);
}

.search-box::placeholder {
    color: #66697d;
}

/* =========================================================
   FEATURES
   ========================================================= */

.modern-section {
    padding: 90px 0;
    background: #fafafa;
}

.section-tag {
    display: inline-block;
    padding: 7px 13px;
    border-radius: 50px;
    background: #eefcff;
    color: #0891b2;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.section-title {
    font-weight: 900;
    letter-spacing: -1.5px;
    margin-top: 14px;
}

.feature-card {
    height: 100%;
    padding: 30px;
    border-radius: 24px;
    background: white;
    border: 1px solid #e9e9ef;
    transition: .35s ease;
    position: relative;
    overflow: hidden;
}

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 60px rgba(0,0,0,.09);
}

.feature-card::before {
    content: "";
    position: absolute;
    width: 100px;
    height: 100px;
    right: -45px;
    top: -45px;
    border-radius: 50%;
    background: rgba(0,229,255,.08);
}

.feature-icon {
    width: 58px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 17px;
    background: linear-gradient(135deg,#e0faff,#eef2ff);
    font-size: 27px;
    margin-bottom: 22px;
}

.feature-card h4 {
    font-weight: 900;
}

.feature-card p {
    color: #707080;
    line-height: 1.7;
    margin-bottom: 0;
}

/* =========================================================
   SCHEDULE SECTION
   ========================================================= */

.schedule-section-modern {
    padding: 80px 0;
    background: #f5f7fb;
}

/* MOBILE */

@media (max-width: 991px) {

    .smart-hero {
        min-height: auto;
        padding: 80px 0 110px;
    }

    .hero-content {
        text-align: center;
    }

    .hero-subtitle {
        margin-left: auto;
        margin-right: auto;
    }

    .hero-actions,
    .hero-stats {
        justify-content: center;
    }

    .bus-stage {
        margin-top: 40px;
        transform: scale(.82);
    }
}

@media (max-width: 575px) {

    .smart-hero {
        padding-top: 55px;
    }

    .hero-title {
        font-size: 46px;
        letter-spacing: -2px;
    }

    .hero-subtitle {
        font-size: 15px;
    }

    .bus-stage {
        transform: scale(.63);
        height: 350px;
        margin-left: -35px;
        margin-right: -35px;
    }

    .quick-search {
        margin-top: -25px;
    }

    .search-glass {
        padding: 18px;
    }

    .modern-section {
        padding: 65px 0;
    }
}


/* =========================================================
   DARK PREMIUM LOWER SECTIONS - FINAL OVERRIDE
   Keeps all existing homepage functionality unchanged.
   ========================================================= */

.modern-section,
.schedule-section-modern {
    position: relative;
    overflow: hidden;
    color: #f8fafc;
}

.modern-section {
    background:
        radial-gradient(circle at 12% 15%, rgba(0,229,255,.07), transparent 30%),
        radial-gradient(circle at 88% 80%, rgba(139,92,246,.09), transparent 32%),
        linear-gradient(180deg, #070711 0%, #0a0a16 48%, #070711 100%);
}

.modern-section::before,
.schedule-section-modern::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(255,255,255,.018) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
    background-size: 55px 55px;
    mask-image: linear-gradient(to bottom, black, transparent 90%);
}

.modern-section > .container,
.schedule-section-modern > .container {
    position: relative;
    z-index: 2;
}

.section-tag {
    background: rgba(0,229,255,.08) !important;
    border: 1px solid rgba(0,229,255,.24) !important;
    color: #67e8f9 !important;
    box-shadow: 0 0 24px rgba(0,229,255,.06);
}

.section-title {
    color: #ffffff !important;
}

.modern-section .text-muted,
.schedule-section-modern .text-muted {
    color: #9ca3af !important;
}

.feature-card {
    height: 100%;
    padding: 30px;
    border-radius: 24px;
    background:
        linear-gradient(145deg, rgba(255,255,255,.075), rgba(255,255,255,.025)) !important;
    border: 1px solid rgba(255,255,255,.11) !important;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,.05),
        0 18px 55px rgba(0,0,0,.24);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.feature-card:hover {
    transform: translateY(-8px);
    border-color: rgba(0,229,255,.38) !important;
    box-shadow:
        0 25px 70px rgba(0,0,0,.42),
        0 0 35px rgba(0,229,255,.08);
}

.feature-card::before {
    background: radial-gradient(circle, rgba(0,229,255,.14), transparent 70%) !important;
}

.feature-icon {
    background:
        linear-gradient(
            135deg,
            rgba(0,229,255,.12),
            rgba(139,92,246,.14)
        ) !important;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 10px 30px rgba(0,229,255,.07);
}

.feature-card h4 {
    color: #ffffff !important;
}

.feature-card p {
    color: #a7adbd !important;
}

.schedule-section-modern {
    background:
        radial-gradient(circle at 15% 20%, rgba(0,229,255,.065), transparent 28%),
        radial-gradient(circle at 85% 75%, rgba(139,92,246,.08), transparent 30%),
        linear-gradient(180deg, #090914 0%, #070711 100%);
}

.schedule-section-modern .section-tag {
    background: rgba(139,92,246,.10) !important;
    border-color: rgba(139,92,246,.28) !important;
    color: #c4b5fd !important;
}

/* Keep the embedded schedule readable when Bootstrap utility classes
   are used inside schedule.php. */

.schedule-section-modern .card,
.schedule-section-modern .modal-content,
.schedule-section-modern .dropdown-menu {
    background-color: #10111f;
    color: #f8fafc;
    border-color: rgba(255,255,255,.10);
}

.schedule-section-modern .card-header,
.schedule-section-modern .card-footer {
    background-color: rgba(255,255,255,.035);
    border-color: rgba(255,255,255,.08);
}

.schedule-section-modern .table {
    --bs-table-bg: transparent;
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.08);
}

.schedule-section-modern .form-control,
.schedule-section-modern .form-select {
    background-color: #0d0e19;
    color: #f8fafc;
    border-color: rgba(255,255,255,.12);
}

.schedule-section-modern .form-control::placeholder {
    color: #6b7280;
}

.schedule-section-modern .form-control:focus,
.schedule-section-modern .form-select:focus {
    background-color: #0d0e19;
    color: #fff;
    border-color: rgba(0,229,255,.55);
    box-shadow: 0 0 0 .2rem rgba(0,229,255,.10);
}

@media (max-width: 575px) {

    .feature-card {
        padding: 23px;
        border-radius: 20px;
    }
}


/* =========================================================
   ORIGINAL HTML
   ========================================================= */

</style>


<!-- =========================================================
    HERO
    ========================================================= -->

<section class="smart-hero">

    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <div class="container position-relative">

        <div class="row align-items-center">

            <div class="col-lg-6">

                <div class="hero-content">

                    <div class="hero-badge">
                        <span></span>
                        Smart Bus Reservation System
                    </div>

                    <h1 class="hero-title">

                        Travel
                        <span class="gradient-text">
                            Smarter.
                        </span>

                        <br>

                        Ride
                        <span class="gradient-text">
                            Better.
                        </span>

                    </h1>

                    <p class="hero-subtitle">

                        Book your bus journey in seconds.
                        Discover routes, choose your preferred seat,
                        and manage your reservation with a modern
                        digital experience.

                    </p>

                    <div class="hero-actions">

                        <a
                            href="#schedules"
                            class="btn-neon"
                        >
                            <i class="bi bi-bus-front-fill"></i>
                            Find Your Bus
                        </a>

                        <a
                            href="#features"
                            class="btn-glass"
                        >
                            <i class="bi bi-stars"></i>
                            Explore Features
                        </a>

                    </div>

                    <div class="hero-stats">

                        <div class="hero-stat">

                            <strong>
                                100%
                            </strong>

                            <span>
                                Digital Booking
                            </span>

                        </div>

                        <div class="hero-stat">

                            <strong>
                                24/7
                            </strong>

                            <span>
                                Availability
                            </span>

                        </div>

                        <div class="hero-stat">

                            <strong>
                                Secure
                            </strong>

                            <span>
                                Reservation
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <div class="col-lg-6">

                <div class="bus-stage">

                    <div class="speed-line speed-1"></div>
                    <div class="speed-line speed-2"></div>
                    <div class="speed-line speed-3"></div>

                    <div class="bus">

                        <div class="bus-roof"></div>

                        <div class="bus-window window-1"></div>
                        <div class="bus-window window-2"></div>
                        <div class="bus-window window-3"></div>

                        <div class="front-glass"></div>

                        <div class="bus-side-line"></div>

                        <div class="neon-strip"></div>

                        <div class="bus-door"></div>

                        <div class="headlight"></div>
                        <div class="rear-light"></div>

                        <div class="bus-logo">
                            OB<span>RS</span>
                        </div>

                        <div class="wheel wheel-1"></div>
                        <div class="wheel wheel-2"></div>

                    </div>

                    <div class="road"></div>

                    <div class="bus-chip chip-1">
                        <i class="bi bi-lightning-charge-fill"></i>
                        Fast Booking
                    </div>

                    <div class="bus-chip chip-2">
                        <i class="bi bi-shield-check"></i>
                        Secure Journey
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
    QUICK SEARCH
    ========================================================= -->

<section class="quick-search">

    <div class="container">

        <div class="search-glass">

            <div class="row g-3 align-items-end">

                <div class="col-lg-3 col-md-6">

                    <div class="search-label">
                        From
                    </div>

                    <input
                        type="text"
                        id="fromCity"
                        class="search-box"
                        placeholder="Departure city"
                    >

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="search-label">
                        To
                    </div>

                    <input
                        type="text"
                        id="toCity"
                        class="search-box"
                        placeholder="Destination city"
                    >

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="search-label">
                        Travel Date
                    </div>

                    <input
                        type="date"
                        id="travelDate"
                        class="search-box"
                    >

                </div>


                <div class="col-lg-3 col-md-6">

                    <button
                        type="button"
                        onclick="findBuses()"
                        class="btn-neon w-100"
                    >

                        <i class="bi bi-search"></i>

                        Find Buses

                    </button>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
    FEATURES
    ========================================================= -->

<section id="features" class="modern-section">

    <div class="container">

        <div class="text-center mb-5">

            <span class="section-tag">
                Built For Modern Travellers
            </span>

            <h2 class="section-title">
                Everything You Need For Your Ride
            </h2>

            <p class="text-muted mt-2">
                Simple booking. Smart experience. Zero unnecessary hassle.
            </p>

        </div>


        <div class="row g-4">

            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        🔎
                    </div>

                    <h4>
                        Explore Buses
                    </h4>

                    <p>
                        Find available buses and explore routes
                        without making the booking process complicated.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        🪑
                    </div>

                    <h4>
                        Choose Your Seat
                    </h4>

                    <p>
                        Select your preferred seat and enjoy
                        complete control over your journey.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        ⚡
                    </div>

                    <h4>
                        Quick Reservation
                    </h4>

                    <p>
                        Complete your reservation quickly with
                        a simple and smooth booking experience.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        🔐
                    </div>

                    <h4>
                        Secure System
                    </h4>

                    <p>
                        Your booking information is handled
                        through a secure reservation system.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        🎫
                    </div>

                    <h4>
                        Digital Ticket
                    </h4>

                    <p>
                        Access your booking details and ticket
                        information whenever you need it.
                    </p>

                </div>

            </div>


            <div class="col-md-4">

                <div class="feature-card">

                    <div class="feature-icon">
                        📱
                    </div>

                    <h4>
                        Easy Management
                    </h4>

                    <p>
                        Manage your reservations and journey
                        details from one convenient platform.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
    SCHEDULES
    ========================================================= -->

<section id="schedules" class="schedule-section-modern">

    <div class="container">

        <div class="text-center mb-5">

            <span class="section-tag">
                Find Your Ride
            </span>

            <h2 class="section-title">
                Available Bus Schedules
            </h2>

            <p class="text-muted">
                Choose your route and reserve your journey.
            </p>

        </div>


        <?php
if (!defined('OBRS_EMBED_SCHEDULE')) {
    define('OBRS_EMBED_SCHEDULE', true);
}

include 'schedule.php';
?>


    </div>

</section>


<!-- =========================================================
    JAVASCRIPT
    ========================================================= -->

<script>

function findBuses() {

    const from =
        document.getElementById("fromCity").value.trim();

    const to =
        document.getElementById("toCity").value.trim();

    const date =
        document.getElementById("travelDate").value;


    if (
        from === "" &&
        to === "" &&
        date === ""
    ) {

        document
            .getElementById("schedules")
            .scrollIntoView({
                behavior: "smooth"
            });

        return;

    }


    const scheduleSection =
        document.getElementById("schedules");


    if (scheduleSection) {

        scheduleSection.scrollIntoView({
            behavior: "smooth"
        });

    }


    setTimeout(function () {

        const inputs =
            document.querySelectorAll(
                '#schedules input'
            );

        inputs.forEach(function (input) {

            const value =
                input.value.toLowerCase();

            if (
                from &&
                value.includes(from.toLowerCase())
            ) {

                input.focus();

            }

        });

    }, 600);

}


/* Smooth anchor navigation */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        document
            .querySelectorAll('a[href^="#"]')
            .forEach(function (link) {

                link.addEventListener(
                    "click",
                    function (e) {

                        const targetId =
                            this.getAttribute("href");

                        if (
                            targetId &&
                            targetId !== "#"
                        ) {

                            const target =
                                document.querySelector(
                                    targetId
                                );

                            if (target) {

                                e.preventDefault();

                                target.scrollIntoView({
                                    behavior: "smooth",
                                    block: "start"
                                });

                            }

                        }

                    }
                );

            });

    }
);

</script>