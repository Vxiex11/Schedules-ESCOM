<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@100..900&family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/menu.css">
    <title>Menú Administrativo</title>
    <style>
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: white;
            z-index: 1000;
            transition: opacity 0.5s;
        }

        .notification.info {
            background-color: #2196F3;
        }

        .notification.error {
            background-color: #F44336;
        }

        .notification.fade-out {
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="preloader">
        <div id="loader"></div>
    </div>

    <p class="tooltip" id="tooltip">Menu</p>
    <div class="layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <div class="sidebar-item" data-tooltip="Home">
                <a href="index.php" class="sidebar-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-school-icon lucide-school"><path d="M14 22v-4a2 2 0 1 0-4 0v4"/><path d="m18 10 3.447 1.724a1 1 0 0 1 .553.894V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-7.382a1 1 0 0 1 .553-.894L6 10"/><path d="M18 5v17"/><path d="m4 6 7.106-3.553a2 2 0 0 1 1.788 0L20 6"/><path d="M6 5v17"/><circle cx="12" cy="9" r="2"/></svg>
                </a>
                </div>

                <div>
                    <div class="sidebar-item" data-tooltip="Teacher Administration">
                        <a href="professors.php" class="sidebar-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-marked-icon lucide-book-marked"><path d="M10 2v8l3-3 3 3V2"/><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/></svg>
                        </a>
                    </div>

                    <div class="sidebar-item" data-tooltip="User Administration">
                        <a href="users.php" class="sidebar-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round-cog-icon lucide-user-round-cog"><path d="m14.305 19.53.923-.382"/><path d="m15.228 16.852-.923-.383"/><path d="m16.852 15.228-.383-.923"/><path d="m16.852 20.772-.383.924"/><path d="m19.148 15.228.383-.923"/><path d="m19.53 21.696-.382-.924"/><path d="M2 21a8 8 0 0 1 10.434-7.62"/><path d="m20.772 16.852.924-.383"/><path d="m20.772 19.148.924.383"/><circle cx="10" cy="8" r="5"/><circle cx="18" cy="18" r="3"/></svg>
                        </a>
                    </div>

                    <div class="sidebar-item" data-tooltip="Subject Administration">
                        <a href="subjects.php" class="sidebar-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-library-big-icon lucide-library-big"><rect width="8" height="18" x="3" y="3" rx="1"/><path d="M7 3v18"/><path d="M20.4 18.9c.2.5-.1 1.1-.6 1.3l-1.9.7c-.5.2-1.1-.1-1.3-.6L11.1 5.1c-.2-.5.1-1.1.6-1.3l1.9-.7c.5-.2 1.1.1 1.3.6Z"/></svg>
                        </a>
                    </div>

                    <div class="sidebar-item" data-tooltip="Timetable Administration">
                        <a href="timetables.php" class="sidebar-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check2-icon lucide-calendar-check-2"><path d="M8 2v4"/><path d="M16 2v4"/><path d="M21 14V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M3 10h18"/><path d="m16 20 2 2 4-4"/></svg>
                        </a>
                    </div>
                    
                    <div class="sidebar-item" data-tooltip="Timetable Administration">
                        <a href="classrooms.php" class="sidebar-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-warehouse-icon lucide-warehouse"><path d="M18 21V10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v11"/><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 1.132-1.803l7.95-3.974a2 2 0 0 1 1.837 0l7.948 3.974A2 2 0 0 1 22 8z"/><path d="M6 13h12"/><path d="M6 17h12"/></svg>
                        </a>
                    </div>
                </div>

                <div class="sidebar-item" data-tooltip="Log-Out">
                    <a class="sidebar-logout-link" id="logoutBtn" href="../login.html">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out-icon lucide-log-out"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>
                    </a>
                </div>
            </nav>
        </aside>
            <main class="content">
                <div class="data-area">
                    <header class="header-content">
                        <h1>Welcome, <span id="userName"></span>!</h1>
                        <p>Manage everything in one place. Check stats, adjust settings, and keep everything under control. Select an option to get started!</p>
                    </header>
                    <div class="menu-options">

                        <a class="menu-card" id="teacherAdmin" style="text-decoration: none;" href="professors.php">
                            <div class="images"><img src="../images/instructor.png" alt="teacher icon"></div>
                            <h2>Teacher Administration</h2>
                        </a>

                        <a class="menu-card" id="userAdmin" style="text-decoration: none;" href="users.php">
                            <div class="images"><img src="../images/user-interface.png" alt="user admin"></div>
                            <h2>User Administration</h2>
                        </a>

                        <a class="menu-card" id="subjectAdmin" style="text-decoration: none;" href="subjects.php">
                            <div class="images"><img src="../images/armario-de-la-escuela.png" alt="subject admin"></div>
                            <h2>Subject Administration</h2>
                        </a>

                        <a class="menu-card" id="timetableAdmin" style="text-decoration: none;" href="timetables.php">
                            <div class="images"><img src="../images/horario-de-estudio.png" alt="timetable admin"></div>
                            <h2>Timetable Administration</h2>
                        </a>

                        <a class="menu-card" id="timetableAdmin" style="text-decoration: none;" href="classrooms.php">
                            <div class="images"><img src="../images/pizarra.png" alt="timetable admin"></div>
                            <h2>Classrooms Administration</h2>
                        </a>
                        
                    </div>
                </div>
            </main>
    </div>
    
    <script src="../js/sidebar.js"></script>
    <script src="../js/welcome.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.0/lottie.min.js"></script>
    
    <!--
    <div class="mobile-menu-toggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </div>
    !-->
    
</body>
</html>