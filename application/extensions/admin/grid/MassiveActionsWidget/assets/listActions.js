/**
 * Massive actions Widget, action behaviour
 *
 * This JavaScript define what's happen when user click on an action
 */

/**
 * Define what happen when an action is clicked:
 *
 * - redirection:
 *      post
 *      fill session
 *
 * - Show a validation modal:-
 *      perform an ajax request and close
 *      perform an ajax request and show the result in the modal
 */
 $(document).on('click', '.listActions a', function ()
{
    $that          = $(this);                                                             // The cliked link
    $actionUrl     = $that.data('url');                                                   // The url of the Survey Controller action to call
    $gridid        = $('.listActions').data('grid-id');
    $oCheckedItems = $.fn.yiiGridView.getChecked($gridid, $('.listActions').data('pk')); // List of the clicked checkbox
    $oCheckedItems = JSON.stringify($oCheckedItems);

    // TODO : Switch action (post, session, ajax...)

    // For actions without modal, doing a redirection
    // TODO: replace all of them with the method above

    // TODO : Switch case "redirection (with 2 type; post or fill session)"
    if($that.data('actionType')=="redirect")
    {
        $oCheckedItems = $.fn.yiiGridView.getChecked($gridid, $('.listActions').data('pk')); // So we can join
        var newForm = jQuery('<form>', {
            'action': $actionUrl,
            'target': '_blank',
            'method': 'POST'
        }).append(jQuery('<input>', {
            'name': $that.data('input-name'),
            'value': $oCheckedItems.join("|"),
            'type': 'hidden'
        })).append(jQuery('<input>', {
            'name': 'YII_CSRF_TOKEN',
            'value': LS.data.csrfToken,
            'type': 'hidden'
        })).appendTo('body');
        newForm.submit();
        return;
    }

    // For actions without modal, doing a redirection
    // Using session before redirect rather than form submission
    if($that.data('actionType') == 'fill-session-and-redirect')
    {
     // postUrl is defined as a var in the View
     $(this).load(postUrl, {
         participantid:$oCheckedItems},function(){
             $(location).attr('href',$actionUrl);
     });
     return;
    }


    // TODO: switch case "Modal"
    $modal  = $('#'+$that.data('modal-id'));   // massive-actions-modal-<?php $aAction['action'];?>-<?php echo $key; ?>

    // Needed modal elements
    $modalTitle    = $modal.find('.modal-title');                   // Modal Title
    $modalBody     = $modal.find('.modal-body-text');               // Modal Body
    $modalButton   = $modal.find('.btn-ok');

    $modalClose    = $modal.find('.modal-footer-close');            // Modal footer with close button
    $ajaxLoader    = $("#ajaxContainerLoading");                    // Ajax loader

    // Original modal state
    $oldModalTitle     = $modalTitle.text();
    $oldModalBody      = $modalBody.html();
    $oldModalButtons   = $modal.find('.modal-footer-buttons');     // Modal footer with yes/no buttons

    // When user close the modal, we put it back to its original state
    $modal.on('hidden.bs.modal', function (e) {
     $modalTitle.text($oldModalTitle);               // the modal title
     $modalBody.empty().append($oldModalBody);       // modal body
     $modalClose.hide();                             // Hide the 'close' button
     $oldModalButtons.show();                        // Show the 'Yes/No' buttons

     if ($that.data('grid-reload') == "yes")
     {
        $.fn.yiiGridView.update($gridid);                         // Update the surveys list
        setTimeout(function(){
            $('#'+$gridid).trigger("actions-updated");}, 500);    // Raise an event if some widgets inside the modals need some refresh (eg: position widget in question list)
     }

    })

    // Define what should be done when user confirm the mass action
    $modalButton.on('click', function(){

        // Custom datas comming from the modal (like sid)
        $postDatas  = {sItems:$oCheckedItems};
        $modal.find('.custom-data').each(function(i, el)
        {
            $postDatas[$(this).attr('name')]=$(this).val();
        });

        // Custom attributes to updates (like question attributes)
        $aAttributesToUpdate = [];
        $modal.find('.attributes-to-update').each(function(i, el)
        {
        $aAttributesToUpdate.push($(this).attr('name'));
        });
        $postDatas['aAttributesToUpdate'] = JSON.stringify($aAttributesToUpdate);

        // Update the modal elements
        // TODO: ALL THIS DEPEND ON KEEPOPEN OR NOT
        $modalBody.empty();                                         // Empty the modal body
        $oldModalButtons.hide();                                    // Hide the 'Yes/No' buttons
        $modalClose.show();                                         // Show the 'close' button
        $ajaxLoader.show();                                         // Show the ajax loader

        // Ajax request
        $.ajax({
            url : $actionUrl,
            type : 'POST',
            data :  $postDatas,

            // html contains the buttons
            success : function(html, statut){
                $ajaxLoader.hide();                                 // Hide the ajax loader

                // This depend on keepopen
                $modalBody.empty().html(html);                      // Inject the returned HTML in the modal body

                if( $modal.data('keepopen') != 'yes' )
                {
                    $modal.modal('hide');
                }
            },
            error :  function(html, statut){
                $ajaxLoader.hide();
                $modal.find('.modal-body-text').empty().html(html.responseText);
                console.log(html);
            }
        });
    });

    // open the modal
    if( $oCheckedItems !== '[]' )
    {
     $modal.modal();
    }
    else
    {
    //If no item selected, the error modal "please select first an item" is shown
    // TODO: add a variable in the widget to replace "item" by the item type (e.g: survey, question, token, etc.)
    $('#error-first-select').modal();
    }
});

/**
 * Bootstrap switch extension
 *
 * 1. Setting the value
 * By default, bootstrap switch use the val() jQuery function, which works well for form submission.
 * But, for the ajax request, we need to collect the value in a array for the post using $postDatas[$(this).attr('name')]=$(this).val();
 * So we need to change the value using $(this).attr('value', state);.
 * The difference can be seen visually in the browser code explorer : by default, the bootstrap switch extension change in an invisble way.
 * With the method here, the input value change will be visible.
 *
 * 2. Defining value Type
 * By default, bootstrap switch use boolean values {true,false} for its states.
 * In the PHP code (like in controller questions::setMultipleAttributes()), we want to keep the code as dry as possible.
 * To avoid to create a single method for each action using bootstrap-switch, just to change the boolean value to something else ({1,0} or {Y,N}, etc), we perform it here.
 * e.g: a bootstrap-switch with the class bootstrap-switch-integer will have its value converted to integer.
 *
 * 3. Managing grid refresh
 * For now, the modals are injected on the bottom of the selector, which is in the grid footer, which is reload on grid refresh
 * So, when refreshing the grid, the bootstrap-switch must be re-applyed to the elements
 *
 */

 function prepareBsSwitchBoolean($gridid){
     // Bootstrap switch with class "bootstrap-switch-boolean" will use the default boolean values.
     // e.g: question mandatory, question other, etc
     $('.bootstrap-switch-boolean').each(function(){
         $(this).bootstrapSwitch();
         $(this).attr('value', false);                                           // we specify its value in a "visible" way (see point 1)

         // Switch change
         $(this).on('switchChange.bootstrapSwitch', function(event, state) {
             $(this).attr('value', state);                                       // When the switch change,we specify its value in a "visible" way (see point 1)
         });
     });
}

function prepareBsSwitchInteger($gridid){
    // Bootstrap switch with class "bootstrap-switch-integer" will use integer values
    // e.g: question statistics_showgraph, question public_statistics, etc
    $('.bootstrap-switch-integer').each(function(){
        $(this).bootstrapSwitch();
        $(this).attr('value', 0);                                               // we specify its value in a "visible" way (see point 1)

        // Switch change
        $(this).on('switchChange.bootstrapSwitch', function(event, state) {
            var intValue = (state==true)?'1':'0';                               // Convertion of the boolean to integer (see point 2)
            $(this).attr('value', intValue)
        });
    });
}

 $(document).ready(function() {
     prepareBsSwitchBoolean(gridId);
     prepareBsSwitchInteger(gridId);

    // Grid refresh: see point 3
    $(document).on('actions-updated', '#'+gridId,  function(){
        prepareBsSwitchBoolean(gridId);
        prepareBsSwitchInteger(gridId);
    });
});
