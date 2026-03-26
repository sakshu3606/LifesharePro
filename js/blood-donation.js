document.getElementById('bloodDonationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Registering...';
    
    // Remove any existing messages
    const existingMessage = document.querySelector('.success-message, .error-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    try {
        const response = await fetch('process_blood_donation.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'success-message';
            successMsg.textContent = data.message;
            this.insertBefore(successMsg, this.firstChild);
            
            // Reset form
            this.reset();
            
            // Scroll to message
            successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remove message after 5 seconds
            setTimeout(() => {
                successMsg.remove();
            }, 5000);
        } else {
            // Show error message
            const errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            errorMsg.textContent = data.message;
            this.insertBefore(errorMsg, this.firstChild);
            
            // Scroll to message
            errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Remove message after 5 seconds
            setTimeout(() => {
                errorMsg.remove();
            }, 5000);
        }
    } catch (error) {
        console.error('Error:', error);
        const errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        errorMsg.textContent = 'An error occurred. Please try again later.';
        this.insertBefore(errorMsg, this.firstChild);
        
        setTimeout(() => {
            errorMsg.remove();
        }, 5000);
    } finally {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Register as Donor';
    }
});

// Add real-time validation
document.querySelectorAll('input[required], select[required], textarea[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.style.borderColor = 'var(--primary)';
        } else {
            this.style.borderColor = 'var(--success)';
        }
    });
    
    field.addEventListener('input', function() {
        if (this.style.borderColor === 'rgb(231, 76, 60)') {
            this.style.borderColor = '#e0e0e0';
        }
    });
});