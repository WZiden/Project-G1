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

document.addEventListener('input', function(event){

    if (event.target.id !== 'deviceSearch'){
        return;
    }

    const searchText =
        event.target.value.toLowerCase().trim();

    document
        .querySelectorAll('.device-card')
        .forEach(card => {

            const text =
                card.textContent.toLowerCase();

            card.style.display =
                text.includes(searchText)
                    ? ''
                    : 'none';
        });
});

document.addEventListener('input', function(event){

    if(event.target.id === 'deviceSearch'){
        console.log(event.target.value);
    }

});

document.addEventListener('input', async function(event){

    const input =
        event.target.closest('.search input[type="search"]');

    if (!input){
        return;
    }

    const query = input.value.trim();

    const container =
        document.getElementById('search-results');

    if (!query){
        container.innerHTML = '';
        return;
    }

    const response = await fetch(
        `search.php?q=${encodeURIComponent(query)}`
    );

    const results = await response.json();

    let html = '';

    if (results.devices.length > 0){

        html += `
            <div class="search-section-title">
                Devices
            </div>
        `;

        results.devices.forEach(device => {

            html += `
                <button
                    type="button"
                    class="search-result"
                    data-type="device"
                    data-id="${device.device_id}">
                    ${device.device_name}
                </button>
            `;
        });
    }

    if (results.rooms.length > 0){

        html += `
            <div class="search-section-title">
                Rooms
            </div>
        `;

        results.rooms.forEach(room => {

            html += `
                <button
                    type="button"
                    class="search-result"
                    data-type="room"
                    data-room="${room}">
                    ${room}
                </button>
            `;
        });
    }

    container.innerHTML = html;
});

document.addEventListener('click', function(event){

    const result = event.target.closest('.search-result');

    if (!result){
        return;
    }

    const type = result.dataset.type;

    if (type === 'device') {

        const deviceName = result.textContent.trim();

        loadPage('Search', {
            query: deviceName
        });
    }
    
    if (type === 'room'){

        const room = result.dataset.room;

        loadPage('Room', { room: room }, room);
    }
});

document.addEventListener('submit', function(event){

    const form = event.target.closest('.search');

    if (!form){
        return;
    }

    event.preventDefault();

    const query = form.querySelector('input[type="search"]').value.trim();

    if (!query){
        return;
    }

    loadPage('Search', {
        query: query
    });
});

// filter history

document.addEventListener("input",function(event){
    if(event.target.id !== "deviceSearch")return;
    const searchText = event.target.value.toLowerCase().trim();

    filterItems(".timeline-item", searchText);
});

function filterItems(selector, searchText) {
    document.querySelectorAll(selector).forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display= text.includes(searchText) ? "":"none";
    });
}