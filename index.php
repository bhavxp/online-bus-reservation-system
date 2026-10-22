
<?php

session_start();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Online Bus Reservation System - OBRS</title>


    <!-- =====================================================
         BOOTSTRAP CSS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         DATATABLES CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css"
    >


    <!-- =====================================================
         OBRS PREMIUM DARK THEME
    ====================================================== -->

    <style>
        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            font-family:
                Inter,
                Arial,
                Helvetica,
                sans-serif;

            background:
                radial-gradient(
                    circle at 15% 10%,
                    rgba(99, 102, 241, 0.16),
                    transparent 30%
                ),
                radial-gradient(
                    circle at 85% 15%,
                    rgba(14, 165, 233, 0.10),
                    transparent 28%
                ),
                linear-gradient(
                    135deg,
                    #030712 0%,
                    #07111f 45%,
                    #030712 100%
                );

            color: #f8fafc;

            min-height: 100vh;

            overflow-x: hidden;
        }


        /* =====================================================
           NAVBAR
        ====================================================== */

        .obrs-navbar {

            background:
                linear-gradient(
                    135deg,
                    rgba(3, 7, 18, 0.96),
                    rgba(7, 17, 31, 0.94),
                    rgba(3, 7, 18, 0.96)
                );

            border-bottom:
                1px solid
                rgba(255,255,255,0.08);

            padding: 12px 0;

            box-shadow:
                0 8px 35px
                rgba(0,0,0,0.35);

            position: sticky;

            top: 0;

            z-index: 9999;

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }


        .obrs-brand {

            font-size: 1.35rem;

            font-weight: 900;

            color: #ffffff !important;

            text-decoration: none;

            display: flex;

            align-items: center;

            gap: 8px;

            letter-spacing: -0.3px;

            transition: 0.25s ease;
        }


        .obrs-brand span {

            color: #818cf8;

        }


        .obrs-brand:hover {

            color: #ffffff !important;

            transform:
                translateY(-1px);
        }


        .obrs-nav-link {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.76
                ) !important;

            font-weight: 600;

            margin: 0 6px;

            padding:
                8px 12px !important;

            border-radius: 9px;

            transition:
                all 0.25s ease;
        }


        .obrs-nav-link:hover {

            color: #ffffff !important;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.06
                );

            transform:
                translateY(-1px);
        }


        .obrs-nav-link.active {

            color: #ffffff !important;

            background:
                rgba(
                    99,
                    102,
                    241,
                    0.14
                );

            box-shadow:
                inset 0 0 0 1px
                rgba(
                    129,
                    140,
                    248,
                    0.10
                );
        }
.navbar .nav-link[href="help_support.php"] {
    color: #fff !important;
}
        /* =====================================================
           PROFILE ICON
        ====================================================== */

        .profile-link {

            width: 44px;

            height: 44px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            text-decoration: none;

            background:
                linear-gradient(
                    135deg,
                    #111827,
                    #1e293b
                );

            border:
                1px solid
                rgba(
                    129,
                    140,
                    248,
                    0.35
                );

            box-shadow:
                0 5px 20px
                rgba(0,0,0,0.28);

            transition:
                all 0.25s ease;

            margin-left: 10px;
        }


        .profile-link:hover {

            transform:
                translateY(-2px)
                scale(1.05);

            background:
                linear-gradient(
                    135deg,
                    #172033,
                    #263449
                );

            border-color:
                rgba(
                    129,
                    140,
                    248,
                    0.65
                );

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.38);
        }


        .profile-icon {

            width: 23px;

            height: 23px;

            fill: #c7d2fe;
        }


        /* =====================================================
           MOBILE NAVBAR
        ====================================================== */

        .navbar-toggler {

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.20
                ) !important;

            padding: 7px 9px;

            border-radius: 10px;
        }


        .navbar-toggler:focus {

            box-shadow:
                0 0 0 3px
                rgba(
                    99,
                    102,
                    241,
                    0.15
                );
        }


        @media (max-width: 991px) {

            .profile-link {

                margin-left: 0;

                margin-top: 10px;

                margin-bottom: 5px;
            }

        }


        /* =====================================================
           PAGE WRAPPER
        ====================================================== */

        .page-wrapper {

            min-height:
                calc(
                    100vh - 70px
                );

            position: relative;
        }


        /* =====================================================
           DARK BOOTSTRAP OVERRIDES
        ====================================================== */

        .table {

            --bs-table-bg: transparent;

            --bs-table-color: #e2e8f0;

        }


        .modal-content {

            background:
                linear-gradient(
                    145deg,
                    #0b1220,
                    #111827
                );

            color: #f8fafc;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.10
                );

            border-radius: 18px;

            box-shadow:
                0 25px 80px
                rgba(0,0,0,0.55);
        }


        .modal-header,
        .modal-footer {

            border-color:
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );
        }


        .btn-close {

            filter:
                invert(1)
                grayscale(1)
                brightness(2);
        }


        .form-control,
        .form-select {

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.045
                ) !important;

            color: #f8fafc !important;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.10
                ) !important;
        }


        .form-control::placeholder {

            color: #64748b;
        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                rgba(
                    129,
                    140,
                    248,
                    0.65
                ) !important;

            box-shadow:
                0 0 0 3px
                rgba(
                    99,
                    102,
                    241,
                    0.12
                ) !important;
        }


        .form-select option {

            background: #0b1220;

            color: #ffffff;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .obrs-footer {

            background:
                linear-gradient(
                    135deg,
                    #030712,
                    #07111f
                );

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.72
                );

            padding: 30px 0;

            margin-top: 50px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );
        }


        .obrs-footer p {

            margin:
                0 0 5px;

            font-weight: 600;
        }


        .obrs-footer small {

            color:
                rgba(
                    255,
                    255,
                    255,
                    0.45
                );
        }


        /* =====================================================
           DATATABLES DARK THEME
        ====================================================== */

        .dt-container {

            color: #cbd5e1;
        }


        .dt-container .form-control,
        .dt-container .form-select {

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.05
                ) !important;

            color: #f8fafc !important;
        }


        .dt-container .dt-paging-button {

            color: #cbd5e1 !important;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                ) !important;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.03
                ) !important;

            border-radius: 8px !important;
        }


        .dt-container
        .dt-paging-button.current {

            background:
                rgba(
                    99,
                    102,
                    241,
                    0.25
                ) !important;

            color: #ffffff !important;
        }


        /* =====================================================
           SCROLLBAR
        ====================================================== */

        ::-webkit-scrollbar {

            width: 9px;
        }


        ::-webkit-scrollbar-track {

            background: #030712;
        }


        ::-webkit-scrollbar-thumb {

            background:
                linear-gradient(
                    #334155,
                    #6366f1
                );

            border-radius: 20px;
        }


        ::-webkit-scrollbar-thumb:hover {

            background:
                linear-gradient(
                    #475569,
                    #818cf8
                );
        }

    </style>


    <!-- =====================================================
         jQuery
    ====================================================== -->

    <script
        src="https://code.jquery.com/jquery-3.7.1.min.js"
    ></script>


    <!-- =====================================================
         BOOTSTRAP JS
    ====================================================== -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>


    <!-- =====================================================
         DATATABLES JS
    ====================================================== -->

    <script
        src="https://cdn.datatables.net/2.1.8/js/dataTables.js"
    ></script>

    <script
        src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"
    ></script>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================== -->

<nav class="navbar navbar-expand-lg obrs-navbar">

    <div class="container">


        <!-- LOGO -->

        <a
            class="obrs-brand"
            href="index.php"
            title="OBRS Home"
        >

            🚍

            <span>
                OBRS
            </span>

        </a>


        <!-- MOBILE MENU -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >

            <span
                class="navbar-toggler-icon"
                style="
                    filter:
                    brightness(0)
                    invert(1);
                "
            ></span>

        </button>


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <ul
                class="
                    navbar-nav
                    ms-auto
                    align-items-lg-center
                "
            >


                <!-- HOME -->

                <li class="nav-item">

                    <a
                        class="
                            nav-link
                            obrs-nav-link
                            active
                        "
                        href="index.php"
                    >
                        Home
                    </a>

                </li>


                <!-- SCHEDULES -->

                <li class="nav-item">

                    <a
                        class="
                            nav-link
                            obrs-nav-link
                        "
                        href="#schedules"
                    >
                        Bus Schedules
                    </a>

                </li>
                    <li class="nav-item">
                        <a class="nav-link" href="help_support.php">
                           🎧 Help &amp; Support
                         </a>
                </li>
                <!-- PROFILE -->

                <li class="nav-item">

                    <?php

                    if (
                        isset(
                            $_SESSION['user_logged_in']
                        )
                        &&
                        $_SESSION['user_logged_in']
                        === true
                    ) {

                        $profile_link =
                            'user_profile.php';

                    } else {

                        $profile_link =
                            'user_login.php';

                    }

                    ?>

                    <a
                        href="<?php
                            echo htmlspecialchars(
                                $profile_link,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                        class="profile-link"
                        title="My Profile"
                        aria-label="My Profile"
                    >

                        <svg
                            class="profile-icon"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >

                            <path
                                d="
                                    M12 12
                                    c2.76 0 5-2.24 5-5
                                    s-2.24-5-5-5
                                    -5 2.24-5 5
                                    2.24 5 5 5z

                                    M12 14
                                    c-4.42 0-8 2.24-8 5
                                    v1
                                    h16
                                    v-1
                                    c0-2.76-3.58-5-8-5z
                                "
                            ></path>

                        </svg>

                    </a>

                </li>


            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     PAGE CONTENT
========================================================== -->

<div class="page-wrapper">

    <?php

    $page =
        $_GET['page']
        ??
        'home';


    /* HOME */

    if (
        $page === 'home'
    ) {

        include 'home.php';

    }


    /* SCHEDULE */

    elseif (
        $page === 'schedule'
    ) {

        include 'schedule.php';

    }


    /* DEFAULT */

    else {

        include 'home.php';

    }

    ?>

</div>



<!-- =========================================================
     FOOTER
========================================================== -->

<footer class="obrs-footer">

    <div class="container text-center">

        <p>

            &copy;

            <?php
            echo date("Y");
            ?>

            Online Bus Reservation System

        </p>


        <small>

            Safe & Simple Bus Booking Experience

        </small>

    </div>

</footer>



</body>

</html>
