// http://www.isummation.com/blog/block-drag-drop-image-or-direct-image-paste-into-ckeditor-using-firefox/
// https://stackoverflow.com/questions/6582559/ckeditor-preventing-users-from-pasting-images

(function (CKEDITOR) {
    'use strict';

    CKEDITOR.plugins.add('blockimagepaste', {
        init: function (editor)
        {
            editor.on('paste', this.onPaste.bind(this));
        },
        _parseUri: function (str) {
            // parseUri 1.2.2
            // (c) Steven Levithan <stevenlevithan.com>
            // MIT License
            // see: http://blog.stevenlevithan.com/archives/parseuri

            var o = this._parseUriOptions,
                    m = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
                    uri = {},
                    i = 14;

            while (i--) {
                uri[o.key[i]] = m[i] || "";
            }

            uri[o.q.name] = {};
            uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
                if ($1)
                    uri[o.q.name][$1] = $2;
            });

            return uri;
        },
        _parseUriOptions: {
            strictMode: true,
            key: ["source", "protocol", "authority", "userInfo", "user", "password", "host", "port", "relative", "path", "directory", "file", "query", "anchor"],
            q: {
                name: "queryKey",
                parser: /(?:^|&)([^&=]*)=?([^&]*)/g
            },
            parser: {
                strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
                loose: /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
            }

        },
        _replaceImg: function (str, src) {

            // remove base64 images
            var data = this.options.regexImgSrcBase64.exec(src);
            if (data) {
                // console.log('Base64 Images not allowed.');
                return '';
            }

            var uri = this._parseUri(src);
            //console.log(uri);

            var elem = CKEDITOR.dom.element.createFromHtml(str);

            // replace absolute URL to relative path
            if (uri.host === window.location.hostname) {
                elem.$.src = uri.relative;
                uri = this._parseUri(uri.relative);
                // console.log('Set relative url');
            }

            if (uri.path.match(this.options.regexUriBoltThumbs)) {
                alert("Wählen Sie ihre Bilder über den Bild-Editor aus.");
                return '';
            }

            // allow only relative files
            if (uri.host !== '') {
                alert("Fremde Bilder dürfen aus urheberrechtlichen Gründen nicht eingefügt werden.");
                return '';
            }

            return elem.getOuterHtml();
        },
        onPaste: function (e) {
            var html = e.data.dataValue;

            if (!html) {
                return;
            }

            // console.log(e);
            // console.log(e.data.dataTransfer.getData('cke/widget-id'));
            if (!e.data.dataTransfer.sourceEditor) {
                //console.log("Daten: Extern");
                html = html.replace(this.options.regexImg, this._replaceImg.bind(this));
            }

            e.data.dataValue = html;
        },
        options: {
            regexImgBase64: /<img[^>]*src="data:image\/(.*?);base64,.*?"[^>]*>/gi,
            regexImg: /<img[^>]*src="(.*?)"[^>]*>/gi,
            regexImgSrcBase64: /^data:image\/(.*?);base64,(.*)$/gi,
            regexUriBoltThumbs: /^\/thumbs\/.*$/gi
        }
    });

})(CKEDITOR);