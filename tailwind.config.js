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
      keyframes: {
        fadeIn: {
          'from': { opacity: '0' },
          'to': { opacity: '1' },
        },
        slideUp: {
          'from': { opacity: '0', transform: 'translateY(50px)' },
          'to': { opacity: '1', transform: 'translateY(0)' },
        },
        slideInLeft: {
          'from': { opacity: '0', transform: 'translateX(-50px)' },
          'to': { opacity: '1', transform: 'translateX(0)' },
        },
        slideInRight: {
          'from': { opacity: '0', transform: 'translateX(50px)' },
          'to': { opacity: '1', transform: 'translateX(0)' },
        },
        bounceIn: {
          '0%': { opacity: '0', transform: 'scale(0.3)' },
          '50%': { opacity: '1', transform: 'scale(1.05)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-20px)' },
        },
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(135deg, rgba(26, 82, 118, 0.9) 0%, rgba(41, 128, 185, 0.8) 50%, rgba(52, 152, 219, 0.7) 100%)',
        'card-gradient': 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
        'feature-gradient': 'linear-gradient(135deg, #1a5276 0%, #2980b9 100%)',
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