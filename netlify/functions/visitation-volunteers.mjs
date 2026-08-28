import { clearSessionCookie, createSessionCookie, readSession, validateCredentials } from "./lib/auth.mjs";
import { escapeHtml, htmlResponse } from "./lib/http.mjs";
import { readStateJSON } from "./lib/state.mjs";

function redirect(location, headers = {}) {
  return new Response(null, {
    status: 303,
    headers: {
      Location: location,
      ...headers
    }
  });
}

function shell(body) {
  return htmlResponse(`<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Visitation Volunteers | Cornerstone Baptist Church</title>
  <link rel="icon" type="image/png" href="/images/Cornerstone.jpg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body{font-family:Segoe UI,Tahoma,sans-serif;background:#1a1a1a;min-height:100vh;margin:0;padding:20px}
    .container{max-width:1200px;margin:0 auto;background:white;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,.3);overflow:hidden}
    .header{background:#14AFB1;color:white;padding:30px;display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}
    .header h1{margin:0;font-size:28px}.btn{padding:10px 18px;border-radius:5px;text-decoration:none;border:0;font-weight:600;cursor:pointer}
    .btn-white{background:rgba(255,255,255,.2);color:white;border:2px solid white}.btn-primary{background:#14AFB1;color:white;width:100%}
    .body{padding:30px}.login{max-width:400px;margin:0 auto}.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:8px}
    input{width:100%;padding:12px;border:1px solid #ddd;border-radius:5px;box-sizing:border-box}table{width:100%;border-collapse:collapse}
    th,td{padding:12px;border-bottom:1px solid #e0e0e0;text-align:left}th{background:#f8f9fa;border-bottom:2px solid #14AFB1}
    .error{background:#ffebee;color:#c62828;padding:12px;border-radius:5px;margin-bottom:20px}
  </style>
</head>
<body>
  <div class="container">${body}</div>
</body>
</html>`);
}

function loginPage(error = "") {
  return shell(`
    <div class="header"><h1><i class="fas fa-users"></i> Visiting Volunteers</h1></div>
    <div class="body login">
      ${error ? `<div class="error">${escapeHtml(error)}</div>` : ""}
      <form method="POST" action="/pages/visiting-volunteers.php">
        <div class="form-group"><label for="username">Username</label><input id="username" name="username" required autofocus></div>
        <div class="form-group"><label for="password">Password</label><input id="password" name="password" type="password" required></div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
      </form>
    </div>
  `);
}

async function dashboard() {
  const state = await readStateJSON("visitation-volunteers.json", { volunteers: [], lastUpdated: "Never" });
  const volunteers = (Array.isArray(state.volunteers) ? state.volunteers : [])
    .slice()
    .sort((a, b) => new Date(b.submittedDate) - new Date(a.submittedDate));

  const rows = volunteers.length
    ? volunteers.map((volunteer) => `
      <tr>
        <td>${escapeHtml(`${volunteer.firstName || ""} ${volunteer.lastName || ""}`.trim())}</td>
        <td>${escapeHtml(volunteer.email || "")}</td>
        <td>${escapeHtml(volunteer.phone || "")}</td>
        <td>${escapeHtml(volunteer.address || "-")}</td>
        <td>${escapeHtml(new Date(volunteer.submittedDate).toLocaleString("en-US"))}</td>
      </tr>
    `).join("")
    : '<tr><td colspan="5" style="text-align:center;color:#777;padding:40px">No volunteers have signed up yet.</td></tr>';

  return shell(`
    <div class="header">
      <h1><i class="fas fa-users"></i> New Move In Visitation Volunteers</h1>
      <div>
        <a href="/pages/visitation.html" class="btn btn-white"><i class="fas fa-arrow-left"></i> Back</a>
        <a href="/pages/visiting-volunteers.php?logout=1" class="btn btn-white"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    <div class="body">
      <p><strong>Total Volunteers:</strong> ${volunteers.length}</p>
      <p><strong>Last Updated:</strong> ${escapeHtml(state.lastUpdated || "Never")}</p>
      <div style="overflow-x:auto">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Date Submitted</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    </div>
  `);
}

export default async function handler(request) {
  const url = new URL(request.url);
  if (url.searchParams.has("logout")) {
    return redirect("/pages/visiting-volunteers.php", {
      "Set-Cookie": clearSessionCookie("visitation")
    });
  }

  if (request.method === "POST") {
    const form = await request.formData();
    const username = String(form.get("username") || "").trim();
    const password = String(form.get("password") || "");
    if (validateCredentials("visitation", username, password)) {
      return redirect("/pages/visiting-volunteers.php", {
        "Set-Cookie": createSessionCookie("visitation", username)
      });
    }
    return loginPage("Incorrect username or password.");
  }

  if (!readSession(request, "visitation")) {
    return loginPage();
  }

  return dashboard();
}
