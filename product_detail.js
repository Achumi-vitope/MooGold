
document.addEventListener('DOMContentLoaded', function () {
    let selectedPrice = 0;

    // Function to update the variation price and total based on the selected price and quantity
    function updateTotal() {
        const quantity = parseInt(document.getElementById('quantity').value, 10);
        const totalPrice = selectedPrice * quantity;
        document.getElementById('variation-price').value ='₹ ' + totalPrice.toFixed(2);
        console.log(`Updated total price: ₹${totalPrice}`);
    }

    // Add click event listener to each table cell
    const offerCells = document.querySelectorAll('.offer-cell');
    console.log(`Offer Cells Found: ${offerCells.length}`);

    offerCells.forEach(cell => {
        cell.addEventListener('click', function () {
            selectedPrice = parseFloat(this.getAttribute('data-price'));
            document.getElementById('variation-price').value = selectedPrice.toFixed(2);
            console.log(`Selected Price: ${selectedPrice}`);

            offerCells.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            updateTotal();
        });
    });

    // Add input event listener to update the total when the quantity changes
    document.getElementById('quantity').addEventListener('input', function () {
        updateTotal();
    });
});

// Existing event listener for offer cells
document.querySelectorAll('.offer-cell').forEach(function (cell) {
    cell.addEventListener('click', function () {
        // Get the data from the clicked cell
        var productName = this.textContent;
        var diamondAmount = this.getAttribute('data-diamonds');
        var bonus = this.getAttribute('data-bonus');
        var price = this.getAttribute('data-price');
        var productId = this.getAttribute('data-variation-id');

        // Populate hidden fields
        document.getElementById('product_name').value = productName;
        document.getElementById('price').value = price;
        document.getElementById('product_id').value = productId;

        // Update the visible price field
        document.getElementById('variation-price').value = price;
    });
});

// New event listener for quantity change
document.getElementById('quantity').addEventListener('input', function() {
    // Get the current quantity
    var quantity = this.value;
    // Update the hidden quantity input
    document.getElementById('quantity').value = quantity;
});
