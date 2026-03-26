// Impact Dashboard Functionality

// Show specific section
function showSection(section) {
    // Hide all sections
    document.getElementById('impactSection').style.display = 'none';
    document.getElementById('requestSection').style.display = 'none';
    document.getElementById('connectionsSection').style.display = 'none';
    
    // Show selected section
    if (section === 'impact') {
        document.getElementById('impactSection').style.display = 'block';
    } else if (section === 'request') {
        document.getElementById('requestSection').style.display = 'block';
    } else if (section === 'connections') {
        document.getElementById('connectionsSection').style.display = 'block';
    }
    
    // Smooth scroll to section
    window.scrollTo({
        top: document.querySelector('.content-section[style*="display: block"]').offsetTop - 80,
        behavior: 'smooth'
    });
}

// Switch between blood and organ request tabs
function switchTab(type) {
    const bloodForm = document.getElementById('bloodRequestForm');
    const organForm = document.getElementById('organRequestForm');
    const tabs = document.querySelectorAll('.tab-btn');
    
    tabs.forEach(tab => tab.classList.remove('active'));
    
    if (type === 'blood') {
        bloodForm.classList.add('active');
        organForm.classList.remove('active');
        tabs[0].classList.add('active');
    } else {
        organForm.classList.add('active');
        bloodForm.classList.remove('active');
        tabs[1].classList.add('active');
    }
}

// Filter connections
function filterConnections(type) {
    const cards = document.querySelectorAll('.connection-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter cards
    cards.forEach(card => {
        if (type === 'all') {
            card.style.display = 'block';
        } else if (type === 'blood' && card.dataset.type === 'blood') {
            card.style.display = 'block';
        } else if (type === 'organ' && card.dataset.type === 'organ') {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Handle request form submission
document.addEventListener('DOMContentLoaded', function() {
    const requestForm = document.getElementById('requestForm');
    
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(e.target);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            // Show success message
            alert('Request submitted successfully! Our team will contact you within 24 hours.');
            
            // Reset form
            e.target.reset();
            
            // In real application, send data to server
            console.log('Request data:', data);
        });
    }
    
    // Show impact section by default
    showSection('impact');
});

// Animate numbers on page load
function animateNumbers() {
    const numbers = document.querySelectorAll('.impact-number');
    
    numbers.forEach(number => {
        const target = parseInt(number.textContent);
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                number.textContent = target;
                clearInterval(timer);
            } else {
                number.textContent = Math.floor(current);
            }
        }, 30);
    });
}

// Run animations when page loads
window.addEventListener('load', animateNumbers);