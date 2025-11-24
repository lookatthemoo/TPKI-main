// Tunggu hingga seluruh HTML dimuat
document.addEventListener('DOMContentLoaded', () => {

    // --- Variabel Global ---
    let statusInterval = null;
    const statusTextBox = document.getElementById('order-status-text');
    const checkoutButton = document.getElementById('checkout-button');
    const statusTrackerBox = document.getElementById('status-tracker-box');
    
    const stepDiterima = document.getElementById('status-step-diterima');
    const stepProses = document.getElementById('status-step-proses');
    const stepJadi = document.getElementById('status-step-jadi');

    // --- Format Rupiah ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    }

    // --- Fungsi Update Tampilan Keranjang (DIPERBAIKI) ---
    function updateDashboard(cartData, total) {
        const orderList = document.getElementById('order-list');
        const totalPriceElement = document.getElementById('total-price');
        const clearCartButton = document.getElementById('clear-cart-button');

        orderList.innerHTML = ''; // Kosongkan dulu

        if (cartData.length === 0) {
            orderList.innerHTML = '<li class="empty-cart">Keranjang kosong...</li>';
            clearCartButton.style.display = 'none';
        } else {
            cartData.forEach(item => {
                const li = document.createElement('li');
                
                // HITUNG SUB-TOTAL per item
                const subTotal = item.price * item.qty;

                // TAMPILAN BARU: Ada "10x" di depan nama
                li.innerHTML = `
                    <div style="display:flex; justify-content:space-between; width:100%;">
                        <span><b>${item.qty}x</b> ${item.name}</span>
                        <strong>${formatRupiah(subTotal)}</strong>
                    </div>
                `;
                orderList.appendChild(li);
            });
            clearCartButton.style.display = 'block';
        }
        
        // Update Total Keseluruhan
        if (totalPriceElement) {
            totalPriceElement.textContent = formatRupiah(total);
            totalPriceElement.classList.add('total-flash');
            setTimeout(() => { totalPriceElement.classList.remove('total-flash'); }, 700);
        }
    }

    // --- Fungsi Kirim Data ke PHP ---
    async function sendCartAction(data) {
        try {
            const response = await fetch('cart_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json(); 
            
            if(result.cart !== undefined && result.total !== undefined) {
                updateDashboard(result.cart, result.total);
            }
            return result;

        } catch (error) {
            console.error('Fetch error:', error);
        }
    }
    
    // --- Event Listener Tombol "TAMBAH +" (DIPERBAIKI) ---
    const addForms = document.querySelectorAll('.add-to-cart-form');
    addForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const button = form.querySelector('button[type="submit"]');
            const originalButtonText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '...';

            const formData = new FormData(form);
            
            // === BAGIAN PENTING: AMBIL ANGKA DARI INPUT ===
            // Kita cari input bernama 'item_qty'
            let qtyInput = parseInt(formData.get('item_qty'), 10);
            
            // Validasi: Kalau kosong atau < 1, anggap 1
            if (isNaN(qtyInput) || qtyInput < 1) qtyInput = 1;

            await sendCartAction({
                action: 'add',
                name: formData.get('item_name'),
                price: parseInt(formData.get('item_price'), 10),
                qty: qtyInput // <-- INI YANG DIKIRIM KE BACKEND
            });
            
            button.disabled = false;
            button.innerHTML = originalButtonText;
            
            // Reset angka jadi 1 lagi setelah tombol ditekan
            const inputEl = form.querySelector('input[name="item_qty"]');
            if(inputEl) inputEl.value = 1;
        });
    });

    // --- Event Listener Tombol Lainnya (Tetap Sama) ---
    const clearCartButton = document.getElementById('clear-cart-button');
    if (clearCartButton) {
        clearCartButton.addEventListener('click', async () => {
            await sendCartAction({ action: 'clear' });
        });
    }

    if (checkoutButton) {
        checkoutButton.addEventListener('click', async () => {
            const result = await sendCartAction({ action: 'checkout' });
            if (result && result.success) {
                updateStatusUI(result.order_status);
                startStatusChecker(); 
            } else if (result) {
                alert(result.message);
            }
        });
    }

    // --- LOGIKA STATUS TRACKER ---
    function updateStatusUI(status) {
        if (!statusTextBox || !stepDiterima || !stepProses || !stepJadi) return;
        statusTextBox.textContent = status;
        statusTrackerBox.style.display = 'block'; 
        
        stepDiterima.classList.remove('active');
        stepProses.classList.remove('active');
        stepJadi.classList.remove('active');
        checkoutButton.disabled = false;
        checkoutButton.textContent = 'Checkout';

        if (status === 'Diterima') {
            stepDiterima.classList.add('active');
            checkoutButton.disabled = true;
            checkoutButton.textContent = 'Pesanan Diproses...';
        } else if (status === 'Sedang di Proses') {
            stepDiterima.classList.add('active');
            stepProses.classList.add('active');
            checkoutButton.disabled = true;
            checkoutButton.textContent = 'Pesanan Dibuat...';
        } else if (status === 'Pesanan Jadi') {
            stepDiterima.classList.add('active');
            stepProses.classList.add('active');
            stepJadi.classList.add('active');
            checkoutButton.disabled = false;
            checkoutButton.textContent = 'Checkout';
            stopStatusChecker();
        } else {
            statusTrackerBox.style.display = 'none';
        }
    }

    async function checkOrderStatus() {
        try {
            const response = await fetch('cek_status.php');
            if (!response.ok) throw new Error('Gagal cek status');
            const result = await response.json();
            updateStatusUI(result.status);
        } catch (error) {
            stopStatusChecker();
        }
    }

    function startStatusChecker() {
        if (statusInterval) clearInterval(statusInterval);
        checkOrderStatus();
        statusInterval = setInterval(checkOrderStatus, 5000);
    }

    function stopStatusChecker() {
        if (statusInterval) {
            clearInterval(statusInterval);
            statusInterval = null;
        }
    }
    
    checkOrderStatus();
});