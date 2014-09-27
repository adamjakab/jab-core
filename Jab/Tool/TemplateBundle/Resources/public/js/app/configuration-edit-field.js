define(['jquery'], function($) {
    $(document).ready(function() {
        var $form = $("form[name=entity_field]");

        /* Toolbar button actions */
        var $toolbarSaveButtons = $("button.form-button");
        $toolbarSaveButtons.click(function() {
            var formButtonToClick = $("button#entity_field_" + $(this).attr("data-ref"));
            if(formButtonToClick.length == 1) {
                formButtonToClick.click();
            }
        });

        /* Submit form automatically when type changes */
        var $fieldTypeSel = $('#entity_field_type');
        $fieldTypeSel.change(function() {
            //var $form = $(this).closest('form');
            $form.submit();
        });
    });
});
