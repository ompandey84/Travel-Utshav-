function validateForm() {
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const address = document.getElementById('address').value;

    // Validate name
    if (name === "") {
        alert("Name must be filled out");
        return false;
    }

    // Validate email
    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
    if (!email.match(emailPattern)) {
        alert("Invalid email address");
        return false;
    }

    // Validate phone number
    const phonePattern = /^\d{10}$/;
    if (!phone.match(phonePattern)) {
        alert("Phone number must be 10 digits");
        return false;
    }

    // Validate password
    if (password === "") {
        alert("Password must be filled out");
        return false;
    }

    // Validate confirm password
    if (password !== confirmPassword) {
        alert("Passwords do not match");
        return false;
    }

    // Validate address
    if (address === "") {
        alert("Address must be filled out");
        return false;
    }

    return true;
}
