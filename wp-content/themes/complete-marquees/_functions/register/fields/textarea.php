<textarea name="<?php echo $id?>" id="<?php echo $id?>" rows="5" cols="50" class="regular-text MCEadvanced"><?php echo $value?></textarea>
<?php /* uncomment to use tinyMCE text editor or leave to use ordinary textarea
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$("#<?php echo $id?>").addClass("mceEditor");
		if ( typeof( tinyMCE ) == "object" &&
			 typeof( tinyMCE.execCommand ) == "function" ) {
		  tinyMCE.settings = {
			theme : "advanced",
			skin:"wp_theme",
			mode : "none",
			plugins : "media",
			language : "en",
			height:"<?php echo $height; ?>",
			width:"100%",
			theme_advanced_layout_manager : "SimpleLayout",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_buttons1 : "bold,italic,strikethrough,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,link,unlink,wp_more,|,spellchecker,fullscreen,wp_adv",
			theme_advanced_buttons2 : "formatselect,underline,justifyfull,forecolor,|,pastetext,pasteword,removeformat,|,charmap,|,outdent,indent,|,undo,redo,wp_help",
			theme_advanced_buttons3_add : "media",

			
		  };
		  tinyMCE.execCommand("mceAddControl", true, "<?php echo $id?>");
		}
	});
</script>
*/ ?>