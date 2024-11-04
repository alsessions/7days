/** @type {import('tailwindcss').Config} */

module.exports = {
  content: [],
  theme: {
    fontFamily: {
      sans: [ 'sans-serif'],
      display: ['Averia Libre', 'sans-serif']
    },
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}
