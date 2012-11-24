<?php

class Plugin_tinymce extends Plugin {
	protected $events = array(
		'rte_classname' => 'initClassname',
		'rte_file_popup' => 'popup',
	);

	function initClassname($options=array()) {
		static $inits; if (is_null($inits)) { $inits = array(); }
		static $uploadcode;
		$CFG = Load::Config();
		$headers = Load::Headers();
        $ua = Load::UserAgent();
        $isPhone = $ua->match('phone');

		// Select plugins
		$plugins = !empty($options['plugins'])
            ? $options['plugins']
            : '';
        if (empty($plugins)) {
            $plugins = !empty($CFG['tinymce_options']['plugins'])
                ? $CFG['tinymce_options']
                : array('contextmenu','inlinepopups','paste','safari',
                    'searchreplace','table','advimage');
        }
        if (is_array($plugins)) { $plugins = implode(',',$plugins); }

        // Select buttons
        $buttons = !empty($options['buttons'])
            ? $options['buttons']
            : '';
        if (empty($buttons)) {
            $buttons = !empty($CFG['tinymce_options']['buttons'])
                ? $CFG['tinymce_options']['buttons']
                : '';
        }
        if (empty($buttons)) {
            $buttons = !$isPhone
                ? 'formatselect,|,lcrf,blockquote,lists,indents,hr,/,'
                    . 'bi,|,links,image,|,forecolor,|,undos,|,removeformat,code'
                : 'formatselect,lcr,lists,indents,/,bi,links';
        }
        if ($isPhone) {
            if (!empty($options['phone_buttons'])) {
                $buttons = $options['phone_buttons'];
            } elseif (!empty($CFG['tinymce_options']['phone_buttons'])) {
                $buttons = $CFG['tinymce_options']['phone_buttons'];
            }
        }
        if (is_array($buttons)) { $buttons = implode(',',$buttons);}
        $buttons = str_replace(
            array('lcrf','lcr','lists','indents','bis','bi','links','undos'),
            array('justifyleft,justifycenter,justifyright,justifyfull',
                'justifyleft,justifycenter,justifyright',
                'bullist,numlist','outdent,indent',
                'bold,italic,strikethrough','bold,italic',
                'link,unlink','undo,redo'),
            $buttons
        );
        $buttonRows = explode('/',$buttons);
        foreach($buttonRows as $i => $row) {
            $buttonRows[$i] = trim($row,',');
        }
        while(sizeof($buttonRows)<3) {
            $buttonRows[] = '';
        }
		
		// Generate the hash for this particular config
		$cfghash = md5("$plugins///$buttons");
		
		// Don't load this config if we already have
		if (!in_array($cfghash,$inits)) {
			$inits[] = $cfghash;
            
            // Set up config options
            $mceOptions = array(
                'mode' => 'specific_textareas',
                'editor_selector' => "tinymce-$cfghash",
                'plugins' => $plugins,
                'theme' => "advanced",
                'skin' => "escher",
                'inlinepopups_skin' => 'escher',
                'file_browser_callback' => 'escher_mce_upload',
                'relative_urls' => false,
                'theme_advanced_toolbar_location' => 'top',
                'theme_advanced_toolbar_align' => 'left',
                'theme_advanced_statusbar_location' => 'bottom',
                'theme_advanced_resizing' => true,
                'theme_advanced_resize_horizontal' => false,
                'theme_advanced_blockformats' => 'p,h1,h2,h3,blockquote',
            );
            if (isset($CFG['tinymce_options'])) {
                $mceOptions = array_merge(
                    $mceOptions,
                    array_diff_key(
                        $CFG['tinymce_options'],
                        array_flip(array('plugins','buttons','editor_selector'))
                    )
                );
            }
            $mceOptions = array_merge(
                $mceOptions,
                array_diff_key(
                    $options,
                    array_flip(array('plugins','buttons'))
                )
            );

			// Are we using gzip?
			if (function_exists('gzcompress') && !ini_get('zlib.output_compression')) {
				$headers->addJS("{$CFG['wwwroot']}/plugins/tinymce/js/tiny_mce/tiny_mce_gzip.js");
				$disk_cache = is_writable(dirname(__FILE__)) ? "true" : "false";
		
				$headers->addFootHTML('
					<script type="text/javascript">
						tinyMCE_GZ.init({
							themes : "advanced",
							plugins : "'.$plugins.'",
							languages : "en",
							disk_cache : '.$disk_cache.'
						});
					</script>'
				);
		
			} else {
				// If no Gzip, just load the regular js.
				$headers->addJS("{$CFG['wwwroot']}/plugins/tinymce/js/tiny_mce/tiny_mce.js");
			}
		
			$mceInit = '
				<script type="text/javascript">
					tinyMCE.init({';
            foreach($mceOptions as $k => $v) {
                if (is_string($v) && !preg_match('/^function/i',$v)) {
                    $v = "\"$v\"";
                } elseif (is_bool($v)) {
                    $v = $v ? 'true' : 'false';
                }
                $mceInit .= "
                        $k : $v,";
            }
            foreach($buttonRows as $i => $row) {
                $mceInit .= '
                        theme_advanced_buttons'.($i+1).' : "'.$row.'",';
            }
			$mceInit .= '
               	    });
				</script>';
            $headers->addFootHTML($mceInit);
		}
		
		/* Initialize upload code, but only if this is the first editor on the page */
		if (is_null($uploadcode)) {
			$uploadcode = 1;
			$headers->addFootHTML('
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
			);
		}

		return 'tinymce-'.$cfghash;
	}

	function popup() {
		$CFG = Load::Config();
		$headers = Load::Headers();
		$headers->addJS("{$CFG['wwwroot']}/plugins/tinymce/js/tiny_mce/tiny_mce_popup.js");
		$headers->addFootHTML('
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
		);
		return 'FileBrowserDialogue.selectFile';
	}
}