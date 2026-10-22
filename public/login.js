const loginButton = document.getElementById("showLogin");
const logoutButton = document.getElementById("showLogout");
const modalContainer = document.getElementById("modalContainer");
const closeModal = document.querySelectorAll(".closeModal");
const userNameHeading = document.getElementById("userNameHeading");

const loginLink = document.querySelectorAll(".loginLink");
const forgottenLink = document.querySelectorAll(".forgottenLink");
const registerLink = document.querySelectorAll(".registerLink");

const loginForm = document.querySelector("#loginForm");
const registerForm = document.querySelector("#registerForm");

const loginErrorMessage = document.getElementById("loginError");
const registerErrorMessage = document.getElementById("registerError");

// const loginemail = document.querySelector("#loginemail");
// const registeremail = document.querySelector("#registeremail");
// const forgottenemail = document.querySelector("#forgottenemail");







// Log out
async function logOut() {
    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("logOut.php");

        if (!response.ok) {
            throw new Error("HTTP: " + response.status);
        }
        
        userNameHeading.innerText = "";
        showLoginBtn();

    } catch (e) {
        // TODO felhantering
        console.log("Error logging out");
        //console.log(e);
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

// Show login page modal
loginButton.addEventListener("click", function () {
    modalContainer.style.display = "block";
});

// Show Login button
function showLoginBtn() {
    loginButton.style.display = "block";
    logoutButton.style.display = "none";
}

// Show Logout button
function showLogOutBtn() {
    loginButton.style.display = "none";
    logoutButton.style.display = "block";
}

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

// Upper left cross exit
closeModal.forEach(button => {
    button.addEventListener("click", function () {
        resetForms();
        resetLoginModal();
        clearErrorMessage();
        modalContainer.style.display = "none";
})});

// Login link
loginLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        resetLoginModal();
})});

// Register link
registerLink.forEach(link => {
    link.addEventListener("click", function () {
        resetForms();
        clearErrorMessage();
        modalContentLogin.style.display = "none";
        modalContentRegister.style.display = "block";
})});

// Close the form when clicks occur outside of the form
modalContainer.addEventListener("click", function(e) {
    // Do not detect clicks in children elements
    if (e.target !== e.currentTarget) {
        return;
    }
    resetForms();
    clearErrorMessage();
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
            loginErrorMessage.style.display = "block";
            loginErrorMessage.innerText = "Error: " + data[1];
        } else {
            showLogOutBtn();
            userNameHeading.innerText = data[2];
            modalContainer.style.display = "none";
        }

    } catch (e) {
        // TODO felhantering
        console.log("Error logging in");
        loginErrorMessage.style.display = "block";
        loginErrorMessage.innerText = "Error trying to log in";
        //console.log(e);
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

       const data = await response.json();  
       if (data[0] != 1) {
            registerErrorMessage.style.display = "block";
            registerErrorMessage.innerText = "Error: " + data[1]
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
});

// User pressed 'register new account' form button
registerForm.addEventListener("submit", function(e) {
    e.preventDefault();
    sendRegisterForm();
    registerForm.reset();
});

// Check if user is logged in
async function checkCookie() {
    try {
        const response = await fetch("onPageLoad.php");
        const data = await response.json(); 

        if (data[0] != 1) {
            modalContainer.style.display = "block";
            showLoginBtn();
        } else {
            showLogOutBtn();
            userNameHeading.innerText = data[1];
        }
       
    } catch (e) {
        // TODO felhantering
        //console.log(e);
        console.log("Error checking cookie");
        showLoginBtn();
    } 
}


function getCookieName() {
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

// Check cookie on load page
document.addEventListener("DOMContentLoaded", function() {
    loginErrorMessage.style.display = "none";
    checkCookie();
});
