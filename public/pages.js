const contentArea = document.getElementById("content-area");
const pageTitle = document.getElementById("page-title");
const searchContainer = document.getElementById("search-container");
const buttons = document.querySelectorAll(".room-btn");
const pageContent = document.getElementById("page-content");

const pagesWithSearch = ["Devices", "Favorite", "Room"];
function addSearchBar() {

    searchContainer.innerHTML = `
    <form class="search">
        <input type="search" placeholder="Search">
        <button type="submit" class="search-btn">Search</button>
    </form>
    `;
}

function loadPage(page, params = {}) {
    pageTitle.textContent = page;
    searchContainer.innerHTML = "";

    const query = new URLSearchParams(params);
    const queryString = query.toString();
    const url = `pages/${page}.php${queryString ? "?" + queryString : ""}`;

    fetch(url)
        .then(response => response.text())
        .then(html => {
            pageContent.innerHTML = html;
            pageTitle.textContent = page;

            if (pagesWithSearch.includes(page)) {
                addSearchBar();
            }
            if (page === "Profile") {
                //document.getElementById("pageUsername").innerText = "";
                //document.getElementById("profileError").style.backgroundColor = "transparent";
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
loadPage("Favorite");

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
                loadPage("Room", { room: room });
            });

            container.appendChild(button);
        });
    });

