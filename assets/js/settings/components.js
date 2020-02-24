jQuery(function($) {
    var prerequisites = WcErply.getSetting('prerequisites');

    function enablePrerequisites(component) {
        $.each(prerequisites[component], function(index, prerequisite) {
            console.log(prerequisite);
            $('.wcerply_settings_components_active_workers[value="' + prerequisite + '"]').attr('checked', true);
            enablePrerequisites(prerequisite);
        });
    }

    function disablePrerequisites(component) {
        $.each(prerequisites, function(id, array) {
            if ($.inArray(component, array) >= 0) {
                $('.wcerply_settings_components_active_workers[value="' + id + '"]').attr('checked', false);
                disablePrerequisites(id);
            }
        })
    }

    $('.wcerply_settings_components_active_workers').on('change', function () {
        var component = $(this).val();
        var checked = $(this).is(':checked');

        if (checked) {
            enablePrerequisites(component);
        } else {
            disablePrerequisites(component);
        }
    });
});
