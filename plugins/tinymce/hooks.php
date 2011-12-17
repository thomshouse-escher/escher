<?php

$hooks = Load::Hooks();

$hooks->registerEvent('rte_classname','__tinymce_init_classname',0,1);
$hooks->registerEvent('rte_file_popup','__tinymce_file_popup',0,0);

function __tinymce_init_classname($modifiers=NULL) {
	static $inits; if (is_null($inits)) { $inits = array(); }
	static $uploadcode;
	$CFG = Load::Config();
	$headers = Load::Headers();
	
	// Plugins and Buttons
	$plugins = array('contextmenu','inlinepopups','paste','safari','searchreplace','table','advimage');
	$buttons1 = array('formatselect','|','justifyleft','justifycenter','justifyright','justifyfull','|',
		'blockquote','bullist','numlist','|','outdent','indent','|','hr');
	$buttons2 = array('bold','italic','|','link','unlink','image','|','forecolor','|',
		'undo','redo','|','removeformat','code');
	$buttons3 = array();
	
	// Generate the hash for this particular config
	$cfghash = md5(implode(',',$plugins).'|'.implode(',',$buttons2).'|'.implode(',',$buttons2).'|'.implode(',',$buttons3));
	
	// Don't load this config if we already have
	if (!in_array($cfghash,$inits)) {
		$inits[] = $cfghash;

		// Are we using gzip?
		if (function_exists('gzcompress') && !ini_get('zlib.output_compression')) {
			$headers->addJS("{$CFG['wwwroot']}/plugins/tinymce/js/tiny_mce_gzip.js");
			$disk_cache = is_writable(dirname(__FILE__)) ? "true" : "false";
	
			$headers->addFootHTML(
			
/* Header Javascript */'
<script type="text/javascript">
	tinyMCE_GZ.init({
		themes : "advanced",
		plugins : "'.implode(',',$plugins).'",
		languages : "en",
		disk_cache : '.$disk_cache.'
	});
</script>'
/* End Script tag */);
	
		} else {
			// If no Gzip, just load the regular js.
			$headers->addJS("{$CFG['wwwroot']}/plugins/tinymce/js/tiny_mce.js");
		}
	
		$headers->addFootHTML(
	
/* Header Javascript */'
<script type="text/javascript">
	tinyMCE.init({
		mode : "specific_textareas",
		editor_selector : "tinymce'.$cfghash.'",
		theme : "advanced",
		skin : "escher",
		plugins : "'.implode(',',$plugins).'",
		inlinepopups_skin : "escher",
		file_browser_callback : "escher_mce_upload",
		relative_urls : false,
		theme_advanced_buttons1 : "'.implode(',',$buttons1).'",
		theme_advanced_buttons2 : "'.implode(',',$buttons2).'",
		theme_advanced_buttons3 : "'.implode(',',$buttons3).'",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing: true,
		theme_advanced_resize_horizontal: false,
		theme_advanced_blockformats : "p,h1,h2,h3,blockquote"
	});
</script>'
/* End Javascript */);
	}
	
	/* Initialize upload code, but only if this is the first editor on the page */
	if (is_null($uploadcode)) {
		$uploadcode = 1;
		$headers->addFootHTML(

/* File Upload Javascript */'
<script type="text/javascript">
function escher_mce_upload(field_name,url,type,win) {
	tinyMCE.activeEditor.windowManager.open({
		file : "'.$CFG['wwwroot'].'/uploads/?popup=true&type=" + type,
		title : "Browse Uploads",
		width : 500,  // Your dimensions may differ - toy around with them!
		height : 400,
		resizable : "yes",
		inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
		close_previous : "no",
		popup_css : false
	}, {
		window : win,
		input : field_name
	});
	return false;
}
</script>'
/* End Javascript */);
	}

	return 'tinymce'.$cfghash;
}

function __tinymce_file_popup() {
	$CFG = Load::Config();
	$headers = Load::Headers();
	$headers->addJS("{$CFG['wwwroot']}/plugins/tinymce/js/tiny_mce_popup.js");
	$headers->addFootHTML(
	
/* Popup header HTML */'
<script language="javascript" type="text/javascript">
var FileBrowserDialogue = {
    init : function () { },
    selectFile : function (URL) {
        var win = tinyMCEPopup.getWindowArg("window");
        win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = URL;
        if (typeof(win.ImageDialog) != "undefined")
        {
            if (win.ImageDialog.getImageData) win.ImageDialog.getImageData();
            if (win.ImageDialog.showPreviewImage) win.ImageDialog.showPreviewImage(URL);
        }
        tinyMCEPopup.close();
    }
}

tinyMCEPopup.onInit.add(FileBrowserDialogue.init, FileBrowserDialogue);

</script>'
/* End Javascript */);
	return 'FileBrowserDialogue.selectFile';
}