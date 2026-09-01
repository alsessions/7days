/** @type {import('tailwindcss').Config} */

module.exports = {
  content: [],
  theme: {
    fontFamily: {
      sans: ['Lato', 'sans-serif'],
      display: ['Playfair Display', 'serif']
    },
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
}
