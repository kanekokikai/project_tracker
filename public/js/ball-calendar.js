(function () {
    var scroller = document.querySelector('[data-ball-calendar]');

    if (!scroller) {
        return;
    }

    var today = scroller.querySelector('.cal-head.is-today');
    var topic = scroller.querySelector('thead .cal-topic');

    var placeToday = function () {
        var todayBox = today.getBoundingClientRect();
        var topicBox = topic.getBoundingClientRect();
        var peek = todayBox.width * 2;
        var next = scroller.scrollLeft + (todayBox.left - topicBox.right - peek);
        scroller.scrollLeft = next > 0 ? next : 0;
    };

    if (today && topic) {
        placeToday();
        window.addEventListener('load', placeToday);
    }

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
