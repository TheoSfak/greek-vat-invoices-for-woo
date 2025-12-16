jQuery(document).ready(function($) {
    // Add upload button after logo input field
    $('#grvatin_company_logo').after('<button type="button" class="button grvatin-upload-logo" style="margin-left: 10px;">Επιλογή Εικόνας</button><button type="button" class="button grvatin-remove-logo" style="margin-left: 5px; display: none;">Αφαίρεση</button>');
    
    // Add image preview
    var logoUrl = $('#grvatin_company_logo').val();
    if (logoUrl) {
        $('#grvatin_company_logo').after('<div class="grvatin-logo-preview" style="margin-top: 10px;"><img src="' + logoUrl + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;"></div>');
        $('.grvatin-remove-logo').show();
    }
    
    // Media uploader
    var mediaUploader;
    
    $('.grvatin-upload-logo').on('click', function(e) {
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
            $('#grvatin_company_logo').val(attachment.url);
            
            // Remove old preview if exists
            $('.grvatin-logo-preview').remove();
            
            // Add new preview
            $('.grvatin-remove-logo').before('<div class="grvatin-logo-preview" style="margin-top: 10px;"><img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px; border: 1px solid #ddd; padding: 5px;"></div>');
            $('.grvatin-remove-logo').show();
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Remove logo
    $('.grvatin-remove-logo').on('click', function(e) {
        e.preventDefault();
        $('#grvatin_company_logo').val('');
        $('.grvatin-logo-preview').remove();
        $(this).hide();
    });
});
