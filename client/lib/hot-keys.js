$(window).on('keydown', function (e) {
    if (e.keyCode === 69 && e.ctrlKey) {
        e.preventDefault();
        $('button[data-action="edit"]').trigger('click');
    }
    if (e.keyCode === 83 && e.ctrlKey) {
        e.preventDefault();
        if ($('body').hasClass('modal-open')) {
            $('.modal-dialog').find('button[data-name="save"]').trigger('click');
        } else {
            $('button[data-action="save"]').trigger('click');
        }
    }
});