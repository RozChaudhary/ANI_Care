
const form = document.getElementById("forgotForm");
const message = document.getElementById("formMessage");
const submitButton = form.querySelector('button[type="submit"]');

form.addEventListener("submit", async function (event) {
    event.preventDefault();

    message.textContent = "";
    submitButton.disabled = true;
    submitButton.textContent = "Sending...";

    try {
        const response = await fetch(form.action, {
            method: "POST",
            body: new FormData(form)
        });

        const data = await response.json();

        message.textContent =
            data.message || "Something went wrong. Please try again.";

        message.style.color = response.ok ? "green" : "red";

    } catch (error) {
        message.textContent =
            "Unable to connect. Please try again.";
        message.style.color = "red";

    } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Send Reset Link";
    }
});
