<?php
/**
 * Cornerstone Baptist Church - Visitation Volunteers List (Password Protected)
 */

session_start();

// CONFIGURATION - Change these credentials to your desired username and password
$ADMIN_USERNAME = 'VisitationAdmin';
$ADMIN_PASSWORD = 'HarvestVisit2026!';

// Check if user is trying to log in
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $ADMIN_USERNAME && $_POST['password'] === $ADMIN_PASSWORD) {
        $_SESSION['volunteers_authenticated'] = true;
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
if (!isset($_SESSION['volunteers_authenticated']) || $_SESSION['volunteers_authenticated'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Volunteers Login - Cornerstone Baptist Church</title>
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
                <i class="fas fa-users"></i>
                <h1>Volunteers Access</h1>
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

// Load volunteer data
$dataFile = dirname(__FILE__) . '/../data/visitation-volunteers.json';
$volunteers = array();
$lastUpdated = 'Never';

if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    $data = json_decode($content, true);
    $volunteers = $data['volunteers'] ?? array();
    $lastUpdated = $data['lastUpdated'] ?? 'Never';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitation Volunteers - Cornerstone Baptist Church</title>
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

        .header-actions {
            display: flex;
            gap: 10px;
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
            justify-content: space-between;
        }

        .stats {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .stat-badge {
            background: white;
            padding: 10px 20px;
            border-radius: 5px;
            border-left: 4px solid #14AFB1;
        }

        .stat-badge h3 {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .stat-badge .value {
            font-size: 24px;
            color: #14AFB1;
            font-weight: bold;
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
            text-decoration: none;
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
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

            .stats {
                flex-direction: column;
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
                <i class="fas fa-users"></i>
                Visitation Volunteers
            </h1>
            <div class="header-actions">
                <a href="visitation.html" class="logout-btn">
                    <i class="fas fa-arrow-left"></i> Back to Visitation
                </a>
                <a href="?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <div class="controls">
            <div class="stats">
                <div class="stat-badge">
                    <h3>Total Volunteers</h3>
                    <div class="value"><?php echo count($volunteers); ?></div>
                </div>
                <div class="stat-badge">
                    <h3>Last Updated</h3>
                    <div class="value" style="font-size: 14px; color: #666;">
                        <?php echo $lastUpdated !== 'Never' ? date('M j, Y', strtotime($lastUpdated)) : 'Never'; ?>
                    </div>
                </div>
            </div>
            <button class="btn btn-success" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Download PDF
            </button>
        </div>

        <div class="table-container">
            <table id="volunteersTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Date Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($volunteers)): ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-users"></i>
                                <p>No volunteers registered yet.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php
                        // Sort by submission date descending
                        usort($volunteers, function($a, $b) {
                            return strtotime($b['submittedDate']) - strtotime($a['submittedDate']);
                        });

                        foreach ($volunteers as $volunteer):
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($volunteer['firstName'] . ' ' . $volunteer['lastName']); ?></td>
                                <td><?php echo htmlspecialchars($volunteer['email']); ?></td>
                                <td><?php echo htmlspecialchars($volunteer['phone']); ?></td>
                                <td><?php echo htmlspecialchars($volunteer['address'] ?? '-'); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($volunteer['submittedDate'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const volunteers = <?php echo json_encode($volunteers); ?>;

        function exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Header
            doc.setFontSize(18);
            doc.text('Cornerstone Baptist Church', 14, 20);
            doc.setFontSize(14);
            doc.text('Visitation Volunteers - New Movers', 14, 28);
            doc.setFontSize(10);
            doc.text(`Generated: ${new Date().toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            })}`, 14, 35);
            doc.text(`Total Volunteers: ${volunteers.length}`, 14, 42);

            // Prepare table data
            const tableData = volunteers.map(vol => [
                `${vol.firstName} ${vol.lastName}`,
                vol.email,
                vol.phone,
                vol.address || '-',
                new Date(vol.submittedDate).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                })
            ]);

            // Add table
            doc.autoTable({
                startY: 50,
                head: [['Name', 'Email', 'Phone', 'Address', 'Date']],
                body: tableData,
                theme: 'striped',
                headStyles: { fillColor: [20, 175, 177] },
                styles: { fontSize: 9 },
                columnStyles: {
                    0: { cellWidth: 35 },
                    1: { cellWidth: 45 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 50 },
                    4: { cellWidth: 25 }
                }
            });

            // Save
            doc.save(`visitation-volunteers-${new Date().toISOString().split('T')[0]}.pdf`);
        }
    </script>
</body>
</html>


