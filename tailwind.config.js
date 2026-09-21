/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './admin/**/*.php',
    './api/**/*.php',
    './includes/**/*.php',
    './student/**/*.php',
    './support/**/*.php',
  ],
  // Classes added at runtime via JS (never appear in a static class="...").
  safelist: ['overflow-hidden'],
  theme: {
    extend: {
      colors: {
        navy: '#0F2A43',
        brand: '#2563EB',
        teal: { DEFAULT: '#0F766E', 50: '#f0fdfa', 100: '#ccfbf1', 600: '#0d9488', 700: '#0F766E' },
      },
      fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
      boxShadow: {
        card: '0 1px 3px rgb(15 42 67 / .08)',
        pop: '0 8px 24px rgb(15 42 67 / .14)',
      },
    },
  },
  plugins: [],
};
