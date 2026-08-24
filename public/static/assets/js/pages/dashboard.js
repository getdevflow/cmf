$(function () {
    'use strict';

    var $dashboard = $('.dashboard-widgets');

    if ($dashboard.length === 0 || $dashboard.data('editable') !== 1) {
        return;
    }

    var $columns = $dashboard.find('.dashboard-widget-column');
    var $warehouse = $dashboard.find('.dashboard-widget-warehouse');
    var $picker = $dashboard.find('.dashboard-widget-picker');
    var $status = $dashboard.find('.dashboard-save-status');
    var saveTimer = null;

    function widgetIds($column) {
        return $column.children('.dashboard-widget').map(function () {
            return $(this).data('widget-id');
        }).get();
    }

    function updateState() {
        var activeCount = $columns.children('.dashboard-widget').length;

        $dashboard.find('.dashboard-empty-state').prop('hidden', activeCount !== 0);
        $columns.each(function () {
            var $column = $(this);
            $column.children('.dashboard-column-empty').prop(
                'hidden',
                activeCount === 0 || $column.children('.dashboard-widget').length !== 0
            );
        });
    }

    function updateCatalog(widgetId, active) {
        var $item = $dashboard.find('.dashboard-widget-catalog-item').filter(function () {
            return $(this).data('widget-id') === widgetId;
        });

        $item.find('.js-dashboard-widget-add').prop('hidden', active).prop('disabled', active);
        $item.find('.dashboard-widget-active').prop('hidden', !active);
    }

    function saveLayout() {
        var token = $dashboard.children('input[type="hidden"]').first();
        var data = {
            left: widgetIds($columns.filter('[data-column="left"]')),
            right: widgetIds($columns.filter('[data-column="right"]'))
        };

        if (token.length) {
            data[token.attr('name')] = token.val();
        }

        $status.removeClass('text-success text-danger').addClass('text-muted').text('Saving...');

        $.ajax({
            url: $dashboard.data('save-url'),
            method: 'POST',
            data: data
        }).done(function (response) {
            $status.removeClass('text-muted text-danger').addClass('text-success')
                .text(response.message || 'Dashboard saved.');
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            $status.removeClass('text-muted text-success').addClass('text-danger')
                .text(response.message || 'Unable to save the dashboard.');
        });
    }

    function scheduleSave() {
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveLayout, 200);
    }

    $columns.sortable({
        placeholder: 'sort-highlight',
        connectWith: '.dashboard-widget-column',
        handle: '.dashboard-widget-handle',
        items: '> .dashboard-widget',
        forcePlaceholderSize: true,
        zIndex: 999999,
        opacity: 0.4,
        update: function () {
            updateState();
            scheduleSave();
        }
    });

    $dashboard.on('click', '.js-dashboard-customize', function () {
        var opening = $picker.prop('hidden');

        $picker.prop('hidden', !opening);
        $dashboard.find('.js-dashboard-customize').attr('aria-expanded', opening ? 'true' : 'false');
    });

    $dashboard.on('click', '.js-dashboard-widget-add', function () {
        var $catalogItem = $(this).closest('.dashboard-widget-catalog-item');
        var widgetId = $catalogItem.data('widget-id');
        var $widget = $warehouse.children('.dashboard-widget').filter(function () {
            return $(this).data('widget-id') === widgetId;
        }).first();

        if ($widget.length === 0) {
            return;
        }

        var preferredColumn = $widget.data('default-column');
        var $target = $columns.filter('[data-column="' + preferredColumn + '"]');

        if ($target.length === 0) {
            $target = $columns.first();
        }

        $widget.appendTo($target);
        updateCatalog(widgetId, true);
        updateState();
        scheduleSave();
    });

    $dashboard.on('click', '.js-dashboard-widget-remove', function () {
        var $widget = $(this).closest('.dashboard-widget');
        var widgetId = $widget.data('widget-id');

        $widget.appendTo($warehouse);
        updateCatalog(widgetId, false);
        updateState();
        scheduleSave();
    });

    $columns.find('.dashboard-widget-handle').css('cursor', 'move');
    updateState();
});
