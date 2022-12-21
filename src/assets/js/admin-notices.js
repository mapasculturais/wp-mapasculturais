(function (wp, notices) {
    wp.data.dispatch('core/notices').createNotice(
        'error',
        notices.text,
        {
            __unstableHTML: true,
            isDismissible: true,
        },
    )
})(window.wp, window.MapasNotices)
