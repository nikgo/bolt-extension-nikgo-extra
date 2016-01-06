nikgo's Bolt Extras
===================

**Warning: Since these changes override Bolt core and template files on the backend it will probably break on upgrade or if you have an unusual bolt install. 
It will be version locked to very specififc range of bolt versions.**

This extension adds a couple of modifications to bolt that I have found useful.

To use this extension add these lines in your `extensions/composer.json`:

````json
{
    ...
    "repositories": {
        "packagist": false,
        "nikgo-extra": {
            "type": "vcs",
            "url": "https://github.com/nikgo/bolt-extension-nikgo-extra"
        }
        ...
    },
    ...
    require": {
        "nikgo/extra": "dev-master"
    },
    ...
}
````
Now you can install this extension in Bolt backend.

# Changes

This release contains changes to:

## Bolt Core

#### Override `Bolt\Storage` class

*Patch for Bolt 2.2 - Storage component has refactored in 3.0*

Changes especially to content search functions:

* Set LIMIT to 500 in SQL Statement
* Combined all fields to match all words in a query for better results
   => Is one word missing, result is not show.
  Example SQL Query:
````sql
( ( bolt_pages.title LIKE '%search%' OR bolt_pages.body LIKE '%search%' )
    AND ( bolt_pages.title LIKE '%all%' OR bolt_pages.body LIKE '%all%' )
    AND ( bolt_pages.title LIKE '%words%' OR bolt_pages.body LIKE '%words%' ) )
````
* Allow only five words in a search query

#### Patch translation path 

*Workaround for Bolt 2.2 - fix in 3.0 available, see Bolt issue #3553 and #3800*

* Load translation files from `app/resources/translations/` for composer installations.

  Example: `de_DE/contenttypes.de_DE.yml`

#### Bolt Backend

* Override `_base/listing.twig` 
 * Show only sortorder if is not empty
 * Show usernames (set trimtext limit from 15 up to 25 chars) 

## CKEditor 

* Extend `Bolt.stack.selectFromPullDown` for CKEditor dialog handling 
* Load extra plugins
  * boltbrowser (Override image browser with Bolt native select dialog)
  * image2
  * magicline
  * quicktable
  * blockimagepaste (Allow only images with relative paths and remove base64 images)

To use CKEditor enchancements in your Bolt installation set `customConfig` in your contenttype html fields or global ck config.

**Example:**

* config.yml

````yaml
wysiwyg:
    ...
    ck:
       ...
       customConfig : '/custom/ckconfig.js'
````

* custom/ckconfig.js

Create this file in your `web` folder:

````javascript
CKEDITOR.editorConfig = function (config)
{
    // Plugins
    // Note: 'stylesheetparser' plugin is incompatible with Advanced Content Filter, 
    // so it disables the filter after installing.
    
    var extraPlugins = 'image2,boltbrowser,quicktable,magicline,blockimagepaste';
    var removePlugins = 'stylesheetparser,tableresize';
    
    config.removePlugins += config.removePlugins ? ',' + removePlugins : removePlugins;
    config.extraPlugins += config.extraPlugins ? ',' + extraPlugins : extraPlugins;

    // Image2
    config.image2_alignClasses = ['left', 'text-center', 'right'];
}

````

**Tipp:** For more ckeditor config paramaters see http://docs.ckeditor.com. You can change toolbar and more.  

## Twig

* Clean excerpt: Add twig function and filter for `cleanexcerpt` to remove annoying spaces and undersorce in a excerpt. 
