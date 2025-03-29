    </div><!-- End of container -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo $base_url; ?>assets/js/script.js"></script>
    
    <!-- Debug script to check image loading -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Base URL: <?php echo $base_url; ?>');
            // Debug all images
            const images = document.querySelectorAll('img');
            images.forEach((img, index) => {
                console.log(`Image ${index} src: ${img.src}, complete: ${img.complete}`);
                img.addEventListener('error', function() {
                    console.error(`Failed to load image: ${this.src}`);
                    // Show a colored border for failed images
                    this.style.border = '2px solid red';
                });
            });
        });
    </script>
</body>
</html> 