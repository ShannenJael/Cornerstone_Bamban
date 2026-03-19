<?php
/**
 * Cornerstone Baptist Church - Usher Report (Password Protected)
 *
 * Simple password protection for the usher report page.
 * Change the password below to your desired password.
 */

session_start();

// CONFIGURATION - Change these credentials to your desired username and password
$REPORT_USERNAME = 'UshersHelp';
$REPORT_PASSWORD = 'HarvestUshers2026!';

// Check if user is trying to log in
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $REPORT_USERNAME && $_POST['password'] === $REPORT_PASSWORD) {
        $_SESSION['usher_authenticated'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $login_error = 'Incorrect username or password. Please try again.';
    }
}

// Check if user is trying to log out
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// If not authenticated, show login form
if (!isset($_SESSION['usher_authenticated']) || $_SESSION['usher_authenticated'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Usher Report Login - Cornerstone Baptist Church</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-container {
                background: #fff;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                max-width: 400px;
                width: 100%;
                padding: 40px;
            }
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-header i {
                font-size: 60px;
                color: #14AFB1;
                margin-bottom: 15px;
            }
            .login-header h1 {
                font-size: 24px;
                color: #333;
                margin-bottom: 5px;
            }
            .login-header p {
                color: #666;
                font-size: 14px;
            }
            .login-form {
                margin-top: 20px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #333;
                font-weight: 500;
            }
            .form-group input {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 5px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            .form-group input:focus {
                outline: none;
                border-color: #14AFB1;
            }
            .btn-login {
                width: 100%;
                padding: 12px;
                background: #14AFB1;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.3s;
            }
            .btn-login:hover {
                background: #0f9a9c;
            }
            .error-message {
                background: #ffebee;
                color: #c62828;
                padding: 12px;
                border-radius: 5px;
                margin-bottom: 20px;
                border-left: 4px solid #c62828;
                font-size: 14px;
            }
            .error-message i {
                margin-right: 8px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-shield-alt"></i>
                <h1>Usher Report Access</h1>
                <p>Cornerstone Baptist Church</p>
            </div>

            <?php if (isset($login_error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// User is authenticated - show the usher report page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usher Report - Cornerstone Baptist Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #14AFB1 0%, #0f9a9c 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .controls {
            padding: 25px 30px;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .control-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .control-group label {
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }

        .control-group input,
        .control-group select {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .control-group input:focus,
        .control-group select:focus {
            outline: none;
            border-color: #14AFB1;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #14AFB1;
            color: white;
        }

        .btn-primary:hover {
            background: #0f9a9c;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .stats {
            padding: 20px 30px;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #14AFB1;
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-card .value {
            color: #14AFB1;
            font-size: 32px;
            font-weight: bold;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #14AFB1;
            white-space: nowrap;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .view-btn {
            background: #14AFB1;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s;
        }

        .view-btn:hover {
            background: #0f9a9c;
        }

        .delete-btn {
            background: #e53935;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
            transition: background 0.3s;
        }

        .delete-btn:hover {
            background: #c62828;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            background: #14AFB1;
            color: white;
            padding: 20px 30px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-body {
            padding: 30px;
        }

        .detail-row {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-row label {
            display: block;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .detail-row .value {
            color: #333;
            font-size: 16px;
        }

        .no-data {
            text-align: center;
            padding: 60px 30px;
            color: #999;
        }

        .no-data i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header h1 {
                font-size: 22px;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .control-group {
                flex-direction: column;
                align-items: stretch;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .table-container {
                padding: 15px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-clipboard-list"></i>
                Visitor Card Reports
            </h1>
            <a href="?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>

        <div class="controls">
            <div class="control-group">
                <label for="startDate">From:</label>
                <input type="date" id="startDate">
            </div>
            <div class="control-group">
                <label for="endDate">To:</label>
                <input type="date" id="endDate">
            </div>
            <div class="control-group">
                <label for="visitorType">Type:</label>
                <select id="visitorType">
                    <option value="">All Types</option>
                    <option value="First Time Visitor">First Time Visitor</option>
                    <option value="Returning Visitor">Returning Visitor</option>
                    <option value="Member">Member</option>
                    <option value="Regular Attender">Regular Attender</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="filterCards()">
                <i class="fas fa-filter"></i> Apply Filter
            </button>
            <button class="btn btn-secondary" onclick="resetFilter()">
                <i class="fas fa-undo"></i> Reset
            </button>
            <button class="btn btn-success" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
            <button class="btn btn-success" onclick="exportCSV()">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Cards</h3>
                <div class="value" id="statTotal">0</div>
            </div>
            <div class="stat-card">
                <h3>First Time</h3>
                <div class="value" id="statFirstTime">0</div>
            </div>
            <div class="stat-card">
                <h3>Returning</h3>
                <div class="value" id="statReturning">0</div>
            </div>
            <div class="stat-card">
                <h3>Members</h3>
                <div class="value" id="statMembers">0</div>
            </div>
        </div>

        <div class="table-container">
            <table id="reportTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reportBody">
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No visitor cards found. Data is stored locally in the browser.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Visitor Card Details</h2>
                <button class="close-modal" onclick="closeModal()">Ã—</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Details will be inserted here -->
            </div>
        </div>
    </div>

    <script>
        let allCards = [];
        let filteredCards = [];

        // Load cards from localStorage
        function loadCards() {
            const stored = localStorage.getItem('calvary_usher_cards');
            allCards = stored ? JSON.parse(stored) : [];
            filteredCards = [...allCards];
            renderTable();
            updateStats();
        }

        // Render table
        function renderTable() {
            const tbody = document.getElementById('reportBody');

            if (filteredCards.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="no-data">
                            <i class="fas fa-inbox"></i>
                            <p>No visitor cards match the current filter.</p>
                        </td>
                    </tr>
                `;
                return;
            }

            // Sort by date descending
            filteredCards.sort((a, b) => new Date(b.date) - new Date(a.date));

            tbody.innerHTML = filteredCards.map(card => `
                <tr>
                    <td>${formatDate(card.date)}</td>
                    <td>${card.firstName} ${card.lastName}</td>
                    <td>${card.visitorType}</td>
                    <td>${card.phone || card.email || '-'}</td>
                    <td>
                        <button class="view-btn" onclick="viewCard('${card.id}')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="delete-btn" onclick="deleteCard('${card.id}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        // Update statistics
        function updateStats() {
            document.getElementById('statTotal').textContent = filteredCards.length;
            document.getElementById('statFirstTime').textContent =
                filteredCards.filter(c => c.visitorType === 'First Time Visitor').length;
            document.getElementById('statReturning').textContent =
                filteredCards.filter(c => c.visitorType === 'Returning Visitor').length;
            document.getElementById('statMembers').textContent =
                filteredCards.filter(c => c.visitorType === 'Member').length;
        }

        // Format date
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Filter cards
        function filterCards() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const visitorType = document.getElementById('visitorType').value;

            filteredCards = allCards.filter(card => {
                let match = true;

                if (startDate && card.date < startDate) match = false;
                if (endDate && card.date > endDate) match = false;
                if (visitorType && card.visitorType !== visitorType) match = false;

                return match;
            });

            renderTable();
            updateStats();
        }

        // Reset filter
        function resetFilter() {
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('visitorType').value = '';
            filteredCards = [...allCards];
            renderTable();
            updateStats();
        }

        // View card details
        function viewCard(id) {
            const card = allCards.find(c => c.id === id);
            if (!card) return;

            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modalBody');

            modalBody.innerHTML = `
                <div class="detail-row">
                    <label>Date Submitted</label>
                    <div class="value">${formatDate(card.date)}</div>
                </div>
                <div class="detail-row">
                    <label>Name</label>
                    <div class="value">${card.firstName} ${card.lastName}</div>
                </div>
                <div class="detail-row">
                    <label>Visitor Type</label>
                    <div class="value">${card.visitorType}</div>
                </div>
                <div class="detail-row">
                    <label>Phone</label>
                    <div class="value">${card.phone || 'Not provided'}</div>
                </div>
                <div class="detail-row">
                    <label>Email</label>
                    <div class="value">${card.email || 'Not provided'}</div>
                </div>
                <div class="detail-row">
                    <label>Address</label>
                    <div class="value">${card.address || 'Not provided'}</div>
                </div>
                <div class="detail-row">
                    <label>City, State, ZIP</label>
                    <div class="value">${[card.city, card.state, card.zip].filter(Boolean).join(', ') || 'Not provided'}</div>
                </div>
                <div class="detail-row">
                    <label>Prayer Request</label>
                    <div class="value">${card.prayerRequest || 'None'}</div>
                </div>
                <div class="detail-row">
                    <label>Additional Comments</label>
                    <div class="value">${card.comments || 'None'}</div>
                </div>
            `;

            modal.classList.add('active');
        }

        // Close modal
        function closeModal() {
            document.getElementById('detailModal').classList.remove('active');
        }

        // Delete card
        function deleteCard(id) {
            if (!confirm('Are you sure you want to delete this visitor card?')) return;

            allCards = allCards.filter(c => c.id !== id);
            localStorage.setItem('calvary_usher_cards', JSON.stringify(allCards));

            filterCards(); // Refresh the display
        }

        // Export to PDF
        function exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(18);
            doc.text('Cornerstone Baptist Church', 14, 20);
            doc.setFontSize(14);
            doc.text('Visitor Card Report', 14, 28);
            doc.setFontSize(10);
            doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 35);

            const tableData = filteredCards.map(card => [
                formatDate(card.date),
                `${card.firstName} ${card.lastName}`,
                card.visitorType,
                card.phone || '-',
                card.email || '-'
            ]);

            doc.autoTable({
                startY: 40,
                head: [['Date', 'Name', 'Type', 'Phone', 'Email']],
                body: tableData,
                theme: 'striped',
                headStyles: { fillColor: [20, 175, 177] }
            });

            doc.save(`visitor-cards-${new Date().toISOString().split('T')[0]}.pdf`);
        }

        // Export to CSV
        function exportCSV() {
            const headers = ['Date', 'First Name', 'Last Name', 'Type', 'Phone', 'Email', 'Address', 'City', 'State', 'ZIP', 'Prayer Request', 'Comments'];

            const rows = filteredCards.map(card => [
                card.date,
                card.firstName,
                card.lastName,
                card.visitorType,
                card.phone || '',
                card.email || '',
                card.address || '',
                card.city || '',
                card.state || '',
                card.zip || '',
                card.prayerRequest || '',
                card.comments || ''
            ]);

            let csvContent = headers.join(',') + '\n';
            rows.forEach(row => {
                csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `visitor-cards-${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Close modal on outside click
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Initialize
        loadCards();
    </script>
</body>
</html>


