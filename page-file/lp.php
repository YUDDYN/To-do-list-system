<?php
session_start();
// Jika user sudah login, langsung alihkan ke dashboard agar tidak perlu melihat landing page lagi
// if (isset($_SESSION['id_user'])) {
//     header('Location: dashboard/index.php');
//     exit();
// }
?>
<!doctype html>
<html lang="en" class="h-full">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Grow Your Productivity</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; font-family: 'Outfit', sans-serif; }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }
    @keyframes glow-pulse {
      0%, 100% { opacity: 0.4; }
      50% { opacity: 0.8; }
    }
    @keyframes leaf-sway {
      0%, 100% { transform: rotate(-3deg); }
      50% { transform: rotate(3deg); }
    }
    @keyframes grow-up {
      0% { transform: scaleY(0); opacity: 0; }
      100% { transform: scaleY(1); opacity: 1; }
    }
    @keyframes fade-in {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes sparkle {
      0%, 100% { opacity: 0; transform: scale(0); }
      50% { opacity: 1; transform: scale(1); }
    }

    /* ===== PAGE TRANSITION OVERLAY ===== */
    @keyframes curtain-close {
      0%   { transform: scaleX(0); transform-origin: left; }
      100% { transform: scaleX(1); transform-origin: left; }
    }
    @keyframes leaf-fly {
      0%   { opacity: 0; transform: translateY(0) rotate(0deg) scale(0.5); }
      30%  { opacity: 1; }
      100% { opacity: 0; transform: translateY(-120px) rotate(360deg) scale(1.2); }
    }

    #page-transition {
      position: fixed;
      inset: 0;
      z-index: 9999;
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.1s;
    }
    #page-transition.active {
      pointer-events: all;
      opacity: 1;
    }
    #transition-curtain {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, #052e16 0%, #064e3b 50%, #0a0f0a 100%);
      transform: scaleX(0);
      transform-origin: left;
    }
    #page-transition.active #transition-curtain {
      animation: curtain-close 0.55s cubic-bezier(0.77,0,0.18,1) forwards;
    }
    .transition-leaf {
      position: absolute;
      width: 32px;
      height: 32px;
      opacity: 0;
    }
    #page-transition.active .transition-leaf {
      animation: leaf-fly 0.8s ease-out forwards;
    }
    #transition-logo {
      position: relative;
      z-index: 2;
      opacity: 0;
      transition: opacity 0.3s 0.4s;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }
    #page-transition.active #transition-logo {
      opacity: 1;
    }
    #transition-logo span {
      font-family: 'Outfit', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #4ade80;
      letter-spacing: 0.05em;
    }

    .float { animation: float 4s ease-in-out infinite; }
    .glow-pulse { animation: glow-pulse 3s ease-in-out infinite; }
    .leaf-sway { animation: leaf-sway 3s ease-in-out infinite; }
    .grow-up { animation: grow-up 1.5s ease-out forwards; transform-origin: bottom; }
    .fade-in { animation: fade-in 0.8s ease-out forwards; opacity: 0; }
    .fade-in-d1 { animation-delay: 0.2s; }
    .fade-in-d2 { animation-delay: 0.4s; }
    .fade-in-d3 { animation-delay: 0.6s; }
    .fade-in-d4 { animation-delay: 0.8s; }

    .btn-glow:hover {
      box-shadow: 0 0 24px rgba(34,197,94,0.45), 0 8px 32px rgba(34,197,94,0.2);
    }
  </style>
 </head>
 <body class="h-full overflow-auto">

  <!-- PAGE TRANSITION OVERLAY -->
  <div id="page-transition">
    <div id="transition-curtain"></div>
    <!-- Floating leaves during transition -->
    <svg class="transition-leaf" style="left:20%;top:60%;animation-delay:0.1s" viewBox="0 0 32 32"><ellipse cx="16" cy="16" rx="12" ry="8" fill="#22c55e" opacity="0.8" transform="rotate(-30 16 16)"/></svg>
    <svg class="transition-leaf" style="left:50%;top:70%;animation-delay:0.2s" viewBox="0 0 32 32"><ellipse cx="16" cy="16" rx="10" ry="6" fill="#4ade80" opacity="0.7" transform="rotate(20 16 16)"/></svg>
    <svg class="transition-leaf" style="left:75%;top:65%;animation-delay:0.05s" viewBox="0 0 32 32"><ellipse cx="16" cy="16" rx="9" ry="5" fill="#86efac" opacity="0.6" transform="rotate(-50 16 16)"/></svg>
    <svg class="transition-leaf" style="left:35%;top:55%;animation-delay:0.15s" viewBox="0 0 32 32"><ellipse cx="16" cy="16" rx="11" ry="7" fill="#16a34a" opacity="0.8" transform="rotate(10 16 16)"/></svg>
    <div id="transition-logo">
      <svg width="48" height="48" viewBox="0 0 48 48"><ellipse cx="24" cy="44" rx="16" ry="3" fill="#166534" opacity="0.6"/><rect x="21" y="20" width="6" height="24" rx="3" fill="#4a3b20"/><ellipse cx="24" cy="20" rx="16" ry="14" fill="#22c55e" opacity="0.85"/><ellipse cx="24" cy="14" rx="10" ry="9" fill="#4ade80" opacity="0.6"/></svg>
      <span>GrowPlan</span>
    </div>
  </div>

  <div id="app" class="w-full h-full relative" style="background: linear-gradient(180deg, #0a0f0a 0%, #0d1a0d 40%, #122012 70%, #1a2e1a 100%);">
    <div class="absolute top-20 left-1/4 w-64 h-64 rounded-full glow-pulse" style="background: radial-gradient(circle, rgba(34,197,94,0.12) 0%, transparent 70%);"></div>
    <div class="absolute top-40 right-1/4 w-48 h-48 rounded-full glow-pulse" style="background: radial-gradient(circle, rgba(74,222,128,0.08) 0%, transparent 70%); animation-delay: 1.5s;"></div>
    <div class="absolute bottom-32 left-1/3 w-72 h-72 rounded-full glow-pulse" style="background: radial-gradient(circle, rgba(34,197,94,0.06) 0%, transparent 70%); animation-delay: 2s;"></div>
    <div class="absolute top-32 right-1/3 w-2 h-2 rounded-full bg-emerald-400" style="animation: sparkle 4s ease-in-out infinite;"></div>
    <div class="absolute top-48 left-1/5 w-1.5 h-1.5 rounded-full bg-green-300" style="animation: sparkle 5s ease-in-out infinite 1s;"></div>
    <div class="absolute top-64 right-1/4 w-1 h-1 rounded-full bg-emerald-300" style="animation: sparkle 3.5s ease-in-out infinite 2s;"></div>

    <nav class="relative z-10 flex items-center justify-between px-8 md:px-16 py-6 fade-in">
      <div class="flex items-center gap-3">
        <img src="../asset/logo.jpg" alt="Grow Plant Logo" class="w-10 h-10 rounded-xl object-cover shadow-lg border border-emerald-400/30">
        <span class="text-emerald-300 font-semibold text-lg tracking-tight">GrowPlan</span>
      </div>
      <div class="flex items-center gap-3">
        <button id="nav-login" onclick="navigateTo('../auth/login.php')" class="px-5 py-2 text-sm text-emerald-300 border border-emerald-800 rounded-full hover:border-emerald-500 hover:bg-emerald-950/50 transition-all duration-300 cursor-pointer">Login</button>
        <button id="nav-cta" onclick="navigateTo('../auth/register.php')" class="px-5 py-2 text-sm bg-emerald-600 text-white rounded-full hover:bg-emerald-500 transition-all duration-300 shadow-lg shadow-emerald-900/40 cursor-pointer btn-glow">Get Started</button>
      </div>
    </nav>

    <main class="relative z-10 flex flex-col lg:flex-row items-center justify-center px-8 md:px-16 gap-8 lg:gap-16" style="min-height: calc(100% - 88px); padding-bottom: 80px;">
      <div class="flex-1 max-w-xl text-center lg:text-left">
        <h1 id="heading" class="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight fade-in fade-in-d1" style="text-shadow: 0 0 40px rgba(34,197,94,0.2);">Grow Your Productivity</h1>
        <p id="subheading" class="mt-5 text-lg md:text-xl text-emerald-200/70 font-light fade-in fade-in-d2">Complete your tasks and watch your plant grow.</p>
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start fade-in fade-in-d3">
          <button id="btn-cta" onclick="navigateTo('../auth/register.php')" class="px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-green-500 text-white font-medium rounded-full hover:from-emerald-500 hover:to-green-400 transition-all duration-300 shadow-xl shadow-emerald-900/50 hover:shadow-emerald-700/50 hover:-translate-y-0.5 cursor-pointer btn-glow">
            Get Started
          </button>
          <button id="btn-login" onclick="navigateTo('../auth/login.php')" class="px-8 py-3.5 border border-emerald-700/50 text-emerald-300 font-medium rounded-full hover:bg-emerald-950/40 hover:border-emerald-500 transition-all duration-300 cursor-pointer">
            Login
          </button>
        </div>
        <div class="mt-10 flex flex-wrap gap-6 justify-center lg:justify-start fade-in fade-in-d4">
          <div class="flex items-center gap-2 text-emerald-400/70 text-sm"><i data-lucide="check-circle" style="width:16px;height:16px;"></i> <span>Task Management</span></div>
          <div class="flex items-center gap-2 text-emerald-400/70 text-sm"><i data-lucide="trending-up" style="width:16px;height:16px;"></i> <span>Growth Tracking</span></div>
          <div class="flex items-center gap-2 text-emerald-400/70 text-sm"><i data-lucide="zap" style="width:16px;height:16px;"></i> <span>Habit Streaks</span></div>
        </div>
      </div>

      <div class="flex-1 max-w-lg flex items-center justify-center relative">
        <div class="absolute w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(34,197,94,0.15) 0%, transparent 60%);"></div>
        <svg viewBox="0 0 400 450" class="w-full max-w-sm relative z-10 float" fill="none">
          <ellipse cx="200" cy="420" rx="140" ry="20" fill="url(#grassGrad)" opacity="0.8"/>
          <path d="M60 420 Q120 415 200 418 Q280 415 340 420 Q280 425 200 422 Q120 425 60 420Z" fill="#166534" opacity="0.6"/>
          <g class="leaf-sway" style="transform-origin: bottom center;">
            <path d="M100 420 Q102 408 98 400" stroke="#4ade80" stroke-width="1.5" fill="none" stroke-linecap="round"/>
            <path d="M130 418 Q133 405 128 398" stroke="#22c55e" stroke-width="1.2" fill="none" stroke-linecap="round"/>
            <path d="M270 419 Q268 406 272 399" stroke="#4ade80" stroke-width="1.3" fill="none" stroke-linecap="round"/>
            <path d="M300 420 Q297 408 301 401" stroke="#22c55e" stroke-width="1.5" fill="none" stroke-linecap="round"/>
          </g>
          <g class="grow-up">
            <path d="M195 420 Q192 380 194 340 Q196 300 200 260 Q204 300 206 340 Q208 380 205 420Z" fill="url(#trunkGrad)"/>
            <path d="M198 320 Q178 300 160 290" stroke="#365314" stroke-width="3" fill="none" stroke-linecap="round"/>
            <path d="M202 290 Q222 270 240 262" stroke="#365314" stroke-width="2.5" fill="none" stroke-linecap="round"/>
            <path d="M199 350 Q175 340 158 335" stroke="#365314" stroke-width="2" fill="none" stroke-linecap="round"/>
            <path d="M201 260 Q185 240 170 230" stroke="#365314" stroke-width="2" fill="none" stroke-linecap="round"/>
            <path d="M201 260 Q218 242 235 235" stroke="#365314" stroke-width="2" fill="none" stroke-linecap="round"/>
          </g>
          <g class="leaf-sway">
            <ellipse cx="200" cy="220" rx="70" ry="60" fill="url(#leafGrad)" opacity="0.9"/>
            <ellipse cx="170" cy="250" rx="40" ry="35" fill="#166534" opacity="0.7"/>
            <ellipse cx="235" cy="245" rx="38" ry="32" fill="#14532d" opacity="0.6"/>
            <ellipse cx="200" cy="180" rx="45" ry="40" fill="url(#leafGrad2)" opacity="0.8"/>
            <ellipse cx="165" cy="210" rx="30" ry="28" fill="#15803d" opacity="0.5"/>
            <ellipse cx="238" cy="205" rx="28" ry="25" fill="#166534" opacity="0.5"/>
          </g>
          <circle cx="180" cy="210" r="3" fill="#4ade80" opacity="0.8" class="glow-pulse"/>
          <circle cx="220" cy="195" r="2.5" fill="#86efac" opacity="0.7" class="glow-pulse" style="animation-delay:1s;"/>
          <circle cx="200" cy="240" r="2" fill="#4ade80" opacity="0.6" class="glow-pulse" style="animation-delay:2s;"/>
          <circle cx="160" cy="235" r="2" fill="#86efac" opacity="0.5" class="glow-pulse" style="animation-delay:0.5s;"/>
          <g transform="translate(120, 365)">
            <rect x="0" y="0" width="85" height="45" rx="10" fill="#1e293b" stroke="#22c55e" stroke-width="0.5" opacity="0.9"/>
            <text x="10" y="14" font-size="7" fill="#86efac" font-family="Outfit">Today's Tasks</text>
            <rect x="10" y="20" width="8" height="8" rx="2" fill="#22c55e" opacity="0.8"/>
            <text x="22" y="27" font-size="6" fill="#cbd5e1" font-family="Outfit">Morning run ✓</text>
            <rect x="10" y="31" width="8" height="8" rx="2" fill="#334155"/>
            <text x="22" y="38" font-size="6" fill="#94a3b8" font-family="Outfit">Read 20 pages</text>
          </g>
          <g transform="translate(245, 320)" class="float" style="animation-delay: 1s;">
            <rect x="0" y="0" width="80" height="30" rx="8" fill="#1e293b" stroke="#22c55e" stroke-width="0.5" opacity="0.9"/>
            <text x="8" y="13" font-size="6" fill="#86efac" font-family="Outfit">Growth</text>
            <rect x="8" y="17" width="64" height="5" rx="2.5" fill="#0f172a"/>
            <rect x="8" y="17" width="45" height="5" rx="2.5" fill="url(#progressGrad)"/>
            <text x="58" y="22" font-size="5" fill="#4ade80" font-family="Outfit">70%</text>
          </g>
          <defs>
            <linearGradient id="grassGrad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#166534"/>
              <stop offset="100%" stop-color="#14532d"/>
            </linearGradient>
            <linearGradient id="trunkGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#3f3f20"/>
              <stop offset="50%" stop-color="#4a3b20"/>
              <stop offset="100%" stop-color="#3f3f20"/>
            </linearGradient>
            <radialGradient id="leafGrad" cx="50%" cy="50%">
              <stop offset="0%" stop-color="#22c55e" stop-opacity="0.6"/>
              <stop offset="100%" stop-color="#15803d"/>
            </radialGradient>
            <radialGradient id="leafGrad2" cx="50%" cy="40%">
              <stop offset="0%" stop-color="#4ade80" stop-opacity="0.4"/>
              <stop offset="100%" stop-color="#166534"/>
            </radialGradient>
            <linearGradient id="progressGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#22c55e"/>
              <stop offset="100%" stop-color="#4ade80"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
    </main>

    <div class="absolute bottom-0 left-0 w-full h-16 z-0" style="background: linear-gradient(180deg, transparent 0%, rgba(22,101,52,0.3) 60%, rgba(20,83,45,0.5) 100%);"></div>
  </div>

  <script>
    lucide.createIcons();

    function navigateTo(url) {
      const overlay = document.getElementById('page-transition');
      overlay.classList.add('active');
      setTimeout(() => {
        window.location.href = url;
      }, 700);
    }
  </script>
 </body>
</html>
