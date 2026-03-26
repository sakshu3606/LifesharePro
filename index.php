<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeShare - Blood & Organ Donation</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chatbot.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand"><h1>❤️ LifeShare</h1></div>
            <ul class="nav-menu">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="blood-donation.php">Blood Donation</a></li>
                <li><a href="organ-donation.php">Organ Donation</a></li>
                <li><a href="search.php">Search Donors</a></li>
                <li><a href="impact-dashboard.php">Impact Dashboard</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
        </div>
    </nav>
    
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Save Lives Today</h1>
            <p class="hero-subtitle">Your donation can give someone a second chance</p>
            <div class="hero-buttons">
                <a href="blood-donation.php" class="btn btn-primary">Donate Blood</a>
                <a href="organ-donation.php" class="btn btn-secondary">Pledge Organs</a>
            </div>
        </div>
    </section>
    
    <section class="features">
        <div class="container">
            <h2>Why Donate?</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">🩸</div>
                    <h3>Blood Donation</h3>
                    <p>Every 2 seconds someone needs blood. One donation can save up to 3 lives. Be a hero today!</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🫀</div>
                    <h3>Organ Donation</h3>
                    <p>One organ donor can save up to 8 lives. Make a lasting impact by pledging your organs.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Find Donors</h3>
                    <p>Search our comprehensive database to find compatible donors in your area quickly and easily.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="stats">
        <div class="container">
            <div class="stat-item">
                <h3 id="bloodDonors">0</h3>
                <p>Blood Donors</p>
            </div>
            <div class="stat-item">
                <h3 id="organPledges">0</h3>
                <p>Organ Pledges</p>
            </div>
            <div class="stat-item">
                <h3 id="livesSaved">0</h3>
                <p>Lives Saved</p>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 LifeShare. All rights reserved. | Saving lives through donation</p>
        </div>
    </footer>
    
    <script src="js/script.js"></script>
    <script src="js/chatbot.js"></script>
</body>
</html>