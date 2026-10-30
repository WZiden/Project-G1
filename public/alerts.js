async function initAlertsPage() {
    fetch("get_alert.php")
        .then(response => response.json())
        .then(data => {
            updateAlertIndicator(data);
            renderAlerts(data);
        });
}
    let showAllAlerts = false;

    function renderAlerts(alerts) {

    const visibleCount = showAllAlerts ? alerts.length: Math.min(3, alerts.length);
    document.getElementById("alertsCount").textContent = `${visibleCount} of ${alerts.length} alerts`;

    lastAlerts = alerts;
    const alertsList = document.getElementById("alertsList");
    const visibleAlerts = showAllAlerts ? alerts: alerts.slice(0, 3);

    alertsList.innerHTML = "";

    visibleAlerts.forEach(alert => {

        alertsList.innerHTML += `
            <div class="timeline-item">
                <div class="alert-info">
                    <div class="alert-header">
                        <h4>${alert.title}</h4>
                    </div>
                    <div class= "alert-body">
                        <p>${alert.room}</p>
                        <p>Status: ${alert.status}</p>
                        <p>${alert.reason}</p>
                    </div>
                </div>
                <div class="alert-side">
                    <span class="alert-time">
                        ${alert.time}
                    </span>

                    <div class="alert-actions">
                    ${alert.status === 'ACTIVE' ? `
                        <button class="room-btn" onclick="alertAction(${alert.id}, 'ACKNOWLEDGE')">
                            Acknowledge
                        </button>
                        <button class="room-btn" onclick="alertAction(${alert.id}, 'DISMISS')">
                            Dismiss
                        </button>          
                        <button class="room-btn" onclick="alertAction(${alert.id}, 'RESOLVE')">
                           Resolve
                        </button>
                    ` : ''}
                    ${alert.status === 'ACKNOWLEDGED' ?`
                        <button class="room-btn" onclick="alertAction(${alert.id}, 'DISMISS')">
                            Dismiss
                        </button>
                        <button class="room-btn" onclick="alertAction(${alert.id}, 'RESOLVE')">
                            Resolve
                        </button>  
                        `
                    :''}
                    </div>
                </div>
            </div>
        `;
    });
    if (alerts.length > 3)
    {
        alertsList.innerHTML += `
            <div class="alerts-footer">
                <button
                    class="room-btn"
                    onclick="toggleAlerts()">
                    ${
                        showAllAlerts ? 'Show less': `Show more`
                    }
                </button>
            </div>
        `;
    }
}
    let lastAlerts = [];

    function toggleAlerts()
    {
        showAllAlerts = !showAllAlerts;

        renderAlerts(lastAlerts);
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

function updateAlertIndicator(alerts) {
    const indicator = document.getElementById("alertIndicator");
    const hasActiveAlert = alerts.some(alert => alert.status === "ACTIVE");
    indicator.style.display = hasActiveAlert ? "block" : "none";
    console.log(alerts.map(alert => alert.status));
    console.log(alerts.some(alert => alert.status === "ACTIVE"));
}   