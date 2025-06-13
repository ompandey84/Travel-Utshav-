function validateForm() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    // Validate email
    const emailPattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
    if (!email.match(emailPattern)) {
        alert("Invalid email address");
        return false;
    }

    // Validate password
    if (password === "") {
        alert("Password must be filled out");
        return false;
    }

    return true;
}
