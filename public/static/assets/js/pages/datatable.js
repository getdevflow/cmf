$(function () {
    $("#example1").DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "aaSorting": [],
        "ordering": true,
        "info": true,
        "autoWidth": true,
        "deferRender": true
    });
    $('#content').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "autoWidth": true
    });
    $('#product').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "autoWidth": true
    });
    $('#user').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "autoWidth": true
    });
});
