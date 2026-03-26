// ===============================
// FETCH DONORS FUNCTION
// ===============================
function fetchDonors() {
    const bloodGroup  = document.getElementById("bloodGroupFilter").value;  // affects blood table only
    const organFilter = document.getElementById("organFilter").value;        // affects organ table only

    // Both filters are sent; backend keeps them independent
    const url = `http://localhost/Projects/lifeshare/search_donors.php`
              + `?bloodGroup=${encodeURIComponent(bloodGroup)}`
              + `&organ=${encodeURIComponent(organFilter)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("resultsContainer");
            container.innerHTML = "";

            if (!data.success || data.data.length === 0) {
                container.innerHTML = "<p class='no-results'>No donors found.</p>";
                return;
            }

            // ── Matched counts from server ────────────────────────────────
            const matchedBlood  = data.matchedBlood  || {};
            const matchedOrgans = data.matchedOrgans || {};

            // ── Separate blood vs organ rows ──────────────────────────────
            // Blood table uses only blood donor rows (already pre-filtered by bloodGroup in PHP)
            // Organ table uses only organ donor rows (already pre-filtered by organ in PHP)
            const bloodRows = data.data.filter(d => d.donor_type === "Blood Donor");
            const organRows = data.data.filter(d => d.donor_type === "Organ Donor");

            // ── Aggregate blood groups ────────────────────────────────────
            const bloodGroupMap = {};
            bloodRows.forEach(d => {
                const bg = d.blood_group || "Unknown";
                if (!bloodGroupMap[bg]) bloodGroupMap[bg] = { total: 0 };
                bloodGroupMap[bg].total += 1;
            });

            // ── Aggregate organ types ─────────────────────────────────────
            const organMap = {};
            organRows.forEach(d => {
                const organList = d.organs ? d.organs.split(",") : ["Unknown"];
                organList.forEach(organ => {
                    const key = organ.trim();
                    if (!organMap[key]) organMap[key] = { total: 0 };
                    organMap[key].total += 1;
                });
            });

            // ── Build wrapper ─────────────────────────────────────────────
            const wrapper = document.createElement("div");
            wrapper.classList.add("tables-parallel");

            // ════════════════════════════════════════════════════════════
            //  BLOOD DONATION TABLE  (filtered by Blood Group dropdown)
            // ════════════════════════════════════════════════════════════
            const bloodSection = document.createElement("div");
            bloodSection.classList.add("table-section", "blood-section");
            bloodSection.innerHTML = `
                <div class="table-title blood-title">
                    <span class="title-icon">🩸</span>
                    <h2>Blood Donation</h2>
                    <p class="table-subtitle">Grouped by Blood Type — Available Stock</p>
                </div>
            `;

            if (Object.keys(bloodGroupMap).length === 0) {
                bloodSection.innerHTML += "<p class='no-results'>No blood donors found for the selected blood group.</p>";
            } else {
                const bloodTable = document.createElement("table");
                bloodTable.classList.add("donor-table", "blood-table");
                bloodTable.innerHTML = `
                    <thead>
                        <tr>
                            <th>Blood Group</th>
                            <th>Total Donors</th>
                            <th>Matched / Used</th>
                            <th>Packets Available</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                `;
                const tbody = bloodTable.querySelector("tbody");
                let sumTotal = 0, sumMatched = 0, sumAvailable = 0;

                Object.entries(bloodGroupMap)
                    .sort((a, b) => a[0].localeCompare(b[0]))
                    .forEach(([group, stats]) => {
                        const matched   = matchedBlood[group] || 0;
                        const available = Math.max(0, stats.total - matched);

                        sumTotal     += stats.total;
                        sumMatched   += matched;
                        sumAvailable += available;

                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td><span class="blood-badge">${group}</span></td>
                            <td class="qty-cell">${stats.total}</td>
                            <td class="qty-cell matched-cell">${matched > 0 ? `<span class="used-pill">${matched}</span>` : '—'}</td>
                            <td class="qty-cell packet-cell">
                                <span class="${available === 0 ? 'avail-zero' : 'avail-ok'}">${available}</span>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                const totalRow = document.createElement("tr");
                totalRow.classList.add("total-row");
                totalRow.innerHTML = `
                    <td><strong>Total</strong></td>
                    <td class="qty-cell"><strong>${sumTotal}</strong></td>
                    <td class="qty-cell matched-cell"><strong>${sumMatched}</strong></td>
                    <td class="qty-cell packet-cell"><strong>${sumAvailable}</strong></td>
                `;
                tbody.appendChild(totalRow);
                bloodSection.appendChild(bloodTable);
            }

            // ════════════════════════════════════════════════════════════
            //  ORGAN DONATION TABLE  (filtered by Organ dropdown)
            // ════════════════════════════════════════════════════════════
            const organSection = document.createElement("div");
            organSection.classList.add("table-section", "organ-section");
            organSection.innerHTML = `
                <div class="table-title organ-title">
                    <span class="title-icon">🫀</span>
                    <h2>Organ Donation</h2>
                    <p class="table-subtitle">Grouped by Organ Type — Available Pledges</p>
                </div>
            `;

            if (Object.keys(organMap).length === 0) {
                organSection.innerHTML += "<p class='no-results'>No organ donors found for the selected organ.</p>";
            } else {
                const organTable = document.createElement("table");
                organTable.classList.add("donor-table", "organ-table");
                organTable.innerHTML = `
                    <thead>
                        <tr>
                            <th>Organ Type</th>
                            <th>Total Donors</th>
                            <th>Matched / Used</th>
                            <th>Quantity Available</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                `;
                const tbody = organTable.querySelector("tbody");
                let sumTotal = 0, sumMatched = 0, sumAvailable = 0;

                Object.entries(organMap)
                    .sort((a, b) => a[0].localeCompare(b[0]))
                    .forEach(([organ, stats]) => {
                        const matched   = matchedOrgans[organ] || 0;
                        const available = Math.max(0, stats.total - matched);

                        sumTotal     += stats.total;
                        sumMatched   += matched;
                        sumAvailable += available;

                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td><span class="organ-badge">${organ}</span></td>
                            <td class="qty-cell">${stats.total}</td>
                            <td class="qty-cell matched-cell">${matched > 0 ? `<span class="used-pill organ-used">${matched}</span>` : '—'}</td>
                            <td class="qty-cell">
                                <span class="${available === 0 ? 'avail-zero' : 'avail-ok'}">${available}</span>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                const totalRow = document.createElement("tr");
                totalRow.classList.add("total-row");
                totalRow.innerHTML = `
                    <td><strong>Total</strong></td>
                    <td class="qty-cell"><strong>${sumTotal}</strong></td>
                    <td class="qty-cell matched-cell"><strong>${sumMatched}</strong></td>
                    <td class="qty-cell"><strong>${sumAvailable}</strong></td>
                `;
                tbody.appendChild(totalRow);
                organSection.appendChild(organTable);
            }

            wrapper.appendChild(bloodSection);
            wrapper.appendChild(organSection);
            container.appendChild(wrapper);
        })
        .catch(error => {
            console.error("Error fetching donors:", error);
        });
}

// ===============================
// AUTO LOAD DATA WHEN PAGE LOADS
// ===============================
document.addEventListener("DOMContentLoaded", function () {
    fetchDonors();
});

// ===============================
// SEARCH BUTTON CLICK
// ===============================
document.getElementById("searchBtn").addEventListener("click", function () {
    fetchDonors();
});