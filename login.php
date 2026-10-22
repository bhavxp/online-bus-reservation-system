<?php

// Secure session configuration
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once 'config/db_connect.php';


// ============================================================
// IF ALREADY LOGGED IN
// ============================================================

if (isset($_SESSION['admin_id'])) {

    header("Location: admin_home.php");

    exit;

}


// ============================================================
// LOGIN ERROR VARIABLE
// ============================================================

$error = "";


// ============================================================
// PROCESS LOGIN
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $username = trim(
        $_POST['username'] ?? ''
    );


    $password = $_POST['password'] ?? '';


    // --------------------------------------------------------
    // VALIDATION
    // --------------------------------------------------------

    if ($username === '' || $password === '') {

        $error = "Please enter username and password.";

    }

    else {


        // ----------------------------------------------------
        // FIND ADMIN USER
        // ----------------------------------------------------

        $stmt = $conn->prepare("

            SELECT
                id,
                username,
                password,
                full_name

            FROM users

            WHERE username = ?

            LIMIT 1

        ");


        $stmt->bind_param(
            "s",
            $username
        );


        $stmt->execute();


        $result = $stmt->get_result();


        // ----------------------------------------------------
        // CHECK USER
        // ----------------------------------------------------

        if ($result->num_rows === 1) {


            $user = $result->fetch_assoc();


            // ------------------------------------------------
            // VERIFY PASSWORD
            // ------------------------------------------------

            if (
                password_verify(
                    $password,
                    $user['password']
                )
            ) {


                // --------------------------------------------
                // PREVENT SESSION FIXATION
                // --------------------------------------------

                session_regenerate_id(true);


                // --------------------------------------------
                // CREATE ADMIN SESSION
                // --------------------------------------------

                $_SESSION['admin_id'] =
                    $user['id'];

                $_SESSION['admin_username'] =
                    $user['username'];

                $_SESSION['admin_name'] =
                    $user['full_name'];


                // --------------------------------------------
                // REDIRECT
                // --------------------------------------------

                header(
                    "Location: admin_home.php"
                );

                exit;

            }

            else {

                $error =
                    "Invalid username or password.";

            }

        }

        else {

            $error =
                "Invalid username or password.";

        }


        $stmt->close();

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login - OBRS</title>


    <!-- Bootstrap 5 -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #212529,
                    #0d6efd
                );

        }


        .login-card {

            width: 100%;

            max-width: 420px;

            border: none;

            border-radius: 15px;

        }


        .login-icon {

            font-size: 55px;

        }

    </style>

</head>


<body>


<div class="container">


    <div class="row justify-content-center">


        <div class="col-12">


            <div class="card login-card shadow-lg">


                <div class="card-body p-5">


                    <!-- LOGO -->

                    <div class="text-center mb-4">

                        <div class="login-icon">
                            🔐
                        </div>

                        <h2 class="fw-bold mt-2">
                            Admin Login
                        </h2>

                        <p class="text-muted">
                            Online Bus Reservation System
                        </p>

                    </div>


                    <!-- ERROR -->

                    <?php if ($error !== ''): ?>

                        <div
                            class="alert alert-danger"
                            role="alert"
                        >

                            <?php
                            echo htmlspecialchars(
                                $error
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <!-- LOGIN FORM -->

                    <form
                        method="POST"
                        action=""
                    >


                        <!-- USERNAME -->

                        <div class="mb-3">

                            <label
                                for="username"
                                class="form-label"
                            >

                                Username

                            </label>

                            <input
                                type="text"
                                class="form-control form-control-lg"
                                id="username"
                                name="username"
                                placeholder="Enter username"
                                autocomplete="username"
                                required
                            >

                        </div>


                        <!-- PASSWORD -->

                        <div class="mb-4">

                            <label
                                for="password"
                                class="form-label"
                            >

                                Password

                            </label>

                            <input
                                type="password"
                                class="form-control form-control-lg"
                                id="password"
                                name="password"
                                placeholder="Enter password"
                                autocomplete="current-password"
                                required
                            >

                        </div>


                        <!-- LOGIN BUTTON -->

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg w-100"
                        >

                            Login

                        </button>


                    </form>


                    <!-- BACK TO WEBSITE -->

                    <div class="text-center mt-4">

                        <a
                            href="index.php"
                            class="text-decoration-none"
                        >

                            ← Back to Website

                        </a>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>


</body>

</html>