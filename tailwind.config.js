/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./Views/**/*.php", "./templates/**/*.php", "./assets/js/**/*.js"],
  theme: {
    extend: {
      colors: {
        blue: {
          50: "#eff6ff",
          100: "#dbeafe",
          600: "#2563eb",
          700: "#1d4ed8",
          800: "#1e40af",
          900: "#1e3a8a",
        },
        indigo: {
          50: "#eef2ff",
          600: "#4f46e5",
          700: "#4338ca",
        },
      },
      animation: {
        "fade-in": "fadeIn 0.8s ease-out forwards",
        "fade-in-delayed": "fadeIn 0.8s ease-out 0.2s forwards",
      },
      keyframes: {
        fadeIn: {
          from: { opacity: "0", transform: "translateY(10px)" },
          to: { opacity: "1", transform: "translateY(0)" },
        },
      },
      borderRadius: {
        none: "0",
        sm: "0.125rem",
        DEFAULT: "0.25rem",
        md: "0.375rem",
        lg: "0.5rem",
        xl: "0.75rem",
        "2xl": "1rem",
        "3xl": "1.5rem",
        full: "9999px",
      },
    },
  },
  plugins: [],
};
