
const form = document.getElementById("registerForm");

form.addEventListener("submit", async function(event) {
    event.preventDefault();

    document.querySelectorAll(".error").forEach(function(el) {
        el.textContent = "";
    });

    const formMessage = document.getElementById("formMessage");
    formMessage.textContent = "";

    const name = document.getElementById("full_name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm_password").value;
    const role = document.getElementById("role").value;

    let valid = true;

    if (!name || name.length > 100) {
        document.getElementById("nameError").textContent =
            "Please enter a valid name.";
        valid = false;
    }

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    || email.length > 255) {
    document.getElementById("emailError").textContent =
        "Please enter a valid email address.";
    valid = false;
}

    if (password.length < 8) {
        document.getElementById("passwordError").textContent =
            "Password must contain at least 8 characters.";
        valid = false;
    }

    if (password !== confirm) {
        document.getElementById("confirmError").textContent =
            "Passwords do not match.";
        valid = false;
    }

    if (!["citizen", "provider"].includes(role)) {
        document.getElementById("roleError").textContent =
            "Please choose an account type.";
        valid = false;
    }

    if (!valid) return;

    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = "Creating Account...";

    try {
        const response = await fetch("register.php", {
            method: "POST",
            body: new FormData(form)
        });

        const result = await response.json();

        if (result.success) {
            formMessage.textContent = result.message;
            formMessage.style.color = "#c0392b";
            form.reset();
        } else {
            const errorElement = document.getElementById(result.field);

            if (errorElement && errorElement.classList.contains("error")) {
                errorElement.textContent = result.message;
            } else {
                formMessage.textContent = result.message;
                formMessage.style.color = "red";
            }
        }
    } catch (error) {
        formMessage.textContent =
            "Unable to connect to the server. Please try again.";
        formMessage.style.color = "red";
        console.error(error);
    } finally {
        button.disabled = false;
        button.textContent = "Create Account ♥";
    }
});
