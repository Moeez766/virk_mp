// seller.js — extra interactivity for the seller listing form
document.addEventListener('DOMContentLoaded', function () {

    // Show/hide auction date fields based on sale type selection
    var saleTypeSelect = document.getElementById('sale_type_select');
    var auctionDates   = document.getElementById('auction_dates');

    if (saleTypeSelect && auctionDates) {
        function toggleAuctionDates() {
            if (saleTypeSelect.value === 'best_offer') {
                auctionDates.style.display = 'block';
            } else {
                auctionDates.style.display = 'none';
            }
        }
        saleTypeSelect.addEventListener('change', toggleAuctionDates);
        toggleAuctionDates(); // run on load
    }

});
