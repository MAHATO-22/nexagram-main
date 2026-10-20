(function () {
    const languageAliases = {
        'en-US': 'en',
        'ja-JP': 'ja',
        'zh-CN': 'zh-Hans',
        'ko-KR': 'ko',
        'hi-IN': 'hi',
        'bn-BD': 'bn',
        'ne-NP': 'ne',
        'ru-RU': 'ru',
        'fr-FR': 'fr',
        'si-LK': 'si',
        'ta-LK': 'ta',
        'my-MM': 'my',
        'mn-MN': 'mn',
        'vi-VN': 'vi'
    };
    const targetLanguage = languageAliases[document.documentElement.lang] || document.documentElement.lang.split('-')[0];
    const unavailableMessage = document.documentElement.dataset.translationUnavailable;
    const failedMessage = document.documentElement.dataset.translationFailed;
    const loadingMessage = document.documentElement.dataset.translationLoading;
    const tooLongMessage = document.documentElement.dataset.translationTooLong;
    const autoTranslateKey = 'nexagram-auto-translate';
    const autoTranslateButton = document.querySelector('[data-auto-translate]');
    const autoTranslateStatus = document.querySelector('[data-auto-translate-status]');
    let autoTranslateEnabled = window.localStorage.getItem(autoTranslateKey) === 'true';
    let autoTranslateRunning = false;
    const translationQueue = new Set();

    async function translateText(text) {
        if (
            !window.LanguageDetector ||
            typeof window.LanguageDetector.create !== 'function' ||
            !window.Translator ||
            typeof window.Translator.create !== 'function'
        ) {
            throw new Error('unavailable');
        }

        const detector = await window.LanguageDetector.create();
        try {
            const detections = await detector.detect(text);
            const detectedLanguage = detections[0] && detections[0].detectedLanguage;
            const sourceLanguage = detectedLanguage === 'zh' ? 'zh-Hans' : detectedLanguage;
            if (!sourceLanguage) {
                throw new Error('unsupported');
            }
            if (sourceLanguage === targetLanguage) {
                return text;
            }

            const translator = await window.Translator.create({sourceLanguage, targetLanguage});
            try {
                return await translator.translate(text);
            } finally {
                translator.destroy();
            }
        } finally {
            detector.destroy();
        }
    }

    function updateAutoTranslateButton() {
        if (!autoTranslateButton) {
            return;
        }
        autoTranslateButton.setAttribute('aria-pressed', String(autoTranslateEnabled));
        autoTranslateButton.textContent = autoTranslateEnabled
            ? autoTranslateButton.dataset.enabledLabel
            : autoTranslateButton.dataset.disabledLabel;
    }

    async function translateElement(parent) {
        const container = parent.querySelector('.translation-tools');
        const source = parent.querySelector('[data-translation-source]');
        const output = container && container.querySelector('[data-translation-result]');
        const button = container && container.querySelector('[data-translate-button]');
        const text = source && source.innerText.trim();

        if (!container || !source || !output || !button || !text || parent.dataset.translationBusy === 'true') {
            return true;
        }

        parent.dataset.translationBusy = 'true';
        button.disabled = true;
        button.textContent = loadingMessage;
        output.hidden = false;
        output.textContent = '';

        try {
            output.textContent = await translateText(text);
            parent.dataset.translationDone = 'true';
            return true;
        } catch (error) {
            output.textContent = error.message === 'unavailable' ? unavailableMessage : failedMessage;
            return error.message !== 'unavailable';
        } finally {
            parent.dataset.translationBusy = 'false';
            button.disabled = false;
            button.textContent = button.dataset.label;
        }
    }

    async function translateAll() {
        if (!autoTranslateEnabled) {
            return;
        }
        document.querySelectorAll('[data-translatable]').forEach(parent => {
            if (parent.dataset.translationDone !== 'true') {
                translationQueue.add(parent);
            }
        });
        if (autoTranslateRunning) {
            return;
        }

        autoTranslateRunning = true;
        try {
            while (translationQueue.size > 0 && autoTranslateEnabled) {
                const parent = translationQueue.values().next().value;
                translationQueue.delete(parent);
                if (parent.dataset.translationDone === 'true') {
                    continue;
                }
                const canContinue = await translateElement(parent);
                if (!canContinue) {
                    if (autoTranslateStatus) {
                        autoTranslateStatus.textContent = unavailableMessage;
                    }
                    translationQueue.clear();
                    break;
                }
            }
        } finally {
            autoTranslateRunning = false;
        }
    }

    if (autoTranslateButton) {
        updateAutoTranslateButton();
        autoTranslateButton.addEventListener('click', function () {
            autoTranslateEnabled = !autoTranslateEnabled;
            window.localStorage.setItem(autoTranslateKey, String(autoTranslateEnabled));
            updateAutoTranslateButton();
            if (autoTranslateEnabled) {
                translateAll();
            } else if (autoTranslateStatus) {
                autoTranslateStatus.textContent = '';
            }
        });
        if (autoTranslateEnabled) {
            translateAll();
        }
    }

    window.NexagramTranslation = {
        translateElement(parent) {
            if (autoTranslateEnabled) {
                if (parent && parent.matches('[data-translatable]') && parent.dataset.translationDone !== 'true') {
                    translationQueue.add(parent);
                }
                translateAll();
            }
            return parent;
        }
    };

    if ('MutationObserver' in window && autoTranslateButton) {
        const observer = new MutationObserver(function (mutations) {
            if (!autoTranslateEnabled) {
                return;
            }
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (!(node instanceof Element)) {
                        return;
                    }
                    if (node.matches('[data-translatable]')) {
                        translationQueue.add(node);
                    }
                    node.querySelectorAll('[data-translatable]').forEach(parent => translationQueue.add(parent));
                });
            });
            translateAll();
        });
        observer.observe(document.body, {childList: true, subtree: true});
    }

    document.addEventListener('click', async function (event) {
        const translateButton = event.target.closest('[data-translate-button]');
        if (translateButton) {
            const container = translateButton.closest('.translation-tools');
            const parent = container && container.closest('[data-translatable]');
            const source = parent && parent.querySelector('[data-translation-source]');
            const output = container && container.querySelector('[data-translation-result]');
            const useButton = container && container.querySelector('[data-translation-use]');
            const textarea = container && document.getElementById(container.dataset.translationInput || '');
            const text = textarea ? textarea.value.trim() : (source ? source.innerText.trim() : '');

            if (!container || !text || translateButton.disabled) {
                return;
            }

            if (textarea) {
                translateButton.disabled = true;
                translateButton.textContent = loadingMessage;
                output.hidden = false;
                output.textContent = '';
                if (useButton) {
                    useButton.hidden = true;
                }
                try {
                    output.textContent = await translateText(text);
                    if (useButton) {
                        useButton.hidden = false;
                    }
                } catch (error) {
                    output.textContent = error.message === 'unavailable' ? unavailableMessage : failedMessage;
                } finally {
                    translateButton.disabled = false;
                    translateButton.textContent = translateButton.dataset.label;
                }
            } else {
                await translateElement(parent);
            }
            return;
        }

        const useButton = event.target.closest('[data-translation-use]');
        if (useButton) {
            const container = useButton.closest('.translation-tools');
            const textarea = container && document.getElementById(container.dataset.translationInput || '');
            const output = container && container.querySelector('[data-translation-result]');
            if (textarea && output && !output.hidden) {
                if (textarea.maxLength > 0 && output.textContent.length > textarea.maxLength) {
                    output.textContent = tooLongMessage;
                    useButton.hidden = true;
                    return;
                }
                textarea.value = output.textContent;
                textarea.dispatchEvent(new Event('input', {bubbles: true}));
                textarea.focus();
            }
        }
    });
})();
