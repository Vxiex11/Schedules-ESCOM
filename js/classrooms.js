document.addEventListener("DOMContentLoaded", () => {
    document.querySelector(".classrooms-table").style.display = "none";

    /*document.getElementById("addClassroomBtn").addEventListener("click", () => {
        openModal(); // new classroom
    });*/

    document.querySelector(".close-modal").addEventListener("click", closeModal);
    document.querySelector(".cancel-btn").addEventListener("click", closeModal);

    document.getElementById("classroomForm").addEventListener("submit", saveClassroom);

    document.getElementById("searchInput").addEventListener("input", async (e) => {
        const query = e.target.value.toLowerCase();
        const type = document.getElementById("searchType").value.toLowerCase();
        const action = type === 'group' ? "read_groups" : "read_classroom";

        if (!query) {
            document.querySelector(".classrooms-table").style.display = "none";
            return;
        }

        const res = await fetch('../admin/classrooms_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({action})
        });

        const text = await res.text();
        //console.log("[DEBUG] Texto crudo recibido:", text); // DEBUGGG

        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error("[!] ERROR JSON invalidate:", e);
            return; // exit if it isnt JSON
        }

        if (result.ok) {
            const filtered = result.items.filter(item => {
                if (type === "group") {
                    return item.nombre_grupo.toLowerCase().includes(query);
                } else {
                    return item.nombre.toLowerCase().includes(query);
                }
            });

            if (filtered.length > 0) {
                document.querySelector(".classrooms-table").style.display = "table";
                updateTableHeader(type);
                renderClassrooms(filtered, type);
            } else {
                document.querySelector(".classrooms-table").style.display = "none";
            }
        }
        console.log("[DEBUG] Result:", result);
    });

});

async function cargarMaterias() {
    const res = await fetch('../admin/classrooms_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_subjects' })
    });
    const result = await res.json();
    if (result.ok) {
        const select = document.getElementById('classroomGroup');
        select.innerHTML = ''; // limpia opciones anteriores
        result.data.forEach(materia => {
            const option = document.createElement('option');
            option.value = materia.id;
            option.textContent = materia.nombre;
            select.appendChild(option);
        });
        $('#classroomSubjects').trigger('change');
    }
}

function updateTableHeader(type) {
    const thead = document.querySelector(".classrooms-table thead");
    
    if (type === "group") {
        thead.innerHTML = `
            <tr>
                <th>ID</th>
                <th>Group</th>
                <th>Actions</th>
            </tr>
        `;
    } else {
        thead.innerHTML = `
            <tr>
                <th>ID</th>
                <th>Classroom</th>
                <th>Actions</th>
            </tr>
        `;
    }
}

function renderClassrooms(items, type) {
    const tbody = document.querySelector(".classrooms-table tbody");
    tbody.innerHTML = "";

    items.forEach(item => {
        let row = "";

        if (type === "group") {
            row = `
                <td>${item.id}</td>
                <td>${item.nombre_grupo}</td>
                <td class="actions">
                    <button class="edit-btn" data-id="${item.id}" data-type="group">
                        <span class="material-icons">edit</span>
                    </button>
                    <button class="delete-btn" data-id="${item.id}" data-type="group">
                        <span class="material-icons">delete</span>
                    </button>
                </td>
            `;
        } else {
            row = `
                <td>${item.id}</td>
                <td>${item.nombre}</td>
                <td class="actions">
                    <button class="edit-btn" data-id="${item.id}" data-type="classroom">
                        <span class="material-icons">edit</span>
                    </button>
                    <button class="delete-btn" data-id="${item.id}" data-type="classroom">
                        <span class="material-icons">delete</span>
                    </button>
                </td>
            `;
        }

        const tr = document.createElement("tr");
        tr.innerHTML = row;
        tbody.appendChild(tr);
    });

    document.querySelector(".classrooms-table").style.display = "table";

    document.querySelectorAll(".edit-btn").forEach(btn =>
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            const type = btn.dataset.type;
            console.log("Edit click: id =", id, "type =", type);
            openModal(id, type);
        })
    );

    document.querySelectorAll(".delete-btn").forEach(btn =>
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            const type = btn.dataset.type;
            deleteClassroom(id, type);
        })
    );
}


async function openModal(id = null, type = "group") {
    const modal = document.getElementById("classroomModal");
    const form = document.getElementById("classroomForm");

    console.log("Tipo que entra:", type);

    modal.style.display = "block";
    form.reset();
    form.dataset.id = id || "";
    modal.setAttribute("data-type", type);
    form.setAttribute("data-type", type); // <- CRUCIAL
    console.log("Tipo seteado en el modal:", modal.getAttribute("data-type"));
    console.log("Tipo seteado en el form:", form.getAttribute("data-type"));

    // Mostrar/ocultar campos
    document.querySelector(".group-field").style.display = (type === "group") ? "block" : "none";
    document.querySelector(".classroom-field").style.display = (type === "classroom") ? "block" : "none";

    const title = type === "group" ? "Group" : "Classroom";
    document.getElementById("modalTitle").textContent = id ? `Edit ${title}` : `New ${title}`;

    // Llenar si es edición
    if (id) {
        const row = [...document.querySelectorAll(".edit-btn")].find(btn => btn.dataset.id == id)?.closest("tr");
        if (row) {
            if (type === "group") {
                document.getElementById("classroomGroup").value = row.children[1].textContent.trim();
            } else {
                document.getElementById("classroomName").value = row.children[1].textContent.trim();
            }
        }
    }
}

function closeModal() {
    document.getElementById("classroomModal").style.display = "none";
    document.getElementById("passwordModal").style.display = "none";
    document.getElementById("classroomModal").style.display = "none";
}

function validateForm() {
    let isValid = true;
    const type = document.getElementById("classroomModal").getAttribute("data-type");

    const Regex = /^[A-Za-z0-9áéíóúÁÉÍÓÚñÑ\s'-]+$/;

    if (type === "group") {
        const group = document.getElementById("classroomGroup").value.trim();
        if (!group) {
            showError("officeError", "Group is required");
            isValid = false;
        } else if (group.length < 3) {
            showError("officeError", "Group must be at least 3 characters");
            isValid = false;
        } else if (!Regex.test(group)) {
            showError("officeError", "Invalid characters in group name");
            isValid = false;
        }

    } else if (type === "classroom") {
        const classroom = document.getElementById("classroomName").value.trim();
        if (!classroom) {
            showError("nameError", "Classroom is required");
            isValid = false;
        } else if (classroom.length < 3) {
            showError("nameError", "Classroom must be at least 3 characters");
            isValid = false;
        } else if (!Regex.test(classroom)) {
            showError("nameError", "Invalid characters in classroom name");
            isValid = false;
        }
    }

    return isValid;
}


function showError(field, message) {
    const errorElement = document.getElementById(`${field}Error`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    // Resaltar campo con error
    const inputElement = document.getElementById(`classroom${field.charAt(0).toUpperCase() + field.slice(1)}`);
    if (inputElement) {
        inputElement.classList.add('error');
    }
}

function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
    
    document.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
    });
}

// Validación en tiempo real
document.getElementById('classroomName').addEventListener('input', () => {
    const name = document.getElementById("classroomName").value.trim();
    if (name.length > 0 && name.length < 3) {
        showError('name', "Minimum 3 characters");
    } else {
        clearError('name');
    }
});

function clearError(field) {
    const errorElement = document.getElementById(`${field}Error`);
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    const inputElement = document.getElementById(`classroom${field.charAt(0).toUpperCase() + field.slice(1)}`);
    if (inputElement) {
        inputElement.classList.remove('error');
    }
}

async function saveClassroom(e) {
    e.preventDefault();
    console.log(" Save function triggered");
    const form = e.target;
    clearErrors();

    if (!validateForm()) {
        console.log("Formulario inválido. Validación falló.");
        return;}

    const id = form.dataset.id;
    const modalType = document.getElementById("classroomModal").getAttribute("data-type");

    let payload = { id };
    let action = "";

    if (modalType === "group") {
        const groupName = document.getElementById("classroomGroup").value.trim();
        if (!groupName) {
            showError("groupError", "Group name is required.");
            return;
        }

        payload.group_name = groupName;
        action = id ? "updateGroup" : "createGroup";

    } else if (modalType === "classroom") {
        const classroomName = document.getElementById("classroomName").value.trim();
        if (!classroomName) {
            showError("nameError", "Classroom name is required.");
            return;
        }

        payload.classroom_name = classroomName;
        action = id ? "updateClassroom" : "createClassroom";
    }

    payload.action = action;

    console.log("[DEBUG] Payload a enviar:", payload);

    try {
        const res = await fetch("../admin/classrooms_api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        console.log("[DEBUG] Save result:", result);

        if (result.ok) {
            closeModal();
            alert("Changes saved successfully!");
            form.reset();
        } else {
            showError("form", result.error || "Error saving data.");
        }
    } catch (error) {
        console.error("[ERROR]", error);
        showError("form", "Connection error.");
    }
}

async function deleteClassroom(id) {
    const password = await askPassword();
    if (!password) return;

    const res = await fetch("../admin/classrooms_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id, password }),
        credentials: "include"
    });

    const text = await res.text();
    try {
        //console.log("Respuesta cruda del servidor:", text); // DEBUG
        const result = JSON.parse(text);
        if (result.ok) {
            alert("Classroom deleted successfully.");
        } else {
            alert(`[!] ERROR TO ELIMINATE: ${result.msg}`);
        }
    } catch (e) {
        console.error("Respuesta no es JSON válido", e);
        //console.log("Texto recibido:", text); // DEBUG
    }
}
