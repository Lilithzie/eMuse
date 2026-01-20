// Modal Functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Form validation example
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Form submitted successfully! (Demo mode)');
            // In production, handle actual form submission
        });
    });
});

// QR Code Scanner Simulation
const qrInput = document.querySelector('input[placeholder*="QR code"]');
if (qrInput) {
    qrInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            alert('QR Code validated! Entry granted. (Demo mode)');
            this.value = '';
        }
    });
}

// Real-time clock for dashboard
function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    const dateString = now.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Update if clock elements exist
    const clockElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');
    
    if (clockElement) clockElement.textContent = timeString;
    if (dateElement) dateElement.textContent = dateString;
}

setInterval(updateClock, 1000);
updateClock();
