// Tunggu hingga seluruh HTML dimuat
document.addEventListener('DOMContentLoaded', () => {

    // --- Variabel Global untuk Status ---
    let statusInterval = null; // Untuk menyimpan interval "live" status
    const statusTextBox = document.getElementById('order-status-text');
    const checkoutButton = document.getElementById('checkout-button');
    const statusTrackerBox = document.getElementById('status-tracker-box');
    
    // === PERUBAHAN DI SINI ===
    // Ambil elemen step yang baru
    const stepDiterima = document.getElementById('status-step-diterima');
    const stepProses = document.getElementById('status-step-proses');
    const stepJadi = document.getElementById('status-step-jadi');


    // --- Format Rupiah (Sama) ---
    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    }

    // --- Fungsi untuk Update Tampilan Keranjang (Sama) ---
    function updateDashboard(cartData, total) {
        const orderList = document.getElementById('order-list');
        const totalPriceElement = document.getElementById('total-price');
        const clearCartButton = document.getElementById('clear-cart-button');

        orderList.innerHTML = ''; // Kosongkan
        if (cartData.length === 0) {
            orderList.innerHTML = '<li class="empty-cart">Keranjang kosong...</li>';
            clearCartButton.style.display = 'none';
        } else {
            cartData.forEach(item => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <span>${item.name}</span>
                    <strong>${formatRupiah(item.price)}</strong>
                `;
                orderList.appendChild(li);
            });
            clearCartButton.style.display = 'block';
        }
        totalPriceElement.textContent = formatRupiah(total);
        
        // Animasi flash
        if(totalPriceElement){
            totalPriceElement.classList.add('total-flash');
            setTimeout(() => { totalPriceElement.classList.remove('total-flash'); }, 700);
        }
    }


    // --- Fungsi 'fetch' (Sama) ---
    async function sendCartAction(data) {
        try {
            const response = await fetch('cart_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json(); // Ambil respon
            
            if(result.cart !== undefined && result.total !== undefined) {
                updateDashboard(result.cart, result.total);
            }

            return result; // Kembalikan hasil (penting untuk checkout)

        } catch (error) {
            console.error('Fetch error:', error);
        }
    }
    
    
    // =======================================
    // === BAGIAN BARU: STATUS CHECKER ===
    // =======================================

    // Fungsi untuk mengupdate tampilan status di UI
    function updateStatusUI(status) {
        // Keluar jika elemen-elemen penting tidak ditemukan
        if (!statusTextBox || !stepDiterima || !stepProses || !stepJadi) return;
        
        statusTextBox.textContent = status;
        statusTrackerBox.style.display = 'block'; // Tampilkan box status
        
        // Reset semua step
        stepDiterima.classList.remove('active');
        stepProses.classList.remove('active');
        stepJadi.classList.remove('active');
        checkoutButton.disabled = false;
        checkoutButton.textContent = 'Checkout';

        // Set step aktif berdasarkan status
        if (status === 'Diterima') {
            stepDiterima.classList.add('active');
            checkoutButton.disabled = true; // Nonaktifkan checkout jika ada pesanan
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
            checkoutButton.disabled = false; // Aktifkan lagi
            checkoutButton.textContent = 'Checkout';
            stopStatusChecker(); // Hentikan pengecekan
        } else {
            // Jika "Belum ada pesanan" atau "Pesanan tidak ditemukan"
            statusTrackerBox.style.display = 'none'; // Sembunyikan box
        }
    }

    // Fungsi yang memanggil cek_status.php (Sama)
    async function checkOrderStatus() {
        try {
            const response = await fetch('cek_status.php');
            if (!response.ok) throw new Error('Gagal cek status');
            
            const result = await response.json();
            updateStatusUI(result.status);
            
        } catch (error) {
            console.error('Cek status error:', error);
            stopStatusChecker(); // Hentikan jika ada error
        }
    }

    // Fungsi untuk memulai pengecekan "live" (tiap 5 detik) (Sama)
    function startStatusChecker() {
        if (statusInterval) {
            clearInterval(statusInterval); // Hapus interval lama jika ada
        }
        checkOrderStatus(); // Cek sekali dulu
        statusInterval = setInterval(checkOrderStatus, 5000); // Cek tiap 5 detik
    }

    // Fungsi untuk menghentikan pengecekan (Sama)
    function stopStatusChecker() {
        if (statusInterval) {
            clearInterval(statusInterval);
            statusInterval = null;
        }
    }
    

    // --- Event Listener Tombol "Tambah" ---
    const addForms = document.querySelectorAll('.add-to-cart-form');
    addForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const button = form.querySelector('button[type="submit"]');
            const originalButtonText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '...';

            const formData = new FormData(form);
            
            // --- BAGIAN PENTING: AMBIL QTY ---
            let qtyInput = parseInt(formData.get('item_qty'), 10);
            if (isNaN(qtyInput) || qtyInput < 1) qtyInput = 1;

            await sendCartAction({
                action: 'add',
                name: formData.get('item_name'),
                price: parseInt(formData.get('item_price'), 10),
                qty: qtyInput // <-- Kirim QTY ke PHP
            });
            
            button.disabled = false;
            button.innerHTML = originalButtonText;
            
            // Reset input angka kembali ke 1
            const inputEl = form.querySelector('input[name="item_qty"]');
            if(inputEl) inputEl.value = 1;
        });
    });
    // --- Event Listener Tombol "Kosongkan Keranjang" (Sama) ---
    const clearCartButton = document.getElementById('clear-cart-button');
    if (clearCartButton) {
        clearCartButton.addEventListener('click', async () => {
            await sendCartAction({ action: 'clear' });
        });
    }

    // --- Event Listener Tombol "Checkout" (Sama) ---
    // --- Event Listener Tombol "Checkout" (MODIFIED) ---
    if (checkoutButton) {
        // 1. Saat klik tombol merah di keranjang -> Buka Modal
        checkoutButton.addEventListener('click', () => {
            const orderList = document.getElementById('order-list');
            // Cek kalau kosong jangan buka modal
            if (orderList.innerHTML.includes('Keranjang kosong')) {
                alert("Keranjang masih kosong!");
            } else {
                openCheckoutModal(); // Panggil fungsi buka modal di index.php
            }
        });
    }

    // 2. Saat Form di Modal disubmit -> Kirim Data ke PHP
    const formCheckout = document.getElementById('formCheckoutFinal');
    if (formCheckout) {
        formCheckout.addEventListener('submit', async (e) => {
            e.preventDefault(); // Cegah refresh
            
            const formData = new FormData(formCheckout);
            const customerName = formData.get('customer_name');
            const paymentMethod = formData.get('payment_method'); // kas_laci atau Nama Bank

            // Kirim ke Backend
            const result = await sendCartAction({ 
                action: 'checkout',
                customer_name: customerName,
                payment_method: paymentMethod
            });
            
            if (result && result.success) {
                closeCheckoutModal(); // Tutup modal
                updateStatusUI(result.order_status);
                startStatusChecker(); 
            } else if (result) {
                alert(result.message);
            }
        });
    }
    
    // --- PENGECEKAN AWAL SAAT HALAMAN DIBUKA ---
    checkOrderStatus();

});
