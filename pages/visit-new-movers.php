<?php
/**
 * Cornerstone Baptist Church - New Movers Visitation Volunteer Signup
 */

$submitted = false;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Create volunteer data
        $volunteer = array(
            'id' => uniqid(),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'address' => $address,
            'email' => $email,
            'phone' => $phone,
            'submittedDate' => date('Y-m-d H:i:s')
        );

        // Load existing volunteers
        $dataFile = dirname(__FILE__) . '/../data/visitation-volunteers.json';
        $volunteers = array();

        if (file_exists($dataFile)) {
            $content = file_get_contents($dataFile);
            $data = json_decode($content, true);
            $volunteers = $data['volunteers'] ?? array();
        }

        // Add new volunteer
        $volunteers[] = $volunteer;

        // Save to file
        $output = array(
            'lastUpdated' => date('Y-m-d H:i:s'),
            'volunteers' => $volunteers
        );

        file_put_contents($dataFile, json_encode($output, JSON_PRETTY_PRINT));

        // Send email notification
        $to = '';
        $subject = 'New Visitation Volunteer - ' . $firstName . ' ' . $lastName;
        $message = "A new volunteer has signed up for New Movers Visitation:\n\n";
        $message .= "Name: $firstName $lastName\n";
        $message .= "Email: $email\n";
        $message .= "Phone: $phone\n";
        $message .= "Address: $address\n";
        $message .= "Date: " . date('F j, Y g:i A') . "\n";

        $headers = "From: \r\n";
        $headers .= "Reply-To: $email\r\n";

        @mail($to, $subject, $message, $headers);

        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Outreach Ministry volunteer signup - Cornerstone Baptist Church">
    <title>Outreach Ministry | Cornerstone Baptist Church</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/Cornerstone.jpg">
    <link rel="apple-touch-icon" href="../images/Cornerstone.jpg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group label .required {
            color: #e53935;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: 'Open Sans', sans-serif;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #14AFB1;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            background: #14AFB1;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: #0f9a9c;
        }

        .success-message {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .success-message h3 {
            color: #2e7d32;
            margin: 0 0 10px 0;
        }

        .success-message p {
            color: #1b5e20;
            margin: 5px 0;
        }

        .error-message {
            background: #ffebee;
            border-left: 4px solid #e53935;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #c62828;
        }

        .scripture-box {
            background: linear-gradient(135deg, #14AFB1 0%, #0f9a9c 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .scripture-box h4 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .scripture-box p {
            margin: 0;
            font-style: italic;
            font-size: 16px;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #14AFB1;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .info-box p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #14AFB1;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #0f9a9c;
        }
    </style>
</head>
<body>
    <!-- Top Bar (Logo Only - FBC Hammond Style) -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="top-bar-logo">
                    <a href="../index.html">
                        <img src="../images/Cornerstone.jpg" alt="Cornerstone Baptist Church Logo">
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation (FBC Hammond Style) -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <div class="mobile-logo">
                    <a href="../index.html">
                        <img src="../images/Cornerstone.jpg" alt="Cornerstone Baptist Church Logo">
                    </a>
                </div>

                <button class="mobile-menu-toggle" aria-label="Toggle navigation">
                    <span class="menu-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <span class="menu-label">Menu</span>
                </button>

                <ul class="nav-menu">
                    <li class="dropdown">
                        <a href="#">I'm New <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="mission.html">Our Mission</a></li>
                            <li><a href="beliefs.html">What We Believe</a></li>
                            <li><a href="staff.html">Meet Our Staff</a></li>
                            <li><a href="visit.html">Plan Your Visit</a></li>
                            <li><a href="contact.html">Contact Us</a></li>
                            <li><a href="visitors-card.html">Visitors Card</a></li>
                            <li><a href="prayer.html">Prayer Requests</a></li>
                            
                            <li><a href="heaven.html">Heaven</a></li>
                        </ul>
                    </li>
                    <li><a href="next-steps.html">Next Steps</a></li>
                    <li class="dropdown">
                        <a href="ministries.html">Ministries <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu two-column">
                            <li><a href="children.html">Kids</a></li>
                            <li><a href="youth.html">Youth</a></li>
                            <li><a href="couples.html">Couples</a></li>
                            <li><a href="missions.html">Missions</a></li>
                            <li><a href="visitation.html">Visitation</a></li>
                            <li class="dropdown sub-dropdown">
                                <a href="#">More <i class="fas fa-chevron-down"></i></a>
                                <ul class="dropdown-menu sub-menu">
                                    
                                    <li><a href="ushers-help.php">Ushers Help</a></li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    <li><a href="events.html">Events</a></li>
                    <li class="dropdown">
                        <a href="#">Media <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="watch.html">Watch</a></li>
                            <li><a href="https://www.facebook.com/calvarybaptistchurchfwb" target="_blank">Connect on Facebook</a></li>
                            <li><a href="media-library.html">Cornerstone Media Library</a></li>
                            <li><a href="privacy.html">Privacy Policy</a></li>
                        </ul>
                    </li>
                    <li><a href="directions.html">Directions</a></li>
                    <li><a href="give.html">Give</a></li>
                </ul>

                <div class="nav-cta-buttons">
                    <a href="visit.html" class="btn btn-nav-outline">Plan Your Visit</a>
                    <a href="heaven.html" class="btn btn-nav-filled">Heaven</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="page-header-overlay"></div>
        <div class="container">
            <h1>Outreach Ministry</h1>
            <p>Sign up to minister to those new to our area</p>
        </div>
    </section>

    <!-- Form Content -->
    <section class="ministry-single-content">
        <div class="container">
            <div class="form-container">
                <?php if ($submitted): ?>
                    <div class="success-message">
                        <h3><i class="fas fa-check-circle"></i> Thank You for Signing Up!</h3>
                        <p>We have received your volunteer information and will be in touch soon.</p>
                        <p>You will receive a confirmation email at <strong><?php echo htmlspecialchars($email); ?></strong></p>
                    </div>
                    <a href="visitation.html" class="back-link">
                        <i class="fas fa-arrow-left"></i> Return to Visitation Page
                    </a>
                <?php else: ?>
                    <div class="scripture-box">
                        <h4>Acts 20:20</h4>
                        <p>"And how I kept back nothing that was profitable unto you, but have shewed you, and have taught you publicly, and from house to house"</p>
                    </div>

                    <div class="info-box">
                        <p>To obey the Lord's example Paul provided, we use a wonderful app called <strong>"Bless"</strong>. This application shows us the people who just moved into the area.</p>
                        <p>This is a wonderful opportunity to invite a person new to the area to church and to a saving knowledge of Jesus Christ. Because you already have their name, and they are new to the area, it provides a wonderful opportunity to introduce the plan of salvation or invite them to our wonderful church.</p>
                        <p>Please fill out the form below to volunteer for New Movers Visitation:</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="firstName">First Name <span class="required">*</span></label>
                            <input type="text" id="firstName" name="firstName" required value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last Name <span class="required">*</span></label>
                            <input type="text" id="lastName" name="lastName" required value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone">Telephone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> Submit Volunteer Information
                        </button>

                        <a href="visitation.html" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Visitation
                        </a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Cornerstone Baptist Church</h3>
                    <p>Sitio Magurul-gurol, Anupul, Bamban, Tarlac</p>
                    <p class="footer-tagline">A place you can call home.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/calvarybaptistchurchfwb" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Service Times</h4>
                    <p>Sunday School: 9:15 AM</p>
                    <p>Sunday Morning: 10:30 AM</p>
                    <p>Sunday Evening: 6:00 PM</p>
                    <p>Wednesday Evening: 7:00 PM</p>
                </div>

                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="beliefs.html">What We Believe</a></li>
                        <li><a href="ministries.html">Ministries</a></li>
                        <li><a href="events.html">Events</a></li>
                        <li><a href="contact.html">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Contact Us</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Sitio Magurul-gurol, Anupul, Bamban, Tarlac</p>
                    <p><i class="fas fa-phone"></i> 09367777459 / 09928528704</p>
                    <p><i class="fas fa-envelope"></i> </p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2026 Cornerstone Baptist Church of Sitio Magurul-gurol, Anupul, Bamban, Tarlac. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>




