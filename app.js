// Theme toggle logic
const themeToggle = document.getElementById('theme-toggle');
const themeIcon = document.getElementById('theme-icon');
const logoImg = document.getElementById('logo-img');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

function setTheme(theme) {
  if (theme === 'dark') {
    document.documentElement.classList.add('dark');
    // Only switch logo on landing/login views
    if ([
      'landing',
      'agency-login',
      'client-login'
    ].includes(currentView)) {
      logoImg.src = 'assets/logo-dark.png';
    }
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    if ([
      'landing',
      'agency-login',
      'client-login'
    ].includes(currentView)) {
      logoImg.src = 'assets/logo-light.png';
    }
    localStorage.setItem('theme', 'light');
  }
}

function toggleTheme() {
  const isDark = document.documentElement.classList.contains('dark');
  setTheme(isDark ? 'light' : 'dark');
}

themeToggle.addEventListener('click', toggleTheme);

// On load, set theme from localStorage or system
const savedTheme = localStorage.getItem('theme');
if (savedTheme) {
  setTheme(savedTheme);
} else {
  setTheme(prefersDark ? 'dark' : 'light');
}

// Navbar mobile menu
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
mobileMenuBtn.addEventListener('click', () => {
  mobileMenu.classList.toggle('hidden');
});

// View switching logic
const views = [
  'landing',
  'agency-login',
  'client-login',
  'agency-dashboard',
  'client-portal',
  'template-store',
  'scraped-data',
  'contact',
];

let currentView = 'landing';

function showView(view) {
  currentView = view;
  views.forEach(v => {
    const section = document.getElementById(`view-${v}`);
    if (section) {
      section.classList.add('hidden');
      section.classList.remove('active');
    }
  });
  const activeSection = document.getElementById(`view-${view}`);
  if (activeSection) {
    activeSection.classList.remove('hidden');
    activeSection.classList.add('active');
  }
  // Navbar visibility: hide on landing/login, show otherwise
  const navbar = document.getElementById('navbar');
  if (view === 'landing') {
    navbar.classList.add('hidden');
  } else {
    navbar.classList.remove('hidden');
  }
  // Logo logic: always light logo on account pages
  if ([
    'agency-dashboard',
    'client-portal',
    'template-store',
    'scraped-data',
    'contact'
  ].includes(view)) {
    logoImg.src = 'assets/logo-light.png';
  } else {
    // Use theme-based logo for landing/login
    if (document.documentElement.classList.contains('dark')) {
      logoImg.src = 'assets/logo-dark.png';
    } else {
      logoImg.src = 'assets/logo-light.png';
    }
  }
  // Hide mobile menu after navigation
  if (!mobileMenu.classList.contains('hidden')) {
    mobileMenu.classList.add('hidden');
  }
}

// Navbar link navigation
const navLinks = document.querySelectorAll('.nav-link');
navLinks.forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    const view = link.getAttribute('data-view');
    showView(view);
  });
});

// Landing page buttons
const landingBtns = document.querySelectorAll('#view-landing [data-view]');
landingBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    showView(btn.getAttribute('data-view'));
  });
});

// Add unified login form simulation
const unifiedLoginForm = document.getElementById('unified-login-form');
if (unifiedLoginForm) {
  unifiedLoginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    showView('agency-dashboard');
  });
}

// Dev-only switch for client/agency view
const devSwitchAgency = document.getElementById('dev-switch-agency');
if (devSwitchAgency) {
  devSwitchAgency.classList.remove('hidden');
  devSwitchAgency.addEventListener('click', () => {
    showView('agency-dashboard');
  });
}

// Contact form logic
const contactForm = document.getElementById('contact-form');
const contactSuccess = document.getElementById('contact-success');
if (contactForm) {
  contactForm.addEventListener('submit', (e) => {
    e.preventDefault();
    // Validate required fields
    let valid = true;
    contactForm.querySelectorAll('input, textarea, select').forEach(input => {
      if (!input.value) {
        input.classList.add('ring-2', 'ring-red-400');
        valid = false;
      } else {
        input.classList.remove('ring-2', 'ring-red-400');
      }
    });
    if (!valid) return;
    contactForm.classList.add('hidden');
    contactSuccess.classList.remove('hidden');
  });
}

// Show landing page by default
showView('landing'); 