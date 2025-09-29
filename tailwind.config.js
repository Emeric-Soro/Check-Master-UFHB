/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./src/**/*.{html,js,jsx,ts,tsx,vue,php}",
        "./public/**/*.{html,js,jsx,ts,tsx,vue,php}",
        "./ressources/**/*.php"
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#f0fdfa',
                    100: '#ccfbf1',
                    200: '#99f6e4',
                    300: '#5eead4',
                    400: '#2dd4bf',
                    500: '#14b8a6',
                    600: '#0F766E',
                    700: '#0d9488',
                    800: '#115e59',
                    900: '#134e4a',
                },

                secondary: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#1F2937',
                },

                neutral: {
                    50: '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    400: '#9ca3af',
                    500: '#6B7280',
                    600: '#4b5563',
                    700: '#374151',
                    800: '#1f2937',
                    900: '#111827',
                },

                success: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    500: '#10b981',
                    600: '#059669',
                    700: '#047857',
                    800: '#065f46',
                },

                warning: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                },

                error: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                },

                info: {
                    50: '#f0f9ff',
                    100: '#e0f2fe',
                    500: '#0ea5e9',
                    600: '#0284c7',
                    700: '#0369a1',
                    800: '#075985',
                },

                surface: '#ffffff',
                'surface-hover': '#f9fafb',
                'surface-active': '#f3f4f6',
            },

            fontFamily: {
                'primary': ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                'secondary': ['Georgia', 'Times New Roman', 'serif'],
                'mono': ['SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'monospace'],
                'sans': ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
            },

            spacing: {
                'component-xs': '0.25rem',
                'component-sm': '0.5rem',
                'component-md': '1rem',
                'component-lg': '1.5rem',
                'component-xl': '2rem',
                'section-xs': '2rem',
                'section-sm': '3rem',
                'section-md': '4rem',
                'section-lg': '5rem',
            },

            borderRadius: {
                'xs': '0.25rem',
                'sm': '0.375rem',
                'md': '0.5rem',
                'lg': '0.75rem',
                'xl': '1rem',
                '2xl': '1.5rem',
            },

            boxShadow: {
                'xs': '0 1px 2px 0 rgba(31, 41, 55, 0.05)',
                'sm': '0 1px 3px 0 rgba(31, 41, 55, 0.1)',
                'md': '0 4px 6px -1px rgba(31, 41, 55, 0.1)',
                'lg': '0 10px 15px -3px rgba(31, 41, 55, 0.1)',
                'xl': '0 20px 25px -5px rgba(31, 41, 55, 0.1)',
                'primary': '0 10px 15px -3px rgba(15, 118, 110, 0.1)',
                'secondary': '0 10px 15px -3px rgba(31, 41, 55, 0.1)',
                'neutral': '0 10px 15px -3px rgba(107, 114, 128, 0.1)',
                'success': '0 10px 15px -3px rgba(16, 185, 129, 0.1)',
                'error': '0 10px 15px -3px rgba(239, 68, 68, 0.1)',
            },

            animation: {
                'fade-in': 'fadeIn 0.3s ease-out',
                'fade-out': 'fadeOut 0.3s ease-in',
                'slide-up': 'slideUp 0.3s ease-out',
                'slide-down': 'slideDown 0.3s ease-out',
                'slide-in-right': 'slideInRight 0.3s ease-out',
                'slide-in-left': 'slideInLeft 0.3s ease-out',
                'scale-in': 'scaleIn 0.2s ease-out',
                'scale-out': 'scaleOut 0.2s ease-in',
                'bounce-gentle': 'bounceGentle 2s infinite',
                'float': 'float 3s ease-in-out infinite',
                'pulse-gentle': 'pulseGentle 2s infinite',
            },

            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' }
                },
                fadeOut: {
                    '0%': { opacity: '1', transform: 'translateY(0)' },
                    '100%': { opacity: '0', transform: 'translateY(-10px)' }
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' }
                },
                slideDown: {
                    '0%': { opacity: '0', transform: 'translateY(-20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' }
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(20px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' }
                },
                slideInLeft: {
                    '0%': { opacity: '0', transform: 'translateX(-20px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' }
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' }
                },
                scaleOut: {
                    '0%': { opacity: '1', transform: 'scale(1)' },
                    '100%': { opacity: '0', transform: 'scale(0.95)' }
                },
                bounceGentle: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-5px)' }
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-10px)' }
                },
                pulseGentle: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.8' }
                }
            },

            transitionDuration: {
                '75': '75ms',
                '100': '100ms',
                '150': '150ms',
                '200': '200ms',
                '250': '250ms',
                '300': '300ms',
                '500': '500ms',
            },

            transitionTimingFunction: {
                'ease-in-quart': 'cubic-bezier(0.5, 0, 0.75, 0)',
                'ease-out-quart': 'cubic-bezier(0.25, 1, 0.5, 1)',
                'ease-in-out-quart': 'cubic-bezier(0.76, 0, 0.24, 1)',
            },

            zIndex: {
                '1': '1',
                '2': '2',
                '10': '10',
                '20': '20',
                '30': '30',
                '40': '40',
                '50': '50',
                'dropdown': '1000',
                'sticky': '1020',
                'fixed': '1030',
                'modal-backdrop': '1040',
                'modal': '1050',
                'popover': '1060',
                'tooltip': '1070',
            },

            backgroundImage: {
                'gradient-primary': 'linear-gradient(135deg, #14b8a6, #0d9488)',
                'gradient-secondary': 'linear-gradient(135deg, #f1f5f9, #cbd5e1)',
                'gradient-neutral': 'linear-gradient(135deg, #9ca3af, #4b5563)',
                'gradient-success': 'linear-gradient(135deg, #10b981, #047857)',
                'gradient-warm': 'linear-gradient(135deg, #fed7aa, #fb923c)',
                'gradient-cool': 'linear-gradient(135deg, #ccfbf1, #5eead4)',
            }
        },
    },

    plugins: [
        function({ addUtilities, addComponents }) {
            addComponents({
                '.transition-fast': {
                    transition: 'all 150ms cubic-bezier(0.4, 0, 0.2, 1)',
                },
                '.transition-base': {
                    transition: 'all 250ms cubic-bezier(0.4, 0, 0.2, 1)',
                },
                '.transition-slow': {
                    transition: 'all 350ms cubic-bezier(0.4, 0, 0.2, 1)',
                },
                '.hover-lift': {
                    '&:hover': {
                        transform: 'translateY(-2px)',
                        transition: 'transform 200ms ease-out',
                    }
                },
                '.hover-scale': {
                    '&:hover': {
                        transform: 'scale(1.02)',
                        transition: 'transform 200ms ease-out',
                    }
                },
                '.hover-glow': {
                    '&:hover': {
                        boxShadow: '0 10px 15px -3px rgba(15, 118, 110, 0.1)',
                        transition: 'box-shadow 200ms ease-out',
                    }
                },
                '.focus-ring': {
                    '&:focus': {
                        outline: 'none',
                        boxShadow: '0 0 0 3px rgba(15, 118, 110, 0.1)',
                    }
                },
                '.scrollbar-hidden': {
                    '-ms-overflow-style': 'none',
                    'scrollbar-width': 'none',
                    '&::-webkit-scrollbar': {
                        display: 'none',
                    }
                },
                '.scrollbar-thin': {
                    '&::-webkit-scrollbar': {
                        width: '6px',
                    },
                    '&::-webkit-scrollbar-track': {
                        background: '#f3f4f6',
                        borderRadius: '3px',
                    },
                    '&::-webkit-scrollbar-thumb': {
                        background: '#9ca3af',
                        borderRadius: '3px',
                    },
                    '&::-webkit-scrollbar-thumb:hover': {
                        background: '#6B7280',
                    }
                },
            });

            addUtilities({
                '.text-balance': {
                    'text-wrap': 'balance',
                },
                '.hidden-sm': {
                    '@media (max-width: 639px)': {
                        display: 'none',
                    }
                },
                '.hidden-md': {
                    '@media (max-width: 767px)': {
                        display: 'none',
                    }
                },
                '.hidden-lg': {
                    '@media (max-width: 1023px)': {
                        display: 'none',
                    }
                },
                '.aspect-card': {
                    aspectRatio: '4 / 3',
                },
                '.aspect-banner': {
                    aspectRatio: '16 / 9',
                },
                '.aspect-square': {
                    aspectRatio: '1 / 1',
                },
            });
        },
    ],
}