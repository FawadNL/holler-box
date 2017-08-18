(function(window, document, $, undefined){

  var holler = {};

  holler.init = function() {

  	holler.listeners();
  	holler.toggleShowOn();
    holler.toggleTypes();
  	holler.toggleDatepicker();
  	holler.emailCheckbox();
    holler.toggleEmailForm();

    $('.hwp-datepicker').datepicker({
  		dateFormat : 'mm/dd/yy'
  	});

  	$('.hwp-colors').wpColorPicker();

    $('#settings_meta_box, #popout_meta_box').addClass('closed');
    
  }

  holler.listeners = function() {

  	// Handle live preview update with visual editor
  	$(document).on( 'tinymce-editor-init', function( event, editor ) {

  		// Update preview on first page load
  		holler.updatePreviewContent();

  		// add listener to visual editor for live updates
  		window.tinymce.activeEditor.on( 'keyup', function(e) {
  			holler.updatePreviewContent();
  		})

  	} );

  	// Updates preview if page loaded with HTML editor tab
  	if( $('#wp-content-wrap').hasClass('html-active') ) {
  		holler.updatePreviewContent();
  	}

  	$('body')
  	.on('change', 'input[name=show_optin]', holler.emailCheckbox )
    .on('change', '.hwp-switch input', holler.toggleSwitch )
  	.on('change', 'input[name=expiration]', holler.toggleDatepicker )
  	.on('change', 'input[name=show_on]', holler.toggleShowOn )
    .on('change', 'input[name=hwp_type]', holler.toggleTypes )
    .on('change', 'select[name=email_provider]', holler.toggleEmailForm )
  	.on('keyup', '#content', holler.updatePreviewContent )
    .on('focus', 'input#scroll_delay', function() {
      $('input[name=display_when][value=delay]').prop('checked', 'checked'); 
    })

    $('#show_on_pages').suggest( window.ajaxurl + "?action=hwp_ajax_page_search", {multiple:true, multipleSep: ","});

  }

  holler.toggleShowOn = function() {

    var showOnVal = $('input[name=show_on]:checked').val();
    var certainPages = $('#show-certain-pages');
    var cats = $('#hwp-cats');
    var tags = $('#hwp-tags');
    var types = $('#hwp-types');
    var exclude = $('#hwp-exclude');

    switch( showOnVal ) {
      case 'all':
        cats.hide();
        tags.hide();
        certainPages.hide();
        types.hide();
        exclude.hide();
        break;
      case 'limited':
        cats.show();
        tags.show();
        certainPages.show();
        types.show();
        exclude.show();
        break;
      default:
        cats.hide();
        tags.hide();
        certainPages.hide();
        types.hide();
        exclude.hide();
    }

  }

  // hide n/a settings when using banner
  holler.toggleTypes = function() {

    var val = $('input[name=hwp_type]:checked').val();
    var pos = $('#position-settings');
    var popChat = $('#popout_meta_box, #show_chat');
    var hideBtn = $('#hide_btn, label[for=hide_btn]');
    var popMeta = $('#popout_meta_box');
    var showOptin = $('#show-optin');

    switch( val ) {
      case 'holler-banner':
        popChat.hide();
        hideBtn.fadeIn();
        popMeta.hide();
        pos.hide();
        showOptin.fadeIn();
        break;
      case 'hwp-popup':
        hideBtn.hide();
        popChat.fadeIn();
        popMeta.hide();
        pos.hide();
        showOptin.fadeIn();
        break;
      case 'popout':
        popMeta.fadeIn();
        pos.fadeIn();
        showOptin.fadeIn();
        break;
      case 'fomo':
        popMeta.hide();
        popChat.hide();
        showOptin.hide();
        break;
      default:
        popChat.fadeIn();
        hideBtn.fadeIn();
        pos.fadeIn();
        popMeta.hide();
        showOptin.fadeIn();
    }
  }

  // New item selected, update preview and settings display
  holler.emailCheckbox = function() {

    var optin = $("#show-email-options, #hwp-note-optin");

    if( $('input[name=show_optin]').is(':checked') ) {

      optin.fadeIn();

    } else {

      optin.fadeOut(200);

    }


  }

  // Handle display of different email options
  holler.toggleEmailForm = function() {

    var sendTo = $('#send-to-option');
    var defaultDiv = $('#default-email-options');
    var custom = $('#custom-email-options');
    var checkedVal = $('select[name=email_provider]').val();
    var itemTypeVal = $('input[name=item_type]:checked').val();
    var optin = $("#hwp-note-optin");
    var mcFields = $('#mailchimp-fields');
    var ckFields = $('#convertkit-fields');
    var mailpoet = $('#mailpoet-fields');

    // Show optin in preview
    if( itemTypeVal === 'optin' ) {

      optin.fadeIn();
      $('#show-email-options').fadeIn();

    }

    if( checkedVal === 'default' ) {
      sendTo.fadeIn();
      defaultDiv.fadeIn();
      custom.hide();
      ckFields.hide();
      mcFields.hide();
      mailpoet.hide();
    } else if( checkedVal === 'custom' ) {
      custom.fadeIn();
      defaultDiv.hide();
      sendTo.hide();
      ckFields.hide();
      mcFields.hide();
      mailpoet.hide();
    } else if( checkedVal === 'mc' ) {
      mcFields.fadeIn();
      defaultDiv.fadeIn();
      ckFields.hide();
      custom.hide();
      sendTo.hide();
      mailpoet.hide();
    } else if( checkedVal === 'ck' ) {
      ckFields.fadeIn();
      defaultDiv.fadeIn();
      mcFields.hide();
      custom.hide();
      sendTo.hide();
      mailpoet.hide();
    } else if( checkedVal === 'mailpoet' ) {
      mailpoet.fadeIn();
      defaultDiv.fadeIn();
      mcFields.hide();
      custom.hide();
      sendTo.hide();
      ckFields.hide();
    }

  }

  holler.toggleChat = function() {

    if( $('input[name=show_chat]').is(':checked') ) {
      $('#hwp-chat').removeClass('hwp-hide');
    } else {
      $('#hwp-chat').addClass('hwp-hide');
    }

  }

  holler.toggleSwitch = function() {

    holler.toggleActiveAjax( $(this).data('id') );

  }

  // Toggle meta value via ajax
  holler.toggleActiveAjax = function( id ) {

    var params = { action: 'hwp_toggle_active', id: id };

    // store interaction data
    $.ajax({
      method: "GET",
      url: window.ajaxurl,
      data: params
      })
      .done(function(msg) {
        console.log(msg);
      })
      .fail(function(err) {
        console.log(err);
      });

  }

  holler.toggleDatepicker = function() {

  	if( $('input[name=expiration]').is(':checked') ) {
  		$('#hwp-until-datepicker').show();
  	} else {
  		$('#hwp-until-datepicker').hide();
  	}

  }

  holler.updatePreviewContent = function() {

  	var content;

  	if( $('#wp-content-wrap').hasClass('tmce-active') ) {
  		// rich editor selected
  		content = window.tinymce.get('content').getContent();
  	} else {
  		// HTML editor selected
  		content = $('#content').val();
  	}

    var firstRow = document.getElementById('hwp-first-row');
    if( firstRow )
  	 firstRow.innerHTML = content;
  }

  holler.init();

  window.hollerAdmin = holler;

})(window, document, jQuery);