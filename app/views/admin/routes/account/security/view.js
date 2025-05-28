const pinCodeInputs = document.querySelectorAll(".pinCode input");
const qrKeyCodeInputs = document.querySelectorAll(".qrKeyCode input");
const loginAlertsToggle = document.getElementById('loginAlertsToggle');

if(loginAlertsToggle){
    loginAlertsToggle.addEventListener('change', function(e){
        let reload = false;
        const formData = new FormData();
        formData.append('token', "{{@TOKEN}}");
        formData.append('loginAlert', loginAlertsToggle.checked);

        axios.post('/{{@SITE.uri_backend}}/account/security?login-alerts', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response) => {
                showAlert(response.data.status, response.data.message);
                if(response.data.status === "success" && reload){
                    setTimeout(function() {window.location.reload(); return false;}, 500);
                    return false;
                }
            })
            .catch((error) => {
                showAlert('error', error.response.data.message || error.message); // Improved error handling
            })
            .finally(() => {
            });
        return true;

    })
}
window.pinRemove = function pinRemove(el){
    let reload = true;

    const currentPassword = document.getElementById('pinRemove_cc');
    if(!currentPassword.value){
        alert('You must confirm your password to perform this action. Please, enter your password and try again.');
        return false;
    }

    el.setAttribute('aria-disabled', true);

    const formData = new FormData();
    formData.append('token', "{{@TOKEN}}");
    formData.append('currentPassword', btoa(currentPassword.value));

    axios.post('/{{@SITE.uri_backend}}/account/security?pin-remove', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            showAlert(response.data.status, response.data.message);
            if(response.data.status === "success" && reload){
                setTimeout(function() {window.location.reload(); return false;}, 500);
                return false;
            }
        })
        .catch((error) => {
            showAlert('error', error.response.data.message || error.message); // Improved error handling
        })
        .finally(() => {
            el.removeAttribute('aria-disabled');
        });
    return true;
}

window.pinRegister = function pinRegister(el){
    let reload = true;
    const formData = new FormData();
    const pinCode = Array.from(pinCodeInputs)
        .map((input) => input.value)
        .join("");
    if (pinCode.length !== 6) {
        showAlert('error', 'You must enter a valid 6-digit code.');
        return false;
    }

    el.setAttribute('aria-disabled', true);

    const currentPassword = document.getElementById('pinSetup_cc');
    if(!currentPassword.value){
        alert('You must confirm your password to perform this action. Please, enter your password and try again.');
        return false;
    }

    formData.append('token', "{{@TOKEN}}");
    formData.append('currentPassword', btoa(currentPassword.value));
    formData.append('pin', btoa(pinCode));

    axios.post('/{{@SITE.uri_backend}}/account/security?pin-register', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            showAlert(response.data.status, response.data.message);
            if(response.data.status === "success" && reload){
                setTimeout(function() {window.location.reload(); return false;}, 500);
                return false;
            }
        })
        .catch((error) => {
            showAlert('error', error.response.data.message || error.message); // Improved error handling
        })
        .finally(() => {
            el.removeAttribute('aria-disabled');
        });
    return true;
}

window.AuthnLogin = function AuthnLogin(el) {
    // Close Modal
    document.getElementById('removeAuthnExit').click();

    const currentPassword = document.getElementById('passkeyRemove_cc');
    if(!currentPassword.value){
        alert('You must confirm your password to perform this action. Please, enter your password and try again.');
        return false;
    }

    el.setAttribute('aria-disabled', true);

    // Log in to a previously registered account
    waApp.username = "{{@CXT->email}}";
    waApp.login()
        .then((response) => {
            if (response && response.id) {
                AuthSendRequest(response, 'remove', false, btoa(currentPassword.value), el);
            } else {
                alert("Authn: Login failed: ");
                el.removeAttribute('aria-disabled');
            }
        })
        .catch((err) => {
            alert("Authn: Log in error: " + err.message);
            el.removeAttribute('aria-disabled');
        });
}

window.AuthnRegister = function AuthnRegister(el) {
    // Close Modal
    document.getElementById('passkeyExit').click();

    const currentPassword = document.getElementById('passkeySetup_cc');
    if(!currentPassword.value){
        alert('You must confirm your password to perform this action. Please, enter your password and try again.');
        return false;
    }

    el.setAttribute('aria-disabled', true);

    // Register a new device / account
    waApp.username = "{{@CXT->email}}";
    waApp.register()
        .then((response) => {
            if (response && response.id) { // Check if response has the id
                AuthSendRequest(response, 'register', false, btoa(currentPassword.value), el); // Pass the response to send it
            } else {
                showAlert("error", "Registration error: ");
                el.removeAttribute('aria-disabled');
            }
        })
        .catch((err) => {
            showAlert("error", "Registration error: " + err.message);
            el.removeAttribute('aria-disabled');
        });
}

function AuthSendRequest(responseObject, type, reload, password, btn) {

    const formData = new FormData();
    let url = '/account/security';
    if(!type){
        alert('Authn: Invalid Method');
        btn.removeAttribute('aria-disabled');
        return false;
    }

    formData.append('token', '{{ @TOKEN }}'); // Make sure the CSRF token is correctly passed
    formData.append('currentPassword', password);
    formData.append('userId', responseObject.id); // Use actual responseObject

    if(type==="register"){
        formData.append('passkey', 1);
        url = url+'?authn-register';
        reload = true;
    } else
    if(type==="remove"){
        url = url+'?authn-remove';
        reload = true;
    }

    axios.post('/{{@SITE.uri_backend}}'+url, formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            alert(response.data.message);
            if(response.data.status === "success" && reload){
                setTimeout(function() {window.location.reload(); return false;}, 500);
                return false;
            }
        })
        .catch((error) => {
            alert(error.response.data.message || error.message); // Improved error handling
        })
        .finally(() => {
            btn.removeAttribute('aria-disabled');
        });
}
const newPasswordForm = document.getElementById('newPasswordForm');
if(newPasswordForm){
    newPasswordForm.addEventListener('submit', async function(e){
        e.preventDefault();

        const newPassword_cc = document.getElementById('newPassword_cc');
        const newPassword = document.getElementById('password');
        const confirmNewPassword = document.getElementById('confirmPassword');

        if(!newPassword_cc.value || !newPassword.value || !confirmNewPassword.value ){
            alert('Fill all required fields and try again.');
            return false;
        } else
        if(newPassword.value !== confirmNewPassword.value){
            alert('Passwords do not match. Please re-enter your passwords and try again.');
            return false;
        }

        var submitBtn = newPasswordForm.querySelector('button[type="submit"]');
        submitBtn.setAttribute('aria-disabled', true);

        let reload = true;
        const formData = new FormData();
        formData.append('password', btoa(newPassword.value));
        formData.append('currentPassword',btoa(newPassword_cc.value));
        formData.append('token', "{{@TOKEN}}");

        await axios.post('/{{@SITE.uri_backend}}/account/security?update-password', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response) => {
                alert(response.data.message);
                if(response.data.status === "success" && reload){
                    setTimeout(function() {window.location.reload(); return false;}, 500);
                    return false;
                }
            })
            .catch((error) => {
                alert(error.response.data.message || error.message); // Improved error handling
            })
            .finally(() => {
                submitBtn.removeAttribute('aria-disabled');
            });

        return true;
    });
}

const newEmailForm = document.getElementById('newEmailForm');
if(newEmailForm){
    newEmailForm.addEventListener('submit', async function(e){
        e.preventDefault();

        const newEmail_cc = document.getElementById('newEmail_cc');
        const newEmail = document.getElementById('newEmail');

        if(!newEmail_cc.value || !newEmail.value ){
            alert('Fill all required fields and try again.');
            return false;
        }

        var submitBtn = newEmailForm.querySelector('button[type="submit"]');
        submitBtn.setAttribute('aria-disabled', true);

        let reload = true;
        const formData = new FormData();
        formData.append('email', newEmail.value);
        formData.append('currentPassword',btoa(newEmail_cc.value));
        formData.append('token', "{{@TOKEN}}");

        await axios.post('/{{@SITE.uri_backend}}/account/security?update-email', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
            .then((response) => {
                alert(response.data.message);
                if(response.data.status === "success" && reload){
                    setTimeout(function() {window.location.reload(); return false; }, 500);
                    return false;
                }
            })
            .catch((error) => {
                alert(error.response.data.message || error.message); // Improved error handling
            })
            .finally(() => {
                submitBtn.removeAttribute('aria-disabled');
            });

        return true;
    })
}


window.validatePassword = function validatePassword(password) {
    const length = document.getElementById('length');
    const lowercase = document.getElementById('lowercase');
    const extra = document.getElementById('extra');
    const submitButton = document.getElementById('submitPasswordButton');

    // Regex patterns for validation
    const lengthPattern = /.{8,}/; // At least 8 characters
    const lowercasePattern = /[a-z]/; // At least one lowercase letter
    const extraPattern = /[0-9]|[!@#$%^&*]|[A-Z]/; // At least one number, special character, or uppercase letter

    // Validate the password against each pattern
    length.className = lengthPattern.test(password) ? 'valid' : 'invalid';
    lowercase.className = lowercasePattern.test(password) ? 'valid' : 'invalid';
    extra.className = extraPattern.test(password) ? 'valid' : 'invalid';

    // Enable the submit button only if all requirements are met
    if (
        lengthPattern.test(password) &&
        lowercasePattern.test(password) &&
        extraPattern.test(password)
    ) {
        submitButton.disabled = false;
    } else {
        submitButton.disabled = true;
    }
}

pinCodeInputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
        if (e.target.value.length > 1) {
            e.target.value = e.target.value.slice(0, 1);
        }
        if (e.target.value.length === 1 && index < pinCodeInputs.length - 1) {
            pinCodeInputs[index + 1].focus();
        }
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !e.target.value && index > 0) {
            pinCodeInputs[index - 1].focus();
        }
        if (e.key === "e") {
            e.preventDefault();
        }
    });

    // Listen for paste event to autofill pinCodeInputs
    input.addEventListener("paste", (e) => {
        const pasteData = e.clipboardData.getData("text");
        if (pasteData.length === pinCodeInputs.length && /^\d+$/.test(pasteData)) {
            pinCodeInputs.forEach((input, i) => {
                input.value = pasteData[i];
            });
        }
    });
});

qrKeyCodeInputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
        if (e.target.value.length > 1) {
            e.target.value = e.target.value.slice(0, 1);
        }
        if (e.target.value.length === 1 && index < qrKeyCodeInputs.length - 1) {
            qrKeyCodeInputs[index + 1].focus();
        }
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !e.target.value && index > 0) {
            qrKeyCodeInputs[index - 1].focus();
        }
        if (e.key === "e") {
            e.preventDefault();
        }
    });

    // Listen for paste event to autofill pinCodeInputs
    input.addEventListener("paste", (e) => {
        const pasteData = e.clipboardData.getData("text");
        if (pasteData.length === qrKeyCodeInputs.length && /^\d+$/.test(pasteData)) {
            qrKeyCodeInputs.forEach((input, i) => {
                input.value = pasteData[i];
            });
        }
    });
});