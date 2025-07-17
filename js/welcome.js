document.addEventListener("DOMContentLoaded", () => {
    fetch("../server/session_info.php")
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                const spanUserName = document.getElementById("userName");
                if (spanUserName) {
                    spanUserName.textContent = data.full_name;
                }
            } else {
                // vamos al login si no hay sesionn
                window.location.href = "../login.html";
            }
        })
        .catch(err => {
            console.error("[!] ERROR al obtener datos de sesion:", err);
            window.location.href = "../login.html";
        });
});
