(function () {
    function initTemplatePicker() {
        var picker = document.querySelector('[data-template-picker]');
        if (!picker) return;

        var inputs = picker.querySelectorAll('[data-template-input]');

        function syncSelection() {
            var cards = picker.querySelectorAll('[data-template-card]');
            cards.forEach(function (card) {
                card.classList.remove('selected');
            });

            inputs.forEach(function (input) {
                if (input.checked) {
                    var card = input.closest('[data-template-card]');
                    if (card) card.classList.add('selected');
                }
            });
        }

        inputs.forEach(function (input) {
            input.addEventListener('change', syncSelection);
        });

        syncSelection();
    }

    function initLoadingButtons() {
        var forms = document.querySelectorAll('[data-loading-form]');

        forms.forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('button[type="submit"]');
                if (!submit) return;

                submit.disabled = true;
                var loadingLabel = submit.getAttribute('data-submit-label');
                if (loadingLabel && loadingLabel.length > 0) {
                    submit.dataset.originalLabel = submit.textContent;
                    submit.textContent = loadingLabel;
                }
            });
        });
    }

    function initPinInput() {
        var pin = document.querySelector('[data-pin-input]');
        if (!pin) return;

        pin.addEventListener('input', function () {
            pin.value = pin.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }

    function initFlashAutoHide() {
        var flashes = document.querySelectorAll('.flash.success');
        flashes.forEach(function (flash) {
            setTimeout(function () {
                flash.style.opacity = '0';
                flash.style.transition = 'opacity 0.25s ease';
                setTimeout(function () {
                    if (flash.parentNode) flash.parentNode.removeChild(flash);
                }, 250);
            }, 4500);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTemplatePicker();
        initLoadingButtons();
        initPinInput();
        initFlashAutoHide();
    });
})();
