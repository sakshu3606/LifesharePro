# ❤️ LifeShare (LifesharePro)

LifeShare is a web-based platform designed to connect blood and organ donors with recipients in need. The system simplifies donor registration, searching, and tracking real-world impact through a centralized dashboard.

---

## 🚀 Features

* 🩸 **Blood Donation Registration**
  Users can register as blood donors through a detailed form.

* 🫀 **Organ Donation Pledge**
  Users can pledge organs and help save multiple lives.

* 🔍 **Search Donors**
  Filter and find donors based on blood group and organ type.

* 📊 **Impact Dashboard**
  Track:

  * Total donors
  * Lives saved
  * Active requests
  * Donor-recipient connections 

* 🆘 **Urgent Requests System**
  Allows users to request blood or organs in emergency situations.

* 🔐 **Secure Data Handling**
  Uses prepared statements and structured database connection 

---

## 🛠️ Tech Stack

* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Database:** MySQL
* **Server:** Apache (XAMPP / Localhost)

---

## 📂 Project Structure

```id="proj123"
LifesharePro/
│── index.php
│── about.php
│── blood-donation.php
│── organ-donation.php
│── search.php
│── impact-dashboard.php
│── process_blood_donation.php
│── process_organ_donation.php
│── config.php
│── setup_database.php
│── stats.php
│── css/
```

---

## ⚙️ Installation & Setup

### 1️⃣ Clone the repository

```bash id="cmd1"
git clone https://github.com/sakshu3606/LifesharePro.git
cd LifesharePro
```

---

### 2️⃣ Setup Database

* Open browser:

```id="url1"
http://localhost/LifesharePro/setup_database.php
```

👉 This will:

* Create database (`lifeshare_db`)
* Create tables (`blood_donors`, `organ_donors`) 

---

### 3️⃣ Configure Database

Update credentials in:

```id="filecfg"
config.php
```

---

### 4️⃣ Run Project

Start XAMPP → Apache + MySQL

Open:

```id="url2"
http://localhost/LifesharePro
```

---

## 🌍 How It Works

### 👨‍⚕️ For Donors

* Register via blood or organ donation forms
* Data is securely stored in database
* Become available for search

### 🧑‍🤝‍🧑 For Recipients

* Search donors by blood group/location
* View available donors
* Connect and request help

---

## 📈 Impact Calculation

* Blood donor → can save up to **3 lives**
* Organ donor → can save up to **8 lives**
* Automatically calculated in system stats 

---

## 🔮 Future Improvements

* 🔐 User authentication system
* 📱 Mobile app integration
* 🌍 Real-time location tracking
* 🤖 AI-based donor matching
