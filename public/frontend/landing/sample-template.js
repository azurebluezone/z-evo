// Generated from resources/views/templates/sample-template.blade.php by tools/extract_landing_assets.py.
// Do not edit this JS by hand; edit the Blade source.

// Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // FAQ Toggle
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');

            if (answer.style.display === 'block') {
                answer.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                answer.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
