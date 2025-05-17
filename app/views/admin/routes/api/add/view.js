
// Initilization
const newApiForm = document.getElementById('newApiForm');
const token = document.querySelector('#token');

// Form Fields
const nameInput = document.getElementById('name');


if(newApiForm){
    newApiForm.addEventListener('submit', function(e){
        e.preventDefault();

        // Validate input value
        const inputValidate = /^[a-zA-Z0-9-_]+$/;
        if (!inputValidate.test(nameInput.value)) {
            showAlert("error", "Key name can only contain letters, numbers, hyphens (-), or underscores (_).");
            return false;
        }

        const schema = {};
        schema['name'] = nameInput.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "api/add?key";
        fetchRequest(payload, uri_request, newApiForm);

    })
}


// Add Event Listeners
async function fetchRequest(formData, url, formEl) {
    event.preventDefault();
    hideAlerts();

    // Disable all inputs and submit button
    const inputs = formEl.querySelectorAll('input, select, button');
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