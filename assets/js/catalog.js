const searchInput = document.querySelector('#catalog-search');
const productCards = Array.from(document.querySelectorAll('[data-product-card]'));
const statusMessage = document.querySelector('.client-search-status');

if (searchInput && productCards.length > 0) {
    searchInput.addEventListener('input', () => {
        const term = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        productCards.forEach((card) => {
            const productText = card.dataset.searchText || '';
            const isMatch = productText.includes(term);

            card.hidden = !isMatch;

            if (isMatch) {
                visibleCount += 1;
            }
        });

        if (statusMessage) {
            statusMessage.textContent = term
                ? `${visibleCount} product${visibleCount === 1 ? '' : 's'} visible while typing. Submit to search the full database.`
                : '';
        }
    });
}
