
// Initialization
const updateMailForm = document.getElementById('updateMailForm');
const token = document.getElementById('token');
const submitBtn = document.getElementById('submitBtn');
const submitOrignalTxt = submitBtn.innerText;
const inputs = document.querySelectorAll('input, select, button, textarea');

function sendUpdateRequest(){

    // Mail Input Fields
    const mailHost = document.getElementById('mailHost');
    const mailPort = document.getElementById('mailPort');
    const mailScheme = document.getElementById('mailScheme');
    const mailUsername = document.getElementById('mailUsername');
    const mailPassword = document.getElementById('mailPassword');

    const schema = {};
    schema['mailHost'] = mailHost.value;
    schema['mailPort'] = mailPort.value;
    schema['mailScheme'] = mailScheme.value;
    schema['mailUsername'] = mailUsername.value;
    schema['mailPassword'] = mailPassword.value;

    const payload = {
        token: token.value,
        schema: schema,
    }

    let uri_request = "mail/smtp?update";
    fetchRequest(payload, uri_request, updateMailForm);
}

async function fetchRequest(formData, url) {
    if(inputs){inputs.forEach(input => input.disabled = true);}

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                "Content-Type": "multipart/form-data", // Important for file uploads
            },
        }).then(response => {
            const data = response.data;
            if (data.status === "success") {
                showAlert("success", response.data.message);
            } else {
                console.log(response);
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            if(inputs){  inputs.forEach(input => input.disabled = false); }
        });

    } catch (error) {
        showAlert("error", "Error updating data. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        if(inputs){  inputs.forEach(input => input.disabled = false); }
    }
}

if(updateMailForm){
    updateMailForm.addEventListener('keydown', function(e){
        if(e.keyCode === 13){
            e.preventDefault();
            sendUpdateRequest();
        }
    });

    updateMailForm.addEventListener('submit', function(e){
        e.preventDefault();
        sendUpdateRequest();
    });
}

const host = window.location.protocol + "//" + window.location.host;

const devReqType = document.getElementById('devReqType');
const devReqParams = document.getElementById('devReqParams');
const devReqUri = document.getElementById('devReqUri');
const reqMethodRadios = document.querySelectorAll('input[name="reqMethod"]');



const fetchExamples = (method, uri) => {
    const templates = {
        POST: `fetch('${uri}', {
    method: 'POST',
    headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': 'Bearer your-api-key'
    },
    body: {
        address: \'user@email.com\',
        subject: \'Subject goes here\',
        message: \'Message\'
    }
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