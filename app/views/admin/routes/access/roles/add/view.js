
// Initialization
const newRoleForm = document.getElementById('newRoleForm');
const token = document.querySelector('#token');

// Form Fields
const name = document.getElementById('name');
const description = document.getElementById('description');
const access = document.getElementById('access');
const fullAccess = document.getElementById('fullAccess'); // Full Access checkbox

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

// Submit Form
if(newRoleForm){
    newRoleForm.addEventListener('submit', function(e){
        e.preventDefault();

        if (name.value == "" || (!fullAccess.checked && access.value == "")) {
            showAlert('error', 'Form incomplete, please fill all required fields and try again.');
            return false;
        }

        const schema = {};
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

        let uri_request = "access/roles/add?save";
        fetchRequest(payload, uri_request, newRoleForm);
    });
}

// Add Event Listeners
async function fetchRequest(formData, url, formEl) {
    event.preventDefault();
    hideAlerts();

    // Disable all inputs and submit button
    const inputs = newRoleForm.querySelectorAll('input, select, button');
    inputs.forEach(input => input.disabled = true);

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
                clearForm(formEl);
            } else {
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            // Re-enable all inputs and submit button
            inputs.forEach(input => input.disabled = false);
        });

    } catch (error) {
        showAlert("error", "Error creating user. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        inputs.forEach(input => input.disabled = false);
    }
}
 