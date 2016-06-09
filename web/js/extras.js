(function (bolt, $, CKEDITOR) {
    'use strict';

    // Register namespace
    bolt.extensions = bolt.extensions || {};
    bolt.extensions.nikgo = bolt.extensions.nikgo || {};

    var extension = {};

    var scriptEls = document.getElementsByTagName('script');
    var thisScriptEl = scriptEls[scriptEls.length - 1];
    var scriptPath = thisScriptEl.src;
    var scriptFolder = scriptPath.substr(0, scriptPath.lastIndexOf('/') + 1);

    // CKEDITOR
    CKEDITOR.plugins.addExternal('image2', scriptFolder + 'ck/image2/');
    CKEDITOR.plugins.addExternal('boltbrowser', scriptFolder + 'ck/boltbrowser/');
    CKEDITOR.plugins.addExternal('magicline', scriptFolder + 'ck/magicline/');
    CKEDITOR.plugins.addExternal('quicktable', scriptFolder + 'ck/quicktable/');
    CKEDITOR.plugins.addExternal('blockimagepaste', scriptFolder + 'ck/blockimagepaste/');

    // Override Bolt Function
    bolt.stack.selectFromPulldown = (function (original) {
        return function (key, path) {
            //do something additional
            if (console) {
                console.log("Key; ", key);
                console.log("Path: ", path);
            }
            if (key === 'ckeditor') {

                var dialog = CKEDITOR.dialog.getCurrent();

                if (dialog.getName() === 'image2') {
                    var element;
                    element = dialog.getContentElement('info', 'src');
                    if (element) {
                        element.setValue('/files/' + path);
                    }
                }

            }
            // call original function
            original(key, path);
        };
    })(bolt.stack.selectFromPulldown);

    //Extension
    extension.selectCKDialog = function () {
        // /async/browse/files/events?key=image
        var key = "ckeditor";

        // Dialog Template:
        //    <div class="modal fade" id="selectModal-{{ key }}" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        //        <div class="modal-dialog modal-lg">
        //            <div class="modal-content"></div>
        //        </div>
        //    </div>

        var $modal = $('#selectModal-' + key);
        if (!$modal.length) {
            $modal = $('<div/>', {
                id: 'selectModal-' + key,
                class: 'modal fade',
                tabindex: '-1',
                role: 'dialog',
                style: 'z-index: 10020;'
            });

            var $modalDialog = $('<div/>', {
                class: 'modal-dialog modal-lg'
            });

            var $modalContent = $('<div/>', {
                class: 'modal-content'
            });

            $modalContent.append($('<div/>', {
                class: 'modal-body'
            }).text("Wird geladen..."));

            var $modalFooter = $('<div/>', {
                class: 'modal-footer'
            });

            $modalFooter.append($('<button/>', {
                type: 'button',
                class: 'btn btn-default',
                'data-dismiss': 'modal'
            }).text("Schließen"));

            $modalContent.append($modalFooter);

            $modal.append($modalDialog);
            $modalDialog.append($modalContent);
            $('body').append($modal);

            var folderUrl = bolt.conf('paths.async') + 'browse/files?key=' + key;

            $modalContent.load(folderUrl, function () {
                bolt.actions.init();
            });
        }

        $modal.modal('show');
    };

    // Register plugin
    bolt.extensions.nikgo.extra = extension;

})(Bolt, jQuery, CKEDITOR);
