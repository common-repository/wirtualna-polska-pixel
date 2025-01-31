jQuery(document).ready(function($) {
 
	$('a.add_to_cart_button').on('click', function(e) {
		//e.preventDefault();

		var qty = parseInt($(this).attr('data-quantity'));
		
		$.ajax({
			url: wph_get_product.ajaxurl,
			data: {
				'action': 'wph_ajax_get_product',
				'product_id' : $(this).attr('data-product_id'),
				'nonce' : wph_get_product.nonce
			},
			success:function(data) {
				var product = JSON.parse(data);
				
				var cont = {
                   "id": product.id,
                   "name": product.name,
                   "price": product.price,
                    "sizes": product.sizes,
                   "quantity": qty,
                    "category": product.category,
                    "in_stock": product.in_stock
                };
				
				if(product.colour)
					cont.colour = product.colour;
				
				wph('track', 'AddToCart', {
					 contents: [cont]
				});
			},
			error: function(errorThrown){
				console.log(errorThrown);
			}
		});  
	});
              
});