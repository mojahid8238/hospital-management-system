(function () {
    document.querySelectorAll('.password-wrapper').forEach(function (wrapper) {
        var input = wrapper.querySelector('input[type="password"], input[type="text"]');
        var btn = wrapper.querySelector('.password-toggle');
        if (!input || !btn) return;
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });
})();
