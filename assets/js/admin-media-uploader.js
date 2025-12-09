jQuery(document).ready(function($) {
    // Add upload button after logo input field
    $('#wcgvi_company_logo').after('<button type="button" class="button wcgvi-upload-logo" style="margin-left: 10px;">Επιλογή Εικόνας</button><button type="button" class="button wcgvi-remove-logo" style="margin-left: 5px; display: none;">Αφαίρεση</button>');
    
    // Add image preview
    var logoUrl = $('#wcgvi_company_logo').val();
    if (logoUrl) {
        $('#wcgvi_company_logo').after('<div class="wcgvi-logo-preview" style="margin-top: 10px;"><img src="' + logoUrl + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;"></div>');
        $('.wcgvi-remove-logo').show();
    }
    
    // Media uploader
    var mediaUploader;
    
    $('.wcgvi-upload-logo').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Extend the wp.media object
        mediaUploader = wp.media({
            title: 'Επιλέξτε Λογότυπο',
            button: {
                text: 'Χρήση αυτής της εικόνας'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#wcgvi_company_logo').val(attachment.url);
            
            // Remove old preview if exists
            $('.wcgvi-logo-preview').remove();
            
            // Add new preview
            $('.wcgvi-remove-logo').before('<div class="wcgvi-logo-preview" style="margin-top: 10px;"><img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;"></div>');
            $('.wcgvi-remove-logo').show();
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Remove logo
    $('.wcgvi-remove-logo').on('click', function(e) {
        e.preventDefault();
        $('#wcgvi_company_logo').val('');
        $('.wcgvi-logo-preview').remove();
        $(this).hide();
    });
});
