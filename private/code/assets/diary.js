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

function more_callback(entries, observer){
    console.log('calling for more');
    let entry=entries[0];
    if(! entry.isIntersecting)
        return;
    observer.disconnect();
    let target=entry.target;
    let action=target.getAttribute('action');
    console.log('loading more...');
    let formData=new FormData();
    hxl_send_form(action,formData,target);
}

function watch4more(){
    const sentinel= document.getElementById('sentinel');
    if(sentinel != null){
        const observer = new IntersectionObserver(more_callback);
        observer.observe(sentinel);
    }else{
        console.log('no sentinel found');
    }
}
