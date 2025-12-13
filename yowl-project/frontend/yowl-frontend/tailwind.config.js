/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                'orange-primary': '#FF6B35',
                'blue-night': '#1E2A38',
            },
            fontFamily: {
                'poppins': ['Poppins', 'sans-serif'],
                'roboto': ['Roboto', 'sans-serif'],
            },
            fontSize: {
                'caption': ['12px', '16px'],
                'body': ['14px', '20px'],
                'h1': ['32px', '40px'],
                'h2': ['28px', '36px'],
                'h3': ['24px', '32px'],
                'h4': ['20px', '28px'],
                'h5': ['18px', '24px'],
                'h6': ['16px', '22px'],
            },
            maxWidth: {
                'mobile': '768px',
            }
        },
    },
    plugins: [],
}
