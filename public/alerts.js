async function initAlertsPage() {
fetch("alert.php")
    .then(response => response.json())
    .then(data => renderAlerts(data));
};

function renderAlerts(alerts) {

    const alertsList =
        document.getElementById("alertsList");

    alertsList.innerHTML = "";

    alerts.forEach(alert => {

        alertsList.innerHTML += `
            <div class="timeline-item">
                <h4>${alert.title}</h4>
                <p>${alert.room}</p>
                <small>${alert.time}</small>
            </div>
        `;
    });
}