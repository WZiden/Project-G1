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

// const loginemail = document.querySelector("#loginemail");
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
        clearErrorMessage();
    }
});

// Check email format
registeremail.addEventListener("blur", function (e) {
    if (registeremail.value.length > 0) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailRegex.test(registeremail.value)) {
            newErrorMessage("Not a valid email format");
        } else {
            clearErrorMessage();
        }
    } else {
        clearErrorMessage();
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
            clearErrorMessage();
        }
    } else {
        clearErrorMessage();
    }
});

/*
// TODO jämföra lösenord
confirmpassword.addEventListener("blur", function(e) {
    if (registerpassword.value.length > 0) {
        if (confirmpassword != XXX) {
            newErrorMessage("The passwords do not match");
        } else {
            clearErrorMessage();
        }        
    }
});
*/

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

function clearErrorMessage() {
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
        clearErrorMessage();
        modalContentLogin.style.display = "none";
        modalContentRegister.style.display = "block";
    })
});

// TODO extra feature
/*
// Check if email already exists
loginemail.addEventListener("blur", function(e) {
    console.log("Check if email exists", loginemail.value);
});
 */

// Send login form
async function sendLoginForm() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(loginForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
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
    // Assiciate the FormData object with the login form element
    const formData = new FormData(registerForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
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
            userNameHeading.innerText = data;
        }

    } catch (e) {
        console.log(e);
        modalContainer.style.display = "block";
    }
}

// Extract username from cookie
function getCookieUsername() {
    let startPosition = document.cookie.search("Username=") + 9;
    let endposition = document.cookie.indexOf(";", startPosition);
    if (endposition == -1) {
        let userName = document.cookie.substring(startPosition);
        console.log(userName);
    } else {
        let userName = document.cookie.substring(startPosition, endposition);
        console.log(userName);
    }

}

// Check login on load page
document.addEventListener("DOMContentLoaded", function () {
    loginErrorMessage.style.display = "none";
    checkLogin();
});
