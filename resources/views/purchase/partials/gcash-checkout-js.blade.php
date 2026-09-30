<script>
    function copyGcashNumber() {
        const number = document.getElementById('gcashNumber')?.textContent.trim();
        if (!number) return;
        navigator.clipboard?.writeText(number).then(
            () => toast(((window.ANEE_REGION || {}).ph === false ? 'PayPal email' : 'GCash number') + ' copied.'),
            () => toast('Could not copy. Please copy it by hand.', 'error')
        );
    }

    const screenshotInput = document.getElementById('screenshot');
    screenshotInput?.addEventListener('change', () => {
        const file = screenshotInput.files?.[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            toast('That image is over 5MB. Pick a smaller one.', 'error');
            screenshotInput.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('screenshotPreviewImg').src = e.target.result;
            document.getElementById('screenshotPreviewName').textContent = file.name;
            document.getElementById('screenshotEmpty').classList.add('hidden');
            document.getElementById('screenshotPreview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    function clearScreenshot(e) {
        e.stopPropagation();
        screenshotInput.value = '';
        document.getElementById('screenshotPreviewImg').src = '';
        document.getElementById('screenshotPreview').classList.add('hidden');
        document.getElementById('screenshotEmpty').classList.remove('hidden');
    }
</script>
