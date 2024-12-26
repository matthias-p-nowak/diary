function getLocation() {
    const options = {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 0,
    };
    return new Promise((resolve, reject) => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    resolve({
                        coords:position.coords
                    });
                },
                error => {
                    reject(new Error('Error occurred: ' + error.message));
                },
                options
            );
        } else {
            reject(new Error('Geolocation is not supported by this browser.'));
        }
    });
}

async function register_event(event) {
    let form = event.target.form ?? event.target.closest('form');
    let formData = new FormData(form);
    if (event.target.hasAttribute('id')) {
        formData.append('id', event.target.getAttribute('id'));
    } else {
        return;
    }
    event.target.classList.add('requested');
    event.target.classList.remove('failed');
    try{
        const location = await getLocation();
        formData.append('latitude', location.coords.latitude);
        formData.append('longitude', location.coords.longitude);
    }catch(error){
        formData.append('error',error);
    }
    let action = form.action;
    hxl_send_form(action, formData, event.target);
}