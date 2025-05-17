
// Form Fields
const editRoleForm = document.getElementById('editRoleForm');
const name = document.getElementById('name');
const description = document.getElementById('description');
const access = document.getElementById('access');
const fullAccess = document.getElementById('fullAccess'); // Full Access checkbox
const roleId = document.querySelector('#roleId');
const deleteRole = document.getElementById('deleteRole');

// Event Listener for Full Access checkbox
if (fullAccess) {
    fullAccess.addEventListener('change', function() {
        if (fullAccess.checked) {
            // If fullAccess is checked, select all options and disable the select box
            Array.from(access.options).forEach(option => option.selected = true);
            access.disabled = true;
        } else {
            // If fullAccess is unchecked, clear selections and enable the select box
            Array.from(access.options).forEach(option => option.selected = false);
            access.disabled = false;
        }
    });
}

// Submit Delete Form
if(deleteRole){
    deleteRole.addEventListener('click', function(e){
        e.preventDefault();

        const schema = {};
        schema['roleId'] = roleId.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/roles/edit?delete";
        fetchRequest(payload, uri_request, null);
    })

}

// Submit Edit Form
if(editRoleForm){
    editRoleForm.addEventListener('submit', function(e){
        e.preventDefault();

        if (name.value == "" || (!fullAccess.checked && access.value == "")) {
            showAlert('error', 'Form incomplete, please fill all required fields and try again.');
            return false;
        }

        const schema = {};
        schema['id'] = roleId.value;
        schema['name'] = name.value;
        schema['description'] = description.value;

        if (fullAccess.checked) {
            schema['access'] = "*"; // Set to "*" if full access is checked
        } else {
            const selectedAccess = Array.from(access.selectedOptions).map(option => option.value);
            schema['access'] = selectedAccess;
        }

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/roles/edit?save";
        fetchRequest(payload, uri_request, editRoleForm);
    });
}

// Add Event Listeners
async function fetchRequest(formData, url, dataForm) {
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

                if(!inputs){
                    window.location.reload();
                    return false;
                }
                // Clear form inputs if success
                //clearForm();
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