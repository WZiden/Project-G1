async function initAlertsPage() {
fetch("get_alert.php")
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
                <p>${alert.reason}</p>
                <p>Status: ${alert.status}</p>
                <small>${alert.time}</small>

                ${alert.status === 'ACTIVE' || alert.status === 'ACKNOWLEDGED' ? `
                    <button onclick="alertAction(${alert.id}, 'ACKNOWLEDGE')">
                        Acknowledge
                    </button>
                    <button onclick="alertAction(${alert.id}, 'DISMISS')">
                        Dismiss
                    </button>
                    <button onclick="alertAction(${alert.id}, 'RESOLVE')">
                        Resolve
                    </button>
                    ` 
                :''
                } 
            </div>
        `;
    });
}


async function alertAction(alertId, action) {
    const response = await fetch("alert_action.php", 
        {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
        body: new URLSearchParams({
            "alert_id": alertId,
            "action": action
        })
    });
    
    const result = await response.json();

    if (result.success) {
        initAlertsPage();
    }
    else
    {
        alert(result.message);
    }
}