/* ================================================= */
/* KODE ANIMASI FADE-IN SAAT SCROLL (Intersection Observer) */
/* ================================================= */
document.addEventListener('DOMContentLoaded', () => {

    // 1. Pilih semua elemen yang ingin dianimasikan
    // Kita beri mereka semua class 'reveal' di HTML
    const revealElements = document.querySelectorAll('.reveal');

    // 2. Buat "Observer" (Mata-mata)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Jika elemennya masuk ke layar (isIntersecting)
            if (entry.isIntersecting) {
                // Tambahkan class 'is-visible'
                entry.target.classList.add('is-visible');
                
                // Berhenti mengamati elemen ini setelah animasinya jalan
                // agar tidak berulang-ulang
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1 // Picu animasi saat 10% elemen terlihat
    });

    // 3. Suruh "Observer" untuk mengamati setiap elemen 'reveal'
    revealElements.forEach(el => {
        observer.observe(el);
    });

/* ================================================= */
/* KODE OPSIONAL: Membuat dot navigasi aktif saat scroll */
/* ================================================= */
    const sections = document.querySelectorAll('section[id]');
    const navDots = document.querySelectorAll('.scroll-dots .dot');

    const activateDot = (id) => {
        navDots.forEach(dot => {
            dot.classList.remove('active');
            // Cek jika href dari link di dalam dot cocok dengan id
            if (dot.getAttribute('href') === `#${id}`) {
                dot.classList.add('active');
            }
        });
    };

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                activateDot(entry.target.id);
            }
        });
    }, {
        rootMargin: '-50% 0px -50% 0px', // Aktifkan saat section ada di tengah layar
        threshold: 0
    });

    sections.forEach(section => {
        sectionObserver.observe(section);
    });
});