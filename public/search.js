function activateSearch(targetSelector){
    const form = document.querySelector(".search");
    if(!form)return;

    form.addEventListener("submit", function(e){
        e.preventDefault();

        const text = form.querySelector("input").value.toLowerCase();
        const items = document.querySelectorAll(targetSelector);

        items.forEach(item =>{
            const content = item.innerText.toLowerCase();
            item.style.display = content.includes(text)?"":"none";

        });
    });
}