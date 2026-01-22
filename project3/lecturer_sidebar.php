<!-- HEADER -->
<header class="header d-flex justify-content-between align-items-center px-3 py-2 bg-primary text-white">
    <div class="d-flex align-items-center">
        <!-- Hamburger icon for mobile -->
        <button class="btn btn-link text-white me-2 d-lg-none" id="menu-toggle">
            <i class="bi bi-list" style="font-size: 1.8rem;"></i>
        </button>
        <a href="#" class="text-white text-decoration-none fw-bold fs-5">Lecturer Dashboard</a>
    </div>

    <div class="logout">
        <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
    </div>
</header>

<!-- SIDEBAR -->
<aside id="sidebar" class=" border-end">
    <ul class="list-unstyled m-0 p-3">
        <li class="mb-2">
            <a href="lecturer_profile.php" class="d-block py-2 px-3 rounded text-white fw-semibold hover-link">My Profile</a>
        </li>
        <li class="mb-2">
            <a href="lecturer_courses.php" class="d-block py-2 px-3 rounded text-white fw-semibold hover-link">My Courses</a>
        </li>
        <li class="mb-2">
            <a href="#" class="d-block py-2 px-3 rounded text-white fw-semibold hover-link">My Result</a>
        </li>
    </ul>
</aside>

<!-- STYLES -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    /* Base layout */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', sans-serif;
    }

    aside {
        position: fixed;
        top: 56px; /* same as header height */
        left: 0;
        width: 250px;
        height: calc(100% - 56px);
        background-color: #424a5b;
        box-shadow: 2px 0 8px rgba(0,0,0,0.1);
        transition: transform 0.3s ease-in-out;
        z-index: 1030;
    }

    /* Hide sidebar off-canvas on mobile */
    @media (max-width: 991px) {
        aside {
            transform: translateX(-100%);
        }
        aside.active {
            transform: translateX(0);
        }
        .content {
            margin-left: 0 !important;
        }
    }

    /* Desktop view */
    @media (min-width: 992px) {
        aside {
            transform: none;
        }
        .content {
            margin-left: 250px;
        }
    }

    /* Header */
    .header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 56px;
        z-index: 1040;
    }

    /* Hover link effect */
    .hover-link:hover {
        background-color: #007bff;
        color: white !important;
        text-decoration: none;
    }

    /* Smooth transition */
    .hover-link {
        transition: all 0.2s ease;
    }
	  /* ===== Sidebar Links ===== */
  .nav-link {
    border-radius: 6px;
    transition: background-color 0.2s ease, color 0.2s ease;
  }

  .nav-link:hover,
  .nav-link.active {
    background-color: #007bff;
    color: #fff !important;
  }

  .nav-link i {
    font-size: 1.1rem;
    vertical-align: middle;
  }

  /* Add nice spacing and subtle separation */
  .nav-item + .nav-item {
    border-top: 1px solid #f1f1f1;
  }

</style>

<!-- SCRIPT -->
<script>
    // Toggle sidebar visibility on mobile
    document.addEventListener("DOMContentLoaded", function() {
        const menuToggle = document.getElementById("menu-toggle");
        const sidebar = document.getElementById("sidebar");

        if (menuToggle) {
            menuToggle.addEventListener("click", () => {
                sidebar.classList.toggle("active");
            });
        }
    });
</script>
