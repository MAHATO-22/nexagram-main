        // Language Dropdown Toggle Script
        const langToggleBtn = document.getElementById('langDropdownToggle');
        const langContainer = document.getElementById('langDropdownContainer');

        langToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langContainer.classList.toggle('open');
        });

        document.addEventListener('click', (e) => {
            if (!langContainer.contains(e.target)) {
                langContainer.classList.remove('open');
            }
        });

        // Theme Toggle Handler Script
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        });

        // Dropzone & File Upload Logic
        const dropZone = document.getElementById('dropZone');
        const mediaInput = document.getElementById('mediaInput');
        const previewGrid = document.getElementById('previewGrid');
        const captionInput = document.getElementById('captionInput');
        const charCounter = document.getElementById('charCounter');
        const postForm = document.getElementById('postForm');
        const addMoreText = window.NEXAGRAM_CONFIG.values[0];

        let fileList = [];

        dropZone.addEventListener('click', () => mediaInput.click());

        ['dragenter', 'dragover'].forEach(name => {
            dropZone.addEventListener(name, (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(name => {
            dropZone.addEventListener(name, (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);
            addFiles(files);
        });

        mediaInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            addFiles(files);
        });

        function addFiles(files) {
            files.forEach(file => {
                if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                    fileList.push(file);
                }
            });
            updateFileInput();
            renderPreviews();
        }

        function removeFile(index) {
            fileList.splice(index, 1);
            updateFileInput();
            renderPreviews();
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            fileList.forEach(file => dataTransfer.items.add(file));
            mediaInput.files = dataTransfer.files;
        }

        // Final sync check before submission
        postForm.addEventListener('submit', function() {
            updateFileInput();
        });

        function renderPreviews() {
            previewGrid.innerHTML = '';

            if (fileList.length === 0) {
                previewGrid.style.display = 'none';
                dropZone.style.display = 'flex';
                return;
            }

            dropZone.style.display = 'none';
            previewGrid.style.display = 'grid';

            fileList.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'preview-item';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-single-btn';
                removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeFile(index);
                });

                const reader = new FileReader();
                reader.onload = (e) => {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-media';
                        item.appendChild(img);
                    } else if (file.type.startsWith('video/')) {
                        const video = document.createElement('video');
                        video.src = e.target.result;
                        video.className = 'preview-media';
                        item.appendChild(video);
                    }
                    item.appendChild(removeBtn);
                };
                reader.readAsDataURL(file);

                previewGrid.appendChild(item);
            });

            // "Add More" photo tile
            const addMoreBox = document.createElement('div');
            addMoreBox.className = 'add-more-box';
            addMoreBox.innerHTML = `<i class="fa-solid fa-plus add-more-icon"></i><span>${addMoreText}</span>`;
            addMoreBox.addEventListener('click', () => mediaInput.click());
            previewGrid.appendChild(addMoreBox);
        }

        captionInput.addEventListener('input', () => {
            charCounter.textContent = `${captionInput.value.length} / 500`;
        });