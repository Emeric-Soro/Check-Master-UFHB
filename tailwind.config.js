/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./public/**/*.{php,html,js}",
    "./ressources/views/**/*.{php,html,js}",
    "./app/**/*.{php,html,js}",
    "./index.php",
  ],
  theme: {
    extend: {
      colors: {
        // Custom color palette for the academic management system
        'academic-blue': {
          50: '#eaf2f8',
          100: '#d4e6f1',
          200: '#a9cce3',
          300: '#a9cce3',
          400: '#5499c7',
          500: '#2980b9',
          600: '#2471a3',
          700: '#1f618d',
          800: '#1a5276',
          900: '#154360',
        },
        'academic-green': {
          50: '#eafaf1',
          100: '#e8f5e9',
          200: '#7ddc80',
          300: '#7ddc80',
          400: '#449d48',
          500: '#4caf50',
          600: '#449d48',
          700: '#388e3c',
          800: '#2e6b31',
          900: '#2e6b31',
        },
        'academic-orange': {
          50: '#fff4e6',
          100: '#fff4e6',
          200: '#fae5d3',
          300: '#f5cba7',
          400: '#f0b27a',
          500: '#ff8c00',
          600: '#d35400',
          700: '#af600f',
          800: '#7e450b',
          900: '#7e450b',
        },
        'academic-red': {
          50: '#fdedec',
          100: '#fdedec',
          200: '#fadbd8',
          300: '#f5b7b1',
          400: '#f1948a',
          500: '#e74c3c',
          600: '#cb4335',
          700: '#b03a2e',
          800: '#78281f',
          900: '#78281f',
        },
        'academic-cyan': {
          100: '#e8f8f5',
          200: '#d1f2eb',
        },
        'bg-main': '#dff2ff',
      },
      backgroundImage: {
        'academic-gradient': 'linear-gradient(135deg, #1a5276 0%, #2980b9 100%)',
        'gradient-purple': 'linear-gradient(135deg, #1a5276 0%, #2980b9 100%)',
        'gradient-blue': 'linear-gradient(135deg, #3498db 0%, #2980b9 100%)',
        'gradient-red': 'linear-gradient(135deg, #ec7063 0%, #e74c3c 100%)',
        'gradient-orange': 'linear-gradient(135deg, #f39c12 0%, #ff8c00 100%)',
        'gradient-green': 'linear-gradient(135deg, #4caf50 0%, #388e3c 100%)',
        'animated-bg': 'linear-gradient(-45deg, #eaf2f8, #f3f4f6, #eaf2f8, #d4e6f1)',
      },
      keyframes: {
        floating: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-15px)' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        gradient: {
          '0%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' },
          '100%': { backgroundPosition: '0% 50%' },
        },
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        modalFade: {
          '0%': { opacity: '0', transform: 'translateY(-30px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        modalFadeIn: {
          '0%': { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        modalFadeOut: {
          '0%': { opacity: '1', transform: 'scale(1)' },
          '100%': { opacity: '0', transform: 'scale(0.95)' },
        },
        pulse: {
          '0%, 100%': { transform: 'scale(1)', opacity: '1' },
          '50%': { transform: 'scale(1.05)', opacity: '0.8' },
        },
      },
      animation: {
        'floating': 'floating 6s ease-in-out infinite',
        'float': 'float 3s ease-in-out infinite',
        'gradient': 'gradient 15s ease infinite',
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'modal-fade': 'modalFade 0.3s',
        'modal-fade-in': 'modalFadeIn 0.3s ease-out forwards',
        'modal-fade-out': 'modalFadeOut 0.3s ease-in forwards',
        'pulse': 'pulse 2s infinite',
      },
      boxShadow: {
        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02)',
        'card-hover': '0 20px 30px -10px rgba(0, 0, 0, 0.1), 0 10px 15px -5px rgba(0, 0, 0, 0.05)',
        'dashboard-preview': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
      },
      borderRadius: {
        'card': '16px',
      },
    },
  },
  plugins: [],
}
