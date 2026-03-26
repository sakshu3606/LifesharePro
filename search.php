<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Donors - LifeShare</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/chatbot.css">
    <style>
        /* ── Parallel table layout ─────────────────────────────── */
        .tables-parallel {
            display: flex;
            gap: 28px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .table-section {
            flex: 1;
            min-width: 300px;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            background: #fff;
        }

        /* ── Filter bar ────────────────────────────────────────── */
        .search-filters {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-top: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
        }

        .filter-group.blood-filter label { color: #c62828; }
        .filter-group.organ-filter label { color: #6a1b9a; }

        .filter-group select {
            padding: 9px 14px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            font-size: 0.93rem;
            background: #fff;
            cursor: pointer;
            min-width: 170px;
            transition: border-color 0.2s;
        }

        .filter-group.blood-filter select:focus { outline: none; border-color: #e53935; }
        .filter-group.organ-filter select:focus  { outline: none; border-color: #6a1b9a; }

        /* ── Section title bars ────────────────────────────────── */
        .table-title {
            padding: 18px 22px 12px;
            text-align: center;
        }
        .table-title .title-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 4px;
        }
        .table-title h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.4px;
        }
        .table-title .table-subtitle {
            margin: 4px 0 0;
            font-size: 0.83rem;
            opacity: 0.8;
        }

        .blood-title { background: linear-gradient(135deg, #e53935, #ef9a9a); color: #fff; }
        .organ-title { background: linear-gradient(135deg, #6a1b9a, #ce93d8); color: #fff; }

        /* ── Tables ────────────────────────────────────────────── */
        .donor-table {
            width: 100%;
            border-collapse: collapse;
        }
        .donor-table thead tr { background: #f5f5f5; }
        .donor-table th {
            padding: 11px 16px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: #444;
            border-bottom: 2px solid #e0e0e0;
        }
        .donor-table td {
            padding: 10px 16px;
            font-size: 0.92rem;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
        }
        .donor-table tbody tr:hover { background: #fafafa; }
        .donor-table tbody tr:last-child td { border-bottom: none; }

        .total-row td {
            background: #f9f9f9;
            border-top: 2px solid #e0e0e0 !important;
            font-size: 0.95rem;
        }

        /* ── Quantity cells ────────────────────────────────────── */
        .qty-cell     { text-align: center; }
        .packet-cell  { font-weight: 600; }
        .matched-cell { color: #999; }

        /* ── Badges ────────────────────────────────────────────── */
        .blood-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background: #fce4ec;
            color: #c62828;
            font-weight: 700;
            font-size: 0.88rem;
            letter-spacing: 0.5px;
        }
        .organ-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background: #f3e5f5;
            color: #6a1b9a;
            font-weight: 600;
            font-size: 0.88rem;
            text-transform: capitalize;
        }

        .used-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            background: #fff3e0;
            color: #e65100;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .used-pill.organ-used {
            background: #ede7f6;
            color: #6a1b9a;
        }

        .avail-ok {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background: #e8f5e9;
            color: #2e7d32;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .avail-zero {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background: #ffebee;
            color: #c62828;
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* ── Responsive ────────────────────────────────────────── */
        @media (max-width: 700px) {
            .tables-parallel { flex-direction: column; }
            .search-filters  { flex-direction: column; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand"><h1>❤️ LifeShare</h1></div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="blood-donation.php">Blood Donation</a></li>
                <li><a href="organ-donation.php">Organ Donation</a></li>
                <li><a href="search.php" class="active">Search Donors</a></li>
                <li><a href="impact-dashboard.php">Impact Dashboard</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
        </div>
    </nav>

    <section class="search-section">
        <div class="container">
            <h1>🔍 Find Donors</h1>

            <div class="search-filters">

                <!-- Filters Blood Table only -->
                <div class="filter-group blood-filter">
                    <label>🩸 Filter Blood Table</label>
                    <select id="bloodGroupFilter">
                        <option value="">All Blood Groups</option>
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

                <!-- Filters Organ Table only -->
                <div class="filter-group organ-filter">
                    <label>🫀 Filter Organ Table</label>
                    <select id="organFilter">
                        <option value="">All Organs</option>
                        <option value="kidney">Kidney</option>
                        <option value="liver">Liver</option>
                        <option value="heart">Heart</option>
                        <option value="lung">Lung</option>
                        <option value="pancreas">Pancreas</option>
                        <option value="cornea">Cornea</option>
                    </select>
                </div>

                <button id="searchBtn" class="btn btn-primary">Search</button>
            </div>

            <div id="resultsContainer" class="results-container">
                <p class="no-results">Enter search criteria</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 LifeShare. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/search.js"></script>
    <script src="js/chatbot.js"></script>
</body>
</html>