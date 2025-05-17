
function displayGreeting() {
    const greetingElement = document.getElementById('greeting');
    const now = new Date();
    const hours = now.getHours(); // Get current hour (0-23)

    let greetingMessage = "";

    if (hours >= 5 && hours < 12) {
        greetingMessage = "Good Morning!";
    } else if (hours >= 12 && hours < 17) {
        greetingMessage = "Good Afternoon!";
    } else if (hours >= 17 && hours < 21) {
        greetingMessage = "Good Evening!";
    } else {
        greetingMessage = "Good Night!";
    }

    greetingElement.textContent = greetingMessage;
}

// Call the function to display the greeting
displayGreeting();
  