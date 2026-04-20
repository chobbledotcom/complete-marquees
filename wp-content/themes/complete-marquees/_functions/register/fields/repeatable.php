<a class="repeatable-add button" href="#">Add field</a>
<ul id="<?php echo $id?>-repeatable" class="custom_repeatable">
	<li>
		<span class="sort hndle">Sort |||  </span>
					
        <span>Label</span> 
		<input type="text" name="<?php echo $id?>" id="<?php echo $id?>" value="<?php echo $value ?>" size="15" /> 
        
        <span>Field name</span>
        <input type="text" name="<?php echo $id?>" id="<?php echo $id?>" value="<?php echo $value ?>" size="15" /> 
					
        <a class="repeatable-remove button" href="#">Remove</a>
    </li>
</ul>
<span class="description">Drag and drop the fields in the desired order.</span>
<script>jQuery(document).ready(function($){ 
jQuery('.repeatable-add').click(function() {
    field = jQuery(this).closest('td').find('.custom_repeatable li:last').clone(true);
    fieldLocation = jQuery(this).closest('td').find('.custom_repeatable li:last');
    jQuery('input', field).val('').attr('name', function(index, name) {
        return name.replace(/(\d+)/, function(fullMatch, n) {
            return Number(n) + 1;
        });
    })
    field.insertAfter(fieldLocation, jQuery(this).closest('td'))
    return false;
});
 
jQuery('.repeatable-remove').click(function(){
    jQuery(this).parent().remove();
    return false;
});
     
jQuery('.custom_repeatable').sortable({
    opacity: 0.6,
    revert: true,
    cursor: 'move',
    handle: '.sort'
});
});
</script>