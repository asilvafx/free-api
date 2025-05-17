
// DOM elements
const upload = document.getElementById('upload');
const fileAvatar = document.getElementById('fileAvatar');
const profileImage = document.getElementById('profileImage');
const confirmImage = document.getElementById('confirmImage');
const cancelImage = document.getElementById('cancelImage');
const imageButtons = document.getElementById('imageButtons');
const editProfile = document.getElementById('editProfile');
const saveProfileChanges = document.getElementById('saveProfileChanges');
const userDisplayName = document.getElementById('userDisplayName');
const userEmail = document.getElementById('userEmail');
const userPhone = document.getElementById('userPhone');

let originalAvatar = profileImage.src; // Store the original avatar

userPhone.addEventListener('keypress', function(e){
// Get the character code of the pressed key
    const charCode = e.which || e.keyCode;
// Get the character from the charCode
    const charStr = String.fromCharCode(charCode);

// Regular expression to match only digits (0-9) and plus (+) sign
    const validChars = /^[0-9+]$/;

// If the character doesn't match the allowed pattern, prevent the input
    if (!validChars.test(charStr)) {
        e.preventDefault();
    }
})

// Show file picker
upload.addEventListener('click', (e) => {
    e.preventDefault();
    fileAvatar.click();
});

// Handle file selection and image preview
fileAvatar.addEventListener('change', (e) => {
    const file = fileAvatar.files[0];

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (event) => {
            profileImage.src = event.target.result;
            toggleImageButtons(true); // Show confirm and cancel buttons
        };
        reader.readAsDataURL(file);
    } else {
        showAlert('error', 'Please upload a valid image file.');
    }
});

// Toggle visibility of confirm/cancel buttons
function toggleImageButtons(show) {
    if (show) {
        imageButtons.classList.remove('d-none');
    } else {
        imageButtons.classList.add('d-none');
    }
}

// Confirm image upload
confirmImage.addEventListener('click', () => {
    const formData = new FormData();
    formData.append('file', fileAvatar.files[0]);
    formData.append('token', '{{ @TOKEN }}');

    axios.post('/{{@SITE.uri_backend}}/account?upload-avatar', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then(() => {
            showAlert('success', 'Profile image updated successfully!');
            originalAvatar = profileImage.src; // Update the original avatar to the new one
            toggleImageButtons(false); // Hide confirm/cancel buttons
        })
        .catch((error) => {
            console.error('There was an error uploading the file!', error);
        });
});

// Cancel image upload and revert to the original avatar
cancelImage.addEventListener('click', () => {
    profileImage.src = originalAvatar; // Revert to original avatar
    fileAvatar.value = ''; // Reset file input
    toggleImageButtons(false); // Hide confirm/cancel buttons
});

// Toggle edit mode for profile fields
editProfile.addEventListener('click', () => {
    const isEditable = userDisplayName.contentEditable === "true";
    toggleEditMode(!isEditable); // Toggle between editable and non-editable
});

// Toggle profile fields edit mode
function toggleEditMode(enable) {
    if(enable){
        userDisplayName.classList.remove('border-0');
        userDisplayName.classList.add('border');
        userDisplayName.classList.remove('pe-none');
        userPhone.classList.remove('border-0');
        userPhone.classList.add('border');
        userPhone.classList.remove('pe-none');
    } else {
        userDisplayName.classList.add('border-0');
        userDisplayName.classList.add('pe-none');
        userPhone.classList.add('border-0');
        userPhone.classList.add('pe-none');
    }
    saveProfileChanges.classList.toggle('d-none', !enable);
}

// Save profile changes
saveProfileChanges.addEventListener('click', (e) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('token', '{{ @TOKEN }}');
    formData.append('displayName', userDisplayName.value);
    formData.append('phone', userPhone.textContent.value);

    axios.post('/{{@SITE.uri_backend}}/account?update-profile', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            showAlert(response.data.status, response.data.message);
            toggleEditMode(false); // Disable edit mode after saving
        })
        .catch((error) => {
            console.error('There was an error updating the profile!', error);
        });
});

// Get references to the textarea and the character count display
var siteDescription = document.getElementById('userBio');
var charCountDisplay = document.getElementById('charCount');

// DOM elements for bio section
const editBioButton = document.getElementById('editBio');
const saveBioChangesButton = document.getElementById('saveBioChanges');
const bioTextarea = document.getElementById('userBio');
const userBioSpan = document.getElementById('userBioSpan');

updateCharCount(bioTextarea);

// Function to toggle bio editing mode
function toggleBioEditMode(enable) {
    if (enable) {
        userBioSpan.classList.add('d-none'); // Hide default span value
        bioTextarea.classList.remove('d-none'); // Show textarea
        charCountDisplay.classList.remove('d-none'); // Show character counter
        saveBioChangesButton.classList.remove('d-none'); // Show save button
    } else {
        bioTextarea.classList.add('d-none'); // Hide textarea
        charCountDisplay.classList.add('d-none'); // Hide character counter
        saveBioChangesButton.classList.add('d-none'); // Hide save button
        userBioSpan.classList.remove('d-none'); // Show default span value
    }
}

// Add event listener to "Edit Bio" button
editBioButton.addEventListener('click', () => {
    const isEditable = bioTextarea.classList.contains('d-none');
    toggleBioEditMode(isEditable); // Show or hide textarea and save button
});

// Add event listener to "Save changes" button for bio
saveBioChangesButton.addEventListener('click', () => {
// Prepare bio data to send via axios

    const updatedBio = bioTextarea.value.trim();

    const formData = new FormData();
    formData.append('token', '{{ @TOKEN }}');
    formData.append('bio', updatedBio);

    axios.post('/{{@SITE.uri_backend}}/account?update-bio', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            var status = response.data.status;
            if(status=="success"){
                document.getElementById('userBioSpan').innerText = updatedBio;
            }
            showAlert(status, response.data.message);
            toggleBioEditMode(false); // Disable edit mode after saving
        })
        .catch((error) => {
            console.error('There was an error updating the profile!', error);
        });

});