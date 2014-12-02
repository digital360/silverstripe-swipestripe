;(function($) { 
	$.entwine('sws', function($){

		$('.order-form').entwine({

			onmatch : function() {
				var self = this;

				this.updateCart();
				this.on('submit', function(e){
					self._indicateProcessing(e);
				});

				this._super();
			},

			onunmatch: function() {
				this._super();
			},

			updateCart: function() {
				var self = this;
				var values = this.serialize();

				$.ajax({
					url: 'checkout/OrderForm/update',
					type: 'POST',
					data: values,
					beforeSend: function() {
						$('#cart-loading-js').show();
						$('#checkout-order-table').addClass('loading-currently');
					},
					success: function(data){
						$('#checkout-order-table').replaceWith(data);
					},
					complete: function() {
						$('#cart-loading-js').hide();
						$('#checkout-order-table').removeClass('loading-currently');
					}
				});
			},

			_indicateProcessing: function(e) {

				$('input[name="action_process"]', this).attr('disabled','disabled');
				$('input[name="action_process"]', this).attr('value', 'Processing...');
				$('.Actions .loading', this).show();
			}
		});

// ----------------------------------------------------------- Setup Create Account Fields

		var createAccount = $("#OrderForm_OrderForm_CreateAccount");

		function getCreateAccountFields(){}
		var getCreateAccountFields = new getCreateAccountFields();

		getCreateAccountFields.getFields = function() {
			
			$.ajax({
				type: 'GET',
				url: '/CheckoutPage_Controller/OrderForm/getAccountFields/',
				complete: function(data) {
					//$(".payment-details .loader").hide();

					if(data.responseText) {
						console.log(data.responseText);
						$(data.responseText).insertAfter("#OrderForm_OrderForm_CreateAccount");
						return;
					}

					// $(".order-form .action").prop('disabled', 'disabled');
					return;
				}
			});
		}

		createAccount.on('change', getCreateAccountFields.getFields);
	});
})(jQuery);