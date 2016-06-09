// http://alfonsoml.blogspot.de/2014/02/how-to-insert-new-browse-page-button-in.html
// https://stackoverflow.com/questions/21381840/custom-button-on-ckeditors-file-browser

(function (bolt, CKEDITOR) {

    'use strict';

    CKEDITOR.plugins.add('boltbrowser', {
        requires: 'image2',
        init: function (editor)
        {
            CKEDITOR.on("dialogDefinition", function (e) {
                
                // Take the dialog name and its definition from the event data.
                var dialog = e.data;

                if ((e.editor !== editor) || (dialog.name !== 'image2')) {
                    return;
                }

                //Customize "Image Map" dialog 
                if (dialog.name === "image2") {
                    var dialogDefinition = dialog.definition;
                    var infoTab = dialogDefinition.getContents('info');

                    // get the existing "browse" button
                    // var browseButton = infoTab.get('browse');

                    // Create a new "Browse page" button, linking to our custom page browser
                    var browseMediaButton = {
                        type: 'button',
                        id: 'browseMedia',
                        hidden: false,
                        style: 'display:inline-block;margin-top:17px; ',
                        filebrowser:
                                {
                                    action: 'Browse',
                                    target: 'info:url',
                                    url: 'files'
                                },
                        label: 'Medien',
                        onClick: bolt.extensions.nikgo.extra.selectCKDialog
                    };

                    // Create a container for the new button and replace the existing browse button with this one
                    var hBox = {type: 'hbox', widths: ['120px', '120px'], children: [browseMediaButton]};
                    infoTab.add(browseMediaButton, 'browse');
                    infoTab.remove('browse');

                }
            });
        }
    });

})(Bolt, CKEDITOR);