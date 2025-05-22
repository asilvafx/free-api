const token = document.querySelector('#token');

const renameKeyForm = document.getElementById('renameKeyForm');
const deleteKeyForm = document.getElementById('deleteKeyForm');
const newDomainForm = document.getElementById('newDomainForm');
const enableApi = document.getElementById('enableApi');
const keySlug = document.getElementById('keySlug');
const removeDomainBtns = document.querySelectorAll('[data-remove-domain]');

function copyKey(){
    const keyInput = document.getElementById("key");

    // Select the text field
    keyInput.select();
    keyInput.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text inside the text field
    navigator.clipboard.writeText(keyInput.value);

    // Alert the copied text
    showAlert("success", "API Key was succefully copied to the clipbard.");
}

function showKey($display){
    const showKeyBtn = document.getElementById('showKey');
    const hideKeyBtn = document.getElementById('hideKey');
    const short_key = document.getElementById('short_key');
    const full_key = document.getElementById('full_key');

    if($display){
        showKeyBtn.classList.add('hidden');
        short_key.classList.add('hidden');
        hideKeyBtn.classList.remove('hidden');
        full_key.classList.remove('hidden');
    } else {
        hideKeyBtn.classList.add('hidden');
        full_key.classList.add('hidden');
        showKeyBtn.classList.remove('hidden');
        short_key.classList.remove('hidden');
    }
}

if(enableApi){
    enableApi.addEventListener('change', function(e){
        let apiName = document.getElementById('apiName');

        const schema = {};
        schema['slug'] = keySlug.value;
        schema['status'] = e.target.checked?1:0;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "api/keys/edit?status";
        fetchRequest(payload, uri_request, null, false);

    })
}

if(renameKeyForm){
    renameKeyForm.addEventListener('submit', function(e){
        e.preventDefault();

        let apiName = document.getElementById('apiName');

        const schema = {};
        schema['slug'] = keySlug.value;
        schema['name'] = apiName.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "api/keys/edit?rename";
        fetchRequest(payload, uri_request, renameKeyForm, false);
    })
}
function updateKeyName($name){
    const renameExit = document.getElementById('renameExit');
    const apiKeyName = document.getElementById('apiKeyName');

    apiKeyName.innerText = $name;
    renameExit.click();
}

if(deleteKeyForm){
    deleteKeyForm.addEventListener('submit', function(e){
        e.preventDefault();

        const schema = {};
        schema['slug'] = keySlug.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "api/keys/edit?delete";
        fetchRequest(payload, uri_request, deleteKeyForm, true);

    })
}
if(newDomainForm){
    newDomainForm.addEventListener('submit', async function(e){
        e.preventDefault();
        let apiUrl = document.getElementById('apiUrl');

        if(apiUrl.value === ""){
            alert('URL cannot be empty.');
            return false;
        }

        const schema = {};
        schema['slug'] = keySlug.value;
        schema['domain'] = apiUrl.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "api/keys/edit?addDomain";
        await fetchRequest(payload, uri_request, newDomainForm, true);
    })
}

if(typeof(removeDomainBtns) && removeDomainBtns !== 'undefined'){
    removeDomainBtns.forEach((button) => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            let domainAttr = button.getAttribute('data-domain');
            if(window.confirm('Are you sure you want to remove this domain from allowed domains?')){
                await removeDomain(domainAttr);
            }

        });
    });
}
async function removeDomain(domain){

    if(domain === ""){
        alert('Domain invalid.');
        return false;
    }

    const schema = {};
    schema['slug'] = keySlug.value;
    schema['domain'] = domain;

    const payload = {
        token: token.value,
        schema: schema,
    }

    let uri_request = "api/keys/edit?removeDomain";
    await fetchRequest(payload, uri_request, null, true);
}

// Add Event Listeners
async function fetchRequest(formData, url, dataForm, reload=false) {
    event.preventDefault();
    hideAlerts();

    // Disable all inputs and submit button
    let inputs = dataForm;
    if(inputs){
        inputs = inputs.querySelectorAll('input, select, button');
        inputs.forEach(input => input.disabled = true);
    }

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                "Content-Type": "application/json",
            },
        }).then(response => {
            const data = response.data;
            if (data.status === "success") {
                showAlert("success", response.data.message);

                if(dataForm===renameKeyForm){
                    updateKeyName(formData.schema.name);
                }
                if(reload){
                    window.location.reload();
                    return false;
                }
            } else {
                showAlert("error", response.data.message);
            }

        }).finally(() => {
            // Re-enable all inputs and submit button
            if(inputs){
                inputs.forEach(input => input.disabled = false);
            }

            if(dataForm===newDomainForm){
                let addDomainExit = document.getElementById('addDomainExit');
                addDomainExit.click();
            }
        });

    } catch (error) {
        showAlert("error", "Error fetching the request. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        if(inputs){
            inputs.forEach(input => input.disabled = false);
        }
    }
} 