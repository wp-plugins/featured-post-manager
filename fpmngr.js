jQuery(function($){
	
	var fpmngr_content = $("#fpmngr_div").html();
	$("#normal-sortables:eq(0)").prepend(fpmngr_content);
	
	$("#select_category").click(function(){
		var cat = $(this).prev().val();
		if(is_int(cat)) {
			window.location = '/wp-admin/index.php?fpmngr_mode=select_cat&fpmngr_cat=' + cat;
		}
		return false;
	});
	
	$("#fpmngr_list li span").click(function(){
		var id = $(this).attr("title");
		if(is_int(id)) {
			window.location = '/wp-admin/index.php?fpmngr_mode=unfeature&fpmngr_id=' + id;
		}
		return false;
	});
	
	$("#fpm_feature_post").click(function(){
		var id = $(this).prev().val();
		if(is_int(id)) {
			window.location = '/wp-admin/index.php?fpmngr_mode=feature&fpmngr_id=' + id;
		}
		return false;
	});

});

function is_int(val) {
    if (isNaN(parseFloat(val))) {
          return false;
     }
     return true
}