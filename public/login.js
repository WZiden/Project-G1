const loginButton = document.getElementById("showLogin");
const modalContainer = document.getElementById("modalContainer");
const closeModal = document.querySelectorAll(".closeModal");

const loginLink = document.querySelectorAll(".loginLink");
const forgottenLink = document.querySelectorAll(".forgottenLink");
const registerLink = document.querySelectorAll(".registerLink");

const loginForm = document.querySelector("#loginForm");
const forgottenForm = document.querySelector("#forgottenForm");
const registerForm = document.querySelector("#registerForm");

// const loginemail = document.querySelector("#loginemail");
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
        resetLoginModal();
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

        const data = await response.json();
        if (data[0] != 1) {
            alert("Error: " + data[1]);
        } else {
            alert("Welcome " + data[2]);
        }

    } catch (e) {
        // TODO felhantering
        console.log("Error logging in");
        //console.log(e);
    }
}

/*
// Send 'reset password' form
async function sendForgottenForm() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(forgottenForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });
        // TODO felhantering
    } catch (e) {
        // TODO felhantering
        console.warn(e);
    }
} */

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

       const data = await response.json();  
       if (data[0] != 1) {
            alert("Error: " + data[1]);
       } else {
            resetLoginModal();
       }
       
    } catch (e) {
        // TODO felhantering
        console.log("Error adding user");
    }
}

// User pressed login form button
loginForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendLoginForm();
    loginForm.reset();
    modalContainer.style.display = "none";
});

/*
// User pressed 'reset password' form button
forgottenForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendForgottenForm();
    forgottenForm.reset();
    resetLoginModal();
}); */

// User pressed 'register new account' form button
registerForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendRegisterForm();
    registerForm.reset();
});


