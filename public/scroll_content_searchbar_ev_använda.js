
function loadPage(page) {
    fetch(`pages/${page}.php`)
        .then(res=> res.text())
        .then(html =>{
            contentarea.innerHTML=html;
            const scrollbar= document.querySelector(".scrollbar");
            const contentarea= document.querySelector("#content-area");
            searchContainer.innerHTML="";
            if(hasScroll(scrollbar)){
                console.log("scroll finns i scrollbar")
                addSearchBar();
            }
            if(hasScroll(contentarea)){
                console.log("scroll finns i cottentarea")
                addSearchBar();
            }
        });
}
function hasNoScroll(element){
    return element.scrollHeight < element.clientHeight;

}




