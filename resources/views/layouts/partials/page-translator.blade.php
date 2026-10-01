<div id="google_translate_element" class="sr-only" aria-hidden="true"></div>
<script>
(() => {
    const selectors = document.querySelectorAll('[data-page-language]');
    const translationCookie = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('googtrans='));
    const selectedLanguage = translationCookie
        ? decodeURIComponent(translationCookie.split('=').slice(1).join('=')).split('/').pop()
        : 'en';

    selectors.forEach((selector) => {
        selector.value = selectedLanguage === 'tl' ? 'tl' : 'en';
        selector.addEventListener('change', () => {
            if (selector.value === 'en') {
                document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            } else {
                document.cookie = `googtrans=/en/${selector.value}; path=/; SameSite=Lax`;
            }

            window.location.reload();
        });
    });
})();

window.googleTranslateElementInit = function () {
    new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,tl',
        autoDisplay: false,
    }, 'google_translate_element');
};
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>