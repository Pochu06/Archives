<script>
    (() => {
        const preferenceKey = 'archives-theme';
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const readPreference = () => {
            try {
                return localStorage.getItem(preferenceKey) || 'system';
            } catch (error) {
                return 'system';
            }
        };
        const applyPreference = (preference) => {
            const isDark = preference === 'dark' || (preference === 'system' && mediaQuery.matches);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.dataset.theme = preference;
        };

        applyPreference(readPreference());

        document.addEventListener('DOMContentLoaded', () => {
            const selectors = document.querySelectorAll('[data-theme-selector]');
            selectors.forEach((selector) => {
                selector.value = readPreference();
                selector.addEventListener('change', () => {
                    try {
                        localStorage.setItem(preferenceKey, selector.value);
                    } catch (error) {
                    }

                    selectors.forEach((item) => { item.value = selector.value; });
                    applyPreference(selector.value);
                });
            });

            mediaQuery.addEventListener('change', () => {
                if (readPreference() === 'system') {
                    applyPreference('system');
                }
            });

            window.addEventListener('storage', (event) => {
                if (event.key === preferenceKey) {
                    const preference = readPreference();
                    applyPreference(preference);
                    selectors.forEach((selector) => { selector.value = preference; });
                }
            });
        });
    })();
</script>
<style>
    html.dark { color-scheme: dark; }
    html[data-theme="light"] { color-scheme: light; }
    html.dark body { background-color: #10191d; color: #e5ecee; }
    html.dark .bg-white { background-color: #19262b !important; color: #e5ecee; }
    html.dark .bg-gray-50, html.dark .bg-slate-50 { background-color: #121d21 !important; }
    html.dark .bg-gray-100, html.dark .bg-slate-100 { background-color: #202d32 !important; }
    html.dark .bg-gray-200, html.dark .bg-slate-200 { background-color: #2b3a3f !important; }
    html.dark .bg-orange-100, html.dark .bg-orange-200 { background-color: #27484d !important; }
    html.dark .bg-green-50, html.dark .bg-red-50, html.dark .bg-blue-50,
    html.dark .bg-amber-50, html.dark .bg-yellow-50, html.dark .bg-orange-50,
    html.dark .bg-emerald-50 { background-color: #202d32 !important; }
    html.dark .bg-gradient-to-r { background-image: none !important; background-color: #202d32 !important; }
    html.dark .bg-orange-500, html.dark .bg-orange-600, html.dark .bg-orange-700,
    html.dark .bg-orange-800, html.dark .bg-orange-900, html.dark .bg-blue-500,
    html.dark .bg-blue-600, html.dark .bg-blue-700 { background-color: #326b73 !important; }
    html.dark .student-archive-stat { background: linear-gradient(135deg, #1f4f56, #326b73) !important; }
    html.dark .hero-bg { background-color: #326b73 !important; }
    html.dark #appSidebar { background-color: #17383e !important; }
    html.dark #appSidebar .sidebar-link:hover { background-color: #285159; }
    html.dark #appSidebar .sidebar-link.active { background-color: #285159; border-right-color: #80bec1; }
    html.dark #appSidebar .border-orange-700 { border-color: #315159 !important; }
    html.dark .hover\:bg-orange-700:hover, html.dark .hover\:bg-blue-700:hover { background-color: #3b7c84 !important; }
    html.dark .text-gray-900, html.dark .text-gray-800, html.dark .text-gray-700,
    html.dark .text-gray-600, html.dark .text-slate-900, html.dark .text-slate-800,
    html.dark .text-slate-700 { color: #e5ecee !important; }
    html.dark .text-gray-500, html.dark .text-gray-400, html.dark .text-slate-600,
    html.dark .text-slate-500, html.dark .text-slate-400 { color: #a8b7bc !important; }
    html.dark .text-green-700, html.dark .text-emerald-800, html.dark .text-emerald-900 { color: #86efac !important; }
    html.dark .text-red-700, html.dark .text-red-800, html.dark .text-red-900 { color: #fca5a5 !important; }
    html.dark .text-blue-500, html.dark .text-blue-600, html.dark .text-blue-700,
    html.dark .text-blue-800, html.dark .text-blue-900, html.dark .text-blue-950,
    html.dark .text-orange-500, html.dark .text-orange-600, html.dark .text-orange-700,
    html.dark .text-orange-800, html.dark .text-orange-900,
    html.dark .hover\:text-orange-600:hover { color: #91ced0 !important; }
    html.dark .border-gray-100, html.dark .border-gray-200, html.dark .border-gray-300,
    html.dark .border-slate-100, html.dark .border-slate-200, html.dark .border-slate-300,
    html.dark .border-orange-100, html.dark .border-orange-200, html.dark .border-orange-300,
    html.dark .border-green-200, html.dark .border-red-200, html.dark .border-blue-100,
    html.dark .border-blue-200, html.dark .border-emerald-200 { border-color: #34464c !important; }
    html.dark .border-orange-500, html.dark .border-orange-600, html.dark .border-orange-700,
    html.dark .border-blue-500, html.dark .border-blue-600, html.dark .border-blue-700 { border-color: #4a777d !important; }
    html.dark input:not([type="checkbox"]):not([type="radio"]), html.dark select, html.dark textarea {
        background-color: #121d21;
        border-color: #40545a;
        color: #e5ecee;
    }
    html.dark input::placeholder, html.dark textarea::placeholder { color: #92a3a8; }
</style>