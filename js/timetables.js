document.addEventListener("DOMContentLoaded", () => {
    document.querySelector(".timetables-table").style.display = "none";

    document.getElementById("addTimetableBtn").addEventListener("click", () => {
        openModal(); // new Timetable
    });

    document.querySelector(".close-modal").addEventListener("click", closeModal);
    document.querySelector(".cancel-btn").addEventListener("click", closeModal);

    document.getElementById("timetableForm").addEventListener("submit", saveTimetable);

    document.getElementById("searchTimetable").addEventListener("input", async (e) => {
        const query = e.target.value.toLowerCase();
        console.log("Searching...");

        if (!query) {
            document.querySelector(".timetables-table").style.display = "none";
            return;
        }

        const res = await fetch('../admin/timetables_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'read' })
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
            const filtered = result.data.filter(s =>
                s.nombre_completo.toLowerCase().includes(query)
            );
            renderTimetables(filtered);
            document.querySelector(".timetables-table").style.display = "table";
        }

        console.log("[DEBUG] Result:", result);
    });

    $(document).ready(function () {
            initSelect2('#timetableSubjects', "Select Subjects");
            initSelect2('#timetableGroups',"Select Groups");
            initSelect2('#timetableProfessors',"Select Professors");
            initSelect2('#timetableDays',"Select Days");
            initSelect2('#timetableStartTime',"Select Start Time");
            initSelect2('#timetableEndTime',"Select End Time");
            initSelect2('#timetableClassroom',"Select Classroom");
    });

});

// function to better interface and select any option that you need to edit the timetables
function initSelect2(id, placeholder = "Select an option") {
    $(id).select2({
        placeholder: placeholder,
        width: '100%',
        dropdownAutoWidth: true,
        theme: 'default'
    });
}

async function fetchTimetables() {
    const res = await fetch('../admin/timetables_api.php', {
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
        renderTimetables(data.data);
    } else {
        alert("Error al cargar profesores");
    }
}

async function loadOptions({action, selectId, valueKey, valueText}){
    try{
        const res = await fetch('../admin/timetables_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({action})
        });

        const text = await res.text();
        //console.log(`[DEBUG] Respuesta cruda de ${action}:`, text); // DEBUG

        const result = JSON.parse(text);
        console.log(`[${action}] ->`, result.data); // DEBUGGG

        if (result.ok) {
            const select = document.getElementById(selectId);
            select.innerHTML = '';
            result.data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[valueKey];
                option.textContent = item[valueText];
                select.appendChild(option);
            });
        } else {
            console.warn(`[!] ${action} no fue exitoso:`, result.msg);
        }
    }catch(error){
        console.error(`[!] ERROR to trying connect with ${action}:`, error);
    }
}

async function cargarTodosLosSelects() {
    await Promise.all([
        loadOptions({ action: "get_professors", selectId: "timetableProfessors", valueKey: "id", valueText: "nombre_completo" }),
        loadOptions({ action: "get_subjects", selectId: "timetableSubjects", valueKey: "id", valueText: "nombre" }),
        loadOptions({ action: "get_groups", selectId: "timetableGroups", valueKey: "id", valueText: "nombre" }),
        loadOptions({ action: "get_days", selectId: "timetableDays", valueKey: "dia", valueText: "dia" }),
        loadOptions({ action: "get_StartTime", selectId: "timetableStartTime", valueKey: "hora_inicio", valueText: "hora_inicio" }),
        loadOptions({ action: "get_EndTime", selectId: "timetableEndTime", valueKey: "hora_fin", valueText: "hora_fin" }),
        loadOptions({ action: "get_Classroom", selectId: "timetableClassroom", valueKey: "id", valueText: "nombre" }),
    ]);
}

function renderTimetables(timetables) {
    const tbody = document.querySelector(".timetables-table tbody");
    tbody.innerHTML = "";

    timetables.forEach(prof => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${prof.id}</td>
            <td>${prof.nombre_completo}</td>
            <td>${prof.nombre_grupo}</td>
            <td data-timetables='${JSON.stringify(prof.materias_ids || [])}'>${prof.nombre_materia}</td>
            <td>${prof.dia}</td>
            <td>${prof.hora_inicio}</td>
            <td>${prof.hora_fin}</td>
            <td>${prof.nombre_salon}</td>
            <td class="actions">
                <button class="edit-btn" data-id="${prof.id}"><span class="material-icons">edit</span></button>
                <button class="delete-btn" data-id="${prof.id}"><span class="material-icons">delete</span></button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    document.querySelectorAll(".edit-btn").forEach(btn =>
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            const timetableData = timetables.find(t => t.id == id);
            if (timetableData) openModal(timetableData);
        })
    );

    document.querySelectorAll(".delete-btn").forEach(btn =>
        btn.addEventListener("click", () => deleteTimetable(btn.dataset.id))
    );
}

async function openModal(timetable = null) {
    const form = document.getElementById("timetableForm");

    clearErrors(); // si tienes validaciones previas
    // Mostrar el modal
    document.getElementById("timetableModal").style.display = "block";

    // maybe we can modularizated this part
    await cargarTodosLosSelects();

    // Resetear el formulario
    form.reset();

    // reset the form
    $('#timetableSubjects').val(null).trigger('change');
    $('#timetableProfessors').val(null).trigger('change');
    $('#timetableGroups').val(null).trigger('change');
    $('#timetableDays').val(null).trigger('change');
    $('#timetableStartTime').val(null).trigger('change');
    $('#timetableEndTime').val(null).trigger('change');
    $('#timetableClassroom').val(null).trigger('change');

    // Limpiar dataset por si venía de una edición previa
    delete form.dataset.id;

    if (timetable) {
        // EDITION
        document.getElementById("modalTitle").textContent = "Edit Timetable";

        // Guardamos el ID en el formulario
        form.dataset.id = timetable.id;

        // Asignar valores al formulario
        $('#timetableProfessors').val(timetable.professor_id).trigger('change');
        console.log("Asignando prof:", timetable.professor_id);
        console.log("Valores disponibles:", [...document.getElementById("timetableProfessors").options].map(o => o.value));
        $('#timetableSubjects').val(timetable.subject_id).trigger('change');
        $('#timetableGroups').val(timetable.group_id).trigger('change');
        $('#timetableDays').val(timetable.dia).trigger('change');
        $('#timetableStartTime').val(timetable.hora_inicio).trigger('change');
        $('#timetableEndTime').val(timetable.hora_fin).trigger('change');
        $('#timetableClassroom').val(timetable.id_classroom).trigger('change');
    } else {
        // Modo nuevo
        document.getElementById("modalTitle").textContent = "New Timetable";
    }
}

function closeModal() {
    document.getElementById("timetableModal").style.display = "none";
    document.getElementById("passwordModal").style.display = "none";
    document.getElementById("timetableModal").style.display = "none";
}

function lockSelect(selectId) {
    const select = document.getElementById(selectId);
    const selectedValue = select.value;

    if (!selectedValue) {
        alert(`Debes seleccionar una opción en ${selectId}`);
        return false;
    }

    // eliminate the other options
    Array.from(select.options).forEach(option => {
        if (option.value !== selectedValue) {
            option.remove();
        }
    });

    // Bloquea el select
    select.disabled = true;
    return true;
}

async function validateForm() {
    console.log("Starting to validate form...");
    const startTime = document.getElementById("timetableStartTime").value.trim();
    const endTime = document.getElementById("timetableEndTime").value.trim();

    if (!startTime || !endTime) {
        alert("Start and end times are required.");
        return false;
    }

    if (startTime === endTime) {
        alert("Start and end times must be different.");
        return false;
    }

    return true;
}

function showError(field, message) {
    const errorElement = document.getElementById(`${field}Error`);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    // Resaltar campo con error
    const inputElement = document.getElementById(`timetable${field.charAt(0).toUpperCase() + field.slice(1)}`);
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

function clearError(field) {
    const errorElement = document.getElementById(`${field}Error`);
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    const inputElement = document.getElementById(`timetable${field.charAt(0).toUpperCase() + field.slice(1)}`);
    if (inputElement) {
        inputElement.classList.remove('error');
    }
}

async function saveTimetable(e) {
    e.preventDefault();
    console.log("[DEBUG] Enter");
    const form = e.target;

    // clean previous error
    clearErrors();

    const isValid = await validateForm();
    
    // Validar campos
    if (!isValid) {
        console.log("ERRORRRRRR...");
        return;
    } 
    //console.log("[DEBUG] ID timetable:", id);

    const id = form.dataset.id;
    const getFirstOrValue = (val) => Array.isArray(val) ? val[0] : val;

    const subject_id = parseInt(getFirstOrValue($('#timetableSubjects').val()));
    const rawProfessor = $('#timetableProfessors').val();
    if (!rawProfessor || isNaN(parseInt(rawProfessor))) {
        alert("Debes seleccionar un profesor válido.");
        return;
    }
    const professor_id = parseInt(rawProfessor);


    const group_id = parseInt(getFirstOrValue($('#timetableGroups').val()));

    const days = getFirstOrValue($('#timetableDays').val());
    const startTime = getFirstOrValue($('#timetableStartTime').val());
    const endTime = getFirstOrValue($('#timetableEndTime').val());
    const id_classroom = getFirstOrValue($('#timetableClassroom').val());

    
    //console.log("[DEBUG] Datos a guardar:", { id, name});

    const action = id ? 'update' : 'create';
    const payload = {
        action,
        id,
        subject_id,
        professor_id,
        group_id,
        days,
        startTime,
        endTime,
        id_classroom
    };

    console.log("[PAYLOAD ENVIADO]", payload); // DEBUG

    try {
        const res = await fetch('../admin/timetables_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const text = await res.text();
        //console.log("[DEBUG] Texto recibido del servidor:", text);
        const result = JSON.parse(text);

        if (result.ok) {
            closeModal();
            alert("Changes Satisfactory!");
            await fetchTimetables();
            form.reset();
        } else {
            console.warn("[ERROR]", result);
            alert(result.msg || result.message || "Error al guardar el horario.");
            console.error("[ERROR DEL BACKEND]", result);
        }
    } catch (error) {
        showError('form', "Connection error");
        console.error("Error:", error);
    }

}

async function deleteTimetable(id) {
    const password = await askPassword();
    if (!password) return;

    const res = await fetch("../admin/timetables_api.php", {
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
            alert("Timetable deleted successfully.");
            fetchTimetables();
        } else {
            alert(`[!] ERROR TO ELIMINATE: ${result.msg}`);
        }
    } catch (e) {
        console.error("Respuesta no es JSON válido", e);
        //console.log("Texto recibido:", text); // DEBUG
    }
}
