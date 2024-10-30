jQuery.noConflict();
(function( $ ) {
  $(function() {
    
  	//setup datatable plugin
  	$('#product-table').DataTable(
  		{
  			"scrollY":        "400px",
	        "scrollCollapse": true,
	        "paging":         false,
	        "sDom": '<"top"i>rt<"bottom"flp><"clear">'
  		});



	//change filter type
	$('.filter-type').on('change', function()
		{
			//reset categories
			$('.category_select').find('input').each(function()
				{
					$(this).prop('checked', false);
				});
			//reset products
			$('#product-table tbody').find('tr').each(function()
				{
					$(this).find('input[name="gmpf_products[]"]').prop('checked',false);
				});
		});

	//get selected shipping country
	$('.shipping_country').on('change', function()
		{
			//alert($(this).val());
			$('.shipping_country_selected').val($(this).val());
		});

	$('select.shipping_method').on('change', function()
		{
			selected = $(this).val();
			$('.country_select').hide();
			$('.country_select.'+selected).show();
		});

	$('.fa-question-circle').tooltipster({
                contentAsHTML: true,
                position: 'top-left'
            });


	//TABS

		$('ul.tabs li').click(function(){
		var tab_id = $(this).attr('data-tab');

		$('ul.tabs li').removeClass('current');
		$('.tab-content').removeClass('current');

		$(this).addClass('current');
		$("#"+tab_id).addClass('current');

		$('#product-table').dataTable().fnAdjustColumnSizing();
	})



  });
})(jQuery);