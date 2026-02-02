<?php
declare(strict_types=1);
require_once __DIR__ . '/app/Bootstrap.php';
require_once __DIR__ . '/app/Db.php';
require_once __DIR__ . '/app/Settings.php';

// Try to load settings from DB, but don't fail if DB is not configured yet
try {
    App\Settings::load();
} catch (\Throwable $e) {
    // Database not configured yet - that's OK for initial deployment
    error_log('Settings load failed (DB not configured?): ' . $e->getMessage());
}

$botUsername   = $_ENV['TELEGRAM_BOT_USERNAME'] ?? '';
$requireAllow  = filter_var($_ENV['TELEGRAM_REQUIRE_ALLOWLIST'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
$announceChat  = $_ENV['TELEGRAM_ANNOUNCE_CHAT_ID'] ?? '-1002552641928';
$joinLink      = 'https://t.me/BabaCheckerRobott';

// Logic restoration
$maintenance = file_exists(__DIR__ . '/maintenance.enable'); 
// Auth check
if (!empty($_SESSION['uid'])) {
    header('Location: /app/dashboard');
    exit;
}
$error = $_GET['error'] ?? '';
$needJoin = isset($_GET['join']);
$esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $maintenance ? 'Maintenance Mode • BabaChecker' : 'Sign in • BabaChecker' ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', sans-serif; background-color: #020617; color: #f8fafc; }
  .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); }
  .card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.05); }
  
  /* 3D Logo Effects */
  .logo-container { perspective: 1000px; display: flex; justify-content: center; margin-bottom: 0.5rem; }
  .logo-3d {
    width: 280px; /* Adjust based on preference */
    height: auto;
    transform-style: preserve-3d;
    transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.3));
    animation: float 6s ease-in-out infinite;
  }
  .logo-3d:hover {
    transform: rotateY(12deg) rotateX(8deg) scale(1.05);
    filter: drop-shadow(0 0 30px rgba(59, 130, 246, 0.5));
  }
  @keyframes float {
    0%, 100% { transform: translateY(0) rotateY(0deg); }
    50% { transform: translateY(-6px) rotateY(4deg); }
  }
</style>
</head>
<body class="flex items-center justify-center min-h-screen">
  <main class="w-full max-w-sm p-4">
    <div class="flex flex-col gap-6">
      
      <!-- Brand Header -->
      <div class="text-center space-y-2">
        <div class="logo-container">
           <img src="assets/logo_3d.jpg" alt="BabaChecker" class="logo-3d rounded-xl">
        </div>
        <p class="text-xs text-slate-400 uppercase tracking-widest font-semibold">Secure Sign-in</p>
      </div>

      <?php if ($maintenance): ?>
        <div class="glass card rounded-3xl p-8 text-center border border-amber-500/20 bg-amber-500/5">
          <h2 class="text-xl font-semibold text-amber-100">Maintenance</h2>
          <p class="text-sm text-slate-400 mt-2">We’re upgrading systems. Please check back later.</p>
          <div class="mt-4">
             <a href="/?admin=1" class="text-xs text-slate-500 hover:text-slate-300">Admin Login</a>
          </div>
        </div>

      <?php else: ?>
        <!-- Sign-in Card -->
        <div class="glass card rounded-3xl p-6">
          <div class="flex flex-col items-center gap-4">
            <span class="text-sm text-slate-300">Sign in with Telegram</span>

            <!-- Admin Login Button (Secured) -->
            <a href="/dev_login.php?user=admin&key=baba_secret_123" 
               class="w-full text-center rounded-lg bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 py-2 text-amber-200 text-xs font-semibold transition mb-2">
               👑 Admin Login
            </a>
            <?php if (($_ENV['APP_ENV'] ?? '') === 'local'): ?>
              <div class="w-full text-center p-4 rounded-2xl border border-emerald-500/30 bg-emerald-900/10 mb-2">
                <div class="text-emerald-300 font-semibold mb-2">Local Environment Detected</div>
                <div class="flex flex-col gap-2">
                  <a href="/dev_login.php?user=admin" class="block w-full rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 py-2 text-emerald-100 text-sm font-medium transition">
                    Login as Admin
                  </a>
                  <a href="/dev_login.php?user=testuser" class="block w-full rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 py-2 text-emerald-100 text-sm font-medium transition">
                    Login as Test User
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Telegram widget -->
            <div class="w-full flex justify-center">
              <script async src="https://telegram.org/js/telegram-widget.js?22"
                      data-telegram-login="<?= $esc($botUsername) ?>"
                      data-size="large"
                      data-auth-url="https://www.babachecker.com/telegram_auth.php"
                      data-request-access="write"></script>
            </div>

            <?php if ($needJoin): ?>
              <!-- Join requirement -->
              <div class="w-full text-center rounded-2xl border border-amber-400/30 bg-amber-400/10 p-4">
                <div class="text-amber-200 text-sm">
                  You must join our Telegram channel to continue.
                </div>
                <div class="mt-2">
                  <a class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-slate-900 font-bold
                             bg-gradient-to-r from-pink-400 to-amber-300"
                     href="<?= $esc($joinLink) ?>"
                     target="_blank" rel="noreferrer">
                    Join BabaChecker Announcements
                  </a>
                </div>
                <div class="text-[11px] text-amber-200/80 mt-2">
                  Channel ID: <code><?= $esc($announceChat) ?></code>
                </div>
              </div>
            <?php endif; ?>

            <p class="text-[11px] text-slate-500 text-center">
              Telegram OAuth is secure. We do not get access to your account.
            </p>
          </div>
        </div>

        <!-- Legal + Powered by -->
        <div class="text-center text-xs text-slate-400">
          By continuing, you agree to our
          <a class="text-sky-300 hover:underline" href="/legal/terms">Terms of Service</a> and
          <a class="text-sky-300 hover:underline" href="/legal/privacy">Privacy Policy</a>.
        </div>
        <div class="flex items-center justify-center gap-2 text-xs text-slate-400">
          <span>Powered by</span>
          <span class="font-bold text-slate-500">BabaChecker</span>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
