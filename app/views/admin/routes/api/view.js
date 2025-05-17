
const enableApi = document.getElementById('enableApi');
const token = document.querySelector("#token");

if(enableApi){
    enableApi.addEventListener('change', function(e){

        let isChecked = e.target.checked?1:0;

        const schema = {};
        schema['value'] = isChecked;

        const payload = {
            token: token.value,
            schema: schema,
        }

        let uri_request = "api?enable";
        fetchRequest(payload, uri_request);

    })
}

// Add Event Listeners
async function fetchRequest(formData, url) {
    event.preventDefault();

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                "Content-Type": "application/json",
            },
        }).then(response => {
            //console.log(response.data);
        });

    } catch (error) {
        alert("error", "Error creating user. Please try again later.");
    }
}
 