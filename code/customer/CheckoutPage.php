<?php
/**
 * A checkout page for displaying the checkout form to a visitor.
 * Automatically created on install of the shop module, cannot be deleted by admin user
 * in the CMS. A required page for the shop module.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPage extends Page {
	
	/**
	 * Automatically create a CheckoutPage if one is not found
	 * on the site at the time the database is built (dev/build).
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!DataObject::get_one('CheckoutPage')) {
			$page = new CheckoutPage();
			$page->Title = 'Checkout';
			$page->Content = '';
			$page->URLSegment = 'checkout';
			$page->ShowInMenus = 0;
			$page->writeToStage('Stage');
			$page->publish('Stage', 'Live');

			DB::alteration_message('Checkout page \'Checkout\' created', 'created');
		}
	}
	
	/**
	 * Prevent CMS users from creating another checkout page.
	 * 
	 * @see SiteTree::canCreate()
	 * @return Boolean Always returns false
	 */
	function canCreate($member = null) {
		return false;
	}
	
	/**
	 * Prevent CMS users from deleting the checkout page.
	 * 
	 * @see SiteTree::canDelete()
	 * @return Boolean Always returns false
	 */
	function canDelete($member = null) {
		return false;
	}

	public function delete() {
		if ($this->canDelete(Member::currentUser())) {
			parent::delete();
		}
	}
	
	/**
	 * Prevent CMS users from unpublishing the checkout page.
	 * 
	 * @see SiteTree::canDeleteFromLive()
	 * @see CheckoutPage::getCMSActions()
	 * @return Boolean Always returns false
	 */
	function canDeleteFromLive($member = null) {
		return false;
	}
	
	/**
	 * To remove the unpublish button from the CMS, as this page must always be published
	 * 
	 * @see SiteTree::getCMSActions()
	 * @see CheckoutPage::canDeleteFromLive()
	 * @return FieldList Actions fieldset with unpublish action removed
	 */
	function getCMSActions() {
		$actions = parent::getCMSActions();
		$actions->removeByName('action_unpublish');
		return $actions;
	}
	
	/**
	 * Remove page type dropdown to prevent users from changing page type.
	 * 
	 * @see Page::getCMSFields()
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('ClassName');
		return $fields;
	}
}

/**
 * Display the checkout page, with order form. Process the order - send the order details
 * off to the Payment class.
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage customer
 */
class CheckoutPage_Controller extends Page_Controller {

	protected $orderProcessed = false;

	private static $allowed_actions = array (
		'index',
		'OrderForm',
		'order'
	);

	/**
	 * Include some CSS and javascript for the checkout page
	 * 
	 * TODO why didn't I use init() here?
	 * 
	 * @return Array Contents for page rendering
	 */
	function index($request) {
		
		// Update stock levels
		// Order::delete_abandoned();

		// Do not allow on page unless
		// something's in the cart
		$currentOrder = Cart::get_current_order();

		// Do not allow the user to get 
		// to the checkout page if they 
		// don't have any items in the cart
		if ($currentOrder->Items() == null || $currentOrder->Items()->count() == 0) {
			// Redirect to home page if already on checkout
			$redirectHeaders = $this->redirectBack()->getHeaders();
			$absoluteCheckoutURL = Director::absoluteURL(DataObject::get_one('CheckoutPage')->Link());

			if ($redirectHeaders['Location'] == $absoluteCheckoutURL) {
				return $this->redirect('/');
			}

			return $this->redirectBack();
		}

		Requirements::css('swipestripe/css/Shop.css');

		return $this->renderOrderForm();
	}

	/**
	 * Add order into checkout session
	 * Assumed: clicked from account page
	 *
	 * @see Order::Link()
	 * 
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function order($request) {

		// Get current user
		$member = Customer::currentUser();
		$orderID = $request->param('ID');

		if (!empty($member) || $member === true) {
			if ($member->exists()) {

				// Get order for ID passed
				$order = Order::get()->filter('ID', $orderID)->first();

				// Check if this order belongs to 
				// this customer
				if (!empty($order)) {

					// Get account page URL for this order
					$accountPageURL = DataObject::get_one('AccountPage')->Link() . 'order/' . $orderID;

					if ($order->canView($member)) {
						// Add order to session
						$currentSessionOrder = Cart::get_current_order();

						// Remove any current order in the session
						// TODO: Do we want to save the current cart??
						$currentSessionOrder->delete();

						Session::set('Cart.OrderID', $orderID);
					} else {
						return $this->httpError(403, _t('AccountPage.CANNOT_VIEW_ORDER', 'You cannot view orders that do not belong to you.'));
					}

					// Check if the order has already been paid
					if ($order->getPaid()) {
						return $this->redirect($accountPageURL);
					}

					// Check if the order is still in the cart pending
					// payment
					if ($order->Status != 'Cart') {
						return $this->redirect($accountPageURL);
					}

				} else {
					return $this->httpError(403, _t('AccountPage.NO_ORDER_EXISTS', 'Order does not exist.'));
				}
			} else {
				return $this->redirect('Security/login?BackURL=' . urlencode('/checkout/order/' . $orderID));
			}

			return $this->renderOrderForm();
		} else {
			return $this->redirect('Security/login?BackURL=' . urlencode('/checkout/order/' . $orderID));
		}
	}

	function OrderForm() {

		$order = Cart::get_current_order();
		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

		$form = OrderForm::create(
			$this, 
			'OrderForm'
		)->disableSecurityToken();

		//Populate fields the first time form is loaded
		$form->populateFields();

		return $form;
	}

	private function renderOrderForm() {
		return array( 
			 'Content' => $this->Content, 
			 'Form' => $this->OrderForm()
		);
	}
}