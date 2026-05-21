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
  <script src="/_sdk/element_sdk.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet">
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
    .float { animation: float 4s ease-in-out infinite; }
    .glow-pulse { animation: glow-pulse 3s ease-in-out infinite; }
    .leaf-sway { animation: leaf-sway 3s ease-in-out infinite; }
    .grow-up { animation: grow-up 1.5s ease-out forwards; transform-origin: bottom; }
    .fade-in { animation: fade-in 0.8s ease-out forwards; opacity: 0; }
    .fade-in-d1 { animation-delay: 0.2s; }
    .fade-in-d2 { animation-delay: 0.4s; }
    .fade-in-d3 { animation-delay: 0.6s; }
    .fade-in-d4 { animation-delay: 0.8s; }
  </style>
  <style>body { box-sizing: border-box; }</style>
  <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
 </head>
 <body class="h-full overflow-auto">
  <div id="app" class="w-full h-full relative" style="background: linear-gradient(180deg, #0a0f0a 0%, #0d1a0d 40%, #122012 70%, #1a2e1a 100%);"><div class="absolute top-20 left-1/4 w-64 h-64 rounded-full glow-pulse" style="background: radial-gradient(circle, rgba(34,197,94,0.12) 0%, transparent 70%);"></div>
   <div class="absolute top-40 right-1/4 w-48 h-48 rounded-full glow-pulse" style="background: radial-gradient(circle, rgba(74,222,128,0.08) 0%, transparent 70%); animation-delay: 1.5s;"></div>
   <div class="absolute bottom-32 left-1/3 w-72 h-72 rounded-full glow-pulse" style="background: radial-gradient(circle, rgba(34,197,94,0.06) 0%, transparent 70%); animation-delay: 2s;"></div><div class="absolute top-32 right-1/3 w-2 h-2 rounded-full bg-emerald-400" style="animation: sparkle 4s ease-in-out infinite;"></div>
   <div class="absolute top-48 left-1/5 w-1.5 h-1.5 rounded-full bg-green-300" style="animation: sparkle 5s ease-in-out infinite 1s;"></div>
   <div class="absolute top-64 right-1/4 w-1 h-1 rounded-full bg-emerald-300" style="animation: sparkle 3.5s ease-in-out infinite 2s;"></div>
   
   <nav class="relative z-10 flex items-center justify-between px-8 md:px-16 py-6 fade-in">
    <div class="flex items-center gap-3">
     <img src="../asset/logo.jpg" alt="Grow Plant Logo" class="w-10 h-10 rounded-xl object-cover shadow-lg border border-emerald-400/30">
     <span class="text-emerald-300 font-semibold text-lg tracking-tight">GrowPlan</span>
    </div>
    
    <div class="flex items-center gap-3">
     <button id="nav-login" class="px-5 py-2 text-sm text-emerald-300 border border-emerald-800 rounded-full hover:border-emerald-500 hover:bg-emerald-950/50 transition-all duration-300">Login</button> 
     <button id="nav-cta" class="px-5 py-2 text-sm bg-emerald-600 text-white rounded-full hover:bg-emerald-500 transition-all duration-300 shadow-lg shadow-emerald-900/40">Get Started</button>
    </div>
   </nav>

   <main class="relative z-10 flex flex-col lg:flex-row items-center justify-center px-8 md:px-16 gap-8 lg:gap-16" style="min-height: calc(100% - 88px); padding-bottom: 80px;"><div class="flex-1 max-w-xl text-center lg:text-left">
     <h1 id="heading" class="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight fade-in fade-in-d1" style="text-shadow: 0 0 40px rgba(34,197,94,0.2);">Grow Your Productivity</h1>
     <p id="subheading" class="mt-5 text-lg md:text-xl text-emerald-200/70 font-light fade-in fade-in-d2">Complete your task and see the growth of the tree and yourself.</p>
     <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start fade-in fade-in-d3"><button id="btn-cta" class="px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-green-500 text-white font-medium rounded-full hover:from-emerald-500 hover:to-green-400 transition-all duration-300 shadow-xl shadow-emerald-900/50 hover:shadow-emerald-700/50 hover:-translate-y-0.5"> Get Started </button> <button id="btn-login" class="px-8 py-3.5 border border-emerald-700/50 text-emerald-300 font-medium rounded-full hover:bg-emerald-950/40 hover:border-emerald-500 transition-all duration-300"> Login </button>
     </div><div class="mt-10 flex flex-wrap gap-6 justify-center lg:justify-start fade-in fade-in-d4">
      <div class="flex items-center gap-2 text-emerald-400/70 text-sm"><i data-lucide="check-circle" style="width:16px;height:16px;"></i> <span>Task Management</span>
      </div>
      <div class="flex items-center gap-2 text-emerald-400/70 text-sm"><i data-lucide="trending-up" style="width:16px;height:16px;"></i> <span>Growth Tracking</span>
      </div>
      <div class="flex items-center gap-2 text-emerald-400/70 text-sm"><i data-lucide="zap" style="width:16px;height:16px;"></i> <span>Habit Streaks</span>
      </div>
     </div>
    </div><div class="flex-1 max-w-lg flex items-center justify-center relative"><div class="absolute w-80 h-80 rounded-full" style="background: radial-gradient(circle, rgba(34,197,94,0.15) 0%, transparent 60%);"></div><svg viewbox="0 0 400 450" class="w-full max-w-sm relative z-10 float" fill="none"><ellipse cx="200" cy="420" rx="140" ry="20" fill="url(#grassGrad)" opacity="0.8" /> <path d="M60 420 Q120 415 200 418 Q280 415 340 420 Q280 425 200 422 Q120 425 60 420Z" fill="#166534" opacity="0.6" /> <g class="leaf-sway" style="transform-origin: bottom center;">
       <path d="M100 420 Q102 408 98 400" stroke="#4ade80" stroke-width="1.5" fill="none" stroke-linecap="round" />
       <path d="M130 418 Q133 405 128 398" stroke="#22c55e" stroke-width="1.2" fill="none" stroke-linecap="round" />
       <path d="M270 419 Q268 406 272 399" stroke="#4ade80" stroke-width="1.3" fill="none" stroke-linecap="round" />
       <path d="M300 420 Q297 408 301 401" stroke="#22c55e" stroke-width="1.5" fill="none" stroke-linecap="round" />
      </g> <g class="grow-up">
       <path d="M195 420 Q192 380 194 340 Q196 300 200 260 Q204 300 206 340 Q208 380 205 420Z" fill="url(#trunkGrad)" /> <path d="M198 320 Q178 300 160 290" stroke="#365314" stroke-width="3" fill="none" stroke-linecap="round" />
       <path d="M202 290 Q222 270 240 262" stroke="#365314" stroke-width="2.5" fill="none" stroke-linecap="round" />
       <path d="M199 350 Q175 340 158 335" stroke="#365314" stroke-width="2" fill="none" stroke-linecap="round" />
       <path d="M201 260 Q185 240 170 230" stroke="#365314" stroke-width="2" fill="none" stroke-linecap="round" />
       <path d="M201 260 Q218 242 235 235" stroke="#365314" stroke-width="2" fill="none" stroke-linecap="round" />
      </g> <g class="leaf-sway">
       <ellipse cx="200" cy="220" rx="70" ry="60" fill="url(#leafGrad)" opacity="0.9" />
       <ellipse cx="170" cy="250" rx="40" ry="35" fill="#166534" opacity="0.7" />
       <ellipse cx="235" cy="245" rx="38" ry="32" fill="#14532d" opacity="0.6" />
       <ellipse cx="200" cy="180" rx="45" ry="40" fill="url(#leafGrad2)" opacity="0.8" />
       <ellipse cx="165" cy="210" rx="30" ry="28" fill="#15803d" opacity="0.5" />
       <ellipse cx="238" cy="205" rx="28" ry="25" fill="#166534" opacity="0.5" />
      </g> <circle cx="180" cy="210" r="3" fill="#4ade80" opacity="0.8" class="glow-pulse" /> <circle cx="220" cy="195" r="2.5" fill="#86efac" opacity="0.7" class="glow-pulse" style="animation-delay:1s;" /> <circle cx="200" cy="240" r="2" fill="#4ade80" opacity="0.6" class="glow-pulse" style="animation-delay:2s;" /> <circle cx="160" cy="235" r="2" fill="#86efac" opacity="0.5" class="glow-pulse" style="animation-delay:0.5s;" /> <g transform="translate(120, 365)">
       <rect x="20" y="0" width="70" height="45" rx="4" fill="#1e293b" stroke="#334155" stroke-width="1.5" />
       <rect x="25" y="5" width="60" height="35" rx="2" fill="#0f172a" /> <rect x="30" y="10" width="35" height="3" rx="1" fill="#22c55e" opacity="0.8" />
       <rect x="30" y="16" width="50" height="2" rx="1" fill="#475569" />
       <rect x="30" y="21" width="45" height="2" rx="1" fill="#475569" />
       <rect x="30" y="26" width="40" height="2" rx="1" fill="#475569" />
       <circle cx="73" cy="11" r="2" fill="#4ade80" /> <path d="M28 16.5 L29 17.5 L31 15.5" stroke="#22c55e" stroke-width="0.8" fill="none" />
       <path d="M28 21.5 L29 22.5 L31 20.5" stroke="#22c55e" stroke-width="0.8" fill="none" /> <path d="M10 45 L100 45 L95 52 L15 52 Z" fill="#334155" />
      </g> <g transform="translate(245, 320)" class="float" style="animation-delay: 1s;">
       <rect x="0" y="0" width="80" height="30" rx="8" fill="#1e293b" stroke="#22c55e" stroke-width="0.5" opacity="0.9" />
       <text x="8" y="13" font-size="6" fill="#86efac" font-family="Outfit">
        Growth
       </text>
       <rect x="8" y="17" width="64" height="5" rx="2.5" fill="#0f172a" />
       <rect x="8" y="17" width="45" height="5" rx="2.5" fill="url(#progressGrad)" />
       <text x="58" y="22" font-size="5" fill="#4ade80" font-family="Outfit">
        70%
       </text>
      </g> <g style="animation: leaf-sway 4s ease-in-out infinite 0.5s;" class="leaf-sway">
       <path d="M310 280 Q315 272 320 278 Q315 284 310 280Z" fill="#4ade80" opacity="0.5" />
      </g> <g style="animation: leaf-sway 5s ease-in-out infinite 1.5s;" class="leaf-sway">
       <path d="M85 270 Q90 262 95 268 Q90 274 85 270Z" fill="#22c55e" opacity="0.4" />
      </g> <defs>
       <lineargradient id="grassGrad" x1="0%" y1="0%" x2="0%" y2="100%">
        <stop offset="0%" stop-color="#166534" />
        <stop offset="100%" stop-color="#14532d" />
       </lineargradient>
       <lineargradient id="trunkGrad" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#3f3f20" />
        <stop offset="50%" stop-color="#4a3b20" />
        <stop offset="100%" stop-color="#3f3f20" />
       </lineargradient>
       <radialgradient id="leafGrad" cx="50%" cy="50%">
        <stop offset="0%" stop-color="#22c55e" stop-opacity="0.6" />
        <stop offset="100%" stop-color="#15803d" />
       </radialgradient>
       <radialgradient id="leafGrad2" cx="50%" cy="40%">
        <stop offset="0%" stop-color="#4ade80" stop-opacity="0.4" />
        <stop offset="100%" stop-color="#166534" />
       </lineargradient>
       <lineargradient id="progressGrad" x1="0%" y1="0%" x2="100%" y2="0%">
        <stop offset="0%" stop-color="#22c55e" />
        <stop offset="100%" stop-color="#4ade80" />
       </lineargradient>
      </defs>
     </svg>
    </div>
   </main><div class="absolute bottom-0 left-0 w-full h-16 z-0" style="background: linear-gradient(180deg, transparent 0%, rgba(22,101,52,0.3) 60%, rgba(20,83,45,0.5) 100%);"></div>
  </div>
  <script>
    lucide.createIcons();

    const defaultConfig = {
      main_heading: "Grow Your Productivity",
      sub_heading: "Complete your tasks and watch your plant grow.",
      cta_button: "Get Started",
      login_button: "Login",
      background_color: "#0a0f0a",
      surface_color: "#1e293b",
      text_color: "#ffffff",
      primary_action: "#22c55e",
      secondary_action: "#4ade80",
      font_family: "Outfit",
      font_size: 16
    };

    function applyConfig(config) {
      const h = document.getElementById('heading');
      const s = document.getElementById('subheading');
      const bc = document.getElementById('btn-cta');
      const bl = document.getElementById('btn-login');
      const nc = document.getElementById('nav-cta');
      const nl = document.getElementById('nav-login');

      if (h) h.textContent = config.main_heading || defaultConfig.main_heading;
      if (s) s.textContent = config.sub_heading || defaultConfig.sub_heading;
      const ctaText = config.cta_button || defaultConfig.cta_button;
      const loginText = config.login_button || defaultConfig.login_button;
      if (bc) bc.textContent = ctaText;
      if (bl) bl.textContent = loginText;
      if (nc) nc.textContent = ctaText;
      if (nl) nl.textContent = loginText;

      const font = config.font_family || defaultConfig.font_family;
      const baseSize = config.font_size || defaultConfig.font_size;
      document.body.style.fontFamily = `${font}, sans-serif`;
      if (h) h.style.fontSize = `${baseSize * 3.5}px`;
      if (s) s.style.fontSize = `${baseSize * 1.25}px`;
    }

    window.elementSdk.init({
      defaultConfig,
      onConfigChange: async (config) => { applyConfig(config); },
      mapToCapabilities: (config) => ({
        recolorables: [
          { get: () => config.background_color || defaultConfig.background_color, set: (v) => { config.background_color = v; window.elementSdk.setConfig({ background_color: v }); } },
          { get: () => config.surface_color || defaultConfig.surface_color, set: (v) => { config.surface_color = v; window.elementSdk.setConfig({ surface_color: v }); } },
          { get: () => config.text_color || defaultConfig.text_color, set: (v) => { config.text_color = v; window.elementSdk.setConfig({ text_color: v }); } },
          { get: () => config.primary_action || defaultConfig.primary_action, set: (v) => { config.primary_action = v; window.elementSdk.setConfig({ primary_action: v }); } },
          { get: () => config.secondary_action || defaultConfig.secondary_action, set: (v) => { config.secondary_action = v; window.elementSdk.setConfig({ secondary_action: v }); } }
        ],
        borderables: [],
        fontEditable: { get: () => config.font_family || defaultConfig.font_family, set: (v) => { config.font_family = v; window.elementSdk.setConfig({ font_family: v }); } },
        fontSizeable: { get: () => config.font_size || defaultConfig.font_size, set: (v) => { config.font_size = v; window.elementSdk.setConfig({ font_size: v }); } }
      }),
      mapToEditPanelValues: (config) => new Map([
        ["main_heading", config.main_heading || defaultConfig.main_heading],
        ["sub_heading", config.sub_heading || defaultConfig.sub_heading],
        ["cta_button", config.cta_button || defaultConfig.cta_button],
        ["login_button", config.login_button || defaultConfig.login_button]
      ])
    });
  </script>
 <script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'9fe3507aa76ece73',t:'MTc3OTE5NTkzOS4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
