<?php
// Show errors during development so we can see problems instead of a blank page
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
session_destroy();
include 'includes/connect.php';

if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    echo "<script>alert('$message');</script>";
    unset($_SESSION['message']);
}

// Removed unused lecturer query that caused an error when the lecturer table is missing
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School-Connect | Home</title>

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
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            line-height: 1.7;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.75rem;
            color: white !important;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--accent) !important;
        }

        .btn-login {
            background: var(--success);
            border: none;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(22, 163, 74, 0.3);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(30, 64, 175, 0.9), rgba(59, 130, 246, 0.8)), 
                        url('school.png') center/cover no-repeat;
            color: white;
            padding: 6rem 0;
            border-radius: 0 0 50px 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.2);
            z-index: 1;
        }

        .hero .container {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .hero p.lead {
            font-size: 1.3rem;
            max-width: 600px;
            margin: 1rem auto 0;
        }

        /* Welcome Section */
        .welcome-img {
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.4s;
        }

        .welcome-img:hover {
            transform: translateY(-10px);
        }

        .section-title {
            position: relative;
            display: inline-block;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 1.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent);
            border-radius: 2px;
        }

        /* Admission Form */
        .admission-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }

        .form-control, .form-select, textarea {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(30, 64, 175, 0.2);
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--success), #22c55e);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-apply:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.3);
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: #e5e7eb;
            padding: 3rem 0 1.5rem;
            margin-top: 5rem;
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
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.2rem;
            }
            .hero {
                padding: 4rem 0;
                border-radius: 0 0 30px 30px;
            }
            .admission-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">SCHOOL-CONNECT</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="#admission">Admission</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="login.php" class="btn btn-login text-white">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4">We Manage and Teach Students With Care</h1>
            <p class="lead">Nurturing minds, building futures — one student at a time.</p>
        </div>
    </section>

    <!-- Welcome Section -->
    <section class="container my-5">
        <div class="row g-5 align-items-center">
            <div class="col-md-5">
                <img src="playground.jpg" alt="Students Playing" class="img-fluid welcome-img">
            </div>
            <div class="col-md-7">
                <h2 class="section-title">Welcome to School-Connect</h2>
                <p class="fs-5 text-muted">
                    School-Connect is a platform that connects students with lecturers and courses. Our mission is to provide a vibrant, academically challenging, and encouraging environment where manifold viewpoints are prized and celebrated. We believe in fostering a community of learners who are passionate about their education and personal growth.
                </p>
                <a href="#admission" class="btn btn-outline-primary btn-lg mt-3">
                    Explore Admission <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Admission Form -->
    <section id="admission" class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="admission-card">
                    <h3 class="text-center mb-4 text-primary fw-bold">
                        <i class="bi bi-file-earmark-text"></i> Admission Form
                    </h3>

                    <form action="data_check.php" method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Full Name</label>
                                <input type="text" class="form-control" name="name" required placeholder="Your Full Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Email</label>
                                <input type="email" class="form-control" name="email" required placeholder="you@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Phone</label>
                                <input type="text" class="form-control" name="phone" required placeholder="+260 961 234 567">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Message</label>
                                <textarea class="form-control" name="message" rows="4" required 
                                          placeholder="Tell us why you'd like to join School-Connect..."></textarea>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" name="apply" class="btn btn-apply text-white">
                                    <i class="bi bi-send"></i> Submit Form
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row text-center text-md-start">
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">School-Connect</h5>
                    <p class="small">Empowering education in Zambia and beyond.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">Developed By</h5>
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
            <p class="text-center small mb-0">&copy; 2025 School-Connect. All rights reserved. Made with <3 </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>