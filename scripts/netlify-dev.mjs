import { spawn } from "node:child_process";
import net from "node:net";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");

function canListen(port) {
  return new Promise((resolve) => {
    const server = net.createServer();
    server.once("error", () => resolve(false));
    server.once("listening", () => {
      server.close(() => resolve(true));
    });
    server.listen(port, "0.0.0.0");
  });
}

async function findPort(start) {
  for (let port = start; port < start + 20; port += 1) {
    if (await canListen(port)) {
      return port;
    }
  }
  throw new Error(`No free port found from ${start} to ${start + 19}.`);
}

await import("./prepare-netlify.mjs");

const requestedPort = Number.parseInt(process.env.PORT || "8888", 10);
const startPort = Number.isNaN(requestedPort) ? 8888 : requestedPort;
const port = await findPort(startPort);
if (port !== startPort) {
  console.log(`Port ${startPort} is busy; using ${port} instead.`);
}

const configRoot = path.join(root, ".netlify", "cli-home");
const env = {
  ...process.env,
  APPDATA: process.env.NETLIFY_APPDATA || configRoot,
  XDG_CONFIG_HOME: process.env.NETLIFY_XDG_CONFIG_HOME || configRoot,
  NETLIFY_CLI_TELEMETRY_DISABLED: process.env.NETLIFY_CLI_TELEMETRY_DISABLED || "1"
};

const netlifyBin = path.join(root, "node_modules", "netlify-cli", "bin", "run.js");

const child = spawn(process.execPath, [
  netlifyBin,
  "dev",
  "--dir",
  "dist",
  "--functions",
  "netlify/functions",
  "--port",
  String(port),
  "--internal-disable-edge-functions",
  "--offline",
  "--no-open"
], {
  cwd: root,
  env,
  stdio: "inherit"
});

child.on("exit", (code, signal) => {
  if (signal) {
    process.kill(process.pid, signal);
    return;
  }
  process.exit(code || 0);
});
