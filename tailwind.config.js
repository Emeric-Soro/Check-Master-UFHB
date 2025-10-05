/** @type {import('tailwindcss').Config} */
export const content = [
  "./src/**/*.{html,js,jsx,ts,tsx,vue,php}", // Adaptez selon votre structure et types de fichiers
  "./public/index.html",
  "./public/*.{html,js,jsx,ts,tsx,vue,php}", // Si vous avez un fichier HTML principal
  // Ajoutez ici tous les chemins vers vos templates
];
export const theme = {
  extend: {
    colors: {
      primary: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
      },
      secondary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
      },
      accent: {
          400: '#f59e0b',
          500: '#f97316',
          600: '#ea580c',
      }
  }
  },
};
export const plugins = [
  require('tailwindcss'),
  require('autoprefixer'),
];