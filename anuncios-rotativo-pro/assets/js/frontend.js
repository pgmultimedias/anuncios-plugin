document.addEventListener('DOMContentLoaded', function() {
const containers = document.querySelectorAll('.arp-container');
containers.forEach(function(container) {
    const slides = container.querySelectorAll('.arp-slide');
    if (slides.length > 1) {
        let currentSlide = 0;
        const rotationSpeed = parseInt(container.getAttribute('data-rotation-speed'), 10) || 5000;
        slides[0].classList.add('active');
        setInterval(function() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, rotationSpeed);
    } else if (slides.length === 1) {
        slides[0].classList.add('active');
    }
});
    });
