
// Initialization
const updateSettingsForm = document.getElementById('updateSettingsForm');
const token = document.getElementById('token');
let inputs = updateSettingsForm;
let keywords = updateSettingsForm;

const logoInput = document.getElementById("logo");
const logoPreview = document.getElementById("logoPreview");
const uploadLogoButton = document.getElementById("uploadLogoButton");

const submitBtn = document.getElementById('submitBtn');
const submitOrignalTxt = submitBtn.innerText;

function sendUpdateRequest(){
    // Site Input Fields
    const siteName = document.getElementById('siteName');
    const siteDescription = document.getElementById('siteDescription');
    const siteKeywords = document.getElementById('siteKeywordsInput');
    const siteFrontend = document.getElementById('siteFrontend');
    const siteRegister = document.getElementById('siteRegister');

    // Appearance Input Fields
    const colorPrimary = document.getElementById('colorPrimary');
    const colorPrimaryText = document.getElementById('colorPrimaryText');
    const colorLight = document.getElementById('colorLight');
    const colorLightSecondary = document.getElementById('colorLightSecondary');
    const colorDark = document.getElementById('colorDark');
    const colorDarkSecondary = document.getElementById('colorDarkSecondary');

    // Routes Input Fields
    const uriBackend = document.getElementById('uriBackend');
    const uriAuth = document.getElementById('uriAuth');

    const schema = {};
    schema['siteName'] = siteName.value;
    schema['siteTitle'] = siteName.value;
    schema['siteDescription'] = siteDescription.value;
    schema['siteKeywords'] = siteKeywords.value;
    schema['siteFrontend'] = siteFrontend.checked?1:0;
    schema['siteRegister'] = siteRegister.checked?1:0;
    schema['colorPrimary'] = colorPrimary.value;
    schema['colorPrimaryText'] = colorPrimaryText.value;
    schema['colorLight'] = colorLight.value;
    schema['colorLightSecondary'] = colorLightSecondary.value;
    schema['colorDark'] = colorDark.value;
    schema['colorDarkSecondary'] = colorDarkSecondary.value;
    schema['uriBackend'] = uriBackend.value;
    schema['uriAuth'] = uriAuth.value;

    const logoUploaded = logoInput.files[0];
    const payload = {
        token: token.value,
        schema: schema,
        file: logoUploaded??null,
    }

    let uri_request = "settings?update";
    fetchRequest(payload, uri_request, updateSettingsForm);
}


window.confirmedUpdate = function confirmedUpdate(){

    const currentPassword = document.getElementById('currentPassword');
    if(currentPassword.value==""){
        alert('Enter your password and try again.');
        return false;
    }

    hideAlerts();

    submitBtn.innerText = 'Processing..';
    document.getElementById('confirmExit').click();

    if(inputs){
        // Disable all inputs and submit button
        inputs = document.querySelectorAll('input, select, button, textarea');
        keywords = document.querySelectorAll('span.keyword');
        inputs.forEach(input => input.disabled = true);
        keywords.forEach(keyword => keyword.setAttribute('aria-disabled', true));
    }

    const formData = new FormData();
    formData.append('token', '{{@TOKEN}}');
    formData.append('crypt', btoa(currentPassword.value));
    currentPassword.value = "";

    try {
        const response = axios.post("/{{@SITE.uri_backend}}/settings?confirm-update", formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        }).then(response => {
            const data = response.data;
            if (data.status === "success") {
                sendUpdateRequest();
                return false;
            } else {
                showAlert("error", response.data.message);
                submitBtn.innerText = submitOrignalTxt;
                submitBtn.disabled = false;
                if(inputs){ inputs.forEach(input => input.disabled = false); }
                keywords.forEach(keyword => keyword.removeAttribute('aria-disabled'));
            }
        });

    } catch (error) {
        console.log(error);
        showAlert("error", "Error fetching the request. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        submitBtn.innerText = submitOrignalTxt;
        submitBtn.disabled = false;
        if(inputs){ inputs.forEach(input => input.disabled = false); }
        keywords.forEach(keyword => keyword.removeAttribute('aria-disabled'));
    }


}

if(updateSettingsForm){
    updateSettingsForm.addEventListener('keydown', function(e){
        if(e.keyCode === 13){
            e.preventDefault();
            return false;
        }
    });

    updateSettingsForm.addEventListener('submit', function(e){
        e.preventDefault();

        const confirmbtn = document.getElementById('confirmbtn');
        confirmbtn.click();

    });
}

let originalLogoSrc = logoPreview.src; // Store the original logo src

// Simulate click on file input
if(uploadLogoButton){
    uploadLogoButton.addEventListener("click", function () {
        logoInput.click();
    });
}

// Handle file selection
if(logoInput){
    logoInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        // Check if the selected file is an image
        if (file && file.type.startsWith("image/")) {
            const reader = new FileReader();

            reader.onload = function (e) {
                logoPreview.src = e.target.result; // Set the new image as preview
            };

            reader.readAsDataURL(file);
        } else {
            showAlert("error", "Please upload a valid image file."); // Alert for invalid file
            logoInput.value = ""; // Clear the input
        }
    });
}

// Add Event Listeners
async function fetchRequest(formData, url) {
    event.preventDefault();

    try {
        const response = await axios.post("/{{@SITE.uri_backend}}/" + url, formData, {
            headers: {
                "Content-Type": "multipart/form-data", // Important for file uploads
            },
        }).then(response => {
            const data = response.data;
            if (data.status === "success") {
                showAlert("success", response.data.message);
                // Clear cache and reload the page
                if(confirm('Some changes may need to reload the page to take effect. Do wish to reload now?')){
                    window.location.reload(true);
                    return false;
                }

            } else {
                console.log(response);
                showAlert("error", response.data.message);
            }
        }).finally(() => {
            // Re-enable all inputs and submit button
            if(inputs){ inputs.forEach(input => input.disabled = false); }
            keywords.forEach(keyword => keyword.removeAttribute('aria-disabled'));
            submitBtn.innerText = submitOrignalTxt;
        });

    } catch (error) {
        showAlert("error", "Error updating data. Please try again later.");
        // Re-enable all inputs and submit button in case of error
        if(inputs){  inputs.forEach(input => input.disabled = false); }
    }
}

// The entered keywords
var allKeywords = [];

// Max number of allowed keywords
var maxTags = 10;

// Initialize keywords from hidden input
function initializeKeywords() {
    var hiddenInput = document.getElementById('siteKeywordsInput');
    var initialKeywords = hiddenInput.value.trim();

    if (initialKeywords) {
        var keywordsArray = initialKeywords.split(',').map(function(word) {
            return word.trim(); // Trim each keyword
        });

        keywordsArray.forEach(function(keyword) {
            addWord(keyword);
        });
    }
}

// Delete a keyword
window.deleteWord = function deleteWord(element) {
    var keywordElement = element.closest('.keyword');
    var keywordText = keywordElement.textContent.trim();

    // Remove the 'X' from the keyword text
    keywordText = keywordText.slice(0, keywordText.length - 1).trim();

    var index = allKeywords.indexOf(keywordText);
    if (index !== -1) {
        allKeywords.splice(index, 1);
    }
    keywordElement.remove();

    // Update the hidden input and re-enable the text input if tags are below maxTags
    updateInputBox();
}

// Add a keyword
function addWord(word) {
    if (!word || allKeywords.length >= maxTags || allKeywords.includes(word)) {
        return;
    }

    allKeywords.push(word);

    var keywordDiv = document.createElement('span');
    keywordDiv.className = 'keyword badge border bg-color text-color p-2 d-flex align-items-center';
    keywordDiv.innerHTML = word + '<a class="delete pe-cursor ms-2" onclick="deleteWord(this)">X</a>';

    var keywordsContainer = document.getElementById('keywordsContainer');
    var inputBox = document.getElementById('siteKeywords');

    // Insert keyword before the input box
    keywordsContainer.insertBefore(keywordDiv, inputBox);

    // Clear the input field and update the hidden input
    inputBox.value = '';

    // Update the hidden input value and check if maxTags is reached
    updateInputBox();
}

// Update the hidden input value with all keywords joined by a comma
function updateInputBox() {
    var inputBox = document.getElementById('siteKeywords');
    var hiddenInput = document.getElementById('siteKeywordsInput');

    // Set the value of the hidden input with comma-separated tags
    hiddenInput.value = allKeywords.join(', ');

    // Disable the input if max tags are reached, re-enable otherwise
    inputBox.disabled = allKeywords.length >= maxTags;
}

// On focus out, add the word from the input
function addWordFromTextBox() {
    var inputBox = document.getElementById('siteKeywords');
    var val = inputBox.value.trim();

    if (val) {
        addWord(val);
    }
}

// On key press, check for ',' or ';' or Enter key
function checkLetter(event) {
    var inputBox = document.getElementById('siteKeywords');
    var val = inputBox.value;

    if (val.length > 0) {
        var lastChar = val.slice(-1); // Check the last character
        var keyPressed = event.key;   // Check the key pressed

        if (lastChar === ',' || lastChar === ';' || keyPressed === 'Enter') {
            var word = val.slice(0, -1).trim(); // Slice the value, excluding the last character

            if (word.length > 0) {
                addWord(word);
            }
        }
    }
}

// Add event listeners
document.getElementById('siteKeywords').addEventListener('blur', addWordFromTextBox);
document.getElementById('siteKeywords').addEventListener('keyup', checkLetter);

// Initialize existing keywords when the page loads
initializeKeywords();

// Max character limit
var maxChars = 250;

// Get references to the textarea and the character count display
var siteDescription = document.getElementById('siteDescription');
var charCountDisplay = document.getElementById('charCount');

// Function to update the character counter
window.updateCharCount = function updateCharCount() {
    var remaining = maxChars - siteDescription.value.length;
    charCountDisplay.textContent = remaining + " characters remaining";
}

// Add event listener to update character count as the user types
siteDescription.addEventListener('input', updateCharCount);

// Initialize the counter when the page loads
updateCharCount();