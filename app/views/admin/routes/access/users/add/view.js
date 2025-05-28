// Initialization
const newUserForm = document.getElementById('newUserForm');
const token = document.querySelector('#token');

// Form Fields
const displayName = document.getElementById('displayName');
const email = document.getElementById('email');
const crypt = document.getElementById('crypt');
const cryptConfirm = document.getElementById('cryptConfirm');
const role = document.getElementById('role');
const isAdmin = document.getElementById('isAdmin'); 

// Submit Form
if(newUserForm){
    newUserForm.addEventListener('submit', function(e){
        e.preventDefault();

        if(crypt.value != cryptConfirm.value){
            showAlert('error', 'Passwords do not match. Please, enter and confirm your password again.');
            return false;
        }
        if(displayName.value==""||role.value==""||email==""||crypt==""){
            showAlert('error', 'Form incomplete, please fill all required fields and try again.');
            return false;
        }

        let is_admin = 0;
        if (typeof(isAdmin) !== 'undefined' && isAdmin !== null) {
            is_admin = isAdmin.checked?1:0;
        }


        const schema = {};
        schema['displayName'] = displayName.value;
        schema['email'] = email.value;
        schema['crypt'] = crypt.value;
        schema['role'] = role.value;
        schema['is_admin'] = is_admin;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/users/add?save";
        fetchRequest(payload, uri_request, newUserForm);
    });
}

// Add Event Listeners
async function fetchRequest(formData, url, formEl) {
    event.preventDefault();
    hideAlerts();

    // Disable all inputs and submit button
    const inputs = newUserForm.querySelectorAll('input, select, button');
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