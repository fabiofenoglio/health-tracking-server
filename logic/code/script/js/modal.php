<?php

JHTML::_('behavior.modal');
JHTML::_('script','modal.js', 'media/system/js', true);
JHTML::_('stylesheet','modal.css');

?>

var global_modal_success;

function show_modal( url, options )
{		
    if (!options) 
        options = {};
    
    if (!options.handler)        
        options.handler = 'iframe';
        
    if (!options.size)        
        options.size = {x: 500, y: 300};
    
    SqueezeBox.initialize();
    SqueezeBox.open(url, options);
    
    if (options.closeOnCompletion)
    {
        ;;
    }
    
    if (options.timeout)
    {
        setTimeout(function(){SqueezeBox.close();}, options.timeout);
    }
}