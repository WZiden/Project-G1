const loginButton = document.getElementById("showLogin");

const modalContainer = document.getElementById("modalContainer");

const closeModal = document.querySelectorAll(".closeModal");

const loginLink = document.querySelectorAll(".loginLink");
const forgottenLink = document.querySelectorAll(".forgottenLink");
const registerLink = document.querySelectorAll(".registerLink");

const loginForm = document.querySelector("#loginForm");
const forgottenForm = document.querySelector("#forgottenForm");
const registerForm = document.querySelector("#registerForm");

const loginemail = document.querySelector("#loginemail");
// const registeremail = document.querySelector("#registeremail");
// const forgottenemail = document.querySelector("#forgottenemail");





// Show login page modal
loginButton.addEventListener("click", function () {
    modalContainer.style.display = "block";
});

/* // TMP log out button
button.addEventListener("click", function () {
    modalContainer.style.display = "block";
}); */

// Login form is displayed first
function resetLoginModal() {
    modalContentLogin.style.display = "block";
    modalContentForgotten.style.display = "none";
    modalContentRegister.style.display = "none";
}

// Reset all forms
function resetForms() {
    loginForm.reset();
    forgottenForm.reset();
    registerForm.reset();
}

// Upper left cross exit
closeModal.forEach(button => {
    button.addEventListener("click", function () {
        resetForms();
        resetLoginModal();
        modalContainer.style.display = "none";
})});

// Login link
loginLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        modalContentLogin.style.display = "block";
        modalContentForgotten.style.display = "none";
        modalContentRegister.style.display = "none";
})});

// Forgotten link
forgottenLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        modalContentLogin.style.display = "none";
        modalContentForgotten.style.display = "block";
        modalContentRegister.style.display = "none";
})});

// Register link
registerLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        modalContentLogin.style.display = "none";
        modalContentForgotten.style.display = "none";
        modalContentRegister.style.display = "block";
})});

// Close the form when clicks occur outside of the form
modalContainer.addEventListener("click", function(e) {
    // Do not detect clicks in children elements
    if (e.target !== e.currentTarget) {
        return;
    }
    resetForms();
    resetLoginModal();
    modalContainer.style.display = "none";
});

/* // TODO
// Check if email already exists
loginemail.addEventListener("blur", function(e) {
    console.log("Check if email exists", loginemail.value);
});
 */


/* 
async function sendLoginForm() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(loginForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("login.php", {
            method: "POST",
            body: formData,
        });
        // TODO felhantering
    } catch (e) {
        // TODO felhantering
        console.warn(e);
    }
}

async function sendForgottenForm() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(forgottenForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("passwordrecovery.php", {
            method: "POST",
            body: formData,
        });
        // TODO felhantering
    } catch (e) {
        // TODO felhantering
        console.warn(e);
    }
} */

async function sendRegisterForm() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(registerForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("add_user.php", {
            method: "POST",
            body: formData,
        });
        // TODO felhantering
    } catch (e) {
        // TODO felhantering
        console.error(e);
    }
}

/*
// Send login form
loginForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendLoginForm();
    loginForm.reset();
    modalContainer.style.display = "none";
});

// Send form
forgottenForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendForgottenForm();
    forgottenForm.reset();
    resetLoginModal();
}); */

// Send register form
registerForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendRegisterForm();
    registerForm.reset();
    resetLoginModal();
});


