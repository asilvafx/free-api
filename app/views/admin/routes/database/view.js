
const devReqType = document.getElementById('devReqType');
const devReqParams = document.getElementById('devReqParams');
const devReqUri = document.getElementById('devReqUri');
const reqMethodRadios = document.querySelectorAll('input[name="reqMethod"]');

const fetchExamples = (method, uri) => {
    const templates = {
        GET: `fetch('${uri}', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-api-key'
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));`,
        POST: `fetch('${uri}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-api-key'
    },
    body: JSON.stringify({
        key: 'value',
        anotherKey: 'anotherValue'
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));`,
        PUT: `fetch('${uri}/1', { // Replace '1' with the actual ID you want to update
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-api-key'
    },
    body: JSON.stringify({
        key: 'updatedValue'
    })
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));`,
        DELETE: `fetch('${uri}/id/1', { // Replace '1' with the actual ID you want to delete
    method: 'DELETE',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer your-api-key'
    }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));`
    };
    return templates[method];
};

const curlExamples = (method, uri) => {
    const templates = {
        GET: `curl -X GET "${uri}" -H "Content-Type: application/json" -H "Authorization: Bearer your-api-key"`,
        POST: `curl -X POST "${uri}" -H "Content-Type: application/json" -H "Authorization: Bearer your-api-key" -d '{"key": "value", "anotherKey": "anotherValue"}'`,
        PUT: `curl -X PUT "${uri}/id/1" -H "Content-Type: application/json" -H "Authorization: Bearer your-api-key" -d '{"key": "updatedValue"}'`, // Replace '1' with the actual ID
        DELETE: `curl -X DELETE "${uri}/id/1" -H "Content-Type: application/json" -H "Authorization: Bearer your-api-key"` // Replace '1' with the actual ID
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
