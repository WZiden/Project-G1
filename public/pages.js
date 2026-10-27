const contentArea = document.getElementById("content-area");
const pageTitle = document.getElementById("page-title");
const buttons = document.querySelectorAll(".room-btn");
const pageContent = document.getElementById("page-content");



function loadPage(page, params = {}, title = page) {
    pageTitle.textContent = title;

    const searchInput = document.querySelector('.search input[type="search"]');

    if (searchInput) {
        searchInput.value = '';
    }

    const searchResults = document.getElementById('search-results');

    if (searchResults) {
        searchResults.innerHTML = '';
    }

    const query = new URLSearchParams(params);
    const queryString = query.toString();
    const url = `pages/${page}.php${queryString ? "?" + queryString : ""}`;
    fetch(url)
        .then(response => response.text())
        .then(html => {
            pageContent.innerHTML = html;

            const roomName = document.getElementById("room-name");

            if (roomName) {
                pageTitle.textContent = roomName.textContent;
            }

            if (page === "Data") {
                initHistoryPage()
            }

            if (page === "Alert") {
                initAlertsPage();
            }

            if (page === "Settings") {
                document.getElementById("settingsStatusMessage").style.backgroundColor = "transparent";
                document.getElementById("deviceSearchContainer").style.display = "none";
                initSettingsPage();
            } else {
                document.getElementById("deviceSearchContainer").style.display = "inherit";
            }
        })
        .catch(error => {
            console.error("Fel vid laddning av sida:", error);
            contentArea.innerHTML = "<p>Error loading page.</p>";
        });
}

buttons.forEach(btn => {
    btn.addEventListener("click", () => {
        const page = btn.dataset.page;

        loadPage(page);
    });
});


function selectRoom(room) {
    fetch("./pages/Room.php?room=" + encodeURIComponent(room))
        .then(response => response.text())
        .then(data => {
            document.getElementById("page-content").innerHTML = data;
        })
        .catch(error => console.error(error));
}
fetch("rooms.php")
    .then(response => response.json())
    .then(rooms => {
        const container = document.getElementById("room-buttons");

        rooms.forEach(room => {
            const button = document.createElement("button");

            button.className = "room-btn";
            button.type = "button";
            button.dataset.page = "Room";
            button.textContent = room;

            button.addEventListener("click", () => {
                loadPage("Room", { room: room }, room);
            });

            container.appendChild(button);
        });
    });
loadPage("Favorite");

/*Skapar innehållet i history sidan*/
function initHistoryPage() {
    const btnHistory = document.getElementById("btnHistory");
    const btnStats = document.getElementById("btnStats");


    if (!btnHistory || !btnStats) return;


    function renderLog() {
        document.getElementById("historyView").style.display = "block";
        document.getElementById("statsView").style.display = "none";

        const timeline = document.querySelector(".timeline");
        timeline.innerHTML = "";

        fetch("pages/gethistory.php")
            .then(res => res.json())
            .then(rows => {
                rows.forEach(row => {
                    const item = document.createElement("div");
                    item.classList.add("timeline-item");

                    item.innerHTML = `
                    <div class="timeline-time">${row.time}</div>
                    <div class ="timeline-message">
                        ${row.username} change ${row.device_name}
                        from <b>${row.oldValue}<b> to <b> ${row.newValue}</b>
                    </div>
                `;
                    timeline.appendChild(item)
                });

            });

        btnHistory.classList.add("active");
        btnStats.classList.remove("active");

    }

    function renderStats() {
        document.getElementById("historyView").style.display = "none";
        document.getElementById("statsView").style.display = "block";

        btnStats.classList.add("active");
        btnHistory.classList.remove("active");


        drawTempChart(); // ← ritar grafen
    }

    // Startläge
    renderLog();

    // Knapparna
    btnHistory.addEventListener("click", renderLog);
    btnStats.addEventListener("click", renderStats);

}

function drawTempChart() {
    const ctx = document.getElementById("tempChart");

    new Chart(ctx, {
        type: "line",
        data: {
            labels: ["08:00", "10:00", "12:00", "14:00", "17:00", "19:00"],
            datasets: [{
                label: "Temperatur (°C)",
                data: [21, 22, 23, 22, 25, 10],
                borderColor: " #76949F",
                backgroundColor: " #76949F",
                borderWidth: 2,
                tension: 0.3 // gör linjen mjuk
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        color: "grey",
                        lineWidth: 0.5
                    },
                    ticks: {
                        color: "white",
                    }
                },

                y: {
                    beginAtZero: false,
                    grid: {
                        color: "grey",
                        lineWidth: 0.5
                    },
                    ticks: {
                        color: "white"
                    }
                }
            }
        }
    });
}

/*** Settings page ***/
function initSettingsPage() {
    const changeNameForm = document.getElementById("changeNameForm");
    const changePasswordForm = document.getElementById("changePasswordForm");
    const userSettingsButton = document.getElementById("userSettings");
    const adminSettingsButton = document.getElementById("adminSettings");
    const userView = document.getElementById("userView");
    const adminView = document.getElementById("adminView");
    const pageUsername = document.getElementById("pageUsername");
    const pageAdmin = document.getElementById("pageAdmin");
    const statusMessage = document.getElementById("settingsStatusMessage");
    const newUserName = document.getElementById("newUserName");
    const newPassword = document.getElementById("newPassword");
    const confirmPassword = document.getElementById("confirmPassword");
    const errorColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--red");

    userView.style.display = "block";
    adminView.style.display = "none";
    pageAdmin.style.display = "none";

    // Settings view button
    userSettingsButton.addEventListener("click", function () {
        userView.style.display = "block";
        adminView.style.display = "none";
        pageUsername.style.display = "block";
        pageAdmin.style.display = "none";
    });

    // Admin view button
    adminSettingsButton.addEventListener("click", function () {
        userView.style.display = "none";
        adminView.style.display = "block";
        pageUsername.style.display = "none";
        pageAdmin.style.display = "block";

        // List all users and devices
        populateUserList();
        //populateDeviceList();
    });

    // Change username form button
    changeNameForm.addEventListener("submit", function (e) {
        e.preventDefault();
        sendChangeName();
        changeNameForm.reset();
        contentArea.scrollTo({ top: 0, behavior: "smooth" });
    });

    // Change password form button
    changePasswordForm.addEventListener("submit", function (e) {
        e.preventDefault();
        sendChangePassword();
        changePasswordForm.reset();
        contentArea.scrollTo({ top: 0, behavior: "smooth" });
    });

    // No special characters in usernames
    newUserName.addEventListener("input", function (e) {
        const nameRegex = /[&"'<>]/;
        if (newUserName.value.match(nameRegex)) {
            statusMessage.style.backgroundColor = errorColor;
            statusMessage.innerText = "The name must not contain any special characters";
        } else {
            statusMessage.style.backgroundColor = "transparent";
            statusMessage.innerText = "";
        }
    });

    // Check password requirements
    newPassword.addEventListener("blur", function (e) {
        if (newPassword.value.length > 0) {
            if (newPassword.value.length < 8) {
                statusMessage.style.backgroundColor = errorColor;
                statusMessage.innerText = "The password must be at least 8 characters";
            } else if (!newPassword.value.match(/[a-z]/)) {
                statusMessage.style.backgroundColor = errorColor;
                statusMessage.innerText = "The password must contain a lowercase letter";
            } else if (!newPassword.value.match(/[A-A]/)) {
                statusMessage.style.backgroundColor = errorColor;
                statusMessage.innerText = "The password must contain an uppercase letter";
            } else if (!newPassword.value.match(/[0-9]/)) {
                statusMessage.style.backgroundColor = errorColor;
                statusMessage.innerText = "The password must contain a number";
            } else {
                statusMessage.style.backgroundColor = "transparent";
                statusMessage.innerText = "";
            }
        } else {
            statusMessage.style.backgroundColor = "transparent";
            statusMessage.innerText = "";
        }
    });

    // Password confirm check
    confirmPassword.addEventListener("blur", function (e) {
        if (confirmPassword.value.length > 0) {
            if (confirmPassword.value !== newPassword.value) {
                statusMessage.style.backgroundColor = errorColor;
                statusMessage.innerText = "The passwords do not match";
            } else {
                statusMessage.style.backgroundColor = "transparent";
                statusMessage.innerText = "";
            }
        } else {
            statusMessage.style.backgroundColor = "transparent";
            statusMessage.innerText = "";
        }
    });


    getCookieUsername();
}

/*** User stuff ***/

// Extract username from cookie
function getCookieUsername() {
    if (document.cookie.search("Username=") != -1) {
        let startPosition = document.cookie.search("Username=") + 9;
        let endposition = document.cookie.indexOf(";", startPosition);
        let userName = "";
        if (endposition == -1) {
            userName = document.cookie.substring(startPosition);
        } else {
            userName = document.cookie.substring(startPosition, endposition);
        }
        document.getElementById("pageUsername").innerText = "Username: " + userName;
    }
}

async function sendChangeName() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(changeNameForm);

    const statusMessage = document.getElementById("settingsStatusMessage");
    const errorColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--red");
    const successColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--green");

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });

        if (response.status == 400) {
            statusMessage.style.backgroundColor = errorColor;
            statusMessage.innerText = "Error: faulty input";
        } else if (response.status == 500) {
            statusMessage.style.backgroundColor = errorColor;
            statusMessage.innerText = "Error: server error";
        } else {
            document.getElementById("pageUsername").innerText = "Username: " + formData.get("newUserName");
            document.getElementById("userNameHeading").innerText = formData.get("newUserName");
            statusMessage.style.backgroundColor = successColor;
            statusMessage.innerText = "Username changed";
        }

    } catch (e) {
        // TODO felhantering
        console.log("Error changing name");
        statusMessage.style.backgroundColor = errorColor;
        statusMessage.innerText = "Error trying to change name";
        //console.log(e);
    }
}

async function sendChangePassword() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(changePasswordForm);

    statusMessage = document.getElementById("settingsStatusMessage");
    const errorColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--red");
    const successColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--green");

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });

        if (response.status == 400) {
            statusMessage.style.backgroundColor = errorColor;
            statusMessage.innerText = "Error: invalid user input";
        } else {
            statusMessage.style.backgroundColor = successColor;
            statusMessage.innerText = "Password changed";
        }
    } catch (e) {
        console.log(e);
    }
}
/*** Admin stuff ***/

// Populate user list
async function populateUserList() {
    try {
        const response = await fetch("get_users_list.php");

        const data = await response.json();
        if (!response.ok) {
            throw new Error("HTTP: " + response.status);
            console.log("response inte ok");
        } else {
            console.log("response ok");
            const userList = document.getElementById("userList");
            userList.innerHTML = "";

            data.forEach(element => {
                const row = document.createElement("li");
                row.textContent = "User ID: " + element["userID"] + " Username: " + element["username"] + " Role: ";
                if (element["permission_level"] == 1) {
                    row.textContent += "Admin";
                } else {
                    row.textContent += "User";
                }
                userList.appendChild(row);
            });
        }
    } catch (e) {
        console.log(e);
    }
}

// Populate device list
async function populateDeviceList() {
    try {
        const response = await fetch("get_devices_list.php");

        const data = await response.json();
        if (!response.ok) {
            throw new Error("HTTP: " + response.status);
        } else {
            const deviceList = document.getElementById("deviceList");
            deviceList.innerHTML = "";

            data.forEach(element => {
                const row = document.createElement("li");
                row.textContent = "Device ID: " + element["device_id"] + " Device name: " + element["device_name"] + " Required level: " +  element["required_user_level"];
                deviceList.appendChild(row);
            });
        }
    } catch (e) {
        console.log(e);
    }
}
/******/