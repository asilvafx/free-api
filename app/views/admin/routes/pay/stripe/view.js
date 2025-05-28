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

    let uri_request = "pay/stripe?update";
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