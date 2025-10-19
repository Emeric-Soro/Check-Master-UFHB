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
        accent: {
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
    },
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: [
      {
        mytheme: {
          "primary": "#1a5276",
          "primary-focus": "#2980b9",
          "primary-content": "#ffffff",
          "secondary": "#f39c12",
          "secondary-focus": "#ca8a04",
          "secondary-content": "#ffffff",
          "accent": "#4caf50",
          "accent-focus": "#2e6b31",
          "accent-content": "#ffffff",
          "neutral": "#3d4451",
          "neutral-focus": "#2a2e37",
          "neutral-content": "#ffffff",
          "base-100": "#ffffff",
          "base-200": "#f8fafc",
          "base-300": "#e2e8f0",
          "base-content": "#1f2937",
          "info": "#3abff8",
          "success": "#4caf50",
          "warning": "#f39c12",
          "error": "#e74c3c",
        },
      },
    ],
    darkTheme: "mytheme",
    base: true,
    styled: true,
    utils: true,
    logs: false,
    rtl: false,
    prefix: "",
  },
};