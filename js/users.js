
document.addEventListener("DOMContentLoaded", () => {
    const optionPassword = document.getElementById("optionPassword");
    const currentPasswordGroup = document.getElementById("currentPasswordGroup");
    const newPasswordGroup = document.getElementById("newPasswordGroup");

    // Mostrar u ocultar campos de contraseña según selección
    optionPassword.addEventListener("change", () => {
        if (optionPassword.value === "yesEdit") {
            currentPasswordGroup.style.display = "block";
            newPasswordGroup.style.display = "block";
            document.getElementById("currentPassword").required = true;
            document.getElementById("newPassword").required = true;
        } else {
            currentPasswordGroup.style.display = "none";
            newPasswordGroup.style.display = "none";
            document.getElementById("currentPassword").required = false;
            document.getElementById("newPassword").required = false;
        }
    });

    // Establece el valor por defecto al abrir modal
    optionPassword.value = "noEdit";
    currentPasswordGroup.style.display = "none";
    newPasswordGroup.style.display = "none";

    document.querySelector(".users-table").style.display = "none";
    //fetchUsers();

    document.querySelector(".close-modal").addEventListener("click", closeModal);
    document.querySelector(".cancel-btn").addEventListener("click", closeModal);

    document.getElementById("addUserBtn").addEventListener("click", () => openUserModal());
    document.getElementById("userForm").addEventListener("submit", saveUser);

    document.getElementById("searchUser").addEventListener("input", async (e) => {
        const query = e.target.value.toLowerCase();

        if (!query) {
            document.querySelector(".users-table").style.display = "none";
            return;
        }

        const res = await fetch('../admin/users_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'read' })
        });

        const result = await res.json();
        if (result.ok) {
            const filtered = result.data.filter(p =>
                p.full_name.toLowerCase().includes(query)
            );
            renderUsers(filtered);
            document.querySelector(".users-table").style.display = "table";
        }
    });
});

async function fetchUsers() {
    const res = await fetch("../admin/users_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "read" }),
    });

    const data = await res.json();
    if (data.ok) renderUsers(data.data);
}

function closeModal() {
    document.getElementById("userModal").style.display = "none";
    document.getElementById("passwordModal").style.display = "none";
    document.getElementById("userModal").style.display = "none";
}

function renderUsers(users) {
    const tbody = document.querySelector(".users-table tbody");
    tbody.innerHTML = "";

    users.forEach(user => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.rol}</td>
            <td>${user.full_name}</td>
            <td>${user.created_at}</td>
            <td class="actions">
                <button class="edit-btn" data-id="${user.id}"><span class="material-icons">edit</span></button>
                <button class="delete-btn" data-id="${user.id}"><span class="material-icons">delete</span></button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.querySelectorAll(".edit-btn").forEach(btn =>
        btn.addEventListener("click", () => openUserModal(btn.dataset.id))
    );
    document.querySelectorAll(".delete-btn").forEach(btn =>
        btn.addEventListener("click", () => deleteUser(btn.dataset.id))
    );
}

function validateForm() {
    let isValid = true;
    console.log("sending...");

    const fullName = document.getElementById("full_name").value.trim();
    const username = document.getElementById("userName").value.trim();
    const passwordInput = document.getElementById("newPassword");
    const currentPasswordInput = document.getElementById("currentPassword");

    const password = passwordInput?.value.trim() || "";
    const currentPassword = currentPasswordInput?.value.trim() || "";

    const form = document.getElementById("userForm");

    // VALIDACIONES
    const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{7,}$/;

    // Full name
    if (!fullName || fullName.length < 5) {
        showError("full_name", "Full name is required and must be at least 5 characters");
        isValid = false;
    } else {
        const nameRegex = /^[A-Za-záéíóúÁÉÍÓÚñÑ\s'-]+$/;
        if (!nameRegex.test(fullName)) {
            showError("full_name", "Only letters and spaces are allowed");
            isValid = false;
        }
    }

    // Username
    if (!username || username.length < 5) {
        showError("userName", "Username is required and must be at least 5 characters");
        isValid = false;
    } else {
        const usernameRegex = /^[A-Za-z0-9áéíóúÁÉÍÓÚñÑ\s'-]+$/;
        if (!usernameRegex.test(username)) {
            showError("userName", "Only letters, numbers and spaces are allowed");
            isValid = false;
        }
    }

    // Password (solo si se llenó o se pidió cambiarla)
    if (passwordInput.required || password.length > 0) {
        if (!passwordRegex.test(password)) {
            showError("newPassword", "Password must be at least 7 characters, include one uppercase letter, one number, and one symbol");
            isValid = false;
        }
    }

    // Current password (solo si se va a editar)
    if (form.dataset.id && passwordInput.required && currentPassword.length < 7) {
        showError("currentPassword", "Current password is required and must be at least 7 characters");
        isValid = false;
    }

    return isValid;
}


function showError(fieldId, message) {
    const input = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`);
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    input.classList.add("input-error");
}

async function openUserModal(id = null) {
    const modal = document.getElementById("userModal");
    modal.style.display = "block";

    const form = document.getElementById("userForm");
    form.reset();
    form.dataset.id = id || "";
    document.getElementById("modalTitle").textContent = id ? "Edit User" : "New User";

    const optionPassword = document.getElementById("optionPassword");
    const optionPasswordGroup = optionPassword.closest(".form-group");

    const currentPasswordGroup = document.getElementById("currentPasswordGroup");
    const newPasswordGroup = document.getElementById("newPasswordGroup");
    const passwordInput = document.getElementById("newPassword");
    const currentPasswordInput = document.getElementById("currentPassword");
    

    if (id) {
        // Edit mode
        const row = [...document.querySelectorAll(".edit-btn")]
            .find(btn => btn.dataset.id == id).closest("tr");

        document.getElementById("userName").value = row.children[1].textContent;
        document.getElementById("rol").value = row.children[2].textContent;
        document.getElementById("full_name").value = row.children[3].textContent;

        // Mostrar opción para editar contraseña
        optionPasswordGroup.style.display = "block";
        optionPassword.value = "noEdit";
        currentPasswordGroup.style.display = "none";
        newPasswordGroup.style.display = "none";
        currentPasswordInput.required = false;
        passwordInput.required = false;
    } else {
        // Create mode
        optionPasswordGroup.style.display = "none";
        currentPasswordGroup.style.display = "none";
        newPasswordGroup.style.display = "block";
        currentPasswordInput.required = false;
        passwordInput.required = true;
    }
}

async function saveUser(e) {
    e.preventDefault();

    if (!validateForm()) {
        return; // if not pass the validation, no continue
    }

    const form = e.target;
    const id = form.dataset.id;

    const username = document.getElementById("userName").value.trim();
    const rol = document.getElementById("rol").value.trim();
    const full_name = document.getElementById("full_name").value.trim();
    const password = document.getElementById("newPassword").value;
    const currentPassword = document.getElementById("currentPassword")?.value;
    const optionPassword = document.getElementById("optionPassword")?.value;

    const payload = {
        action: id ? "update" : "create",
        id,
        username,
        rol,
        full_name,
    };

    if (!id || optionPassword === "yesEdit") {
        if (!password) {
            alert("Password is required");
            return;
        }
        payload.password = password;

        if (id && optionPassword === "yesEdit" && !currentPassword) {
            alert("Current password is required");
            return;
        }

        if (id && optionPassword === "yesEdit") {
            payload.currentPassword = currentPassword;
        }
    }

    const res = await fetch("../admin/users_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
    });

    const text = await res.text();
    console.log("Respuesta del servidor:", text);
    let result;

    try {
        result = JSON.parse(text);
    } catch (e) {
        console.error("JSON inválido", e);
        return;
    }

    if (result.ok) {
        alert("Changes Satisfactory!");
        closeModal();
        fetchUsers();
    } else {
        alert(result.msg || "Error al guardar");
    }

}

// modal to enter password to confirm delete user


async function deleteUser(id) {

    const password = await askPassword();

    if(!password) return;

    const res = await fetch("../admin/users_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id, password}),
        credentials: "include"
    });

    const text = await res.text();
    console.log("Respuesta del servidor:", text);

    try {
        const result = JSON.parse(text);
        if (result.ok) {
            alert("User deleted successfully.");
            fetchUsers();
        } else {
            alert(`[!] ERROR TO ELIMINATE: ${result.msg}`);
        }
    } catch (e) {
        console.error("Respuesta no es JSON válido", e);
    }
}

/*function closeUserModal() {
    document.getElementById("userModal").style.display = "none";
}*/
