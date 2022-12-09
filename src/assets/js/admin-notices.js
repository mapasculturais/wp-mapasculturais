(function (wp, notices) {
    wp.data.dispatch('core/notices').createNotice(
        'warning',
        notices.text,
        {
            __unstableHTML: true,
            isDismissible: true,
        },
    )
})(window.wp, window.MapasNotices)
