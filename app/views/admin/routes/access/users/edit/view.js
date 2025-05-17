// Initialization
const dataForm = document.getElementById('dataForm');
const token = document.querySelector('#token');

// Form Fields
const displayName = document.getElementById('displayName');
const email = document.getElementById('email');
const role = document.getElementById('role');
const isAdmin = document.getElementById('isAdmin');
const user_id = document.getElementById('userId');
const deleteUser = document.getElementById('deleteUser');
const newPasswordForm = document.getElementById('newPasswordForm');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirmPassword');
const passwordModal = document.getElementById('passwordModal');
const passwordChangeExit = document.getElementById('passwordChangeExit');

// Submit Change Password Form
if(newPasswordForm){
    newPasswordForm.addEventListener('submit', function(e){
        e.preventDefault();

        if(passwordInput.value==""){
            showAlert("error", 'Password cannot be empty');
            return false;
        } else
        if(passwordInput.value != confirmPasswordInput.value ){
            showAlert("error", 'Passwords do not match.');
            return false;
        }

        const schema = {};
        schema['userId'] = user_id.value;
        schema['crypt'] = btoa(passwordInput.value);

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/users/edit?password";
        fetchRequest(payload, uri_request, newPasswordForm);
    })
}
// Submit Delete Form
if(deleteUser){
    deleteUser.addEventListener('click', function(e){
        e.preventDefault();

        const schema = {};
        schema['userId'] = user_id.value;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/users/edit?delete";
        fetchRequest(payload, uri_request, null);
    })

}

// Submit Edit Form
if(dataForm){
    dataForm.addEventListener('submit', function(e){
        e.preventDefault();

        var is_admin = isAdmin.checked?1:0;

        const schema = {};
        schema['userId'] = user_id.value;
        schema['displayName'] = displayName.value;
        schema['email'] = email.value;
        schema['role'] = role.value;
        schema['is_admin'] = is_admin;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "access/users/edit?save";
        fetchRequest(payload, uri_request, dataForm);
    });
}

// Add Event Listeners
async function fetchRequest(formData, url, formEl) {
    event.preventDefault();
    hideAlerts();

    // Disable all inputs and submit button
    let inputs = formEl;
    if(formEl){
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
                if(!formEl){
                    window.location.reload();
                }

                // Clear form inputs if success
                // if(formEl){ clearForm(formEl); ]
            } else {
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            // Re-enable all inputs and submit button
            if(formEl){ inputs.forEach(input => input.disabled = false); }

            if(passwordModal.classList.contains('show')){
                passwordChangeExit.click();
            }
        });

    } catch (error) {
        showAlert("error", "Error fetching api. Please try again later.");
        // Re-enable all inputs and submit button in case of error

        if(formEl){  inputs.forEach(input => input.disabled = false); }

    }
}