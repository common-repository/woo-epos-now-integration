jQuery(document).ready(function ($) {
    $(".select2").select2({
        tags: true,
        tokenSeparators: [',', ' ']
    });
});
function deleteConfirmFunction() {
    var r = confirm("This action will delete log file(s). \r\n Are you sure to do this action?");
    if (r == true) {
        return true;
    } else {
        return false;
    }
}