// Validace formulářů registrace, přihlášení a profilu (frontend).
// Pravidla jsou v atributech inputů (required, type=email, pattern, minlength, ...),
// tento skript jen zobrazí srozumitelné hlášky. Server všechno kontroluje znovu.
(function () {
    'use strict';

    // Najde místo pro chybovou hlášku u daného pole
    function errorBox(input) {
        return input.form.querySelector('[data-error-for="' + input.name + '"]');
    }

    // Vlastní pravidla, která HTML atributy neumí
    function customCheck(input) {
        // heslo: malé písmeno, velké písmeno, číslice
        if (input.hasAttribute('data-password') && input.value !== '') {
            if (!/\p{Ll}/u.test(input.value) || !/\p{Lu}/u.test(input.value) || !/\d/.test(input.value)) {
                return 'Heslo musí obsahovat malé písmeno, velké písmeno a číslici.';
            }
        }
        // shoda s jiným polem (potvrzení hesla)
        if (input.hasAttribute('data-match') && input.value !== '') {
            var other = document.getElementById(input.getAttribute('data-match'));
            if (other && other.value !== input.value) {
                return 'Hesla se neshodují.';
            }
        }
        // fotka: velikost a typ
        if (input.hasAttribute('data-photo') && input.files && input.files.length) {
            var file = input.files[0];
            if (file.size > parseInt(input.getAttribute('data-max-size'), 10)) {
                return 'Soubor je příliš velký.';
            }
            if (!/^image\//.test(file.type) && !/\.(jpe?g|png|gif|bmp|tiff?|webp)$/i.test(file.name)) {
                return 'Vyber obrázek (JPEG, PNG, GIF, BMP, TIFF nebo WebP).';
            }
        }
        return '';
    }

    // Zkontroluje jedno pole a vypíše chybu. Vrací true, když je v pořádku.
    function validateInput(input) {
        input.setCustomValidity(customCheck(input));
        var v = input.validity;
        var msg = '';

        if (!v.valid) {
            if (v.valueMissing) {
                msg = input.type === 'file' ? 'Vyber soubor.' : 'Toto pole je povinné.';
            } else if (v.typeMismatch && input.type === 'email') {
                msg = 'Zadej platný e-mail.';
            } else if (v.tooShort) {
                msg = 'Zadej alespoň ' + input.minLength + ' znaků.';
            } else if (v.patternMismatch) {
                msg = input.getAttribute('data-msg') || 'Hodnota má nesprávný tvar.';
            } else {
                msg = input.validationMessage;   // vlastní hlášky z customCheck()
            }
        }

        var box = errorBox(input);
        if (box) { box.textContent = msg; }
        input.classList.toggle('is-invalid', msg !== '');
        input.setAttribute('aria-invalid', msg !== '' ? 'true' : 'false');
        return msg === '';
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        var fields = form.querySelectorAll('input:not([type=hidden]), select, textarea');

        fields.forEach(function (input) {
            input.addEventListener('blur', function () { validateInput(input); });
            input.addEventListener('input', function () {
                // opravuje-li uživatel chybné pole, hlášku hned aktualizujeme
                if (input.classList.contains('is-invalid')) { validateInput(input); }
                // změna hesla = překontrolovat potvrzení
                if (input.hasAttribute('data-password')) {
                    var confirm = form.querySelector('[data-match="' + input.id + '"]');
                    if (confirm && confirm.value !== '') { validateInput(confirm); }
                }
            });
        });

        form.addEventListener('submit', function (event) {
            var firstBad = null;
            fields.forEach(function (input) {
                if (!validateInput(input) && !firstBad) { firstBad = input; }
            });
            if (firstBad) {
                event.preventDefault();
                firstBad.focus();
            }
        });
    });

    // Zobrazit / skrýt heslo
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Skrýt' : 'Zobrazit';
        });
    });

    // Měřič síly hesla (jen orientační; pravidla hlídá validace)
    document.querySelectorAll('input[data-password]').forEach(function (input) {
        var meter = document.querySelector('[data-strength-for="' + input.id + '"]');
        if (!meter) { return; }
        var label = input.form.querySelector('[data-strength-label]');
        var names = ['', 'Slabé', 'Střední', 'Dobré', 'Silné'];
        input.addEventListener('input', function () {
            var v = input.value, level = 0;
            if (v.length >= 8) { level++; }
            if (/\p{Ll}/u.test(v) && /\p{Lu}/u.test(v)) { level++; }
            if (/\d/.test(v)) { level++; }
            if (v.length >= 12 || /[^\p{L}\d]/u.test(v)) { level++; }
            if (v === '') { level = 0; }
            meter.setAttribute('data-level', level);
            if (label) { label.textContent = names[level]; }
        });
    });

    // Nahrání fotky: název souboru a náhled v rámečku
    var photoInput = document.querySelector('input[data-photo]');
    var box = document.querySelector('[data-upload]');
    if (photoInput && box) {
        var nameEl = box.querySelector('[data-upload-name]');
        var thumb = box.querySelector('[data-upload-thumb]');
        photoInput.addEventListener('change', function () {
            var file = photoInput.files[0];
            if (!file) { return; }
            nameEl.textContent = file.name + ' (' + (file.size / 1048576).toFixed(1) + ' MB)';
            // TIFF prohlížeče nezobrazí, náhled se pak jen nezmění
            if (/^image\/(jpeg|png|gif|webp|bmp)$/.test(file.type)) {
                var url = URL.createObjectURL(file);
                if (thumb.tagName === 'IMG') {
                    thumb.src = url;
                } else {
                    var img = document.createElement('img');
                    img.className = 'upload-thumb';
                    img.setAttribute('data-upload-thumb', '');
                    img.alt = 'Náhled vybrané fotky';
                    img.src = url;
                    thumb.replaceWith(img);
                    thumb = img;
                }
            }
        });
        // chyba u pole se ukáže i na rámečku
        var obs = function () { box.classList.toggle('is-invalid', photoInput.classList.contains('is-invalid')); };
        photoInput.addEventListener('blur', obs);
        photoInput.addEventListener('change', obs);
    }
})();
