 


function loadOtpInputs() {
  const confirmInputs = document.querySelectorAll('.confirmInputs input');
  confirmInputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
      if (e.target.value.length > 1) {
        e.target.value = e.target.value.slice(0, 1);
      }
      if (e.target.value.length === 1 && index < confirmInputs.length - 1) {
        confirmInputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        confirmInputs[index - 1].focus();
      }
      if (e.key === 'e') {
        e.preventDefault();
      }
    });

    // Listen for paste event to autofill confirmInputs
    input.addEventListener('paste', (e) => {
      const pasteData = e.clipboardData.getData('text');
      if (pasteData.length === confirmInputs.length && /^\d+$/.test(pasteData)) {
        confirmInputs.forEach((input, i) => {
          input.value = pasteData[i];
        });
      }
    });
  });
}
function confirmEmail(step, el) {
  if (!step) {
    return false;
  }

  switch (step) {
    case 1:
      sendOtp(el);
      loadOtpInputs();
      break;
    case 2:
      verifyOtp(el);
      break;
    default:
      return false;
  }
  return true;
}

async function sendOtp(el) {
  el.setAttribute('aria-disabled', true);
  const stepDivOne = document.querySelector('[data-confirm-step="1"]');
  const stepDivTwo = document.querySelector('[data-confirm-step="2"]');
  stepDivOne.classList.add('hidden');
  stepDivTwo.classList.remove('hidden');
  const formData = new FormData();
  formData.append('token', '{{ @TOKEN }}');
  await axios
    .post('/{{@SITE.uri_backend}}/account?send-otp', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    .then(() => {
      el.removeAttribute('aria-disabled');
    });
}
async function verifyOtp(el) {
  el.setAttribute('aria-disabled', true);
  const confirmInputs = document.querySelectorAll('.confirmInputs input');
  const otpCode = Array.from(confirmInputs)
    .map((input) => input.value)
    .join('');
  if (otpCode.length !== 6) {
    alert('You must enter a valid 6-digit code.');
    return false;
  }
  const confirmExit = document.getElementById('confirmExit');

  const formData = new FormData();
  formData.append('otp', otpCode);
  formData.append('token', '{{ @TOKEN }}');

  await axios
    .post('/{{@SITE.uri_backend}}/account?verify-otp', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    .then((response) => {
      if (response.data.status == 'success') {
        confirmExit.click();
        alert('Email was confirmed successfully!');
        window.location.reload();
        return false;
      } else {
        alert(response.data.message);
      }
    })
    .catch((error) => {
      alert('There was an error processing your request!', error);
    })
    .finally(() => {
      el.removeAttribute('aria-disabled');
    });
}

function sidebarToggle(){
    const sidebar = document.getElementById('sidebar');

    if(sidebar){
        if(sidebar.classList.contains('show')){
            sidebar.classList.add('hide');
            sidebar.classList.remove('show');
        } else {
            sidebar.classList.remove('hide');
            sidebar.classList.add('show'); 
        }
    }
}

// Toggle password input view
function togglePassword(inputId, element) {
    const input = document.getElementById(inputId);
    const eyeIcon = element.querySelector('.eye-icon');
    const eyeSlashIcon = element.querySelector('.eye-slash-icon');

    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.style.display = 'none';
        eyeSlashIcon.style.display = 'inline-block';
    } else {
        input.type = 'password';
        eyeIcon.style.display = 'inline-block';
        eyeSlashIcon.style.display = 'none';
    }
}


// Function to clear the form inputs
function clearForm(formEl) {
    formEl.reset();  // Reset form fields to their initial values   
}

// Show Alert Function
function showAlert($type, $msg){ 

    const successDiv = document.querySelector('#success-div');
    const successMsg = document.querySelector('#success-msg');
    const errorDiv = document.querySelector('#error-div');
    const errorMsg = document.querySelector('#error-msg');

    if(successDiv && successMsg && errorDiv && errorMsg){
    if($type==='success'){
        if(!errorDiv.classList.contains('hidden')){
            errorDiv.classList.add('hidden');
        } 
        successMsg.innerHTML = $msg;
        successDiv.classList.remove('hidden'); 
    } else {
        if(!successDiv.classList.contains('hidden')){
            successDiv.classList.add('hidden');
        } 
        errorMsg.innerHTML = $msg;
        errorDiv.classList.remove('hidden'); 
    } window.scrollTo({ top: 0, behavior: 'smooth' }); }
}

// Hide All Alerts Function
function hideAlerts(){

    const successDiv = document.querySelector('#success-div');
    const errorDiv = document.querySelector('#error-div');

    if(successDiv && errorDiv){
    if(!errorDiv.classList.contains('hidden')){
            errorDiv.classList.add('hidden');
    } 
    if(!successDiv.classList.contains('hidden')){
            successDiv.classList.add('hidden');
    } 
    }
}  

// Function to update the character counter
function updateCharCount(el) {
if(el){
const remaining = el.getAttribute('maxlength') - el.value.length;
const elParent = el.parentElement;
const elCounter = elParent.querySelector('.charCounter');
if(elCounter){ elCounter.textContent = remaining + " characters remaining"; }
} return true;
}  
