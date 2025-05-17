
const uploadPackageForm = document.getElementById('uploadPackageForm');
const uploadPackageBtn = document.getElementById('uploadPackageBtn');
const uploadPackageInput = document.getElementById('uploadPackageInput');

if (uploadPackageForm) {
    uploadPackageForm.addEventListener('submit', function (e) {
        e.preventDefault();

        let file = uploadPackageInput.files[0];
        if (!file) {
            showAlert("error", "Upload empty. Select a file and try again.");
            return false;
        }

        // Check if the file is a valid .zip file
        const fileName = file.name;
        const fileExtension = fileName.split('.').pop().toLowerCase();

        if (fileExtension !== 'zip') {
            showAlert("error", "Invalid file type. Only .zip files are accepted.");
            return false;
        }

        const formData = new FormData();
        formData.append('file', file);

        let uri_request = "integrations?upload";
        fetchRequest(formData, uri_request);
    });
}

async function fetchRequest(formData, url) {
    hideAlerts();

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data', // Important for file uploads
            },
        });
        // Success response handling
        if (response.data.status == "success") {
            showAlert("success", response.data.message);
            setTimeout(function(){ window.location.reload() }, 1500);
            return false;
        } else {
            showAlert("error", response.data.message || "An error occurred.");
        }

    } catch (error) {
        console.error("Error: ", error);
        showAlert("error", "Failed to process the request. Try again.");
    }
}

function removeIntegration(El){
    El.setAttribute('aria-disabled', true);
    El.innerText = "Processing..";
    window.location.reload(true);
    return true;
}