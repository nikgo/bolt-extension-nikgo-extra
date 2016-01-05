CKEDITOR.editorConfig = function (config)
{

    // Toolbar
    config.toolbar = [
        {name: 'undo', items: ['Undo', 'Redo']},
        {name: 'styles', items: ['Format', 'Styles', 'Paste']},
        {name: 'basicstyles', items: ['Bold', 'Italic', '-', 'RemoveFormat']},
        {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote']},
        {name: 'links', items: ['Link', 'Unlink']},
        {name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar']},
        {name: 'document', items: ['Source']},
        {name: 'tools', items: ['Maximize']}
    ];

    // enable auto start for autoGrow plugin
    config.autoGrow_onStartup = true;

    // set the most common block elements.
    config.format_tags = 'p;h1;h2;h3;pre';

    // Plugins
    // Note: 'stylesheetparser' plugin is incompatible with Advanced Content Filter, 
    // so it disables the filter after installing.

    var extraPlugins = 'magicline,image2,boltbrowser,quicktable,blockimagepaste';
    var removePlugins = 'stylesheetparser,tableresize';

    config.extraPlugins += config.extraPlugins ? ',' + extraPlugins : extraPlugins;
    config.removePlugins += config.removePlugins ? ',' + removePlugins : removePlugins;

    // Image2
    config.image2_alignClasses = ['left', 'text-center', 'right'];

    config.disallowedContent = {
        'table tr td th': {
            styles: true
        },
        table: {
            attributes: true
        }
    };

};