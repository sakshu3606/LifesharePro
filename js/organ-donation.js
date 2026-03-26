document.getElementById('organDonationForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
        const response = await fetch('process_organ_donation.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        alert(data.message);

        if (data.success) {
            this.reset();
        }

    } catch (error) {
        alert("Error occurred");
        console.error(error);
    }

    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit Pledge';
});
