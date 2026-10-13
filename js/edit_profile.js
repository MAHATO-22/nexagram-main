        // ပုံရွေးလိုက်တာနဲ့ တန်းပြီး Preview ပြပေးမည့် JavaScript
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('imgPreview');
                const textPreview = document.getElementById('imgPreviewText');

                output.src = reader.result;
                output.style.display = 'block';
                if (textPreview) {
                    textPreview.style.display = 'none';
                }
            };
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    