// Emergency button functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add emergency button to the body
    const emergencyButton = document.createElement('button');
    emergencyButton.className = 'emergency-button';
    emergencyButton.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
    emergencyButton.setAttribute('aria-label', 'Emergency Help');
    document.body.appendChild(emergencyButton);
    
    // Create emergency modal
    const emergencyModal = document.createElement('div');
    emergencyModal.className = 'emergency-modal';
    emergencyModal.innerHTML = `
        <div class="emergency-content">
            <h2>Emergency Assistance</h2>
            <p>Select an option below for immediate help:</p>
            <div class="emergency-options">
                <div class="emergency-option" onclick="callEmergency('tel:+911234567890')">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Call Emergency Helpline</h3>
                        <p>Connect with our 24/7 emergency support team</p>
                    </div>
                </div>
                <div class="emergency-option" onclick="sendSOS()">
                    <i class="fas fa-bell"></i>
                    <div>
                        <h3>Send SOS Alert</h3>
                        <p>Alert our support team about your emergency</p>
                    </div>
                </div>
                <div class="emergency-option" onclick="shareLocation()">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Share Your Location</h3>
                        <p>Send your current location to our team</p>
                    </div>
                </div>
            </div>
            <button class="emergency-close" onclick="closeEmergencyModal()">Close</button>
        </div>
    `;
    document.body.appendChild(emergencyModal);
    
    // Add event listener to emergency button
    emergencyButton.addEventListener('click', function() {
        emergencyModal.classList.add('show');
    });
});

// Emergency call function
function callEmergency(number) {
    window.location.href = number;
}

// Send SOS function
function sendSOS() {
    alert('SOS alert has been sent to our support team. Someone will contact you shortly.');
    closeEmergencyModal();
}

// Share location function
function shareLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            alert(`Your location (${latitude}, ${longitude}) has been shared with our support team. Help is on the way.`);
            closeEmergencyModal();
        }, function() {
            alert('Unable to get your location. Please try another option or call the emergency number.');
        });
    } else {
        alert('Geolocation is not supported by your browser. Please try another option or call the emergency number.');
    }
}

// Close emergency modal
function closeEmergencyModal() {
    document.querySelector('.emergency-modal').classList.remove('show');
}

// Payment receipt generation
function generateReceipt(orderDetails) {
    // Create order ID
    const orderId = 'TU' + Date.now().toString().slice(-8);
    
    // Format date
    const now = new Date();
    const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit' };
    const formattedDate = now.toLocaleDateString('en-US', dateOptions);
    const formattedTime = now.toLocaleTimeString('en-US', timeOptions);
    
    // Create receipt HTML
    const receiptHTML = `
        <div class="receipt-container">
            <div class="receipt-header">
                <div class="receipt-logo">
                    <i class="fas fa-paper-plane" style="font-size: 3rem; color: #219150;"></i>
                </div>
                <h2 class="receipt-title">Travel Utsav</h2>
                <p class="receipt-subtitle">Payment Receipt</p>
            </div>
            
            <div class="receipt-body">
                <div class="receipt-info">
                    <div class="receipt-row">
                        <span class="receipt-label">Order ID:</span>
                        <span class="receipt-value">${orderId}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Date:</span>
                        <span class="receipt-value">${formattedDate}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Time:</span>
                        <span class="receipt-value">${formattedTime}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Item:</span>
                        <span class="receipt-value">${orderDetails.itemName}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Payment Method:</span>
                        <span class="receipt-value">${orderDetails.paymentMethod}</span>
                    </div>
                    ${orderDetails.cardLast4 ? `
                    <div class="receipt-row">
                        <span class="receipt-label">Card:</span>
                        <span class="receipt-value">xxxx-xxxx-xxxx-${orderDetails.cardLast4}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="receipt-total">
                    Total: ₹${orderDetails.amount}
                </div>
            </div>
            
            <div class="receipt-actions">
                <button class="receipt-btn" onclick="downloadReceipt('${orderId}', '${orderDetails.itemName}', '${orderDetails.amount}')">
                    <i class="fas fa-download"></i> Download PDF
                </button>
                <a href="home.html" class="receipt-btn secondary-btn">
                    <i class="fas fa-home"></i> Return Home
                </a>
            </div>
            
            <div class="receipt-footer">
                <p>Thank you for choosing Travel Utsav!</p>
                <p>For any queries, please contact us at travelutsav@gmail.com or +91-7241142006</p>
                <p>Indore, Madhya Pradesh - 452001</p>
            </div>
        </div>
    `;
    
    return receiptHTML;
}

// Function to download receipt as PDF
function downloadReceipt(orderId, itemName, amount) {
    alert(`Your receipt for ${itemName} (Order ID: ${orderId}) will be downloaded as a PDF.`);
    // In a real implementation, this would use a library like jsPDF to generate a PDF
}

// Process payment form submission
function processPayment(form, paymentMethod) {
    // Get form data
    const nameInput = form.querySelector('input[id*="name"]');
    const emailInput = form.querySelector('input[type="email"]');
    const amountInput = form.querySelector('input[id*="amount"]');
    const bookingDetailsInput = form.querySelector('input[id*="booking-details"]');
    
    // Extract card last 4 digits if available
    let cardLast4 = '';
    if (paymentMethod.includes('card')) {
        const cardInput = form.querySelector('input[placeholder="Card Number"]');
        if (cardInput && cardInput.value) {
            cardLast4 = cardInput.value.slice(-4);
        }
    }
    
    // Get amount from input (removing currency symbol)
    const amount = amountInput.value.replace(/[^\d]/g, '');
    
    // Create order details object
    const orderDetails = {
        itemName: bookingDetailsInput.value,
        paymentMethod: paymentMethod,
        amount: amount,
        cardLast4: cardLast4
    };
    
    // Show success message
    const successMessage = document.createElement('div');
    successMessage.className = 'success-message show';
    successMessage.innerHTML = 'Your order has been successfully placed!';
    
    // Hide payment container and show receipt
    const paymentContainer = document.querySelector('.payment-container');
    paymentContainer.innerHTML = '';
    paymentContainer.appendChild(successMessage);
    
    // Generate and display receipt
    const receiptHTML = generateReceipt(orderDetails);
    setTimeout(() => {
        paymentContainer.innerHTML += receiptHTML;
    }, 1500);
    
    // Don't submit the form
    return false;
} 