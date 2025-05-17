const token = document.querySelector('#token');

const renameFileForm = document.getElementById('renameFileForm');
const deleteFileForm = document.getElementById('deleteFileForm');
const uploadFileForm = document.getElementById('uploadFileForm');
const uploadFile = document.getElementById('uploadFile');
const fileName = document.getElementById('fileName');
const fileNameSave = document.getElementById('fileNameSave');
const previewImg = document.getElementById('previewImg');
const previewUrl = document.getElementById('previewUrl');

const host = window.location.protocol + "//" + window.location.host;

const devReqType = document.getElementById('devReqType');
const devReqParams = document.getElementById('devReqParams');
const devReqUri = document.getElementById('devReqUri');
const reqMethodRadios = document.querySelectorAll('input[name="reqMethod"]');

const fetchExamples = (method, uri) => {
    const templates = {
        POST: `const formData = new FormData();

const fileInput = document.getElementById('fileInput');
formData.append('file', fileInput.files[0]);

formData.append('filename', 'custom-filename-optional');

fetch('${uri}', {
    method: 'POST',
    headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': 'Bearer your-api-key'
    },
    body: formData
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));`,
    };
    return templates[method];
};

const curlExamples = (method, uri) => {
    const templates = {
        POST: `curl -X POST "${uri}" -H "Content-Type: multipart/form-data" "Authorization: Bearer your-api-key" -F "file=@/path/to/your/file.jpg" -F "filename=custom-filename-optional" -v`,
    };
    return templates[method];
};

const updateTextarea = () => {
    const selectedMethod = devReqType.value;
    const uri = devReqUri.value;
    const selectedReqMethod = document.querySelector('input[name="reqMethod"]:checked').value;

    let example;
    if (selectedReqMethod === 'node') {
        example = fetchExamples(selectedMethod, uri); // Use fetch examples for Node.js
    } else if (selectedReqMethod === 'curl') {
        example = curlExamples(selectedMethod, uri); // Use cURL examples
    }

    devReqParams.value = example;
};

// Set default to GET example using the URI from devReqUri
updateTextarea();

// Update textarea based on selected method and URI
devReqType.addEventListener('change', updateTextarea);
reqMethodRadios.forEach(radio => {
    radio.addEventListener('change', updateTextarea);
});

const renameModal = document.getElementById('renameModal');
renameModal.addEventListener('show.ui.modal', function (e) {
    setFile(e.relatedTarget);
});
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.ui.modal', function (e) {
    setFile(e.relatedTarget);
});
const previewModal = document.getElementById('previewModal');
previewModal.addEventListener('show.ui.modal', function (e) {
    setFile(e.relatedTarget);
});

function setFile(target) {
    var file = target.getAttribute('data-ui-file');

    // Split the string at the last dot (.)
    var lastDotIndex = file.lastIndexOf('.');

    // Extract the file name (everything before the last dot)
    var fileNameInput = file.substring(0, lastDotIndex);

    // Extract the file extension (everything after and including the last dot)
    var fileExt = file.substring(lastDotIndex);

    fileNameSave.value = file;
    fileName.value = fileNameInput;
    previewUrl.value = host + "/{{@PUBLIC}}/uploads/" + file;

    let isImage = [".png", ".gif", ".webp", ".svg", ".jpg", ".jpeg", ".avif"].includes(fileExt.toLowerCase());
    if (isImage) {
        previewImg.setAttribute('src', '{{@PUBLIC}}/uploads/' + file);
    } else {
        previewImg.setAttribute('src', '{{@PUBLIC}}/assets/img/file_na.png');
    }
}

function shareFile(){

    // Select the text field
    previewUrl.select();
    previewUrl.setSelectionRange(0, 99999); // For mobile devices

    // Copy the text inside the text field
    navigator.clipboard.writeText(previewUrl.value);

}

if(renameFileForm){
    renameFileForm.addEventListener('submit', function(e){
        e.preventDefault();

        var file = fileNameSave.value;

        // Split the string at the last dot (.)
        var lastDotIndex = file.lastIndexOf('.');

        // Extract the file extension (everything after and including the last dot)
        var fileExt = file.substring(lastDotIndex);

        // Set new file name
        var newFileName = fileName.value+fileExt;

        const formData = new FormData();
        formData.append('action', 'rename');
        formData.append('file', fileNameSave.value);
        formData.append('name', newFileName);

        let uri_request = "files/manage";
        fetchRequest(formData, uri_request, null, true);
    });
}

if(deleteFileForm){
    deleteFileForm.addEventListener('submit', function(e){
        e.preventDefault();

        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('file', fileNameSave.value);

        let uri_request = "files/manage";
        fetchRequest(formData, uri_request, null, true);
    });
}

if(uploadFileForm){
    uploadFileForm.addEventListener('submit', function(e){
        e.preventDefault();

        let file = uploadFile.files[0];
        if (!file) {
            showAlert("error", "Upload empty. Select a file and try again.");
            return false;
        }

        // Limit file size to 10MB
        const maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if (file.size > maxSize) {
            showAlert("error", "File size exceeds the 10MB limit.");
            return false;
        }

        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('file', file);

        let uri_request = "files/manage";
        fetchRequest(formData, uri_request, null, true);

    })
}

async function fetchRequest(formData, url, event, reload = false) {
    hideAlerts();

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data', // Important for file uploads
            },
        });
        // Success response handling
        if (response.data.status == "success") {
            if (reload) {
                alert(response.data.message);
                window.location.reload();
                return false;
            }

            showAlert("success", response.data.message);
        } else {
            showAlert("error", response.data.message || "An error occurred.");
        }
        document.getElementById('previewExit').click();

    } catch (error) {
        console.error("Error: ", error);
        showAlert("error", "Failed to process the request. Try again.");
    }
}

function toggleButtonState(button, state) {
    if (state === "loading") {
        button.disabled = true;
        button.textContent = "Processing...";
    } else {
        button.disabled = false;
        button.textContent = "Submit";
    }
}

// Assuming you have these variables available dynamically
const totalSpace = parseFloat("{{@totalSpace}}");  // Should be in bytes
const usedSpace = parseFloat("{{@totalSize}}");    // Should be in bytes
const freeSpace = parseFloat("{{@totalFree}}");    // Should be in bytes

// Function to calculate the used storage percentage
function calculatePercentage(used, total) {
    // Check if total is greater than zero to avoid division by zero
    if (total > 0) {
        // Perform the calculation and ensure the result isn't extremely small
        let percentage = ((used / total) * 100).toFixed(2);
        return percentage;
    } else {
        console.log("Total space is 0, cannot calculate percentage.");
        return "0.00"; // Handle cases where total is zero
    }
}

// Update the progress bar and percentage values
let storagePercentage = calculatePercentage(usedSpace, totalSpace);
const storageProgress = document.getElementById('storageProgress');
const storagePercentageText = document.getElementById('storagePercentage');
const usedPercentageText = document.getElementById('usedPercentage');

let storageTextValue = storagePercentage;
if(storageTextValue<1){
    storageTextValue = "<1";
    storagePercentage = 1;
}

// Update progress bar and text content
storageProgress.style.width = `${storagePercentage}%`;
storageProgress.setAttribute('aria-valuenow', storagePercentage);
storagePercentageText.textContent = `${storageTextValue}%`;
usedPercentageText.textContent = storageTextValue;


function filterFiles() {
    const searchInput = document.getElementById('searchBar');
    const filter = searchInput.value.toLowerCase();
    const fileList = document.querySelectorAll('.file-list .col');

    fileList.forEach(file => {
        const fileName = file.querySelector('.card-body .h5').textContent.toLowerCase();
        if (fileName.includes(filter)) {
            file.style.display = '';
        } else {
            file.style.display = 'none';
        }
    });
}

// Download file function
const dataDownload = document.querySelectorAll('button[data-download]');

if (dataDownload) {
    dataDownload.forEach(item => {

        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var host = window.location.protocol + "//" + window.location.host;
            var file = item.getAttribute('data-download');
            var downloadLink = host + "/{{@PUBLIC}}/uploads/" + file;

            // Create an invisible link element and trigger the download
            var a = document.createElement('a');
            a.href = downloadLink;
            a.setAttribute('download', file); // Force the browser to download the file
            document.body.appendChild(a); // Append the link to the body

            // Trigger the download
            a.click();

            // Clean up the DOM by removing the anchor element
            document.body.removeChild(a);
        });

    });
}