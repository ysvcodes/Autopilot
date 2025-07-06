<?php
session_start();
$signup_success = '';
$signup_error = '';
$final_message = '';
$clear_form = false;
$isSignupAttempt = false;
// Only process and set session on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    require_once __DIR__ . '/database_connection/connection.php';
    $first = trim($_POST['signup_first_name'] ?? '');
    $last = trim($_POST['signup_last_name'] ?? '');
    $email = trim($_POST['signup_email'] ?? '');
    $pass  = $_POST['signup_password'] ?? '';
    $confirm = $_POST['signup_confirm_password'] ?? '';
    if (empty($first) || empty($last) || empty($email) || empty($pass) || empty($confirm)) {
        $signup_error = 'All fields are required.';
        $clear_form = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_error = 'Invalid email address.';
        $clear_form = true;
    } elseif ($pass !== $confirm) {
        $signup_error = 'Passwords do not match.';
        $clear_form = true;
    } else {
        // Store password in plain text (not recommended for production)
        try {
            $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)');
            $stmt->execute([$first, $last, $email, $pass]);
            $signup_success = 'Account created successfully! You can now log in.';
            $clear_form = true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $signup_error = 'Email already exists.';
            } else {
                $signup_error = 'Database error: ' . $e->getMessage();
            }
            $clear_form = true;
        }
    }
    // Only set session and redirect if there is a message to show
    if ($signup_success || $signup_error) {
        $_SESSION['signup_success'] = $signup_success;
        $_SESSION['signup_error'] = $signup_error;
        $_SESSION['clear_form'] = $clear_form;
        $_SESSION['isSignupAttempt'] = true;
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}
// On GET, show notification only if session flag is set, then clear it
if (isset($_SESSION['isSignupAttempt']) && $_SESSION['isSignupAttempt'] && 
    ((isset($_SESSION['signup_success']) && $_SESSION['signup_success']) || (isset($_SESSION['signup_error']) && $_SESSION['signup_error']))) {
    $signup_success = $_SESSION['signup_success'] ?? '';
    $signup_error = $_SESSION['signup_error'] ?? '';
    $clear_form = $_SESSION['clear_form'] ?? false;
    $isSignupAttempt = true;
    unset($_SESSION['signup_success'], $_SESSION['signup_error'], $_SESSION['clear_form'], $_SESSION['isSignupAttempt']);
}
// Admin login 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require_once __DIR__ . '/database_connection/connection.php';
    $login_email = trim($_POST['login_email'] ?? '');
    $login_password = $_POST['login_password'] ?? '';
    if (strpos($login_email, '@') === false) {
        // Admin/internal login by name
        $stmt = $pdo->prepare('SELECT * FROM internal WHERE name = ?');
        $stmt->execute([$login_email]);
        $user = $stmt->fetch();
        if ($user && password_verify($login_password, $user['password_hash'])) {
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: admin.php');
            exit();
        }
    } else {
        // Agency login by email
        $stmt = $pdo->prepare('SELECT * FROM agency_admins WHERE email = ?');
        $stmt->execute([$login_email]);
        $agency = $stmt->fetch();
        if ($agency && password_verify($login_password, $agency['password_hash'])) {
            $_SESSION['agency_id'] = $agency['agency_id'];
            $_SESSION['agency_admin_id'] = $agency['id'];
            $_SESSION['agency_name'] = $agency['agency_name'] ?? '';
            $_SESSION['role'] = $agency['role'];
            header('Location: agencyspanel.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AutoPilot</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .logo-large {
      max-width: 100%;
      max-height: 24rem;
      height: auto;
      width: auto;
      display: block;
    }
    .logo-gap {
      margin-bottom: 1.5rem;
    }
    .right-section-bg {
      background: linear-gradient(135deg, #edebe7 0%, #e3e8f0 100%);
    }
    .learn-btn, .sign-in-btn {
      background: linear-gradient(90deg, #1397d4 0%, #0769b0 100%);
      color: #fff;
      transition: background 0.2s, transform 0.15s, box-shadow 0.15s;
    }
    .learn-btn:hover, .sign-in-btn:hover {
      background: linear-gradient(90deg, #0d5e8c 0%, #05406b 100%);
      transform: scale(1.04);
      box-shadow: 0 6px 24px 0 rgba(19, 151, 212, 0.15);
    }
    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0; }
    }
    #typewriter-cursor {
      animation: blink 1s steps(1) infinite;
      font-weight: bold;
      font-size: 1em;
    }
    #create-account-section.show {
      display: flex !important;
    }
    #create-account-modal.show {
      opacity: 1 !important;
      transform: translateY(0) !important;
    }
    #create-account-modal.hide {
      opacity: 0 !important;
      transform: translateY(40px) !important;
      transition: opacity 0.3s, transform 0.3s;
    }
  </style>
</head>
<body class="min-h-screen">
<?php if (!empty($final_message)): ?>
  <div id="final-message-container">
    <?= $final_message ?>
  </div>
<?php endif; ?>
  <div class="flex flex-col md:flex-row min-h-screen">
    <!-- Left: Login -->
    <div class="flex-1 flex flex-col justify-center items-center pt-6" style="background-color: #001322;">
      <div class="w-full max-w-md mx-auto">
        <div class="flex flex-col items-center mb-8 mt-8">
          <img src="assets/autoGT.png" alt="autoGT Logo" class="logo-large logo-gap" style="max-width: 320px; max-height: 180px; margin-bottom: 1.5rem;" />
          <h1 class="text-3xl md:text-4xl font-extrabold" style="background: linear-gradient(90deg, #1397d4 0%, #0769b0 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent;"><span id="typewriter"></span></h1>
          <p class="text-center mb-2" style="color: #eeecea; opacity: 0.8;">Sign in to your account to manage your automations</p>
        </div>
        <form class="flex flex-col gap-4" method="POST" action="" id="login-form">
          <div>
            <label class="block text-slate-200 font-semibold mb-1">Email</label>
            <input type="text" name="login_email" id="login_email" placeholder="Email" required class="px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 w-full focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" />
          </div>
          <div>
            <label class="block text-slate-200 font-semibold mb-1">Password</label>
            <input type="password" name="login_password" id="login_password" placeholder="Password" required class="px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 w-full focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" />
          </div>
          <p class="text-xs text-slate-400">For demo, use: john@example.com (any password)</p>
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
              <input id="remember" type="checkbox" class="rounded border-slate-700 text-blue-600 focus:ring-blue-500" />
              <label for="remember" class="text-slate-300">Remember me</label>
            </div>
            <a href="#" class="text-blue-400 hover:underline font-medium">Forgot your password?</a>
          </div>
          <button type="submit" name="login" class="mt-2 px-8 py-3 rounded-lg font-bold text-lg shadow transition-all sign-in-btn">Sign in</button>
          <p class="text-center text-slate-400 text-sm mt-2">Don't have an account? <a href="#" class="text-blue-400 hover:underline font-medium">Create an account</a></p>
          <div class="flex items-center my-4">
            <div class="flex-grow border-t border-slate-700"></div>
            <span class="mx-4 text-slate-500 text-sm">Or continue with</span>
            <div class="flex-grow border-t border-slate-700"></div>
          </div>
          <div class="flex gap-4">
            <button type="button" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-800 font-semibold flex items-center justify-center gap-2 hover:bg-slate-300 transition-all items-center min-h-12">
              <svg class="h-5 w-5" viewBox="0 0 48 48"><g><path fill="#EA4335" d="M24 9.5c3.54 0 6.44 1.22 8.47 3.23l6.32-6.32C34.64 2.99 29.74 1 24 1 14.82 1 6.98 6.99 3.13 15.19l7.36 5.72C12.2 15.1 17.61 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.64-.15-3.22-.43-4.74H24v9.24h12.42c-.54 2.9-2.18 5.36-4.66 7.02l7.36 5.72C43.02 37.01 46.1 31.25 46.1 24.5z"/><path fill="#FBBC05" d="M10.49 28.91c-1.13-2.09-1.78-4.47-1.78-7.01s.65-4.92 1.78-7.01l-7.36-5.72C2.01 13.25 1 18.67 1 24.5s1.01 11.25 2.13 15.33l7.36-5.72z"/><path fill="#34A853" d="M24 46c5.74 0 10.64-1.99 14.47-5.23l-7.36-5.72c-2.01 1.36-4.56 2.15-7.11 2.15-6.39 0-11.8-5.6-13.51-13.19l-7.36 5.72C6.98 41.01 14.82 46 24 46z"/></g></svg>
              Google
            </button>
            <button type="button" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-800 font-semibold flex items-center justify-center gap-2 hover:bg-slate-300 transition-all items-center min-h-12">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0h-21.35C.595 0 0 .592 0 1.326v21.348C0 23.408.595 24 1.325 24h11.495v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.797.143v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.406 24 24 23.408 24 22.674V1.326C24 .592 23.406 0 22.675 0"/></svg>
              Facebook
            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- Right: Features -->
    <div class="flex-1 flex flex-col justify-center items-center relative right-section-bg px-8 py-12 pt-6 overflow-hidden" style="background: #f4f1ef;">
      <div class="w-full max-w-lg mx-auto relative z-10 mt-8">
        <img src="assets/autoGT.png" alt="autoGT Logo" class="logo-large logo-gap" style="max-width: 320px; max-height: 180px; margin-bottom: 1.5rem;" />
        <h2 class="text-3xl md:text-4xl font-extrabold mb-8" style="background: linear-gradient(90deg, #1397d4 0%, #0769b0 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent;"><span id="typewriter2"></span><span id="typewriter-cursor" style="display:inline-block;width:1ch;">|</span></h2>
        <div class="flex flex-col mb-8">
          <div class="flex items-start gap-4 mb-6">
            <div class="rounded-xl p-3 flex items-center justify-center" style="background: linear-gradient(90deg, #1397d4 0%, #0769b0 100%); width: 56px; height: 56px;">
              <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
              <h3 class="text-xl font-bold" style="color: #0769b0;">Powerful Automations</h3>
              <p class="text-[#001321]/90">Create and manage automated workflows with our intuitive interface</p>
            </div>
          </div>
          <div class="flex items-start gap-4 mb-6">
            <div class="rounded-xl p-3 flex items-center justify-center" style="background: linear-gradient(90deg, #1397d4 0%, #0769b0 100%); width: 56px; height: 56px;">
              <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12h18M12 3v18"/></svg>
            </div>
            <div>
              <h3 class="text-xl font-bold" style="color: #0769b0;">Real-time Monitoring</h3>
              <p class="text-[#001321]/90">Track performance and get insights about your automations</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="rounded-xl p-3 flex items-center justify-center" style="background: linear-gradient(90deg, #1397d4 0%, #0769b0 100%); width: 56px; height: 56px;">
              <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 8-4 8-4s8 0 8 4"/></svg>
            </div>
            <div>
              <h3 class="text-xl font-bold" style="color: #0769b0;">Team Collaboration</h3>
              <p class="text-[#001321]/90">Work together with your team to build and manage automations</p>
            </div>
          </div>
        </div>
        <button class="mt-2 px-8 py-3 rounded-lg font-semibold learn-btn">
          Learn more about our features <span class="ml-1">&rarr;</span>
        </button>
      </div>
    </div>
  </div>
  <!-- Create Account Modal/Page -->
  <div id="create-account-section" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-70 z-50 hidden">
    <div id="create-account-modal" class="bg-[#0a1a2f] rounded-2xl shadow-lg p-8 w-full max-w-md flex flex-col items-center opacity-0 translate-y-8 transition-all duration-300">
      <!-- <img src="assets/autoGT.png" alt="AutoPilot Logo" class="logo-large logo-gap" style="max-width: 100px; margin-bottom: 1.5rem;" /> -->
      <h2 class="text-2xl font-extrabold text-white mb-1">Create an account</h2>
      <p class="text-slate-300 mb-6">Sign up to start using AutoPilot</p>
      <?php if (!empty($signup_success)): ?>
        <div id="fade-message" class="success" style="color: green; background: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 10px; text-align:center; transition: opacity 0.8s, transform 0.8s;">
          <?= htmlspecialchars($signup_success) ?>
        </div>
      <?php elseif (!empty($signup_error)): ?>
        <div id="fade-message" class="error" style="color: red; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 10px; text-align:center; transition: opacity 0.8s, transform 0.8s;">
          <?= htmlspecialchars($signup_error) ?>
        </div>
      <?php endif; ?>
      <form class="w-full flex flex-col gap-4" method="POST" action="" id="signup-form">
        <div>
          <label class="block text-slate-200 font-semibold mb-1">First name</label>
          <input type="text" name="signup_first_name" placeholder="First name" required class="w-full px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" value="<?= htmlspecialchars($_POST['signup_first_name'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-slate-200 font-semibold mb-1">Last name</label>
          <input type="text" name="signup_last_name" placeholder="Last name" required class="w-full px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" value="<?= htmlspecialchars($_POST['signup_last_name'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-slate-200 font-semibold mb-1">Email</label>
          <input type="email" name="signup_email" placeholder="Email" required class="w-full px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" value="<?= htmlspecialchars($_POST['signup_email'] ?? '') ?>" />
        </div>
        <div>
          <label class="block text-slate-200 font-semibold mb-1">Password</label>
          <input type="password" name="signup_password" placeholder="Password" required class="w-full px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" />
        </div>
        <div>
          <label class="block text-slate-200 font-semibold mb-1">Confirm Password</label>
          <input type="password" name="signup_confirm_password" placeholder="Confirm password" required class="w-full px-4 py-3 rounded-lg border border-slate-700 bg-[#232f3e] text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all" />
        </div>
        <button type="submit" name="signup" class="mt-2 px-8 py-3 rounded-lg font-bold text-lg shadow transition-all sign-in-btn">Sign up</button>
      </form>
      <p class="text-slate-400 text-sm mt-4">By creating an account, you agree to our <a href="#" class="text-blue-400 hover:underline">Terms of Service</a></p>
      <div class="flex items-center my-6 w-full">
        <div class="flex-grow border-t border-slate-700"></div>
        <span class="mx-4 text-slate-500 text-sm">Or sign up with</span>
        <div class="flex-grow border-t border-slate-700"></div>
      </div>
      <div class="flex gap-4 w-full">
        <button type="button" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-800 font-semibold flex items-center justify-center gap-2 hover:bg-slate-300 transition-all items-center min-h-12">
          <svg class="h-5 w-5" viewBox="0 0 48 48"><g><path fill="#EA4335" d="M24 9.5c3.54 0 6.44 1.22 8.47 3.23l6.32-6.32C34.64 2.99 29.74 1 24 1 14.82 1 6.98 6.99 3.13 15.19l7.36 5.72C12.2 15.1 17.61 9.5 24 9.5z"/><path fill="#4285F4" d="M46.1 24.5c0-1.64-.15-3.22-.43-4.74H24v9.24h12.42c-.54 2.9-2.18 5.36-4.66 7.02l7.36 5.72C43.02 37.01 46.1 31.25 46.1 24.5z"/><path fill="#FBBC05" d="M10.49 28.91c-1.13-2.09-1.78-4.47-1.78-7.01s.65-4.92 1.78-7.01l-7.36-5.72C2.01 13.25 1 18.67 1 24.5s1.01 11.25 2.13 15.33l7.36-5.72z"/><path fill="#34A853" d="M24 46c5.74 0 10.64-1.99 14.47-5.23l-7.36-5.72c-2.01 1.36-4.56 2.15-7.11 2.15-6.39 0-11.8-5.6-13.51-13.19l-7.36 5.72C6.98 41.01 14.82 46 24 46z"/></g></svg>
          Google
        </button>
        <button type="button" class="flex-1 px-4 py-2 rounded-lg border border-slate-200 bg-white text-slate-800 font-semibold flex items-center justify-center gap-2 hover:bg-slate-300 transition-all items-center min-h-12">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M22.675 0h-21.35C.595 0 0 .592 0 1.326v21.348C0 23.408.595 24 1.325 24h11.495v-9.294H9.692v-3.622h3.128V8.413c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.797.143v3.24l-1.918.001c-1.504 0-1.797.715-1.797 1.763v2.313h3.587l-.467 3.622h-3.12V24h6.116C23.406 24 24 23.408 24 22.674V1.326C24 .592 23.406 0 22.675 0"/></svg>
          Facebook
        </button>
      </div>
      <button id="close-create-account" class="mt-6 text-slate-400 hover:text-white text-sm">Cancel</button>
    </div>
  </div>
  <!-- Notification (side, bottom right) -->
  <div id="side-notification" style="display:none; position:fixed; bottom:40px; right:40px; min-width:260px; max-width:340px; z-index:9999; border-radius:10px; box-shadow:0 2px 12px rgba(0,0,0,0.12); padding:16px 24px 32px 20px; font-size:1rem; font-weight:500; opacity:0; transition:opacity 0.5s, transform 0.5s; background:#fff; color:#222;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
      <div style="display:flex; align-items:center; gap:8px;">
        <span id="side-notification-icon" style="font-size:1.3em;">🔔</span>
        <span style="font-size:0.98em; font-weight:600; opacity:0.7;">Sign up status</span>
      </div>
      <button id="side-notification-close" style="background:none; border:none; font-size:1.1em; color:#888; cursor:pointer; margin-left:8px;">&times;</button>
    </div>
    <span id="side-notification-text" style="display:block; font-size:0.98em; margin-bottom:2px;"></span>
    <div id="side-notification-timer" style="position:absolute; left:0; bottom:0; height:6px; width:100%; background:rgba(0,0,0,0.07); border-radius:0 0 10px 10px; overflow:hidden;">
      <div id="side-notification-timer-bar" style="height:100%; width:0; background:#888; transition:width 7s linear;"></div>
    </div>
  </div>
  <script>
    // Remember Me functionality
    document.addEventListener('DOMContentLoaded', function() {
      const loginForm = document.getElementById('login-form');
      const emailInput = document.getElementById('login_email');
      const passwordInput = document.getElementById('login_password');
      const rememberCheckbox = document.getElementById('remember');
      
      // Load saved credentials on page load
      const savedEmail = localStorage.getItem('autopilot_remember_email');
      const savedPassword = localStorage.getItem('autopilot_remember_password');
      const rememberMe = localStorage.getItem('autopilot_remember_me');
      
      if (savedEmail && savedPassword && rememberMe === 'true') {
        emailInput.value = savedEmail;
        passwordInput.value = savedPassword;
        rememberCheckbox.checked = true;
      }
      
      // Save credentials when form is submitted
      if (loginForm) {
        loginForm.addEventListener('submit', function() {
          if (rememberCheckbox.checked) {
            localStorage.setItem('autopilot_remember_email', emailInput.value);
            localStorage.setItem('autopilot_remember_password', passwordInput.value);
            localStorage.setItem('autopilot_remember_me', 'true');
          } else {
            // Clear saved credentials if checkbox is unchecked
            localStorage.removeItem('autopilot_remember_email');
            localStorage.removeItem('autopilot_remember_password');
            localStorage.removeItem('autopilot_remember_me');
          }
        });
      }
      
      // Clear credentials when checkbox is unchecked
      if (rememberCheckbox) {
        rememberCheckbox.addEventListener('change', function() {
          if (!this.checked) {
            localStorage.removeItem('autopilot_remember_email');
            localStorage.removeItem('autopilot_remember_password');
            localStorage.removeItem('autopilot_remember_me');
          }
        });
      }
    });

    // Typewriter effect
    document.addEventListener('DOMContentLoaded', function() {
      const text1 = 'Welcome to AutoPilot';
      const text2 = 'Automate your workflow\nwith powerful tools!';
      const el1 = document.getElementById('typewriter');
      const el2 = document.getElementById('typewriter2');
      const cursor = document.getElementById('typewriter-cursor');
      let i = 0, j = 0;
      cursor.style.visibility = 'hidden';
      function type1() {
        if (i <= text1.length) {
          el1.textContent = text1.slice(0, i);
          i++;
          setTimeout(type1, 70);
        } else {
          setTimeout(type2, 400);
        }
      }
      function type2() {
        cursor.style.visibility = 'visible';
        if (j <= text2.length) {
          el2.innerHTML = text2.slice(0, j).replace(/\n/g, '<br/>');
          j++;
          setTimeout(type2, 70);
        } else {
          cursor.style.visibility = 'visible';
        }
      }
      type1();
    });
    // Show create account section
    document.addEventListener('DOMContentLoaded', function() {
      var createAccountBtn = document.querySelector('p.text-center.text-slate-400.text-sm.mt-2 a');
      var createAccountSection = document.getElementById('create-account-section');
      var closeCreateAccount = document.getElementById('close-create-account');
      var createAccountModal = document.getElementById('create-account-modal');
      if (createAccountBtn && createAccountSection && closeCreateAccount && createAccountModal) {
        createAccountBtn.addEventListener('click', function(e) {
          e.preventDefault();
          createAccountSection.classList.remove('hidden');
          createAccountModal.classList.remove('hide');
          setTimeout(function() {
            createAccountModal.classList.add('show');
          }, 10);
        });
        function hideModal() {
          createAccountModal.classList.remove('show');
          createAccountModal.classList.add('hide');
          setTimeout(function() {
            createAccountSection.classList.add('hidden');
          }, 300);
        }
        closeCreateAccount.addEventListener('click', function() {
          hideModal();
        });
        // Close modal
        createAccountSection.addEventListener('click', function(e) {
          if (e.target === createAccountSection) {
            hideModal();
          }
        });
      }
    });
    // Fade out sign-up message and show persistent message at top
    window.addEventListener('DOMContentLoaded', function() {
      var fadeMsg = document.getElementById('fade-message');
      if (fadeMsg) {
        setTimeout(function() {
          fadeMsg.style.opacity = '0';
          fadeMsg.style.transform = 'translateY(30px)';
          setTimeout(function() {
            fadeMsg.style.display = 'none';
            // Optionally scroll to top to show the persistent message
            var finalMsg = document.getElementById('final-message-container');
            if (finalMsg) {
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }
          }, 900);
        }, 1800);
      }
    });
    // Show side notification and clear form fields after sign-up
    window.addEventListener('DOMContentLoaded', function() {
      var notif = document.getElementById('side-notification');
      var notifText = document.getElementById('side-notification-text');
      var notifIcon = document.getElementById('side-notification-icon');
      var notifClose = document.getElementById('side-notification-close');
      var notifTimerBar = document.getElementById('side-notification-timer-bar');
      var signupForm = document.getElementById('signup-form');
      var signupSuccess = <?php echo json_encode($signup_success); ?>;
      var signupError = <?php echo json_encode($signup_error); ?>;
      var clearForm = <?php echo json_encode($clear_form); ?>;
      var isSignupAttempt = <?php echo $isSignupAttempt ? 'true' : 'false'; ?>;
      var notifTimeout = null;

      // Only show notification if there was a sign-up POST attempt AND there is a non-empty message
      if (isSignupAttempt && ((signupSuccess && signupSuccess.length > 0) || (signupError && signupError.length > 0)) && notif && notifText && (typeof clearForm !== 'undefined')) {
        notifText.textContent = signupSuccess || signupError;
        notif.style.background = signupSuccess ? '#d4edda' : '#f8d7da';
        notif.style.color = signupSuccess ? 'green' : 'red';
        if (notifIcon) notifIcon.textContent = signupSuccess ? '✅' : '❌';
        notif.style.opacity = '1';
        notif.style.display = 'block';
        notif.style.transform = 'translateY(0)';
        if (notifTimerBar) {
          notifTimerBar.style.background = signupSuccess ? '#4bb543' : '#e3342f';
          notifTimerBar.style.width = '0';
          setTimeout(function() {
            notifTimerBar.style.width = '100%';
          }, 50);
        }
        notifTimeout = setTimeout(function() {
          notif.style.opacity = '0';
          notif.style.transform = 'translateY(30px)';
          setTimeout(function() {
            notif.style.display = 'none';
            if (notifTimerBar) notifTimerBar.style.width = '0';
          }, 600);
        }, 7000);
      }
      // Close button handler
      if (notifClose) {
        notifClose.onclick = function() {
          notif.style.opacity = '0';
          notif.style.transform = 'translateY(30px)';
          setTimeout(function() {
            notif.style.display = 'none';
            if (notifTimerBar) notifTimerBar.style.width = '0';
          }, 600);
          if (notifTimeout) clearTimeout(notifTimeout);
        };
      }
      // Clear form fields after submit
      if (isSignupAttempt && clearForm && signupForm) {
        setTimeout(function() {
          signupForm.reset();
          var inputs = signupForm.querySelectorAll('input');
          inputs.forEach(function(input) { input.value = ''; });
        }, 100);
      }
    });
  </script>
</body>
</html> 