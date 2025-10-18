/** @type {import('tailwindcss').Config} */
export const content = [
  "./src/**/*.{html,js,jsx,ts,tsx,vue,php}",
  "./public/**/*.{html,js,jsx,ts,tsx,vue,php}",
  "./ressources/views/**/*.{html,js,jsx,ts,tsx,vue,php}",
];

export const theme = {
  extend: {
    colors: {
      // Couleurs officielles Check Master UFHB
      primary: {
          DEFAULT: '#1a5276',
          light: '#2980b9',
          lighter: '#3498db',
          50: '#eaf2f8',
          100: '#d4e6f1',
          200: '#a9cce3',
          300: '#5499c7',
          400: '#2980b9',
          500: '#1a5276',
          600: '#2471a3',
          700: '#1f618d',
          800: '#1a5276',
          900: '#154360',
      },
      secondary: {
          DEFAULT: '#ff8c00',
          50: '#fff4e6',
          100: '#fae5d3',
          200: '#f5cba7',
          300: '#f0b27a',
          400: '#ff8c00',
          500: '#ff8c00',
          600: '#d35400',
          700: '#af600f',
          800: '#7e450b',
          900: '#5a3308',
      },
      accent: {
          DEFAULT: '#4caf50',
          50: '#eafaf1',
          100: '#d5f4e6',
          200: '#7ddc80',
          300: '#7ddc80',
          400: '#4caf50',
          500: '#4caf50',
          600: '#449d48',
          700: '#388e3c',
          800: '#2e6b31',
          900: '#1b5e20',
      },
      success: '#4caf50',
      warning: '#f39c12',
      danger: '#e74c3c',
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
};

export const plugins = [
  require('tailwindcss'),
  require('autoprefixer'),
];