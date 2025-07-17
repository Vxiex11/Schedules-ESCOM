<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    <!-- CSS de Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <link rel="stylesheet" href="../css/menu.css">
    <link rel="stylesheet" href="../css/professors.css">

    
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
                        <a href="timetable.php" class="sidebar-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-check2-icon lucide-calendar-check-2"><path d="M8 2v4"/><path d="M16 2v4"/><path d="M21 14V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8"/><path d="M3 10h18"/><path d="m16 20 2 2 4-4"/></svg>
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

        <main>
        
            <div class="admin-container">
                <!-- Sidebar (usar el mismo que en tu menú principal) -->
                
                <main class="content_professors">
                    <div class="header-section">
                        <h1><span class="material-icons">groups</span>Professors Administration</h1>
                        <div class="search-add">
                            <div class="search-box">
                                <input type="text" id="searchProfessor" placeholder="Search professor...">
                                <button class="search-btn"><span class="material-icons">search</span></button>
                            </div>
                            <button class="add-btn" id="addProfessorBtn">
                                <span class="material-icons">add</span><p>New Professor</p>
                            </button>
                        </div>
                    </div>

                    <div class="professors-table-container">
                        <table class="professors-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subjects</th>
                                    <th>Office</th>
                                    <th>State</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- EXAMPLE -->
                                <tr>
                                    <td>101</td>
                                    <td>María Rodríguez</td>
                                    <td>maria@escuela.edu</td>
                                    <td>Matemáticas, Física</td>
                                    <td>222</td>
                                    <td><span class="status active">Activo</span></td>
                                    <td class="actions">
                                        <button class="edit-btn"><span class="material-icons">edit</span></button>
                                        <button class="delete-btn"><span class="material-icons">delete</span></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>102</td>
                                    <td>Carlos Méndez</td>
                                    <td>carlos@escuela.edu</td>
                                    <td>Historia</td>
                                    <td>444</td>
                                    <td><span class="status active">Activo</span></td>
                                    <td class="actions">
                                        <button class="edit-btn"><span class="material-icons">edit</span></button>
                                        <button class="delete-btn"><span class="material-icons">delete</span></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="pagination">
                        <button class="page-btn"><span class="material-icons">chevron_left</span></button>
                        <span class="page-number">1</span>
                        <button class="page-btn"><span class="material-icons">chevron_right</span></button>
                    </div>
                </main>

                <!-- Modal para agregar/editar profesor -->
                <div class="modal" id="professorModal">
                    <div class="modal-content">
                        <span class="close-modal" style="display: none;">&times;</span>
                        <h2 id="modalTitle">New Professor</h2>
                        <form id="professorForm" class="formProfessors" novalidate>
                            <div class="form-group">
                                <label for="professorName">Full Name</label>
                                <input type="text" id="professorName" required minlength="5" maxlength="100" placeholder="Ex. Rodrigo Flores">
                                <div class="error-message" id="nameError"></div>
                            </div>
                            <div class="form-group">
                                <label for="professorEmail">Email</label>
                                <input type="email" id="professorEmail" required pattern="[^@]+@[^@]+\.[^@]+" placeholder="Ex. pepito123@gmail.com">
                                <div class="error-message" id="emailError"></div>
                            </div>
                            <div class="form-group">
                                <label for="professorSubjects">Subjects</label>
                                <select id="professorSubjects" class="form-control select-custom" name="subjects[]" style="width: 596px;" multiple required></select>
                                <div class="error-message" id="subjectsError"></div>
                            </div>
                            <div class="form-group">
                                <label for="professorOffice">Office</label>
                                <input type="text" id="professorOffice" class="input-office" required maxlength="20" placeholder="Ex. 1207">
                                <div class="error-message" id="officeError"></div>
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <div class="status-toggle">
                                    <label>
                                        <input type="radio" class="active" name="status" value="active" checked> <p>Active</p>
                                    </label>
                                    <label>
                                        <input type="radio" class="inactive" name="status" value="inactive"> <p>Inactive</p>
                                    </label>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="cancel-btn">Cancel</button>
                                <button type="submit" class="save-btn">Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="passwordModal" class="modalPasswords">
                    <div class="modalPasswords2">
                        <p>Enter your password to confirm deletion:</p>
                        <input type="password" id="confirmPassword" placeholder="Password" class="inputPasswordModal">
                        <br><br>
                        <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                        <button type="button" onclick="submitPassword()" class="save-btn">Save</button>
                    </div>
                </div>
            </div>
        </main>

    </div>

    <script src="../js/passwordModal.js"></script> <!-- We need to put first this js!-->
    <script src="../js/sidebar.js"></script>
    <script src="../js/professors.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.0/lottie.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

</body>
</html>