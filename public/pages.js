const contentArea = document.getElementById("content-area");
const pageTitle = document.getElementById("page-title");
const searchContainer= document.getElementById("search-container");
const buttons = document.querySelectorAll(".room-btn");
const pageContent = document.getElementById("page-content");

const pagesWithSearch = ["Devices","Favorite","Room"];
function addSearchBar(){
   
    searchContainer.innerHTML= `
    <form class="search">
        <input type="search" placeholder="Search">
        <button type="submit" class="search-btn">Search</button>
    </form>
    `;
}

function loadPage(page){
    pageTitle.textContent = page;
    searchContainer.innerHTML ="";

    fetch(`pages/${page}.php`)
        .then(response => response.text())
        .then(html =>{
            pageContent.innerHTML = html;
            pageTitle.textContent = page;
            if(pagesWithSearch.includes(page)){
            addSearchBar();
            }
        })
        .catch(()=>{
            contentArea.innerHTML="<p>Error loading page.</p>"
        });
   
}

buttons.forEach(btn => {
    btn.addEventListener("click",() => {
        const page = btn.dataset.page;
       
        loadPage(page);
    });
});
loadPage("Favorite");



