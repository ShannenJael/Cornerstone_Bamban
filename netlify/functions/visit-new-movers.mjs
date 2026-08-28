import { getClientIp, htmlResponse, sanitizeText } from "./lib/http.mjs";
import { readStateJSON, writeStateJSON } from "./lib/state.mjs";

function page(body) {
  return htmlResponse(`<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Outreach Ministry | Cornerstone Baptist Church</title>
  <link rel="icon" type="image/png" href="/images/Cornerstone.jpg">
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body{background:#f5f5f5}
    .form-container{max-width:700px;margin:40px auto;background:#fff;padding:40px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.1)}
    .form-group{margin-bottom:25px}.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#333}
    .form-group input,.form-group textarea{width:100%;padding:12px 15px;border:2px solid #ddd;border-radius:5px;font-size:16px;font-family:inherit;box-sizing:border-box}
    .btn-submit{background:#14AFB1;color:white;padding:15px 40px;border:0;border-radius:5px;font-size:16px;font-weight:600;cursor:pointer}
    .scripture-box{background:#14AFB1;color:white;padding:25px;border-radius:10px;margin-bottom:30px;text-align:center}
    .message{background:#e8f5e9;border-left:4px solid #4caf50;padding:20px;margin-bottom:30px;border-radius:5px}
    .error{background:#ffebee;border-left-color:#e53935;color:#c62828}
    .back-link{display:inline-flex;align-items:center;gap:8px;color:#14AFB1;text-decoration:none;font-weight:600;margin-top:20px}
  </style>
</head>
<body>
  <main class="form-container">${body}</main>
  <script src="/js/main.js"></script>
</body>
</html>`);
}

function formPage(error = "") {
  return page(`
    <div class="scripture-box">
      <h2>Outreach Ministry</h2>
      <p>Sign up to minister to those new to our area.</p>
    </div>
    ${error ? `<div class="message error">${error}</div>` : ""}
    <form method="POST" action="/pages/visit-new-movers.php">
      <div class="form-group"><label for="firstName">First Name *</label><input id="firstName" name="firstName" required></div>
      <div class="form-group"><label for="lastName">Last Name *</label><input id="lastName" name="lastName" required></div>
      <div class="form-group"><label for="address">Address</label><textarea id="address" name="address" rows="3"></textarea></div>
      <div class="form-group"><label for="email">Email Address *</label><input type="email" id="email" name="email" required></div>
      <div class="form-group"><label for="phone">Telephone Number *</label><input type="tel" id="phone" name="phone" required></div>
      <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Submit Volunteer Information</button>
    </form>
    <a href="/pages/visitation.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to Visitation</a>
  `);
}

async function postWebhook(volunteer) {
  if (!process.env.CONTACT_WEBHOOK_URL) {
    return;
  }
  await fetch(process.env.CONTACT_WEBHOOK_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ type: "visitation-volunteer", volunteer })
  });
}

export default async function handler(request) {
  if (request.method === "GET") {
    return formPage();
  }
  if (request.method !== "POST") {
    return htmlResponse("Method not allowed", 405);
  }

  const form = await request.formData();
  const volunteer = {
    id: `vol_${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`,
    firstName: sanitizeText(form.get("firstName"), 200),
    lastName: sanitizeText(form.get("lastName"), 200),
    address: sanitizeText(form.get("address"), 1000),
    email: sanitizeText(form.get("email"), 300),
    phone: sanitizeText(form.get("phone"), 100),
    submittedDate: new Date().toISOString(),
    ip: getClientIp(request)
  };

  if (!volunteer.firstName || !volunteer.lastName || !volunteer.email || !volunteer.phone) {
    return formPage("Please fill in all required fields.");
  }

  const state = await readStateJSON("visitation-volunteers.json", { volunteers: [] });
  state.volunteers = Array.isArray(state.volunteers) ? state.volunteers : [];
  state.volunteers.push(volunteer);
  state.lastUpdated = new Date().toISOString();
  await writeStateJSON("visitation-volunteers.json", state);

  await postWebhook(volunteer).catch(() => {});

  return page(`
    <div class="message">
      <h2><i class="fas fa-check-circle"></i> Thank You for Signing Up!</h2>
      <p>We have received your volunteer information and will be in touch soon.</p>
      <p>Confirmation email: <strong>${volunteer.email}</strong></p>
    </div>
    <a href="/pages/visitation.html" class="back-link"><i class="fas fa-arrow-left"></i> Return to Visitation Page</a>
  `);
}
