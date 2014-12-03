<?php
/**
 * Form for displaying on the {@link CheckoutPage} with all the necessary details 
 * for a visitor to complete their order and pass off to the {@link Payment} gateway class.
 */
class OrderForm extends Form {

	protected $order;
	protected $customer;

	private static $allowed_actions = array(
		'process',
		'update'
	);
	
	/**
	 * Construct the form, get the grouped fields and set the fields for this form appropriately,
	 * the fields are passed in an associative array so that the fields can be grouped into sets 
	 * making it easier for the template to grab certain fields for different parts of the form.
	 * 
	 * @param Controller $controller
	 * @param String $name
	 * @param Array $groupedFields Associative array of fields grouped into sets
	 * @param FieldList $actions
	 * @param Validator $validator
	 * @param Order $currentOrder
	 */
	function __construct($controller, $name) {

		parent::__construct($controller, $name, FieldList::create(), FieldList::create(), null);

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript('swipestripe/javascript/OrderForm.js');

		$this->order = Cart::get_current_order();
		$this->customer = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

		$this->fields = $this->createFields();
		$this->actions = $this->createActions();
		$this->validator = $this->createValidator();

		$this->setupFormErrors();

		$this->setTemplate('OrderForm');
		$this->addExtraClass('order-form');
	}

	/**
	 * Set up current form errors in session to
	 * the current form if appropriate.
	 *
	 * NOTE: At the moment, SilverStripe populates
	 * the 'confirm password' with the 'password'
	 * so it's overloaded for now to rectify.
	 */
	public function setupFormErrors() {

		// Only run when fields exist
		if ($this->fields->exists()) {
			$errorInfo = Session::get("FormInfo.{$this->FormName()}");

			if(isset($errorInfo['errors']) && is_array($errorInfo['errors'])) {

				foreach($errorInfo['errors'] as $error) {
					$field = $this->fields->dataFieldByName($error['fieldName']);

					if(!$field) {
						$errorInfo['message'] = $error['message'];
						$errorInfo['type'] = $error['messageType'];
					} else {
						$field->setError($error['message'], $error['messageType']);
					}				
				}

				// load data in from previous submission upon error
				if(isset($errorInfo['data'])) {
					// Unset Payment Method and Password fields
					// as we don't want these re-populated
					unset($errorInfo['data']['PaymentMethod']);
					unset($errorInfo['data']['Password']);
					$this->loadDataFrom($errorInfo['data']);
				}
			}

			if(isset($errorInfo['message']) && isset($errorInfo['type'])) {
				$this->setMessage($errorInfo['message'], $errorInfo['type']);
			}
		}
	}

	public function createFields() {

		$order = $this->order;
		$member = $this->customer;

		//Personal details fields
		if(!$member->ID || $member->Password == '') {

			$link = $this->controller->Link();
			
			$note = _t('CheckoutPage.NOTE','NOTE:');
			$passwd = _t('CheckoutPage.PLEASE_CHOOSE_PASSWORD','Please choose a password, so you can login and check your order history in the future.');
			$mber = sprintf(
				'If you are already a member please %slog in%s for an express checkout, otherwise continue below.', 
				"<a href=\"Security/login?BackURL=$link\">", 
				'</a>'
			);

			$passwordField = new ConfirmedPasswordField('Password', _t('CheckoutPage.PASSWORD', "Password"));
			$passwordField->minLength = 6;
			$passwordField->setAttribute('required', 'required');

			$personalFields = CompositeField::create(
				new CompositeField(
					EmailField::create('Email', _t('CheckoutPage.EMAIL', 'Email'))
						->setCustomValidationMessage(_t('CheckoutPage.PLEASE_ENTER_EMAIL_ADDRESS', "Please enter your email address."))
				),
				new CompositeField(
					new FieldGroup(
						$passwordField
					)
				)
			)->setID('PersonalDetails')->setName('PersonalDetails');

			$loginFields = CompositeField::create(
				new CompositeField(
					new LiteralField(
						'AccountInfo', 
						"
						<p class=\"alert alert-info highlight\">
							$mber
						</p>
						"
					)
				)
			)->setID('LoginFields')->setName('LoginFields');
		}

		//Order item fields
		$items = $order->Items();
		$itemFields = CompositeField::create()->setName('ItemsFields');
		if ($items) foreach ($items as $item) {
			$itemFields->push(new OrderForm_ItemField($item));
		}

		//Order modifications fields
		$subTotalModsFields = CompositeField::create()->setName('SubTotalModificationsFields');
		$subTotalMods = $order->SubTotalModifications();

		if ($subTotalMods && $subTotalMods->exists()) foreach ($subTotalMods as $modification) {
			$modFields = $modification->getFormFields();
			foreach ($modFields as $field) {
				$subTotalModsFields->push($field);
			}
		}

		$totalModsFields = CompositeField::create()->setName('TotalModificationsFields');
		$totalMods = $order->TotalModifications();

		if ($totalMods && $totalMods->exists()) foreach ($totalMods as $modification) {
			$modFields = $modification->getFormFields();
			foreach ($modFields as $field) {
				$totalModsFields->push($field);
			}
		}

		//Payment fields
		$supported_methods = PaymentProcessor::get_supported_methods();

		$paymentProcessFields = new FieldList();
		$years = range(date('y'), date('y') + 10);

		$paymentFields = CompositeField::create()->setName('PaymentFields');

		$source = array();
		
		// Add a default field
		$source['none'] = 'Select Payment Method';

		foreach ($supported_methods as $methodName) {
			$methodConfig = PaymentFactory::get_factory_config($methodName);
			$source[$methodName] = $methodConfig['title'];
		}

		$methods = new DropDownField('PaymentMethod', 'Select Payment Method', $source);
		$methods->setCustomValidationMessage(_t('CheckoutPage.SELECT_PAYMENT_METHOD',"Please select a payment method."));
		$methods->addExtraClass('fields');
		$paymentFields->push($methods);

		Requirements::javascript('digital360-payments/scripts/digital360-payments.js');

		$fields = FieldList::create(
			$itemFields,
			$subTotalModsFields,
			$totalModsFields,
			$notesFields = CompositeField::create(
				TextareaField::create('Notes', _t('CheckoutPage.NOTES_ABOUT_ORDER',"Notes about this order"))
			)->setName('NotesFields'),
			$paymentFields
		);

		if (isset($personalFields)) {
			$fields->push($personalFields);
		}

		if (isset($loginFields)) {
			$fields->push($loginFields);
		}

		$this->extend('updateFields', $fields);
		$fields->setForm($this);
		return $fields;
	}

	public function createActions() {
		$proceedButton = new FormAction('process', _t('CheckoutPage.PROCEED_TO_PAY', 'Proceed to pay'));
		$proceedButton->setDisabled(true);

		$actions = FieldList::create(
			$proceedButton
		);

		$this->extend('updateActions', $actions);
		$actions->setForm($this);
		return $actions;
	}

	public function createValidator() {

		$validator = OrderForm_Validator::create(
			'PaymentMethod'
		);

		if (!$this->customer->ID || $this->customer->Password == '') {
			$validator->addRequiredField('Password');
			$validator->addRequiredField('Email');
		}

		$this->extend('updateValidator', $validator);
		$validator->setForm($this);
		return $validator;
	}

	public function getPersonalDetailsFields() {
		return $this->Fields()->fieldByName('PersonalDetails');
	}

	public function getLoginFields() {
		return $this->Fields()->fieldByName('LoginFields');
	}

	public function getItemsFields() {
		return $this->Fields()->fieldByName('ItemsFields')->FieldList();
	}

	public function getSubTotalModificationsFields() {
		return $this->Fields()->fieldByName('SubTotalModificationsFields')->FieldList();
	}

	public function getTotalModificationsFields() {
		return $this->Fields()->fieldByName('TotalModificationsFields')->FieldList();
	}

	public function getNotesFields() {
		return $this->Fields()->fieldByName('NotesFields');
	}

	public function getPaymentFields() {
		return $this->Fields()->fieldByName('PaymentFields');
	}
	
	/**
	 * Helper function to return the current {@link Order}, used in the template for this form
	 * 
	 * @return Order
	 */
	public function Cart() {
		return $this->order;
	}

	/**
	 * Overloaded so that form error messages are displayed.
	 * 
	 * @see OrderFormValidator::php()
	 * @see Form::validate()
	 */
	public function validate(){
		parent::validate();

		// Check for errors thrown
		// to specific fields
		// (usually by the requiredFields 
		// param in the form)
		$errors = $this->getValidator()->getErrors();

		if (!empty($errors)) {
			if (!is_array($errors)) {
				$this->sessionMessage($errors, 'bad');
			} else {
				$this->sessionMessage('Issues have occured with the form below.  Please rectify before continuing.', 'bad');
			}

			return false;
		} else {

			// If no errors are found, check
			// for messages to the form
			$messages = $this->Message();

			if (!empty($messages)) {
				$this->sessionMessage($messages, 'bad');
				return false;
			}
		}

		return true;
	}

	public function process($data, $form) {
		$this->extend('onBeforeProcess', $data);

		//Check payment type
		try {
			$paymentMethod = Convert::raw2sql($data['PaymentMethod']);
			$paymentProcessor = PaymentFactory::factory($paymentMethod);
		}
		catch (Exception $e) {
			// If payment method not found, return back
			// with an error message to the form
			$this->sessionMessage('Invalid Payment Type', 'bad');
			return $this->controller->redirectBack();
		}

		//Save or create a new customer/member
		$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
		if (!$member->exists()) {

			$existingCustomer = Customer::get()->filter('Email', $data['Email']);
			if ($existingCustomer && $existingCustomer->exists()) {
				$form->sessionMessage(
					_t('CheckoutPage.MEMBER_ALREADY_EXISTS', 'Sorry, a member already exists with that email address. If this is your email address, please log in first before placing your order.'),
					'bad'
				);
				$this->controller->redirectBack();
				return false;
			}

			$member = Customer::create();
			$form->saveInto($member);
			$member->write();
			$member->addToGroupByCode('customers');
			$member->logIn();
		}

		// Save the order
		$order = Cart::get_current_order();
		$items = $order->Items();

		$order->onBeforePayment();

		try {
			$shopConfig = ShopConfig::current_shop_config();
			$precision = $shopConfig->BaseCurrencyPrecision;

			$paymentData = array(
				'Amount' => number_format($order->Total()->getAmount(), $precision, '.', ''),
				'Currency' => $order->Total()->getCurrency(),
				'Reference' => $order->ID,
				'Form' => $form
			);

			// Add Payment fields if there is any
			$paymentData['PaymentFields'] = new stdClass();

			if (!empty($data)) {
				foreach ($data as $key => $paymentFields) {
					$paymentData['PaymentFields']->$key = $paymentFields;
				}
			}

			$paymentProcessor->payment->OrderID = $order->ID;
			$paymentProcessor->payment->PaidByID = $member->ID;

			$paymentProcessor->setRedirectURL($order->Link());
			$paymentProcessor->capture($paymentData);

			// Check SS form validation, 
			// if failed return back to repay page
			if (!$form->validate()) {

				// If validation failed, return back
				return $this->controller->redirectBack();
			} else {

				// If validation succeeded, 
				// continue with saving the order
				$form->saveInto($order);
				$order->MemberID = $member->ID;
				$order->Status = Order::STATUS_PENDING;
				$order->OrderedOn = SS_Datetime::now()->getValue();
				$order->write();

				// Saving an update on the order
				if ($notes = $data['Notes']) {
					$update = new Order_Update();
					$update->Note = $notes;
					$update->Visible = true;
					$update->OrderID = $order->ID;
					$update->MemberID = $member->ID;
					$update->write();
				}

				// Add modifiers to order
				$order->updateModifications($data)->write();

				Session::clear('Cart.OrderID');
			}
		}
		catch (Exception $e) {

			//This is where we catch gateway validation or gateway unreachable errors
			$result = $paymentProcessor->gateway->getValidationResult();
			$payment = $paymentProcessor->payment;

			//TODO: Need to get errors and save for display on order page
			SS_Log::log(new Exception(print_r($result->message(), true)), SS_Log::NOTICE);
			SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);

			$this->controller->redirect($order->Link());
		}
	}

	public function update(SS_HTTPRequest $request) {

		if ($request->isPOST()) {

			$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
			$order = Cart::get_current_order();

			//Update the Order 
			$order->update($request->postVars());

			$order->updateModifications($request->postVars())
				->write();

			$form = OrderForm::create(
				$this->controller, 
				'OrderForm'
			)->disableSecurityToken();

			// $form->validate();

			return $form->renderWith('OrderFormCart');
		}
	}

	public function populateFields() {

		//Populate values in the form the first time
		if (!Session::get("FormInfo.{$this->FormName()}.errors")) {

			$member = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');
			$data = array_merge(
				$member->toMap()
			);

			$this->extend('updatePopulateFields', $data);
			$this->loadDataFrom($data);
		}
	}
}

/**
 * Validate the {@link OrderForm}, check that the current {@link Order} is valid.
 */
class OrderForm_Validator extends RequiredFields {

	/**
	 * Check that current order is valid
	 *
	 * @param Array $data Submitted data
	 * @return Boolean Returns TRUE if the submitted data is valid, otherwise FALSE.
	 */
	public function php($data) {

		$valid = parent::php($data);
		$fields = $this->form->Fields();
		
		//Check the order is valid
		$currentOrder = Cart::get_current_order();
		if (!$currentOrder) {
			$this->form->sessionMessage(
				_t('Form.ORDER_IS_NOT_VALID', 'Your cart seems to be empty, please add an item from the shop'),
				'bad'
			);
			
			//Have to set an error for Form::validate()
			$this->errors[] = true;
			$valid = false;
		}
		else {
			$validation = $currentOrder->validateForCart();
			
			if (!$validation->valid()) {
				
				$this->form->sessionMessage(
					_t('Form.ORDER_IS_NOT_VALID', 'There seems to be a problem with your order. ' . $validation->message()),
					'bad'
				);
				
				//Have to set an error for Form::validate()
				$this->errors[] = true;
				$valid = false;
			}
		}
		return $valid;
	}
	
	/**
	 * Helper so that form fields can access the form and current form data
	 * 
	 * @return Form
	 */
	public function getForm() {
		return $this->form;
	}
}

/**
 * Represent each {@link Item} in the {@link Order} on the {@link OrderForm}.
 */
class OrderForm_ItemField extends FormField {

	/**
	 * Template for rendering
	 *
	 * @var String
	 */
	protected $template = "OrderForm_ItemField";
	
	/**
	 * Current {@link Item} this field represents.
	 * 
	 * @var Item
	 */
	protected $item;
	
	/**
	 * Construct the form field and set the {@link Item} it represents.
	 * 
	 * @param Item $item
	 * @param Form $form
	 */
	public function __construct($item, $form = null){

		$this->item = $item;
		$name = 'OrderItem' . $item->ID;
		parent::__construct($name, null, '', null, $form);
	}
	
	/**
	 * Render the form field with the correct template.
	 * 
	 * @see FormField::FieldHolder()
	 * @return String
	 */
	public function FieldHolder($properties = array()) {
		return $this->renderWith($this->template);
	}
	
	/**
	 * Retrieve the {@link Item} this field represents.
	 * 
	 * @return Item
	 */
	public function Item() {
		return $this->item;
	}
	
	/**
	 * Set the {@link Item} this field represents.
	 * 
	 * @param Item $item
	 */
	public function setItem(Item $item) {
		$this->item = $item;
	}
	
	/**
	 * Validate this form field, make sure the {@link Item} exists, is in the current 
	 * {@link Order} and the item is valid for adding to the cart.
	 * 
	 * @see FormField::validate()
	 * @return Boolean
	 */
	public function validate($validator) {

		$valid = true;
		$item = $this->Item();
		$currentOrder = Cart::get_current_order();
		$items = $currentOrder->Items();
		
		//Check that item exists and is in the current order
		if (!$item || !$item->exists() || !$items->find('ID', $item->ID)) {
			
			$errorMessage = _t('Form.ITEM_IS_NOT_IN_ORDER', 'This product is not in the Order.');
			if ($msg = $this->getCustomValidationMessage()) {
				$errorMessage = $msg;
			}
			
			$validator->validationError(
				$this->getName(),
				$errorMessage,
				"error"
			);
			$valid = false;
		}
		else if ($item) {
			
			$validation = $item->validateForCart();
			
			if (!$validation->valid()) {
				
				$errorMessage = $validation->message();
				if ($msg = $this->getCustomValidationMessage()) {
					$errorMessage = $msg;
				}
				
				$validator->validationError(
					$this->getName(),
					$errorMessage,
					"error"
				);
				$valid = false;
			}
		}
		
		return $valid;
	}
}

