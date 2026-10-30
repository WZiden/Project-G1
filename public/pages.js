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
        const searchInput= document.getElementById("deviceSearch");
        const container = document.getElementById("statsView");
        if(searchInput){
            searchInput.placeholder = "Filter Statistics";
        }
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
    const userTable = document.getElementById("userTableBody");
    const deviceTable = document.getElementById("deviceTableBody");

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

    userTable.addEventListener("change", function(e) {
        changeLevel("User", e.target.id, e.target.value);
    });

    deviceTable.addEventListener("change", function(e) {
        changeLevel("Device", e.target.id, e.target.value);
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
        const userList = await fetch("get_users_list.php");

        const data = await userList.json();
        if (!userList.ok) {
            throw new Error("HTTP: " + response.status);
            console.log("userList inte ok");
        } else {

            const userTable = document.getElementById("userTableBody");
            data.forEach(element => {
                const newRow = userTable.insertRow();
                const cellID = newRow.insertCell(0);
                const cellName = newRow.insertCell(1);
                const cellLevel = newRow.insertCell(2);
                const select = document.createElement("select");
                select.id = element["userID"];
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
                cellID.innerText = element["userID"];
                cellName.innerText = element["username"];
                cellLevel.appendChild(select);
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

            const deviceTable = document.getElementById("deviceTableBody");
            data.forEach(element => {
                const newRow = deviceTable.insertRow();
                const cellID = newRow.insertCell(0);
                const cellName = newRow.insertCell(1);
                const cellLevel = newRow.insertCell(2);
                const select = document.createElement("select");
                select.id = element["device_id"];
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
                cellID.innerText = element["device_id"];
                cellName.innerText = element["device_name"];
                cellLevel.appendChild(select);
            });
        }
    } catch (e) {
        console.log(e);
    }
}

async function changeLevel(type, typeID, newLevel) {    
    const response = await fetch("change_level.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({type: type, typeID: typeID, newLevel: newLevel}),
    });

    if (!response.ok) {
        console.log("Error changing level");
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