const updateStripeForm = document.getElementById('updateStripe');
const token = document.getElementById('token');
const inputs = document.querySelectorAll('input, select, button, textarea');

if(updateStripeForm){
    updateStripeForm.addEventListener('submit', async function (e){
        e.preventDefault();

        await sendUpdateRequest();

    })
}

async function sendUpdateRequest() {

    let stripeStatus = document.getElementById('stripeStatus').checked;
    let stripePk = document.getElementById('stripePk').value;
    let stripeSk = document.getElementById('stripeSk').value;

    const schema = {};
    schema['stripe'] = stripeStatus ? 1 : 0;
    schema['stripe_pk'] = stripePk;
    schema['stripe_sk'] = btoa(stripeSk);

    const payload = {
        token: token.value,
        schema: schema,
    }

    let uri_request = "integrations?view=Stripe&update";
    await fetchRequest(payload, uri_request);
}

async function fetchRequest(formData, url) {
    try {
        inputs.forEach(input => input.disabled = true);
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
            inputs.forEach(input => input.disabled = false);
        });

    } catch (error) {
        showAlert("error", "Error updating data. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        inputs.forEach(input => input.disabled = false);
    }
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
    body: JSON.stringify({
        currency: 'usd',
        email: 'user@email.com',
        amount: 100,
        paymentMethodType: "card"
    }),
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