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
