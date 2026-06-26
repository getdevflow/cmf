(function ($) {
    'use strict';

    function adminUrl()
    {
        return window.DevflowWorkflow && window.DevflowWorkflow.adminUrl
            ? window.DevflowWorkflow.adminUrl
            : window.DevflowAdminUrl;
    }

    function escapeHtml(value)
    {
        return $('<div>').text(value || '').html();
    }

    function loadContentNotifications()
    {
        $.ajax({
            url: adminUrl() + 'content-notifications/unread/',
            method: 'GET',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                return;
            }

            var count = response.notifications.length;
            var $count = $('#content-notification-count');
            var $list = $('#content-notification-list');

            if (count > 0) {
                $count.text(count).show();
                $('#content-notification-header').text(count + ' workflow notification(s)');
            } else {
                $count.hide();
                $('#content-notification-header').text('No workflow notifications');
            }

            var html = '';

            $.each(response.notifications, function (_, item) {
                html += '<li>';
                html += '<a href="#" class="js-content-notification" data-url="' +
                    escapeHtml(item.url || '') +
                    '" data-notification-id="' + escapeHtml(item.notification_id) + '">';
                html += '<i class="fa fa-edit text-aqua"></i> ';
                html += '<strong>' + escapeHtml(item.title) + '</strong><br>';
                html += '<small>' + escapeHtml(item.body).substring(0, 100) + '</small>';
                html += '</a>';
                html += '</li>';
            });

            $list.html(html);
        });
    }

    $(document).on('click', '.js-content-notification', function (e) {
        e.preventDefault();

        var url = $(this).data('url');

        $.ajax({
            url: adminUrl() + 'content-notifications/read/',
            method: 'POST',
            dataType: 'json',
            data: {
                notification_id: $(this).data('notification-id')
            }
        }).done(function () {
            if (url) {
                window.location.href = url;
                return;
            }

            loadContentNotifications();
        });
    });

    $('#mark-content-notifications-read').on('click', function (e) {
        e.preventDefault();

        $.ajax({
            url: adminUrl() + 'content-notifications/read-all/',
            method: 'POST',
            dataType: 'json'
        }).done(loadContentNotifications);
    });

    loadContentNotifications();

    window.setInterval(loadContentNotifications, 60000);
})(jQuery);
