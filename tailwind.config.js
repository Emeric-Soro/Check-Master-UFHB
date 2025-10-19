/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,js,jsx,ts,tsx,vue,php}",
    "./public/**/*.{html,js,jsx,ts,tsx,vue,php}",
    "./ressources/views/**/*.{html,js,jsx,ts,tsx,vue,php}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#1a5276',
          light: '#2980b9',
          lighter: '#3498db',
        },
        accent: { // Utilisé aussi pour 'success'
          DEFAULT: '#4caf50',
          800: '#2e6b31',
        },
        warning: {
          DEFAULT: '#f39c12',
        },
        danger: {
          DEFAULT: '#e74c3c',
        },
      },
      fontFamily: {
        'poppins': ['Poppins', 'sans-serif'],
        'montserrat': ['Montserrat', 'sans-serif'],
        'sans': ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'elevate': '0 25px 60px -15px rgba(26,82,118,0.25)',
        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
      },
      animation: {
        'fade-in': 'fadeIn 1s ease-in-out',
        'slide-up': 'slideUp 0.8s ease-out',
        'slide-in-left': 'slideInLeft 0.8s ease-out',
        'slide-in-right': 'slideInRight 0.8s ease-out',
        'bounce-in': 'bounceIn 1s ease-out',
        'float': 'float 3s ease-in-out infinite',
        'pulse-slow': 'pulse 3s ease-in-out infinite',
        'spin-slow': 'spin 6s linear infinite',
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(135deg, rgba(26, 82, 118, 0.9) 0%, rgba(41, 128, 185, 0.8) 50%, rgba(52, 152, 219, 0.7) 100%)',
        'card-gradient': 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
        'feature-gradient': 'linear-gradient(135deg, #1a5276 0%, #2980b9 100%)',
      },
    },
  },
  plugins: [require('daisyui')],
  // Configurer daisyUI pour utiliser nos couleurs
  daisyui: {
    themes: [
      {
        mytheme: { // Nom du thème personnalisé
          "primary": "#1a5276",
          "primary-focus": "#2980b9", // Utiliser light pour focus
          "primary-content": "#ffffff",
          "secondary": "#f39c12", // Utiliser warning pour secondary si besoin
          "secondary-focus": "#ca8a04",
          "secondary-content": "#ffffff",
          "accent": "#4caf50",
          "accent-focus": "#2e6b31", // Utiliser accent-800 pour focus
          "accent-content": "#ffffff",
          "neutral": "#3d4451", // Gris foncé pour les éléments neutres
          "neutral-focus": "#2a2e37",
          "neutral-content": "#ffffff",
          "base-100": "#ffffff", // Fond blanc pour les éléments de base
          "base-200": "#f8fafc", // Gris très clair pour les fonds secondaires
          "base-300": "#e2e8f0", // Gris clair pour les bordures/séparateurs
          "base-content": "#1f2937", // Texte sombre par défaut
          "info": "#3abff8", // Couleur info par défaut de DaisyUI
          "success": "#4caf50", // Utiliser notre vert accent
          "warning": "#f39c12", // Utiliser notre orange warning
          "error": "#e74c3c", // Utiliser notre rouge danger
        },
      },
    ],
    darkTheme: "mytheme", // Ou un thème sombre si défini
    base: true, // Applique les styles de base de DaisyUI
    styled: true, // Applique les styles de composants de DaisyUI
    utils: true, // Applique les classes utilitaires de DaisyUI
    logs: false, // Désactiver les logs en production
    rtl: false, // Désactive le mode RTL
    prefix: "", // Pas de préfixe pour les classes DaisyUI
  },
};