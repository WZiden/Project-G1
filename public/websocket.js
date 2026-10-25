async function loadUpdates() {
    try {
        const response = await fetch('get_updates.php');

        const states = await response.json();

        updateEntities(states);

    } catch (error) {
        console.error('Failed to fetch updates', error);
    }
}

function updateEntities(states){
    Object.entries(states).forEach(([entityId, state]) =>
        {
            updateEntity(entityId,state);
        });
}

function updateInfoEntity(entityId, state){
    document.querySelectorAll(`.entity-state[data-entity-id="${entityId}"]`).forEach(element => {
        let value = state.state;
        if (state.attributes?.unit_of_measurement){
            value += ' ' + state.attributes.unit_of_measurement;
        }

        element.textContent = value;

    });

}

function updateEntity(entityId, state){

    updateInfoEntity(entityId, state);
    updateToggle(entityId, state);
    updateLock(entityId, state);
    updateClimate(entityId, state);
    updateCover(entityId, state);
    updateStatusText(entityId, state);

}

function updateToggle(entityId, state){

    const button = document.querySelector(
        `.toggle-button[data-entity-id="${entityId}"]`
    );

    if (!button){
        return;
    }

    button.dataset.state =
        state.state === 'on'
            ? 'on'
            : 'off';

    const serviceInput =
        button
            .closest('form')
            ?.querySelector('[name="service"]');

    if (serviceInput){

        serviceInput.value =
            state.state === 'on'
                ? 'turn_off'
                : 'turn_on';

    }
}

function updateLock(entityId, state){

    const button = document.querySelector(
        `.lock-button[data-entity-id="${entityId}"]`
    );

    if (!button){
        return;
    }

    button.dataset.locked =
        state.state === 'locked'
            ? 'true'
            : 'false';

}

function updateClimate(entityId, state){

    const slider = document.querySelector(`.climate-slider[data-entity-id="${entityId}"]`);

    const targetLabel = document.querySelector(`.climate-target-temp[data-entity-id="${entityId}"]`);

    const currentLabel = document.querySelector(`.climate-current-temp[data-entity-id="${entityId}"]`);

    const unit = state.attributes?.unit_of_measurement ?? '°C';

    if (slider && state.attributes?.temperature !== undefined){
        slider.value = state.attributes.temperature;
    }

    if (targetLabel && state.attributes?.temperature !== undefined){
        targetLabel.textContent = state.attributes.temperature + unit;
    }

    if (currentLabel && state.attributes?.current_temperature !== undefined){
        currentLabel.textContent = 'Current Temperature: ' + state.attributes.current_temperature + unit;
    }
}

function updateCover(entityId, state){

    const slider = document.querySelector(
        `.cover-slider[data-entity-id="${entityId}"]`
    );

    const label = document.querySelector(
        `.cover-position-label[data-entity-id="${entityId}"]`
    );

    const position =
        state.attributes?.current_position;

    if (position === undefined){
        return;
    }

    if (slider){
        slider.value = position;
    }

    if (label){
        label.textContent = position + '%';
    }
}

function updateCover(entityId, state){

    const slider = document.querySelector(`.cover-slider[data-entity-id="${entityId}"]`);

    const label = document.querySelector( `.cover-position-label[data-entity-id="${entityId}"]`);

    const position = state.attributes?.current_position;

    if (position === undefined){
        return;
    }

    if (slider){
        slider.value = position;
    }

    if (label){
        label.textContent = position + '%';
    }
}

function updateStatusText(entityId, state){
    document.querySelectorAll(`.entity-status[data-entity-id="${entityId}"]`).forEach(element => {
        const value = state.state.charAt(0).toUpperCase() + state.state.slice(1);
        
        element.textContent = value;

    });

}

setInterval(() => {
    document.querySelectorAll('.camera-feed').forEach(camera => {
        const baseSrc = camera.src.split('&t=')[0];
        camera.src = `${baseSrc}&t=${Date.now()}`;
    });
}, 1000);

setInterval(loadUpdates, 2000);