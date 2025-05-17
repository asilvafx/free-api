// Initilization
const newPermissionForm = document.getElementById('newPermissionForm');
const token = document.querySelector('#token');

// Form Fields
const nameInput = document.getElementById('name');

function deletePermission($pid){

    if(confirm('Are you sure?\nThis action cannot be undone.')){
        const schema = {};
        schema['id'] = $pid;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/permissions?delete";

        fetchRequest(payload, uri_request, null);
    }

    return false;
}


if(newPermissionForm){
    newPermissionForm.addEventListener('submit', function(e){
        e.preventDefault();

        const schema = {};
        schema['name'] = nameInput.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/permissions?add";
        fetchRequest(payload, uri_request, newPermissionForm);

    })
}


// Add Event Listeners
async function fetchRequest(formData, url, formEl) {
    hideAlerts();

    // Disable all inputs and submit button
    let inputs = formEl;
    if(inputs){
        inputs = formEl.querySelectorAll('input, select, button');
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
                // Clear form inputs if success
                if(inputs){
                    clearForm(formEl);
                }
                window.location.reload();
            } else {
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            // Re-enable all inputs and submit button
            if(inputs){
                inputs.forEach(input => input.disabled = false);
            }
        });

    } catch (error) {
        showAlert("error", "Error creating user. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        if(inputs){
            inputs.forEach(input => input.disabled = false);
        }
    }
}