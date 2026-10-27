async function callDeviceService(device, entityId, service, serviceData = {}){
    const formData = new FormData();

    formData.append('entity_id', entityId);

    formData.append('service', service);

    formData.append('service_data', JSON.stringify(serviceData));

    formData.append('device', device);

    const response = await fetch('device_control.php',
        {
            method: 'POST',
            body: formData
        });

    const result = await response.json();

    if(!response.ok || !result.success){
        if(response.status === 403){
            alert(result.message);
        }

        console.log(
{
    status: response.status,
    result: result
});

        throw new Error(result.message || 'Something went wrong');
    }

    return result;
}

document.addEventListener('submit', async function(event){
    const form = event.target;

    if (!form.matches('.device-control-form'))    {
        return;
    }

    event.preventDefault();
    const entityId = form.querySelector('[name="entity_id"]').value;
    const service = form.querySelector('[name="service"]').value;
    const device = form.dataset.deviceName;

    try{
        const result = await callDeviceService(device, entityId, service);
        console.log(result);
        if (!result.success){
            return;
        }

        const serviceInput = form.querySelector('[name="service"]');
        const button = form.querySelector('.toggle-button');

        if (button){
            if (service === 'turn_on'){
                button.dataset.state = 'on';
                serviceInput.value = 'turn_off';
            }
            else if (service === 'turn_off'){
                button.dataset.state = 'off';
                serviceInput.value = 'turn_on';
            }
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

    const controls = slider.closest('.climate-controls');
    const device = controls.dataset.deviceName;

    try{
        const result = await callDeviceService(device, slider.dataset.entity, 'set_temperature',
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

document.addEventListener('change', async function(event) {
    const slider = event.target;

    if (!slider.matches('.cover-slider')) {
        return;
    }

    const controls = slider.closest('.cover-controls');

    const device = controls.dataset.deviceName;

    try{
        const result = await callDeviceService(device, slider.dataset.entity, 'set_cover_position',
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
    const button = event.target.closest('.cover-stop-button');


    if (!button){
        return;
    }

    const controls = button.closest('.cover-controls');
    const device = controls.dataset.deviceName;

    try{
        const result = await callDeviceService(device, button.dataset.entity, 'stop_cover');

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
    const device = form.dataset.deviceName;


    const wasLocked = button.dataset.locked === 'true';
    const service = serviceInput.value;

    button.dataset.loading = 'true';

    try {
        await callDeviceService(device, entity, service);

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

document.addEventListener('change', async function(event){
    const picker = event.target;

    if (!picker.matches('.rgb-picker')){
        return;
    }

    const controls = picker.closest('.rgb-controls');
    const device = controls.dataset.deviceName;

    const hex = picker.value;

    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);

    try{
        const result = await callDeviceService(device, picker.dataset.entityId, 'turn_on', {
                rgb_color: [r, g, b]
            }
        );

        console.log(result);
    }
    catch(error){
        console.error(error);
    }
});

document.addEventListener('input', function(event){
    const slider = event.target;

    if (!slider.matches('.brightness-slider')){
        return;
    }

    const label = document.querySelector(`.brightness-value[data-entity-id="${slider.dataset.entityId}"]`);

    if (label){
        label.textContent = slider.value;
    }
});

document.addEventListener('change', async function(event){
    const slider = event.target;

    if (!slider.matches('.brightness-slider')){
        return;
    }

    const controls = slider.closest('.rgb-controls');
    const device = controls.dataset.deviceName;

    try{
        const result = await callDeviceService(device, slider.dataset.entityId, 'turn_on', {
                brightness: Number(slider.value)
            }
        );

        console.log(result);
    }
    catch(error){
        console.error(error);
    }
});

document.addEventListener('submit', async function(event){
    const form = event.target;

    if (!form.matches('.select-control-form')){
        return;
    }

    event.preventDefault();

    const entityId = form.querySelector('[name="entity_id"]').value;
    const option = form.querySelector('[name="option"]').value;
    const device = form.dataset.deviceName;

    try{
        const result = await callDeviceService(device, entityId, 'select_option', {
                option: option
            }
        );

        console.log(result);
    }
    catch(error){
        console.error(error);
    }
});