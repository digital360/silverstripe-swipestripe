<?php
/**
 * Form for displaying on the {@link CheckoutPage} with all the necessary details 
 * for a visitor to complete their order and pass off to the {@link Payment} gateway class.
 */
class RepayForm extends Form {

	protected $order;
	protected $customer;
	
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

		$orderID = Session::get('Repay.OrderID');
		if ($orderID) {
			$this->order = DataObject::get_by_id('Order', $orderID);
		}
		$this->customer = Customer::currentUser() ? Customer::currentUser() : singleton('Customer');

		$this->fields = $this->createFields();
		$this->actions = $this->createActions();
		$this->validator = $this->createValidator();

		$this->setupFormErrors();
		$this->setTemplate('RepayForm');
		$this->addExtraClass('order-form');
	}

	/**
	 * Set up current form errors in session to
	 * the current form if appropriate.
	 */
	public function setupFormErrors() {	
		//Only run when fields exist
		if ($this->fields->exists()) {
			parent::setupFormErrors();
		}
	}

	public function createFields() {

		$order = $this->order;
		$member = $this->customer;

		//Payment fields
		$supported_methods = PaymentProcessor::get_supported_methods();

		$paymentProcessFields = new FieldList();
		$outstanding = $order->TotalOutstanding()->Nice();
		$years = range(date('y'), date('y') + 10);

		$paymentFields = CompositeField::create()->setName('PaymentFields');

		$source = array();

		// Add a default field
		$source['none'] = 'Select Payment Method';
		
		foreach ($supported_methods as $methodName) {
			$methodConfig = PaymentFactory::get_factory_config($methodName);
			$source[$methodName] = $methodConfig['title'];
		}

		$paymentFields->push(new HeaderField(_t('CheckoutPage.PAYMENT',"Payment"), 3));
		$paymentFields->push(LiteralField::create('RepayLit', "<p>Process a payment for the oustanding amount: $outstanding</p>"));

		$methods = new DropDownField('PaymentMethod', 'Select Payment Method', $source);
		$methods->setCustomValidationMessage(_t('CheckoutPage.SELECT_PAYMENT_METHOD',"Please select a payment method."));
		$methods->addExtraClass('fields');
		$paymentFields->push($methods);

		Requirements::javascript('digital360-payments/scripts/digital360-payments.js');

		$fields = new FieldList(
			$paymentFields
		);

		$this->extend('updateFields', $fields);
		$fields->setForm($this);

		return $fields;
	}

	public function createActions() {
		$actions = FieldList::create(
			new FormAction('process', _t('CheckoutPage.PROCEED_TO_PAY',"Proceed to pay"))
		);

		$this->extend('updateActions', $actions);
		$actions->setForm($this);
		return $actions;
	}

	public function createValidator() {

		$validator = RequiredFields::create(
			'PaymentMethod'
		);

		$this->extend('updateValidator', $validator);
		$validator->setForm($this);
		return $validator;
	}

	public function getPaymentFields() {
		return $this->Fields()->fieldByName('PaymentFields');
	}
	
	/**
	 * Helper function to return the current {@link Order}, used in the template for this form
	 * 
	 * @return Order
	 */
	function Cart() {
		return $this->order;
	}

	/**
	 * Overloaded so that form error messages are displayed.
	 * 
	 * @see OrderFormValidator::php()
	 * @see Form::validate()
	 */
	public function validate() {
		parent::validate();
		
		// Check for errors thrown
		// to specific fields
		// (usually by the requiredFields 
		// param in the form)
		$errors = $this->getValidator()->getErrors();

		if (!empty($errors)) {
			$this->sessionMessage($errors, 'bad');
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

		//Check payment type
		try {
			$paymentMethod = $data['PaymentMethod'];
			$paymentProcessor = PaymentFactory::factory($paymentMethod);
		}
		catch (Exception $e) {
			// If payment method not found, return back
			// with an error message to the form
			$this->sessionMessage('Invalid Payment Type', 'bad');
			return $this->controller->redirectBack();
		}

		$member = Customer::currentUser();

		$orderID = Session::get('Repay.OrderID');
		if ($orderID) {
			$order = DataObject::get_by_id('Order', $orderID);
		}
		Session::clear('Repay.OrderID');
		
		$order->onBeforePayment();

		// Check SS form validation, 
		// if failed return back to repay page
		if (!$form->validate()) {
			return $this->controller->redirectBack();
		}

		try {
			$paymentData = array(
				'Amount' => number_format($order->TotalOutstanding()->getAmount(), 2, '.', ''),
				'Currency' => $order->TotalOutstanding()->getCurrency(),
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
		}
		catch (Exception $e) {

			//This is where we catch gateway validation or gateway unreachable errors
			$result = $paymentProcessor->gateway->getValidationResult();
			$payment = $paymentProcessor->payment;

			//TODO: Need to get errors and save for display on order page
			SS_Log::log(new Exception(print_r($result->message(), true)), SS_Log::NOTICE);
			SS_Log::log(new Exception(print_r($e->getMessage(), true)), SS_Log::NOTICE);

			return $this->controller->redirect($order->Link());
		}
	}

	function populateFields() {

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
