<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organ Donation - LifeShare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chatbot.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand"><h1>❤️ LifeShare</h1></div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="blood-donation.php">Blood Donation</a></li>
                <li><a href="orgen-donation.php" class="active">Organ Donation</a></li>
                <li><a href="search.php">Search Donors</a></li>
                <li><a href="impact-dashboard.php">Impact Dashboard</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
        </div>
    </nav>
    <section class="form-section">
        <div class="container">
            <div class="form-header">
                <h1>🫀 Pledge for Organ Donation</h1>
                <p>Make a commitment to save lives</p>
            </div>
            <form id="organDonationForm" class="donation-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName">Full Name *</label>
                        <input type="text" id="fullName" name="fullName" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth *</label>
                        <input type="date" id="dob" name="dob" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bloodGroup">Blood Group *</label>
                        <select id="bloodGroup" name="bloodGroup" required>
                            <option value="">Select</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">Address *</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State *</label>
                        <input type="text" id="state" name="state" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Organs to Donate *</label>
                    <div class="checkbox-grid">
                        <label><input type="checkbox" name="organs[]" value="Heart"> Heart</label>
                        <label><input type="checkbox" name="organs[]" value="Liver"> Liver</label>
                        <label><input type="checkbox" name="organs[]" value="Kidneys"> Kidneys</label>
                        <label><input type="checkbox" name="organs[]" value="Lungs"> Lungs</label>
                        <label><input type="checkbox" name="organs[]" value="Pancreas"> Pancreas</label>
                        <label><input type="checkbox" name="organs[]" value="Corneas"> Corneas</label>
                        <label><input type="checkbox" name="organs[]" value="Skin"> Skin</label>
                        <label><input type="checkbox" name="organs[]" value="Bones"> Bones</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="emergencyContact">Emergency Contact *</label>
                    <input type="text" id="emergencyContact" name="emergencyContact" required>
                </div>
                <div class="form-group">
                    <label for="emergencyPhone">Emergency Phone *</label>
                    <input type="tel" id="emergencyPhone" name="emergencyPhone" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I pledge to donate my organs</label>
                </div>
                <button type="submit" class="btn btn-primary btn-large">Submit Pledge</button>
            </form>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 LifeShare. All rights reserved.</p>
        </div>
    </footer>
    <script src="js/organ-donation.js"></script>
    <script src="js/chatbot.js"></script>
</body>
</html>