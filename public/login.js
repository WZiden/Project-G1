const logoutButton = document.getElementById("showLogout");
const modalContainer = document.getElementById("modalContainer");
const userNameHeading = document.getElementById("userNameHeading");

const loginLink = document.querySelectorAll(".loginLink");
const forgottenLink = document.querySelectorAll(".forgottenLink");
const registerLink = document.querySelectorAll(".registerLink");

const loginForm = document.querySelector("#loginForm");
const registerForm = document.querySelector("#registerForm");

const loginErrorMessage = document.getElementById("loginError");
const registerErrorMessage = document.getElementById("registerError");

const registeremail = document.querySelector("#registeremail");

const registerusername = document.getElementById("registerusername");
const registerpassword = document.getElementById("registerpassword");
const confirmpassword = document.getElementById("confirmpassword");



// No special characters in usernames
registerusername.addEventListener("input", function (e) {
    const nameRegex = /[&"'<>]/;
    if (registerusername.value.match(nameRegex)) {
        newErrorMessage("The name must not contain any special characters");
    } else {
        clearLoginErrorMessage();
    }
});

// Check email format
registeremail.addEventListener("blur", function (e) {
    if (registeremail.value.length > 0) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(registeremail.value)) {
            newErrorMessage("Not a valid email format");
        } else {
            clearLoginErrorMessage();
        }
    } else {
        clearLoginErrorMessage();
    }
});

// Check password requirements
registerpassword.addEventListener("blur", function (e) {
    if (registerpassword.value.length > 0) {
        if (registerpassword.value.length < 8) {
            newErrorMessage("The password must be at least 8 characters");
        } else if (!registerpassword.value.match(/[a-z]/)) {
            newErrorMessage("The password must contain a lowercase letter");
        } else if (!registerpassword.value.match(/[A-Z]/)) {
            newErrorMessage("The password must contain an uppercase letter");
        } else if (!registerpassword.value.match(/[0-9]/)) {
            newErrorMessage("The password must contain a number");
        } else {
            clearLoginErrorMessage();
        }
    } else {
        clearLoginErrorMessage();
    }
});

function newErrorMessage(text) {
    registerErrorMessage.style.display = "block";
    registerErrorMessage.innerText = text;
}

// Log out
async function logOut() {
    try {
        const response = await fetch("logOut.php");

        if (!response.ok) {
            throw new Error("HTTP: " + response.status);
        }

        location.reload();

    } catch (e) {
        console.log(e);
    }
}

function clearLoginErrorMessage() {
    loginErrorMessage.innerText = "";
    registerErrorMessage.innerText = "";
    loginErrorMessage.style.display = "none";
    registerErrorMessage.style.display = "none";
}

// Log out button
logoutButton.addEventListener("click", function () {
    logOut();
});

// Login form is displayed first
function resetLoginModal() {
    modalContentLogin.style.display = "block";
    modalContentRegister.style.display = "none";
}

// Reset all forms
function resetForms() {
    loginForm.reset();
    registerForm.reset();
}

// Login link
loginLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        resetLoginModal();
    })
});

// Register link
registerLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        clearLoginErrorMessage();
        modalContentLogin.style.display = "none";
        modalContentRegister.style.display = "block";
    })
});

// Send login form
async function sendLoginForm() {
    const formData = new FormData(loginForm);

    try {
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });

        if (response.status == 400) {
            loginErrorMessage.style.display = "block";
            loginErrorMessage.innerText = "Error trying to log in - response 400";
        } else {
            modalContainer.style.display = "none";
            location.reload();
        }

    } catch (e) {
        loginErrorMessage.style.display = "block";
        loginErrorMessage.innerText = "Error trying to log in - catch";
        console.log(e);
    }
}

// Send 'register new account' form
async function sendRegisterForm() {
    const formData = new FormData(registerForm);

    try {
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });

        if (response.status == 400) {
            registerErrorMessage.style.display = "block";
            registerErrorMessage.innerText = "Error: unable to create new user";
        } else {
            resetLoginModal();
        }
    } catch (e) {
        console.log(e);
    }
}

// Login form button
loginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    sendLoginForm();
    loginForm.reset();
});

// 'Register new account' form button
registerForm.addEventListener("submit", function (e) {
    e.preventDefault();
    sendRegisterForm();
    registerForm.reset();
});

// Check if user is logged in
async function checkLogin() {
    try {
        const response = await fetch("onPageLoad.php");
        const data = await response.json();

        if (response.status == 401) {
            modalContainer.style.display = "block";
        } else {
            userNameHeading.innerText = "Welcome " + data;
        }

    } catch (e) {
        console.log(e);
        modalContainer.style.display = "block";
    }
}

// Check login on load page
document.addEventListener("DOMContentLoaded", function () {
    loginErrorMessage.style.display = "none";
    checkLogin();
});
