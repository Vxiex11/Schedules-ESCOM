document.addEventListener("DOMContentLoaded", () => {
    document.querySelector(".subjects-table").style.display = "none";

    document.getElementById("addSubjectBtn").addEventListener("click", () => {
        openModal(); // new subject
    });

    document.querySelector(".close-modal").addEventListener("click", closeModal);
    document.querySelector(".cancel-btn").addEventListener("click", closeModal);

    document.getElementById("subjectForm").addEventListener("submit", saveSubject);

    document.getElementById("searchSubject").addEventListener("input", async (e) => {
    const query = e.target.value.toLowerCase();

        if (!query) {
            document.querySelector(".subjects-table").style.display = "none";
            return;
        }

        const res = await fetch('../admin/subjects_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'read' })
        });

        const result = await res.json();
        if (result.ok) {
            const filtered = result.data.filter(s =>
                s.subject_name.toLowerCase().includes(query)
            );
            renderSubjects(filtered);
            document.querySelector(".subjects-table").style.display = "table";
        }
    });

    $(document).ready(function () {
        $('#subjectSubjects').select2({
            placeholder: "Select the subjects",
            width: '100%',
            dropdownAutoWidth: true,
            theme: 'default'
        });
    });
});

async function fetchSubjects() {
    const res = await fetch('../admin/subjects_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'read' })
    });

    const text = await res.text();
 
    let data;
    try {
        data = JSON.parse(text);
    } catch (err) {
        console.error("Error parseando JSON:", err);
        return;
    }

    if (data.ok) {
        renderSubjects(data.data);
    } else {
        alert("Error al cargar profesores");
    }
}

async function cargarMaterias() {
    const res = await fetch('../admin/Subjects_api.php', {
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

function renderSubjects(subjects) {
    const tbody = document.querySelector(".subjects-table tbody");
    tbody.innerHTML = "";

    subjects.forEach(prof => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${prof.id}</td>
            <td data-subjects='${JSON.stringify(prof.materias_ids || [])}'>${prof.subject_name}</td>
            <td>${prof.professor_name}</td>
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
        btn.addEventListener("click", () => deleteSubject(btn.dataset.id))
    );
}

async function openModal(id = null) {
    document.getElementById("subjectModal").style.display = "block";
    document.getElementById("subjectForm").reset();
    document.getElementById("modalTitle").textContent = id ? "Edit Subject" : "New Subject";
    document.getElementById("subjectForm").dataset.id = id || "";

    await cargarMaterias();
    $('#professorSubjects').val(null).trigger('change');

    if (id) {
        const row = [...document.querySelectorAll(".edit-btn")].find(btn => btn.dataset.id == id).closest("tr");
        document.getElementById("subjectName").value = row.children[1].textContent;

        const subjects = JSON.parse(row.children[3].dataset.subjects || "[]");
        $('#professorSubjects').val(subjects).trigger('change');
    }
}

function closeModal() {
    document.getElementById("subjectModal").style.display = "none";
    document.getElementById("passwordModal").style.display = "none";
    document.getElementById("subjectModal").style.display = "none";
}

function validateForm() {
    let isValid = true;
    
    // Validar nombre
    const name = document.getElementById("subjectName").value.trim();
    console.log("[DEBUG] Name:", name);
    if (!name) {
        showError('name', "Full name is required");
        isValid = false;
    } else if (name.length < 5) {
        showError('name', "Minimum 5 characters");
        isValid = false;
    }
    
    const nameRegex = /^[A-Za-z0-9áéíóúÁÉÍÓÚñÑ\s'-]+$/;
    if (!nameRegex.test(name)) {
        showError('name', "Invalid characters in name");
        isValid = false;
    }
    console.log("[DEBUG] fin");
    return isValid;
}

function showError(field, message) {
    const errorElement = document.getElementById(`${field}Error`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    // Resaltar campo con error
    const inputElement = document.getElementById(`subject${field.charAt(0).toUpperCase() + field.slice(1)}`);
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
document.getElementById('subjectName').addEventListener('input', () => {
    const name = document.getElementById("subjectName").value.trim();
    if (name.length > 0 && name.length < 5) {
        showError('name', "Minimum 5 characters");
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
    
    const inputElement = document.getElementById(`subject${field.charAt(0).toUpperCase() + field.slice(1)}`);
    if (inputElement) {
        inputElement.classList.remove('error');
    }
}

async function saveSubject(e) {
    e.preventDefault();
    console.log("[DEBUG] Enter");
    const form = e.target;

    // Limpiar errores previos
    clearErrors();
    
    // Validar campos
    if (!validateForm()) {
        //console.log("Validating form...");
        return;
    }

    const id = form.dataset.id;
    //console.log("[DEBUG] ID subject:", id);
    const name = document.getElementById("subjectName").value;

    //console.log("[DEBUG] Datos a guardar:", { id, name});

    const action = id ? 'update' : 'create';
    const payload = {
        action,
        id,
        subjectname: name,
    };

    try {
        const res = await fetch('../admin/subjects_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        
        if (result.ok) {
            // first close the modal
            closeModal();
            // notification
            alert("Changes Satisfactory!");
            // refresh row of Subjects
            await fetchSubjects();
            // reseat form
            form.reset();
        } else {
            showError('form', result.message || "Error saving subject");
        }
    } catch (error) {
        showError('form', "Connection error");
        console.error("Error:", error);
    }
}

async function deleteSubject(id) {
    const password = await askPassword();
    if (!password) return;

    const res = await fetch("../admin/subjects_api.php", {
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
            alert("Subject deleted successfully.");
            fetchSubjects();
        } else {
            alert(`[!] ERROR TO ELIMINATE: ${result.msg}`);
        }
    } catch (e) {
        console.error("Respuesta no es JSON válido", e);
        //console.log("Texto recibido:", text); // DEBUG
    }
}
