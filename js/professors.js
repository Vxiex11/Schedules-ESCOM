document.addEventListener("DOMContentLoaded", () => {
    document.querySelector(".professors-table").style.display = "none";

    document.getElementById("addProfessorBtn").addEventListener("click", () => {
        openModal(); // new professor
    });

    document.querySelector(".close-modal").addEventListener("click", closeModal);
    document.querySelector(".cancel-btn").addEventListener("click", closeModal);

    document.getElementById("professorForm").addEventListener("submit", saveProfessor);

    document.getElementById("searchProfessor").addEventListener("input", async (e) => {
        const query = e.target.value.toLowerCase();

        if (!query) {
            document.querySelector(".professors-table").style.display = "none";
            return;
        }

        const res = await fetch('../admin/professors_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'read' })
        });

        const result = await res.json();
        if (result.ok) {
            const filtered = result.data.filter(p =>
                p.nombre_completo.toLowerCase().includes(query)
            );
            renderProfessors(filtered);
            document.querySelector(".professors-table").style.display = "table";
        }
    });

    $(document).ready(function () {
        $('#professorSubjects').select2({
            placeholder: "Select the subjects",
            width: '100%',
            dropdownAutoWidth: true,
            theme: 'default'
        });
    });
});

async function fetchProfessors() {
    const res = await fetch('../admin/professors_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'read' })
    });

    const text = await res.text();
    //console.log("Respuesta del servidor:", text); // DEBUGGGGG 
    let data;
    try {
        data = JSON.parse(text);
    } catch (err) {
        console.error("Error parseando JSON:", err);
        return;
    }

    if (data.ok) {
        renderProfessors(data.data);
    } else {
        alert("Error al cargar profesores");
    }
}

async function cargarMaterias() {
    const res = await fetch('../admin/professors_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_subjects' })
    });
    const result = await res.json();
    if (result.ok) {
        const select = document.getElementById('professorSubjects');
        select.innerHTML = ''; // limpia opciones anteriores
        result.data.forEach(materia => {
            const option = document.createElement('option');
            option.value = materia.id;
            option.textContent = materia.nombre;
            select.appendChild(option);
        });
        $('#professorSubjects').trigger('change');
    }
}

function renderProfessors(professors) {
    const tbody = document.querySelector(".professors-table tbody");
    tbody.innerHTML = "";

    professors.forEach(prof => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${prof.id}</td>
            <td>${prof.nombre_completo}</td>
            <td>${prof.email}</td>
            <td data-subjects='${JSON.stringify(prof.materias_ids || [])}'>${prof.materias.join(", ")}</td>
            <td>${prof.oficina}</td>
            <td>
                <span class="status ${prof.state == 'active' ? 'active' : 'inactive'}">
                    ${prof.state == "active" ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="actions">
                <button class="edit-btn" data-id="${prof.id}"><span class="material-icons">edit</span></button>
                <button class="delete-btn" data-id="${prof.id}"><span class="material-icons">delete</span></button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.querySelectorAll(".edit-btn").forEach(btn =>
        btn.addEventListener("click", () => openModal(btn.dataset.id))
    );
    document.querySelectorAll(".delete-btn").forEach(btn =>
        btn.addEventListener("click", () => deleteProfessor(btn.dataset.id))
    );
}

async function openModal(id = null) {
    document.getElementById("professorModal").style.display = "block";
    document.getElementById("professorForm").reset();
    document.getElementById("modalTitle").textContent = id ? "Edit Professor" : "New Professor";
    document.getElementById("professorForm").dataset.id = id || "";

    await cargarMaterias();
    $('#professorSubjects').val(null).trigger('change');

    if (id) {
        const row = [...document.querySelectorAll(".edit-btn")].find(btn => btn.dataset.id == id).closest("tr");
        document.getElementById("professorName").value = row.children[1].textContent;
        document.getElementById("professorEmail").value = row.children[2].textContent;
        document.getElementById("professorOffice").value = row.children[4].textContent;

        const subjects = JSON.parse(row.children[3].dataset.subjects || "[]");
        $('#professorSubjects').val(subjects).trigger('change');

        const state = row.dataset.state || "active"; // por defecto activo
        document.querySelector(`input[name="status"][value="${state}"]`).checked = true;
    }
}

function closeModal() {
    document.getElementById("professorModal").style.display = "none";
    document.getElementById("passwordModal").style.display = "none";
    document.getElementById("professorModal").style.display = "none";
}

function validateForm() {
    let isValid = true;
    
    // Validar nombre
    const name = document.getElementById("professorName").value.trim();
    if (!name) {
        showError('name', "Full name is required");
        isValid = false;
    } else if (name.length < 5) {
        showError('name', "Minimum 5 characters");
        isValid = false;
    }
    
    const nameRegex = /^[A-Za-záéíóúÁÉÍÓÚñÑ\s'-]+$/;
    if (!nameRegex.test(name)) {
        showError('name', "Invalid characters in name");
        isValid = false;
    }

    // Validar email
    const email = document.getElementById("professorEmail").value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
        showError('email', "Email is required");
        isValid = false;
    } else if (!emailRegex.test(email)) {
        showError('email', "Invalid email format");
        isValid = false;
    }
    
    // Validar materias
    const subjects = $('#professorSubjects').val();
    if (!subjects || subjects.length === 0) {
        showError('subjects', "At least one subject is required");
        isValid = false;
    }
    
    // Validar oficina
    const office = document.getElementById("professorOffice").value.trim();
    if (!office) {
        showError('office', "Office is required");
        isValid = false;
    }

    // NO XSS
    const officeRegex = /^[A-Za-z0-9\s-]+$/;
    if (!officeRegex.test(office)) {
        showError('office', "Only letters, numbers and hyphens");
        isValid = false;
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
    const inputElement = document.getElementById(`professor${field.charAt(0).toUpperCase() + field.slice(1)}`);
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
document.getElementById('professorName').addEventListener('input', () => {
    const name = document.getElementById("professorName").value.trim();
    if (name.length > 0 && name.length < 5) {
        showError('name', "Minimum 5 characters");
    } else {
        clearError('name');
    }
});

document.getElementById('professorEmail').addEventListener('input', () => {
    const email = document.getElementById("professorEmail").value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
        showError('email', "Invalid email format");
    } else {
        clearError('email');
    }
});

function clearError(field) {
    const errorElement = document.getElementById(`${field}Error`);
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    const inputElement = document.getElementById(`professor${field.charAt(0).toUpperCase() + field.slice(1)}`);
    if (inputElement) {
        inputElement.classList.remove('error');
    }
}

async function saveProfessor(e) {
    e.preventDefault();
    const form = e.target;

        // Limpiar errores previos
    clearErrors();
    
    // Validar campos
    if (!validateForm()) {
        return;
    }

    const id = form.dataset.id;
    const name = document.getElementById("professorName").value;
    const email = document.getElementById("professorEmail").value;
    const office = document.getElementById("professorOffice").value;
    const subjects = $('#professorSubjects').val().map(id => parseInt(id));
    const state = document.querySelector('input[name="status"]:checked').value;

    const action = id ? 'update' : 'create';
    const payload = {
        action,
        id,
        name,
        email,
        office,
        subjects, 
        state
    };

    try {
        const res = await fetch('../admin/professors_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await res.json();

        if (result.error === 'email_exists') {
            showError('email', "This email is already registered");
            return;
        }
        
        if (result.ok) {
            // first close the modal
            closeModal();
            // notification
            alert("Changes Satisfactory!");
            // refresh row of professors
            await fetchProfessors();
            // reseat form
            form.reset();
            $('#professorSubjects').val(null).trigger('change');
        } else {
            showError('form', result.message || "Error saving professor");
        }
    } catch (error) {
        showError('form', "Connection error");
        console.error("Error:", error);
    }
}

async function deleteProfessor(id) {
    const password = await askPassword();
    if (!password) return;

    const res = await fetch("../admin/professors_api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id, password }),
        credentials: "include"
    });

    const text = await res.text();
    try {
        console.log("Respuesta cruda del servidor:", text);
        const result = JSON.parse(text);
        if (result.ok) {
            alert("Professor deleted successfully.");
            fetchProfessors();
        } else {
            alert(`[!] ERROR TO ELIMINATE: ${result.msg}`);
        }
    } catch (e) {
        console.error("Respuesta no es JSON válido", e);
        //console.log("Texto recibido:", text); // DEBUG
    }
}
