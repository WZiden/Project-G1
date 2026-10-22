async function callDeviceService(entityId, service, serviceData = {}){
    const formData = new FormData();

    formData.append('entity_id', entityId);

    formData.append('service', service);

    formData.append('service_data', JSON.stringify(serviceData));

    const response = await fetch('device_control.php',
        {
            method: 'POST',
            body: formData
        });

    return await response.json();
}

document.addEventListener('submit', async function(event){
    const form = event.target;

    if (!form.matches('.device-control-form'))    {
        return;
    }

    event.preventDefault();

    const entityId = form.querySelector('[name="entity_id"]').value;

    const service = form.querySelector('[name="service"]').value;

    try{
        const result = await callDeviceService(entityId, service);

        console.log(result);

        if (!result.success){
            return;
        }

        const statusElement = document.querySelector(`.device-status-value[data-entity="${entityId}"]`);

        const button = form.querySelector('.device-button');

        const serviceInput = form.querySelector('[name="service"]');

        if (service === 'turn_on'){
            if (statusElement){
                statusElement.textContent = 'On';
            }

            button.textContent = 'TURN OFF';

            button.classList.remove('on');
            button.classList.add('off');

            serviceInput.value = 'turn_off';
        }
        else if (service === 'turn_off'){
            if (statusElement){
                statusElement.textContent = 'Off';
            }

            button.textContent = 'TURN ON';

            button.classList.remove('off');
            button.classList.add('on');

            serviceInput.value = 'turn_on';
        }
    }
    catch(error){
        console.error(error);
    }
});

document.addEventListener('input', function(event){
    const slider = event.target;

    if (!slider.matches('.climate-slider')){
        return;
    }

    const entityId = slider.dataset.entity;

    const tempLabel = document.querySelector(`.climate-target-temp[data-entity="${entityId}"]`);

    if (tempLabel){
        tempLabel.textContent = slider.value + '°C';
    }
});

document.addEventListener('change', async function(event){
    const slider = event.target;

    if (!slider.matches('.climate-slider')){
        return;
    }

    try{
        const result = await callDeviceService(slider.dataset.entity, 'set_temperature',
                {
                    temperature: Number(slider.value)
                });

        console.log(result);
    }
    catch(error){
        console.error(error);
    }
});

document.addEventListener('input', function(event){
    const slider = event.target;

    if (!slider.matches('.cover-slider')){
        return;
    }

    const entityId = slider.dataset.entity;

    const label = document.querySelector(`.cover-position-label[data-entity="${entityId}"]`);

    if (label){
        label.textContent = slider.value + '%';
    }
});

document.addEventListener('change', async function(event){
    const slider = event.target;

    if (!slider.matches('.cover-slider')){
        return;
    }

    try{
        const result = await callDeviceService(slider.dataset.entity, 'set_cover_position',
            {
                position: Number(slider.value)
            });

        console.log(result);
    }
    catch(error){
        console.error(error);
    }
});

document.addEventListener('click', async function(event){
    const button = event.target;

    if (!button.matches('.cover-stop-button')){
        return;
    }

    try{
        const result = await callDeviceService(button.dataset.entity, 'stop_cover');

        console.log(result);
    }
    catch(error){
        console.error(error);
    }
});

document.addEventListener('click', async function(event) {
    const button = event.target.closest('.lock-button');

    if (!button) {
        return;
    }

    event.preventDefault();

    if (button.dataset.loading === 'true') {
        return;
    }

    const form = button.closest('form');
    const entity = form.querySelector('[name="entity_id"]').value;
    const serviceInput = form.querySelector('[name="service"]');

    const wasLocked = button.dataset.locked === 'true';
    const service = serviceInput.value;

    button.dataset.loading = 'true';

    try {
        await callDeviceService(entity, service);

        // Uppdatera tillståndet först när anropet lyckats
        button.dataset.locked = String(!wasLocked);

        // Förbered rätt tjänst för nästa klick
        serviceInput.value = wasLocked ? 'lock' : 'unlock';
    } catch (error) {
        console.error(error);
    } finally {
        button.dataset.loading = 'false';
    }
});