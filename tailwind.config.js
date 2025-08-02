/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.twig',
        './assets/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#4F46E5',  // Indigo-600
                secondary: '#9333EA', // Purple-600
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            },
        },
    },
    plugins: [],
}
