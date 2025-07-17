
// FUNCTIONS

function validatename(name)
{
    const onlyLetters = /^[a-zA-Z0-9._-]{3,}$/;
    return onlyLetters.test(name);
}

function validatePassword(password)
{
    const islarge = password.length >= 7;
    return islarge;
}

/*  !!!!!!!!!!!!!!!!!!!!!!!!!!!!
    VALIDATE PASSWORD WHEN WE NEED TO REGISTER AND CREATE NEW PASSWORD
function validatePassword(password) {
    // Al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo
    const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    return pattern.test(password);
}
*/

// LISTENER 

const login = document.getElementById("login1");

let attempts = 0;

login.addEventListener("submit", async function (e) {
    e.preventDefault();

    let isValid = true;

    const usernameInput = document.getElementById("name");
    const passwordInput = document.getElementById("password");
    const username = usernameInput.value.trim();
    const password = passwordInput.value;

    usernameInput.classList.remove("is-invalid");
    passwordInput.classList.remove("is-invalid");

    if (!validatename(username)) {
        usernameInput.classList.add("is-invalid");
        isValid = false;
    }

    if (!validatePassword(password)) {
        passwordInput.classList.add("is-invalid");
        isValid = false;
    }

    if (!isValid) {
        attempts++;
        if (attempts >= 5) {
            alert("Too many attemps, try later...");
            login.querySelector("button").disabled = true;
            setTimeout(() => {
                login.querySelector("button").disabled = false;
                attempts = 0;
            }, 50000); // 50 seconds blocked
        }
        return;
    }

    // if pass the validations, go to server
    try {
        const response = await fetch("server/auth.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                username: username,
                password: password,
            }),
        });

        const data = await response.json();

        if (data.ok) {
            alert("Login exitoso.");
            // rol
            if (data.role === "admin") {
                window.location.href = "admin/index.php";
            } else {
                window.location.href = "students/index.php";
            }
        } else {
            alert(data.msg || "Usuario o contraseña incorrectos.");
        }
    } catch (err) {
        console.error("Error de red o servidor:", err);
        alert("Error en la conexión. Intenta de nuevo.");
    }
});

document.getElementById("registerForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const full_name = document.getElementById("regName").value.trim();
    const username = document.getElementById("regUsername").value.trim();
    const password = document.getElementById("regPassword").value;

    if (!validatename(username) || !validatePassword(password) || full_name.length < 3) {
        alert("All the fields are required");
        return;
    }

    try {
        const response = await fetch("server/register.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                full_name,
                username,
                password
            })
        });

        const text = await response.text();
        console.log("server crude:", text);

        const data = JSON.parse(text);  //we take manually

        if (data.ok) {
            alert("User register succesfully, login...");
            document.getElementById("registerForm").reset();
            bootstrap.Modal.getInstance(document.getElementById("registerModal")).hide();
        } else {
            alert(data.msg || "Error to register.");
        }
    } catch (err) {
        console.error(err);
        alert("ERROR SERVER");
    }
});
