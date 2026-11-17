(function () {
    var scroller = document.querySelector('[data-ball-calendar]');

    if (!scroller) {
        return;
    }

    var table = scroller.querySelector('.ball-cal');
    var today = scroller.querySelector('.cal-head.is-today');
    var topic = scroller.querySelector('thead .cal-topic');

    var px = function (value, fallback) {
        var raw = String(value || '').trim();
        var amount = parseFloat(raw);
        var root = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;

        if (!amount) {
            return fallback * root;
        }

        if (raw.endsWith('px')) {
            return amount;
        }

        return amount * root;
    };

    var sizeColumns = function () {
        if (!table) {
            return;
        }

        var days = table.querySelectorAll('thead .cal-head').length;
        var cols = table.querySelectorAll('col');

        if (!days || cols.length < days + 2) {
            return;
        }

        var styles = getComputedStyle(table);
        var who = px(styles.getPropertyValue('--cal-who'), 11.5);
        var room = px(styles.getPropertyValue('--cal-room'), 16);
        var dayMin = px(styles.getPropertyValue('--cal-day-min'), 3);
        var tableWidth = Math.max(scroller.clientWidth, who + room + days * dayMin);
        var dayWidth = (tableWidth - who - room) / days;

        table.style.width = tableWidth + 'px';
        cols[0].style.width = who + 'px';
        cols[1].style.width = room + 'px';

        for (var i = 0; i < days; i++) {
            cols[i + 2].style.width = dayWidth + 'px';
        }
    };

    var placeToday = function () {
        if (!today || !topic) {
            return;
        }

        var todayBox = today.getBoundingClientRect();
        var topicBox = topic.getBoundingClientRect();
        var peek = todayBox.width * 2;
        var next = scroller.scrollLeft + (todayBox.left - topicBox.right - peek);
        scroller.scrollLeft = next > 0 ? next : 0;
    };

    var layout = function () {
        sizeColumns();
        placeToday();
    };

    layout();
    window.addEventListener('load', layout);
    window.addEventListener('resize', layout);

    scroller.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            return;
        }

        var row = event.target.closest('.cal-row');

        if (!row) {
            return;
        }

        var href = row.getAttribute('data-href');

        if (href) {
            window.location.href = href;
        }
    });

    scroller.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') {
            return;
        }

        var row = event.target.closest('.cal-row');

        if (!row || document.activeElement !== row) {
            return;
        }

        var href = row.getAttribute('data-href');

        if (href) {
            window.location.href = href;
        }
    });
})();
