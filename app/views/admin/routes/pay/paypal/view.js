const updatePaypalForm = document.getElementById('updatePaypal');
const token = document.getElementById('token');
const inputs = document.querySelectorAll('input, select, button, textarea');

if(updatePaypalForm){
    updatePaypalForm.addEventListener('submit', async function (e){
        e.preventDefault();

        await sendUpdateRequest();

    })
}

async function sendUpdateRequest() {

    let paypalStatus = document.getElementById('paypalStatus').checked;
    let paypalPk = document.getElementById('paypalPk').value;
    let paypalSk = document.getElementById('paypalSk').value;

    const schema = {};
    schema['paypal'] = paypalStatus ? 1 : 0;
    schema['paypal_pk'] = paypalPk;
    schema['paypal_sk'] = btoa(paypalSk);

    const payload = {
        token: token.value,
        schema: schema,
    }

    let uri_request = "pay/paypal?update";
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