
const form = document.getElementById("LoginForm");
const message = document.getElementById("loginMessage");

form.addEventListener("submit", async function(event) {
    event.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const button = form.querySelector('button[type="submit"]');

    message.textContent = "";
    message.style.color = "#c0392b";

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        loginMessage.textContent = "Please enter a valid email address.";
        return;
    }

    if (!password) {
        loginMessage.textContent = "Please enter your password.";
        return;
    }

    button.disabled = true;
    button.textContent = "Signing In...";

    try {
        const response = await fetch("login.php", {
            method: "POST",
            body: new FormData(form)
        });

        const result = await response.json();

        message.textContent = result.message;
        message.style.color = result.success ? "#c0392b" : "red";

        if (result.success) {
            form.reset();
        }

    } catch (error) {
        loginMessageMessage.textContent = "Unable to login. Please try again.";
        loginMessage.style.color = "red";
        console.error(error);

    } finally {
        button.disabled = false;
        button.textContent = "Sign In to ANI Care ♥";
    }
});
