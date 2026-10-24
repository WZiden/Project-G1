const contentArea = document.getElementById("content-area");
const pageTitle = document.getElementById("page-title");
const buttons = document.querySelectorAll(".room-btn");
const pageContent = document.getElementById("page-content");



function loadPage(page, params = {}, title = page) {
    pageTitle.textContent = title;
   
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
            
            if(page=== "Data"){
                initHistoryPage()
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
    const container = document.getElementById("historyStatsContent");
    const btnHistory = document.getElementById("btnHistory");
    const btnStats = document.getElementById("btnStats");


    if (!container || !btnHistory || !btnStats ) return;

    const logg = [
        { time: "08:12", message: "System startade", type: "system" },
        { time: "09:45", message: "Användare loggade in", type: "info" },
        { time: "11:03", message: "Fel: API timeout", type: "error" },
        { time: "14:22", message: "Backup klar", type: "system" }
    ];

    logg.sort((a, b) => a.time.localeCompare(b.time));

    function renderLog() {
        document.getElementById("historyView").style.display = "block";
        document.getElementById("statsView").style.display = "none";

        const timeline = document.querySelector(".timeline");
        timeline.innerHTML = "";

        logg.forEach(entry => {
            const item = document.createElement("div");
            item.classList.add("timeline-item");
            item.innerHTML = `
                <div class="timeline-time">${entry.time}</div>
                <div class="timeline-message">${entry.message}</div>
            `;
            timeline.appendChild(item);
        });

        btnHistory.classList.add("active");
        btnStats.classList.remove("active");
       
    }

    function renderStats() {
     document.getElementById("historyView").style.display="none";
     document.getElementById("statsView").style.display ="block";

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
            labels: ["08:00", "10:00", "12:00", "14:00","17:00", "19:00"],
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
                x:{
                    grid:{
                        color: "grey",
                        lineWidth:0.5
                    },
                    ticks: {
                        color: "white",
                    }
                },
    
                y: {
                    beginAtZero: false,
                    grid: {
                        color:"grey",
                        lineWidth:0.5
                    },
                    ticks:{
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

    // User pressed change username form button
    changeNameForm.addEventListener("submit", function(e) {
        e.preventDefault();
        sendChangeName();
        changeNameForm.reset();
    });

    // User pressed change password form button
    changePasswordForm.addEventListener("submit", function(e) {
        e.preventDefault();
        sendChangePassword();
        changePasswordForm.reset();
    });

    getCookieUsername();
}

// Extract username from cookie
function getCookieUsername() {
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

        const data = await response.json();
        if (data[0] != 1) {
            statusMessage.style.backgroundColor = errorColor;
            statusMessage.innerText = "Error: " + data[1];
        } else {
            document.getElementById("pageUsername").innerText = "Username: " + data[2];
            document.getElementById("userNameHeading").innerText = data[2];
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
        const data = await response.json();
        if (data[0] != 1) {
            statusMessage.style.backgroundColor = errorColor;
            statusMessage.innerText = "Error: " + data[1];
        } else {
            statusMessage.style.backgroundColor = successColor;
            statusMessage.innerText = "Password changed";
        }

    } catch (e) {
        // TODO felhantering
        console.log("Error changing name");
        statusMessage.style.backgroundColor = errorColor;
        statusMessage.innerText = "Error trying to change name";
        //console.log(e);
    }    
}

/******/