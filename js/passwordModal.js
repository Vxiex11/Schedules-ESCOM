let passwordResolve;

function askPassword() {
    return new Promise((resolve) => {
        passwordResolve = resolve;
        document.getElementById("confirmPassword").value = "";
        document.getElementById("passwordModal").style.display = "flex";
    });
}

function submitPassword() {
    const password = document.getElementById("confirmPassword").value.trim();
    closeModal();
    passwordResolve(password || null);
}
