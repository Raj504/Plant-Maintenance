/* PlantCare – small amount of plain JavaScript used everywhere */
document.addEventListener('DOMContentLoaded', function () {

    // Show toasts queued by the server
    document.querySelectorAll('.pm-toast').forEach(function (el) {
        bootstrap.Toast.getOrCreateInstance(el).show();
    });

    // Mobile sidebar toggle
    var sidebar = document.getElementById('pmSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var toggle = document.getElementById('sidebarToggle');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // Confirm dialogs: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Demo login buttons (local environment only)
    document.querySelectorAll('.demo-login').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelector('input[name=email]').value = btn.dataset.email;
            document.querySelector('input[name=password]').value = 'password';
        });
    });

    // Auto-submit filter selects: <select data-autosubmit>
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.closest('form').submit(); });
    });

    // Show/hide blocks based on a select value: data-toggle-target="#id" data-toggle-values="a,b"
    document.querySelectorAll('[data-toggle-target]').forEach(function (el) {
        var update = function () {
            var target = document.querySelector(el.dataset.toggleTarget);
            if (!target) return;
            var values = el.dataset.toggleValues.split(',');
            var current = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;
            target.style.display = values.indexOf(current) >= 0 ? '' : 'none';
        };
        el.addEventListener('change', update);
        update();
    });

    // Dynamic repeating rows: container with data-repeater, template in <template>, add button [data-repeater-add]
    document.querySelectorAll('[data-repeater-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var container = document.querySelector(btn.dataset.repeaterAdd);
            var template = container.querySelector('template');
            var index = container.querySelectorAll('.repeater-row').length;
            var html = template.innerHTML.replace(/__INDEX__/g, index);
            container.insertAdjacentHTML('beforeend', html);
        });
    });
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-repeater-remove]')) {
            e.target.closest('.repeater-row').remove();
        }
    });

    // Simple cost total: inputs with data-sum="group" and output [data-sum-total="group"]
    document.querySelectorAll('[data-sum]').forEach(function (input) {
        input.addEventListener('input', function () {
            var group = input.dataset.sum;
            var total = 0;
            document.querySelectorAll('[data-sum="' + group + '"]').forEach(function (i) { total += parseFloat(i.value) || 0; });
            document.querySelectorAll('[data-sum-total="' + group + '"]').forEach(function (o) {
                if (o.tagName === 'INPUT') { o.value = total.toFixed(2); } else { o.textContent = total.toFixed(2); }
            });
        });
    });
});
