// Animated counter for statistics
function animateCounter(element, target, duration = 2000) {

    target = parseInt(target) || 0; // Safety check

    if (target === 0) {
        element.textContent = "0";
        return;
    }

    let current = 0;
    const increment = target / (duration / 16);

    const timer = setInterval(() => {
        current += increment;

        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 16);
}


// Load statistics from server
async function loadStats() {

    try {

        const response = await fetch('http://localhost/Projects/lifeshare/stats.php');

        if (!response.ok) {
            throw new Error("HTTP error " + response.status);
        }

        const data = await response.json();

        console.log("Stats Data:", data); // 🔥 Debug

        if (data.success) {

            animateCounter(
                document.getElementById("bloodDonors"),
                data.bloodDonors
            );

            animateCounter(
                document.getElementById("organPledges"),
                data.organPledges
            );

            animateCounter(
                document.getElementById("livesSaved"),
                data.livesSaved
            );

        } else {
            console.log("Stats failed:", data);
        }

    } catch (error) {
        console.error("Error loading stats:", error);
    }
}



// Load stats when page loads
document.addEventListener('DOMContentLoaded', function () {

    loadStats();

    // Scroll animation (unchanged)
    const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {

            if (entry.isIntersecting) {

                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(30px)';

                setTimeout(() => {
                    entry.target.style.transition = 'all 0.6s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);

                observer.unobserve(entry.target);
            }

        });
    }, observerOptions);

    document.querySelectorAll('.feature-card').forEach(card => {
        observer.observe(card);
    });

});


// Refresh stats every 30 seconds
setInterval(loadStats, 30000);
