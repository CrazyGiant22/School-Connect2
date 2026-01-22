<?php
require_once __DIR__ . '/includes/security-helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | School-Connect</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1e40af;
            --secondary: #3b82f6;
            --success: #16a34a;
            --accent: #a3e635;
            --dark: #1f2937;
            --light: #f8fafc;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.65)), 
                        url('pupils.jpeg') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1rem;
            color: var(--dark);
        }

        .login-container {
            max-width: 420px;
            width: 100%;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Back to Home Button */
        .back-btn {
            position: absolute;
            top: 18px;
            left: 18px;
            z-index: 10;
            background: white;
            color: var(--primary);
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-btn:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(163, 230, 53, 0.3);
        }

        /* Card Header */
        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.6rem;
            text-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }

        .card-header i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Card Body */
        .card-body {
            padding: 2.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1.5px solid #d1d5db;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.2);
        }

        .input-group-text {
            background: transparent;
            border-radius: 12px 0 0 12px;
            border-right: none;
        }

        /* Login Button */
        .btn-login {
            background: linear-gradient(135deg, var(--success), #22c55e);
            border: none;
            border-radius: 50px;
            padding: 0.8rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.3);
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
        }

        /* Footer */
        footer {
            background: rgba(31, 41, 55, 0.95);
            color: #e5e7eb;
            padding: 2rem 0 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 0.9rem;
            backdrop-filter: blur(8px);
        }

        footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        footer a:hover {
            text-decoration: underline;
        }

        .footer-title {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .card-body {
                padding: 2rem 1.5rem;
            }
            .back-btn {
                width: 38px;
                height: 38px;
                font-size: 1.1rem;
            }
            .card-header h3 {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 login-container">

                <!-- Login Card -->
                <div class="login-card">

                    <!-- Back to Home -->
                    <a href="index.php" class="back-btn" title="Back to Home">
                        <i class="bi bi-house-door-fill"></i>
                    </a>

                    <!-- Header -->
                    <div class="card-header">
                        <i class="bi bi-shield-lock"></i>
                        <h3>Welcome To School-Connect</h3>
                        <br>
                        <h3>Please Login with your Credentials</h3>
                    </div>

                    <!-- Body -->
                    <div class="card-body">

                        <!-- Session Message -->
                        <?php
                        error_reporting(0);
                        session_start();
                        if (!empty($_SESSION['loginMessage'])) {
                            echo '<div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    ' . htmlspecialchars($_SESSION['loginMessage']) . '
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                            unset($_SESSION['loginMessage']);
                        }
                        ?>

                        <form action="login_check_secure.php" method="POST">
                            <?php echo csrfTokenField(); ?>
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="bi bi-person-circle"></i> Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username"
                                           placeholder="Enter your username" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Enter your password" required>
                                </div>
                            </div>

                            <button type="submit" name="submit" class="btn btn-login text-white">
                                <i class="bi bi-box-arrow-in-right"></i> Login Securely
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                <a href="#" class="text-decoration-none text-primary">Forgot Password?</a>
                            </small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row text-center text-md-start">
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">School-Connect</h5>
                    <p class="small">Empowering education in Zambia.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">Developer</h5>
                    <p class="mb-0"><strong>Jeremiah Ng`ambi</strong></p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">Contact</h5>
                    <p class="mb-0">+260 969 215 491</p>
                    <p class="mb-0">+260 779 214 169</p>
                    <p class="mb-0">
                        <a href="mailto:ngambijeremiah600@gmail.com">ngambijeremiah600@gmail.com</a>
                    </p>
                     <p class="mb-0">
                        <a href="https://www.facebook.com/CrazyGiant22">Facebook: Jeremiah Ng`ambi</a>
                    </p>
                </div>
            </div>
            <hr class="bg-light opacity-20">
            <p class="text-center small mb-0">
                &copy; 2025 School-Connect. Made with <i class="bi bi-heart-fill text-danger/angular"></i> 
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>