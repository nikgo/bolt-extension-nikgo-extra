(function() {
   'use strict';
   
   var scriptEls = document.getElementsByTagName( 'script' );
   var thisScriptEl = scriptEls[scriptEls.length - 1];
   var scriptPath = thisScriptEl.src;
   var scriptFolder = scriptPath.substr(0, scriptPath.lastIndexOf( '/' )+1 );  
   
   alert(scriptFolder);
    
    
    
});