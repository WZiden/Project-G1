const contentArea = document.getElementById("content-area");
const pageTitle = document.getElementById("page-title");
const buttons = document.querySelectorAll(".room-btn");
const pageContent = document.getElementById("page-content");

let sensorOptions = {};



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
            
            if(page=== "History"){
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
        const searchInput = document.getElementById("deviceSearch");
        const timeline = document.querySelector(".timeline");

        if(searchInput){
            searchInput.placeholder = "Filter User log";
        }
        timeline.innerHTML = "";

        fetch("pages/gethistory.php")
            .then(res => res.json())
            .then(rows => {
                rows.forEach(row => {
                    const item = document.createElement("div");
                    item.classList.add("timeline-item");

                    item.innerHTML = `
                    <div class="timeline-time">${row.time}</div>
                    <div class ="timeline-message">${row.message}</div>
                `;
                    timeline.appendChild(item)
                });

            });

        btnHistory.classList.add("active");
        btnStats.classList.remove("active");

    }

    async function renderStats() {
        document.getElementById("historyView").style.display="none";
        document.getElementById("statsView").style.display ="block";
        const timeline = document.querySelector(".timeline");
       document.getElementById("deviceSearch").style.display ="none";
        const container = document.getElementById("statsView");
      
        btnStats.classList.add("active");
        btnHistory.classList.remove("active");

        if (!container.dataset.loaded) {
            const html = await fetch("pages/Statistics_View.php").then(res => res.text());

            container.innerHTML = html;

            container.dataset.loaded = "true";

            initStatistics(); // ← ritar grafen
        }; 
    }

    // Startläge
    renderLog();

    // Knapparna
    btnHistory.addEventListener("click", renderLog);
    btnStats.addEventListener("click", renderStats);

}



/*** Settings page ***/

function showErrorMessage (message) {
    const statusMessage = document.getElementById("settingsStatusMessage");
    const errorColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--red");
    statusMessage.style.backgroundColor = errorColor;
    statusMessage.innerText = message;
}

function showSuccessMessage (message) {
    const statusMessage = document.getElementById("settingsStatusMessage");
    const successColor = getComputedStyle(document.querySelector(":root")).getPropertyValue("--green");
    statusMessage.style.backgroundColor = successColor;
    statusMessage.innerText = message;
}

function clearErrorMessage (message) {
    const statusMessage = document.getElementById("settingsStatusMessage");
    statusMessage.style.backgroundColor = "transparent";
    statusMessage.innerText = "";
}

function initSettingsPage() {
    const changeNameForm = document.getElementById("changeNameForm");
    const changePasswordForm = document.getElementById("changePasswordForm");
    const userSettingsButton = document.getElementById("userSettings");
    const adminSettingsButton = document.getElementById("adminSettings");
    const userView = document.getElementById("userView");
    const adminView = document.getElementById("adminView");
    const pageUsername = document.getElementById("pageUsername");
    const pageAdmin = document.getElementById("pageAdmin");
    const newUserName = document.getElementById("newUserName");
    const newPassword = document.getElementById("newPassword");
    const confirmPassword = document.getElementById("confirmPassword");
    const userCaption = document.getElementById("userCaption");
    const deviceCaption = document.getElementById("deviceCaption");
   
    userSettingsButton.classList.add("active")
    adminSettingsButton.classList.remove("active")
    userView.style.display = "block";
    adminView.style.display = "none";
    pageAdmin.style.display = "none";
    document.getElementById("userTableMaxi").style.display = "none";
    document.getElementById("deviceTableMaxi").style.display = "none";

    if (!checkAdmin()) {
        adminSettingsButton.style.display = "none";
    }

    // Settings view button
    userSettingsButton.addEventListener("click", function () {
        userSettingsButton.classList.add("active")
        adminSettingsButton.classList.remove("active")
        userView.style.display = "block";
        adminView.style.display = "none";
        pageUsername.style.display = "block";
        pageAdmin.style.display = "none";
       
    });

    // Admin view button
    adminSettingsButton.addEventListener("click", function () {
        adminSettingsButton.classList.add("active")
        userSettingsButton.classList.remove("active")
        userView.style.display = "none";
        adminView.style.display = "block";
        pageUsername.style.display = "none";
        pageAdmin.style.display = "block";
     
        // List all users and devices
        populateUserList();
        populateDeviceList();
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
            showErrorMessage("The name must not contain any special characters");
        } else {
            clearErrorMessage();
        }
    });

    // Check password requirements
    newPassword.addEventListener("blur", function (e) {
        if (newPassword.value.length > 0) {
            if (newPassword.value.length < 8) {
                showErrorMessage("The password must be at least 8 characters");
            } else if (!newPassword.value.match(/[a-z]/)) {
                showErrorMessage("The password must contain a lowercase letter");
            } else if (!newPassword.value.match(/[A-Z]/)) {
                showErrorMessage("The password must contain an uppercase letter");
            } else if (!newPassword.value.match(/[0-9]/)) {
                showErrorMessage("The password must contain a number");
            } else {
                clearErrorMessage();
            }
        } else {
            clearErrorMessage();
        }
    });

    // Password confirm check
    confirmPassword.addEventListener("blur", function (e) {
        if (confirmPassword.value.length > 0) {
            if (confirmPassword.value !== newPassword.value) {
                showErrorMessage("The passwords do not match");
            } else {
                clearErrorMessage();
            }
        } else {
            clearErrorMessage();
        }
    });

    clearSelectListener = function(e) {
        clearErrorMessage();
    };

    selectListener = function(e) {
        clearErrorMessage();
        const confirm = document.getElementById("confirm:" + e.target.id.substring(7));
        confirm.value = e.target.value;
        // TODO markera knappen på något sätt
    };

    deleteListener = function(e) {
        clearErrorMessage();
        if (confirm("Do you wish to delete user " + e.target.value + " (ID: " + e.target.id.substring(7) + ")?")) {
            deleteUser(e.target.id.substring(7));
        }

    };
    
    confirmUserListener = function(e) {
        clearErrorMessage();
        if (e.target.value) {
            changeLevel("User", e.target.id.substring(8), e.target.value);
            e.target.value = "";
        }
    };

    confirmDeviceListener = function(e) {
        clearErrorMessage();
        if (e.target.value) {
            changeLevel("Device", e.target.id.substring(8), e.target.value);
            e.target.value = "";
        }
    };



    userCaption.addEventListener("click", function (e) {
        userCaptionFunction();
    });

    userCaption.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            userCaptionFunction();
        }
    });

    deviceCaption.addEventListener("click", function (e) {
        deviceCaptionFunction();
    });

    deviceCaption.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            deviceCaptionFunction();
        }
    });

    getCookieUsername();
}

/*** User stuff ***/

// Extract admin from cookie
function checkAdmin() {
    if (document.cookie.search("Admin=") != -1) {
        let startPosition = document.cookie.search("Admin=") + 6;
        let endposition = document.cookie.indexOf(";", startPosition);
        let userName = "";
        if (endposition == -1) {
            userName = document.cookie.substring(startPosition);
        } else {
            userName = document.cookie.substring(startPosition, endposition);
        }
        if (userName == "yes") {
            return true;
        }        
    }
    return false;
}

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

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });

        if (response.status == 400) {
            showErrorMessage("Error: faulty input");
        } else if (response.status == 500) {
            showErrorMessage("Error: server error");
        } else {
            document.getElementById("pageUsername").innerText = "Username: " + formData.get("newUserName");
            document.getElementById("userNameHeading").innerText = formData.get("newUserName");
            showSuccessMessage("Username changed");
        }

    } catch (e) {
        showErrorMessage("Error trying to change name");
    }
}

async function sendChangePassword() {
    // Assiciate the FormData object with the login form element
    const formData = new FormData(changePasswordForm);

    try {
        // Send the FormData as an HTTP POST request using fetch()
        const response = await fetch("user_login_handler.php", {
            method: "POST",
            body: formData,
        });

        if (response.status == 400) {
            showErrorMessage("Error: invalid user input");
        } else {
            clearErrorMessage();
        }
    } catch (e) {
        showErrorMessage("Error trying to change password");
    }
}
/*** Admin stuff ***/

// Populate user list
async function populateUserList() {
    try {
        const userList = await fetch("get_users_list.php");

        const data = await userList.json();
        if (!userList.ok) {
            throw new Error("HTTP: " + response.status);
        } else {

            const userTable = document.getElementById("userTableBody");
            userTable.innerHTML = "";
            data.forEach(element => {
                // Row
                const newRow = userTable.insertRow();
                const cellName = newRow.insertCell(0);
                const cellID = newRow.insertCell(1);
                const cellLevel = newRow.insertCell(2);
                const cellDelete = newRow.insertCell(3);
                const cellConfirm = newRow.insertCell(4);

                // Select
                const select = document.createElement("select");
                select.id = "select:" + element["userID"];
                const options = ["Admin", "User"];
                options.forEach((option) => {
                    const selectOptions = document.createElement("option");
                    selectOptions.value = option.toLowerCase();
                    selectOptions.textContent = option;
                    select.appendChild(selectOptions);
                    if (element["permission_level"] == 1) {
                        select.selectedIndex = 0;
                    } else if (element["permission_level"] == 2) {
                        select.selectedIndex = 1;
                    }
                })

                // Buttons
                const deleteButton = document.createElement("button");
                deleteButton.id = "delete:" + element["userID"];
                deleteButton.value = element["username"];
                deleteButton.textContent = "Delete user";
                deleteButton.className = "deletebutton";
                const confirmUserButton = document.createElement("button");
                confirmUserButton.id = "confirm:" + element["userID"];
                confirmUserButton.textContent = "Confirm changes";
                confirmUserButton.className = "confirmbutton";
                
                // Listeners
                select.addEventListener("click", clearSelectListener);
                select.addEventListener("change", selectListener);
                deleteButton.addEventListener("click", deleteListener);
                confirmUserButton.addEventListener("click", confirmUserListener);

                // Populate row
                cellName.innerText = element["username"];
                cellID.innerText = element["userID"];
                cellLevel.appendChild(select);
                cellDelete.appendChild(deleteButton);
                cellConfirm.appendChild(confirmUserButton);
            });
        }
    } catch (e) {
        console.log("Error populating user list");
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

            const deviceTable = document.getElementById("deviceTableBody");
            deviceTable.innerHTML = "";
            data.forEach(element => {
                // Row
                const newRow = deviceTable.insertRow();
                const cellName = newRow.insertCell(0);
                const cellID = newRow.insertCell(1);
                const cellLevel = newRow.insertCell(2);
                const cellConfirm = newRow.insertCell(3);

                // Select
                const select = document.createElement("select");
                select.id = "select:" + element["device_id"];
                const options = ["Admin", "User"];
                options.forEach((option) => {
                    const selectOptions = document.createElement("option");
                    selectOptions.value = option.toLowerCase();
                    selectOptions.textContent = option;
                    select.appendChild(selectOptions);
                    if (element["required_user_level"] == 1) {
                        select.selectedIndex = 0;
                    } else if (element["required_user_level"] == 2) {
                        select.selectedIndex = 1;
                    }
                })

                // Button
                const confirmDeviceButton = document.createElement("button");
                confirmDeviceButton.id = "confirm:" + element["device_id"];
                confirmDeviceButton.textContent = "Confirm changes";
                confirmDeviceButton.className = "confirmbutton";
                
                // Listeners
                select.addEventListener("click", clearSelectListener);
                select.addEventListener("change", selectListener);
                confirmDeviceButton.addEventListener("click", confirmDeviceListener);

                // Populate row
                cellName.innerText = element["device_name"];
                cellID.innerText = element["device_id"];
                cellLevel.appendChild(select);
                cellConfirm.appendChild(confirmDeviceButton);
            });
        }
    } catch (e) {
        console.log("Error populating device list");
    }
}


async function markConfirmButton(typeID) {    

}

async function changeLevel(type, typeID, newLevel) {    
    const response = await fetch("change_level.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({type: type, typeID: typeID, newLevel: newLevel}),
    });

    if (!response.ok && type == "User") {
        populateUserList();
        showErrorMessage("Error changing role");
    } else if (!response.ok && type == "Device") {
        populateDeviceList();
        showErrorMessage("Error changing requred user level");
    } else if (type == "User") {
        populateUserList();
        showSuccessMessage("Role changed");
    } else if (type == "Device") {
        populateDeviceList();
        showSuccessMessage("Required user level changed");
    }
}

async function deleteUser(userID) {    
    const response = await fetch("change_level.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({type: "Delete", typeID: userID}),
    });

    if (!response.ok) {
        showErrorMessage("Error deleting user");
    } else {
        populateUserList();
        showSuccessMessage("User deleted");
    }
}

// Expands/minimize User table
function userCaptionFunction () {
    const table = document.getElementById("userTableBody");
    if (table.style.display == "") {
        table.style.display = "none";
        document.getElementById("userTableMini").style.display = "none";
        document.getElementById("userTableMaxi").style.display = "inline";
    } else {
        table.style.display = "";
        document.getElementById("userTableMini").style.display = "inline";
        document.getElementById("userTableMaxi").style.display = "none";
    }
}

// Expands/minimize Device table
function deviceCaptionFunction () {
    const table = document.getElementById("deviceTableBody");
    if (table.style.display == "") {
        table.style.display = "none";
        document.getElementById("deviceTableMini").style.display = "none";
        document.getElementById("deviceTableMaxi").style.display = "inline";
    } else {
        table.style.display = "";
        document.getElementById("deviceTableMini").style.display = "inline";
        document.getElementById("deviceTableMaxi").style.display = "none";
    }
}
/******/

//Statistik Funktioner

let statsChart = null;

function initStatistics(){

    document.getElementById('statsType').addEventListener('change', handleFilterChange);

    document.getElementById('statsRoom').addEventListener('change', handleFilterChange);

    document.getElementById('statsPeriod').addEventListener('change', loadStatistics);

    const statsData = document.getElementById('statsData');
    sensorOptions = JSON.parse(statsData.dataset.options);

    updateRoomList();

    loadStatistics();
}

function updateRoomList() {

    const type = document.getElementById("statsType").value;

    const select = document.getElementById("statsRoom");

    const currentRoom = select.value;

    select.innerHTML =
        '<option value="">All Areas</option>';

    if (!sensorOptions[type]) {
        return;
    }

    const rooms = [
        ...new Set(sensorOptions[type].map(sensor => sensor.area_name).filter(Boolean))
    ].sort();

    rooms.forEach(room => {

        const option =
            document.createElement("option");

        option.value = room;
        option.textContent = room;

        select.appendChild(option);
    });

    if (rooms.includes(currentRoom)) {
        select.value = currentRoom;
    } else {
        select.value = "";
    }
}

function handleFilterChange(){

    updateRoomList();

    loadStatistics();
}

async function loadStatistics(){

    const type = document.getElementById('statsType').value;

    const room = document.getElementById('statsRoom').value;

    const period = document.getElementById('statsPeriod').value;

    const params = new URLSearchParams();

    params.append('class', type);
    params.append('start', period);

    if(room){
        params.append('area', room);
    }

    try{
        const response = await fetch(`statistics.php?${params}`);

        const data = await response.json();

        const units = [...new Set(data.datasets.map(ds => ds.unit))];
        
        document.getElementById("statsTitle").textContent = units.length === 1 ? `Temperature (${units[0]})` : 'Statistics';

        renderChart(data);

    }catch(error){
        console.error('Failed to load statistics:', error);
    }
}

function renderChart(data){
    
    const ctx = document.getElementById('statsChart');

    if(statsChart){
        statsChart.destroy();
    }

    const unit = data.datasets[0]?.unit ?? "";

    const colors = [
        "#4CAF50",
        "#2196F3",
        "#FF9800",
        "#E91E63",
        "#9C27B0",
        "#00BCD4",
        "#FFC107",
        "#FF5722",
        "#8BC34A",
        "#3F51B5",
        "#F44336",
        "#009688",
        "#CDDC39",
        "#673AB7",
        "#795548",
        "#607D8B",
        "#FF4081",
        "#7C4DFF",
        "#26A69A",
        "#FFA726" 
    ];

    const datasets = data.datasets.map((dataset, index) => ({
        ...dataset,
        borderColor: colors[index % colors.length],
        backgroundColor: colors[index % colors.length],
        borderWidth: 2,
        tension: 0.3
    }));


    statsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                    mode: "index",
                    intersect: false
                },
                plugins: {
                    legend: {
                        labels: {
                            color: "white"
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const unit = context.dataset.unit || "";

                                return `${context.dataset.label}: ${context.parsed.y} ${unit}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: "grey",
                            lineWidth: 0.5
                        },
                        ticks: {
                            color: "white"
                        }
                    },
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: "grey",
                            lineWidth: 0.5
                        },
                        ticks: {
                            color: "white",
                            callback: function(value) {
                                return `${value} ${unit}`;
                            }
                        }
                    }
                }
            }
        });


}