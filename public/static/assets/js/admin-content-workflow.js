(function ($) {
    'use strict';

    var lastEditorSelection = null;

    function adminUrl()
    {
        return window.DevflowWorkflow && window.DevflowWorkflow.adminUrl
            ? window.DevflowWorkflow.adminUrl
            : window.DevflowAdminUrl;
    }

    function contentWorkflowUrl(contentId, action)
    {
        return adminUrl() + 'content-workflow/' + contentId + '/' + action + '/';
    }

    function escapeHtml(value)
    {
        return $('<div>').text(value || '').html();
    }

    function prettyValue(value)
    {
        if (value === null || typeof value === 'undefined') {
            return '';
        }

        if (typeof value === 'object') {
            return JSON.stringify(value, null, 2);
        }

        try {
            return JSON.stringify(JSON.parse(value), null, 2);
        } catch (e) {
            return String(value);
        }
    }

    function revisionFieldLabel(field)
    {
        switch (field) {
            case 'content_status':
                return 'Status';
            case 'content_attribute':
                return 'Attributes';
            case 'content_title':
                return 'Title';
            case 'content_slug':
                return 'Slug';
            case 'content_body':
                return 'Body';
            default:
                return String(field || '').replace(/^content_/, '').replace(/_/g, ' ');
        }
    }

    function getSelectedTextContext()
    {
        var parentId = $('#editorial-comment-parent-id').val();

        if (parentId) {
            return null;
        }

        captureEditorSelection();

        if (lastEditorSelection && lastEditorSelection.text) {
            return lastEditorSelection;
        }

        return null;
    }

    function commentPermissions(response)
    {
        return response.permissions || {};
    }

    function renderComment(comment, permissions, depth)
    {
        depth = depth || 0;

        var statusClass = comment.comment_status === 'resolved' ? ' resolved-comment' : '';
        var html = '';

        html += '<div class="well well-sm editorial-comment' + statusClass + '" data-comment-id="' + escapeHtml(comment.comment_id) + '" style="margin-left:' + (depth * 20) + 'px;">';

        if (comment.comment_type === 'reply') {
            html += '<span class="label label-default">Reply</span> ';
        } else {
            html += '<span class="label label-info">Editorial</span> ';
        }

        if (comment.comment_status === 'resolved') {
            html += '<span class="label label-success">Resolved</span> ';
        }

        html += '<p class="editorial-comment-body" style="margin-top:8px;">' + escapeHtml(comment.comment_body) + '</p>';

        if (comment.selection_json) {
            try {
                var selection = typeof comment.selection_json === 'string'
                    ? JSON.parse(comment.selection_json)
                    : comment.selection_json;

                if (selection && selection.text) {
                    html += '<blockquote class="small">' + escapeHtml(selection.text) + '</blockquote>';
                }
            } catch (e) {
            }
        }

        html += '<small class="text-muted">';
        html += escapeHtml(comment.created_at);

        if (comment.updated_at) {
            html += ' &middot; Edited ' + escapeHtml(comment.updated_at);
        }

        html += '</small>';

        html += '<div class="comment-actions" style="margin-top:8px;">';

        if (permissions.can_reply_comment && comment.comment_status !== 'resolved') {
            html += '<button type="button" class="btn btn-xs btn-default js-reply-comment">Reply</button> ';
        }

        if (permissions.can_edit_comment && comment.comment_status !== 'resolved') {
            html += '<button type="button" class="btn btn-xs btn-info js-edit-comment">Edit</button> ';
        }

        if (permissions.can_resolve_comment) {
            if (comment.comment_status === 'resolved') {
                html += '<button type="button" class="btn btn-xs btn-warning js-reopen-comment">Reopen</button> ';
            } else {
                html += '<button type="button" class="btn btn-xs btn-success js-resolve-comment">Resolve</button> ';
            }
        }

        if (permissions.can_delete_comment) {
            html += '<button type="button" class="btn btn-xs btn-danger js-delete-comment">Delete</button>';
        }

        html += '</div>';

        html += '<div class="edit-comment-form" style="display:none; margin-top:8px;">';
        html += '<textarea class="form-control edit-comment-body" rows="3">' + escapeHtml(comment.comment_body) + '</textarea>';
        html += '<br>';
        html += '<button type="button" class="btn btn-xs btn-primary js-save-edit-comment">Save</button> ';
        html += '<button type="button" class="btn btn-xs btn-default js-cancel-edit-comment">Cancel</button>';
        html += '</div>';

        if (comment.replies && comment.replies.length) {
            $.each(comment.replies, function (_, reply) {
                html += renderComment(reply, permissions, depth + 1);
            });
        }

        html += '</div>';

        return html;
    }

    function showWorkflowMessage(message, type)
    {
        var css = type === 'error' ? 'alert-danger' : 'alert-success';

        $('#content-workflow-box').prepend(
            '<div class="alert ' + css + ' workflow-alert">' + message + '</div>'
        );

        setTimeout(function () {
            $('.workflow-alert').fadeOut(200, function () {
                $(this).remove();
            });
        }, 2500);
    }

    $('.js-workflow-action').on('click', function () {
        var $button = $(this);
        var action = $button.data('action');
        var contentId = $('#content-workflow-box').data('content-id');

        $button.prop('disabled', true);

        $.ajax({
            url: contentWorkflowUrl(contentId, action),
            method: 'POST',
            dataType: 'json',
            data: {
                message: $('#workflow-message').val() || ''
            }
        }).done(function (response) {
            if (response.success && response.workflow) {
                updateWorkflowUi(response.workflow.stage, response.workflow.status);

                var message = $('#workflow-message').val();

                if (message) {
                    $('#workflow-last-message').show();
                    $('#workflow-last-message-text').text(message);
                }

                $('#workflow-message').val('');

                showWorkflowMessage(response.message || 'Workflow updated.', 'success');

                $('#load-content-activity').trigger('click');
            }
        }).fail(function () {
            showWorkflowMessage('Workflow update failed.', 'error');
        }).always(function () {
            $button.prop('disabled', false);
        });
    });

    function loadEditorialCommentSummary()
    {
        var contentId = $('#content-workflow-box').data('content-id');

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comments/summary'),
            method: 'GET',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success || !response.summary) {
                return;
            }

            var open = parseInt(response.summary.open, 10) || 0;
            var $badge = $('#editorial-comments-open-count');

            if (open > 0) {
                $badge.text(open + ' open').show();
            } else {
                $badge.hide();
            }
        });
    }

    $('#load-content-revisions').on('click', function () {
        var contentId = $('#content-revisions-box').data('content-id');
        var $list = $('#content-revisions-list');

        $list.html('<p class="text-muted">Loading...</p>');

        $.ajax({
            url: adminUrl() + 'content-workflow/' + contentId + '/revisions/',
            method: 'GET',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success || !response.revisions.length) {
                $list.html('<p class="text-muted">No revisions found.</p>');
                return;
            }

            var html = '';

            $.each(response.revisions, function (_, revision) {
                html += '<div class="well well-sm">';
                html += '<strong>' + $('<div>').text(revision.event_type).html() + '</strong><br>';
                html += '<small>' + $('<div>').text(revision.recorded_at).html() + '</small><br>';
                if (response.can_restore) {
                    html += '<button type="button" ' +
                        'class="btn btn-xs btn-warning js-restore-revision" ' +
                        'data-event-id="' + escapeHtml(revision.event_id) + '" ' +
                        'data-event-type="' + escapeHtml(revision.event_type || '') + '" ' +
                        'data-recorded-at="' + escapeHtml(revision.recorded_at || '') + '">' +
                        'Restore as Draft' +
                        '</button>';
                }
                html += '<button type="button" class="btn btn-xs btn-info js-view-revision-diff" data-event-id="' +
                    escapeHtml(revision.event_id) +
                    '">View Diff</button> ';
                html += '</div>';
            });

            $list.html(html);
        });
    });

    var restoreRevisionEventId = null;

    $(document).on('click', '.js-restore-revision', function () {
        var $button = $(this);

        restoreRevisionEventId = $button.data('event-id');

        var details = '';
        details += '<strong>Revision:</strong> ' + escapeHtml($button.data('event-type') || '') + '<br>';
        details += '<strong>Recorded:</strong> ' + escapeHtml($button.data('recorded-at') || '');

        $('#restore-revision-modal-details')
            .html(details)
            .show();

        $('#restore-revision-modal').modal('show');
    });

    $('#confirm-restore-revision').on('click', function () {
        var $button = $(this);
        var contentId = $('#content-revisions-box').data('content-id');

        if (!restoreRevisionEventId) {
            return;
        }

        $button.prop('disabled', true).text('Restoring...');

        $.ajax({
            url: adminUrl() + 'content-workflow/' + contentId + '/restore-revision/',
            method: 'POST',
            dataType: 'json',
            data: {
                event_id: restoreRevisionEventId
            }
        }).done(function (response) {
            if (response.success) {
                $('#restore-revision-modal').modal('hide');

                showWorkflowMessage(response.message || 'Revision restored as draft.', 'success');

                window.setTimeout(function () {
                    window.location.reload();
                }, 600);
            }
        }).fail(function () {
            showWorkflowMessage('Revision could not be restored.', 'error');
        }).always(function () {
            $button.prop('disabled', false).text('Restore as Draft');
            restoreRevisionEventId = null;
        });
    });

    $(document).on('click', '.js-view-revision-diff', function () {
        var $button = $(this);
        var contentId = $('#content-revisions-box').data('content-id');
        var eventId = $button.data('event-id');
        var $target = $button.closest('.well');
        var $existing = $target.find('.revision-diff');

        if ($existing.length) {
            $existing.slideToggle(150);
            return;
        }

        $button.prop('disabled', true).text('Loading Diff...');

        $.ajax({
            url: adminUrl() + 'content-workflow/' + contentId + '/revision-diff/',
            method: 'GET',
            dataType: 'json',
            data: {
                event_id: eventId
            }
        }).done(function (response) {
            var html = '<div class="revision-diff">';

            if (!response.success || !response.changes.length) {
                html += '<hr><p class="text-muted">No visible changes.</p>';
                html += '</div>';
                $target.append(html);
                return;
            }

            html += '<hr><strong>Changes</strong><ul class="list-unstyled">';

            $.each(response.changes, function (_, change) {
                html += '<li>';
                html += '<div class="revision-field-change">';
                html += '<strong>' + escapeHtml(change.field) + '</strong>';

                html += '<div><small class="text-danger">Before:</small>';
                html += '<pre>' + escapeHtml(prettyValue(change.before) || '') + '</pre></div>';

                html += '<div><small class="text-success">After:</small>';
                html += '<pre>' + escapeHtml(prettyValue(change.after) || '') + '</pre></div>';

                html += '</div>';
                html += '</li>';
            });

            html += '</ul></div>';

            $target.append(html);
        }).always(function () {
            $button.prop('disabled', false).text('View Diff');
        });
    });

    $('#load-content-activity').on('click', function () {
        var contentId = $('#content-activity-box').data('content-id');
        var $list = $('#content-activity-list');

        $list.html('<li><i class="fa fa-spinner fa-spin bg-gray"></i><div class="timeline-item"><div class="timeline-body">Loading...</div></div></li>');

        $.ajax({
            url: adminUrl() + 'content-workflow/' + contentId + '/activity/',
            method: 'GET',
            dataType: 'json'
        }).done(function (response) {
            if (!response.success || !response.activity.length) {
                $list.html('<li><i class="fa fa-info bg-gray"></i><div class="timeline-item"><div class="timeline-body text-muted">No activity yet.</div></div></li>');
                return;
            }

            var html = '';

            $.each(response.activity, function (_, item) {
                html += '<li>';
                html += '<i class="fa fa-history bg-blue"></i>';
                html += '<div class="timeline-item">';
                html += '<span class="time"><i class="fa fa-clock-o"></i> ' + escapeHtml(item.created_at) + '</span>';
                html += '<h3 class="timeline-header">' + escapeHtml(item.label || item.activity_type) + '</h3>';
                html += '<div class="timeline-body">';
                html += '<p>' + escapeHtml(item.message) + '</p>';

                if (item.from_status || item.to_status) {
                    html += '<small class="text-muted">' +
                        escapeHtml(item.from_status || '') +
                        ' &rarr; ' +
                        escapeHtml(item.to_status || '') +
                        '</small>';
                }

                html += '</div></div></li>';
            });

            html += '<li><i class="fa fa-clock-o bg-gray"></i></li>';

            $list.html(html);
        });
    });

    function workflowStageLabel(stage)
    {
        switch (stage) {
            case 'draft': return 'Draft';
            case 'in_review': return 'In Review';
            case 'changes_requested': return 'Changes Requested';
            case 'approved': return 'Approved';
            case 'scheduled': return 'Scheduled';
            case 'published': return 'Published';
            case 'archived': return 'Archived';
            default: return String(stage || '').replace(/_/g, ' ');
        }
    }

    function workflowStageClass(stage)
    {
        switch (stage) {
            case 'draft': return 'label-default';
            case 'in_review': return 'label-warning';
            case 'changes_requested': return 'label-danger';
            case 'approved': return 'label-success';
            case 'scheduled': return 'label-info';
            case 'published': return 'label-primary';
            case 'archived': return 'label-default';
            default: return 'label-default';
        }
    }

    function updateWorkflowUi(stage, status)
    {
        var $stage = $('#workflow-stage');

        $stage
            .removeClass('label-default label-warning label-danger label-success label-info label-primary')
            .addClass(workflowStageClass(stage))
            .attr('data-stage', stage)
            .text(workflowStageLabel(stage));

        if (status) {
            $('[name="status"]').val(status).trigger('change');
        }

        $('.js-workflow-action').each(function () {
            var $button = $(this);
            var visibleStages = String($button.data('visible-stages') || '').split(',');

            if (visibleStages.indexOf(stage) !== -1) {
                $button.show();
            } else {
                $button.hide();
            }
        });
    }

    function captureEasyMdeSelection()
    {
        if (!window.DevflowEasyMDE || !window.DevflowEasyMDE.codemirror) {
            return false;
        }

        var cm = window.DevflowEasyMDE.codemirror;
        var text = cm.getSelection();

        if (!text) {
            return false;
        }

        lastEditorSelection = {
            field: 'content_body',
            text: text.substring(0, 500),
            markdown: text.substring(0, 1000),
            start: cm.indexFromPos(cm.getCursor('from')),
            end: cm.indexFromPos(cm.getCursor('to')),
            editor: 'easymde'
        };

        return true;
    }

    function captureTinyMceSelection()
    {
        if (typeof tinymce === 'undefined') {
            return false;
        }

        var editor = tinymce.activeEditor;

        if (!editor || !editor.selection) {
            return false;
        }

        var text = editor.selection.getContent({ format: 'text' });

        if (!text) {
            return false;
        }

        lastEditorSelection = {
            field: editor.id || 'content_body',
            text: text.substring(0, 500),
            html: editor.selection.getContent({ format: 'html' }).substring(0, 1000),
            start: null,
            end: null,
            editor: 'tinymce'
        };

        return true;
    }

    function captureEditorSelection()
    {
        return captureEasyMdeSelection() || captureTinyMceSelection();
    }

    $(document).on('mouseup keyup', '.CodeMirror, .CodeMirror-code', captureEditorSelection);
    $(document).on('mouseup keyup', '.mce-content-body', captureEditorSelection);

    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function (e) {
            e.editor.on('mouseup keyup NodeChange', captureEditorSelection);
        });
    }

    if (window.DevflowEasyMDE && window.DevflowEasyMDE.codemirror) {
        window.DevflowEasyMDE.codemirror.on('cursorActivity', captureEditorSelection);
    }

    var currentCommentStatus = 'open';

    function loadEditorialComments()
    {
        var contentId = $('#content-workflow-box').data('content-id');

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comments'),
            method: 'GET',
            dataType: 'json',
            data: {
                status: currentCommentStatus
            }
        }).done(function (response) {
            var $target = $('#editorial-comments');

            if (!response.success || !response.comments.length) {
                $target.html('<p class="text-muted">No editorial comments found.</p>');
                return;
            }

            var permissions = commentPermissions(response);
            var html = '';

            $.each(response.comments, function (_, comment) {
                html += renderComment(comment, permissions, 0);
            });

            $target.html(html);
        });
    }

    var editorialCommentsStorageKey =
        'devflow.editorialComments.expanded.' + $('#content-workflow-box').data('content-id');

    function applyEditorialCommentsState()
    {
        var expanded = localStorage.getItem(editorialCommentsStorageKey);

        if (expanded === null) {
            expanded = '1';
        }

        var $body = $('#editorial-comments-body');
        var $icon = $('#toggle-editorial-comments i');

        if (expanded === '1') {
            $body.show();
            $icon.removeClass('fa-plus').addClass('fa-minus');
        } else {
            $body.hide();
            $icon.removeClass('fa-minus').addClass('fa-plus');
        }
    }

    $(document).on('click', '#toggle-editorial-comments', function () {
        var $body = $('#editorial-comments-body');
        var isVisible = $body.is(':visible');

        if (isVisible) {
            $body.slideUp(150);
            $('#toggle-editorial-comments i')
                .removeClass('fa-minus')
                .addClass('fa-plus');

            localStorage.setItem(editorialCommentsStorageKey, '0');
        } else {
            $body.slideDown(150);
            $('#toggle-editorial-comments i')
                .removeClass('fa-plus')
                .addClass('fa-minus');

            localStorage.setItem(editorialCommentsStorageKey, '1');
        }
    });

    $('#save-workflow-reviewers').on('click', function () {
        var $button = $(this);
        var contentId = $('#content-workflow-box').data('content-id');

        $button.prop('disabled', true);

        $.ajax({
            url: contentWorkflowUrl(contentId, 'assign-reviewers'),
            method: 'POST',
            dataType: 'json',
            data: {
                reviewers: $('#workflow-reviewers').val() || []
            }
        }).done(function (response) {
            if (response.success) {
                if (response.workflow && response.workflow.reviewer_names) {
                    renderWorkflowReviewerSummary(response.workflow.reviewer_names);
                }

                showWorkflowMessage(response.message || 'Reviewers updated.', 'success');
                $('#load-content-activity').trigger('click');
            }
        }).fail(function () {
            showWorkflowMessage('Reviewers could not be updated.', 'error');
        }).always(function () {
            $button.prop('disabled', false);
        });
    });

    function renderWorkflowReviewerSummary(reviewers)
    {
        var stage = $('#workflow-stage').data('stage') || 'draft';
        var isReviewActive = stage === 'in_review';
        var isReviewFinished = ['approved', 'scheduled', 'published', 'archived'].indexOf(stage) !== -1;

        var title = 'Reviewers:';

        if (isReviewActive) {
            title = 'Current Reviewers:';
        } else if (isReviewFinished) {
            title = 'Review Participants:';
        }

        var $summary = $('#workflow-reviewer-summary');

        if (!reviewers || !reviewers.length) {
            $summary.html(
                '<strong>' + title + '</strong>' +
                '<p class="text-muted" id="workflow-reviewer-empty">' +
                (isReviewFinished
                    ? 'Review was completed without assigned reviewers.'
                    : 'No reviewers assigned yet.') +
                '</p>'
            );
            return;
        }

        var html = '<strong>' + title + '</strong>';
        html += '<ul class="list-unstyled" id="workflow-reviewer-list">';

        $.each(reviewers, function (_, reviewer) {
            html += '<li>';
            html += '<i class="fa fa-user text-muted"></i> ';
            html += escapeHtml(reviewer.name || 'Unknown User');

            if (reviewer.email) {
                html += ' <small class="text-muted">' + escapeHtml(reviewer.email) + '</small>';
            }

            if (isReviewActive && reviewer.status) {
                html += ' <span class="label ' +
                    (reviewer.status === 'complete' ? 'label-success' : 'label-warning') +
                    '">' +
                    (reviewer.status === 'complete' ? 'Complete' : 'Pending') +
                    '</span>';
            } else if (isReviewFinished) {
                html += ' <span class="label label-success">Review Closed</span>';
            }

            html += '</li>';
        });

        html += '</ul>';

        $summary.html(html);
    }

    $(document).on('click', '.js-comment-filter', function () {
        currentCommentStatus = $(this).data('status') || 'open';

        $('.js-comment-filter')
            .removeClass('btn-primary')
            .addClass('btn-default');

        $(this)
            .removeClass('btn-default')
            .addClass('btn-primary');

        loadEditorialComments();
    });

    $('#load-editorial-comments').on('click', loadEditorialComments);

    $('#add-editorial-comment').on('click', function () {
        var $button = $(this);
        var contentId = $('#content-workflow-box').data('content-id');
        var comment = $('#editorial-comment').val();
        var parentId = $('#editorial-comment-parent-id').val();
        var type = $('#editorial-comment-type').val() || 'editorial';

        if (!comment) {
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comment'),
            method: 'POST',
            dataType: 'json',
            data: {
                comment: comment,
                parent_id: parentId,
                comment_type: type,
                selection: getSelectedTextContext()
            }
        }).done(function (response) {
            if (response.success) {
                $('#editorial-comment').val('');
                $('#editorial-comment-parent-id').val('');
                $('#editorial-comment-type').val('editorial');
                $('#editorial-comment-context').hide();

                lastEditorSelection = null;

                loadEditorialComments();
                loadEditorialCommentSummary();
                $('#load-content-activity').trigger('click');
            }
        }).always(function () {
            $button.prop('disabled', false);
        });
    });

    $(document).on('click', '.js-reply-comment', function () {
        var commentId = $(this).closest('.editorial-comment').data('comment-id');

        $('#editorial-comment-parent-id').val(commentId);
        $('#editorial-comment-type').val('reply');
        $('#editorial-comment-context').show();
        $('#editorial-comment').focus();
    });

    $('#cancel-comment-reply').on('click', function () {
        $('#editorial-comment-parent-id').val('');
        $('#editorial-comment-type').val('editorial');
        $('#editorial-comment-context').hide();
    });

    $(document).on('click', '.js-edit-comment', function () {
        var $comment = $(this).closest('.editorial-comment');

        $comment.find('.edit-comment-form:first').show();
        $comment.find('.editorial-comment-body:first').hide();
    });

    $(document).on('click', '.js-cancel-edit-comment', function () {
        var $comment = $(this).closest('.editorial-comment');

        $comment.find('.edit-comment-form:first').hide();
        $comment.find('.editorial-comment-body:first').show();
    });

    $(document).on('click', '.js-save-edit-comment', function () {
        var $button = $(this);
        var $comment = $button.closest('.editorial-comment');
        var contentId = $('#content-workflow-box').data('content-id');
        var commentId = $comment.data('comment-id');
        var body = $comment.find('.edit-comment-body:first').val();

        if (!body) {
            return;
        }

        $button.prop('disabled', true);

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comment/update'),
            method: 'POST',
            dataType: 'json',
            data: {
                comment_id: commentId,
                comment: body
            }
        }).done(function (response) {
            if (response.success) {
                loadEditorialComments();
                loadEditorialCommentSummary();
                $('#load-content-activity').trigger('click');
            }
        }).always(function () {
            $button.prop('disabled', false);
        });
    });

    $(document).on('click', '.js-resolve-comment', function () {
        var $comment = $(this).closest('.editorial-comment');
        var contentId = $('#content-workflow-box').data('content-id');

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comment/resolve'),
            method: 'POST',
            dataType: 'json',
            data: {
                comment_id: $comment.data('comment-id')
            }
        }).done(function (response) {
            if (response.success) {
                loadEditorialComments();
                loadEditorialCommentSummary();
                $('#load-content-activity').trigger('click');
            }
        });
    });

    $(document).on('click', '.js-reopen-comment', function () {
        var $comment = $(this).closest('.editorial-comment');
        var contentId = $('#content-workflow-box').data('content-id');

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comment/reopen'),
            method: 'POST',
            dataType: 'json',
            data: {
                comment_id: $comment.data('comment-id')
            }
        }).done(function (response) {
            if (response.success) {
                loadEditorialComments();
                loadEditorialCommentSummary();
                $('#load-content-activity').trigger('click');
            }
        });
    });

    var deleteEditorialCommentId = null;

    $(document).on('click', '.js-delete-comment', function () {
        var $comment = $(this).closest('.editorial-comment');

        deleteEditorialCommentId = $comment.data('comment-id');

        var preview = $.trim($comment.find('.editorial-comment-body:first').text());

        if (preview) {
            $('#delete-editorial-comment-preview')
                .html(escapeHtml(preview.substring(0, 300)))
                .show();
        } else {
            $('#delete-editorial-comment-preview').hide().empty();
        }

        $('#delete-editorial-comment-modal').modal('show');
    });

    $(document).on('click', '#confirm-delete-editorial-comment', function () {
        var $button = $(this);
        var contentId = $('#content-workflow-box').data('content-id');

        if (!deleteEditorialCommentId) {
            return;
        }

        $button.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: contentWorkflowUrl(contentId, 'comment/delete'),
            method: 'POST',
            dataType: 'json',
            data: {
                comment_id: deleteEditorialCommentId
            }
        }).done(function (response) {
            if (response.success) {
                $('#delete-editorial-comment-modal').modal('hide');

                loadEditorialComments();
                loadEditorialCommentSummary();
                $('#load-content-activity').trigger('click');

                showWorkflowMessage('Editorial comment deleted.', 'success');
            }
        }).fail(function () {
            showWorkflowMessage('Editorial comment could not be deleted.', 'error');
        }).always(function () {
            $button.prop('disabled', false).text('Delete Comment');
            deleteEditorialCommentId = null;
        });
    });


    updateWorkflowUi(
        $('#workflow-stage').data('stage') || 'draft',
        $('[name="status"]').val()
    );
    loadEditorialComments();
    loadEditorialCommentSummary();
    applyEditorialCommentsState();
})(jQuery);
