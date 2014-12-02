<?php

class SwipeStripeSecurityAdmin extends SecurityAdmin {

	// public function getEditForm($id = null, $fields = null) {

	// 	$form = parent::getEditForm();
	// 	$fields = $form->form;
	// 	ddd($form);
	// 	// Remove 
		
	// 	// $memberList = GridField::create(
	// 	// 	'Members',
	// 	// 	false,
	// 	// 	Member::get()->filter('ClassName', 'Member'),
	// 	// 	$memberListConfig = GridFieldConfig_RecordEditor::create()
	// 	// 		->addComponent(new GridFieldButtonRow('after'))
	// 	// 		->addComponent(new GridFieldExportButton('buttons-after-left'))
	// 	// )->addExtraClass("members_grid");

	// 	// $fields = new FieldList(
	// 	// 	$root = new TabSet(
	// 	// 		'Root',
	// 	// 		$usersTab = new Tab('Users', _t('SecurityAdmin.Users', 'Users'),
	// 	// 			$memberList,
	// 	// 			new LiteralField('MembersCautionText',
	// 	// 				sprintf('<p class="caution-remove"><strong>%s</strong></p>',
	// 	// 					_t(
	// 	// 						'SecurityAdmin.MemberListCaution', 
	// 	// 						'Caution: Removing members from this list will remove them from all groups and the'
	// 	// 							. ' database'
	// 	// 					)
	// 	// 				)
	// 	// 			)
	// 	// 		),
	// 	// 		$groupsTab = new Tab('Groups', singleton('Group')->i18n_plural_name(),
	// 	// 			$groupList
	// 	// 		)
	// 	// 	),
	// 	// 	// necessary for tree node selection in LeftAndMain.EditForm.js
	// 	// 	new HiddenField('ID', false, 0)
	// 	// );

	// 	// // Add import capabilities. Limit to admin since the import logic can affect assigned permissions
	// 	// if(Permission::check('ADMIN')) {
	// 	// 	$fields->addFieldsToTab('Root.Users', array(
	// 	// 		new HeaderField(_t('SecurityAdmin.IMPORTUSERS', 'Import users'), 3),
	// 	// 		new LiteralField(
	// 	// 			'MemberImportFormIframe',
	// 	// 			sprintf(
	// 	// 					'<iframe src="%s" id="MemberImportFormIframe" width="100%%" height="250px" frameBorder="0">'
	// 	// 				. '</iframe>',
	// 	// 				$this->Link('memberimport')
	// 	// 			)
	// 	// 		)
	// 	// 	));
	// 	// 	$fields->addFieldsToTab('Root.Groups', array(
	// 	// 		new HeaderField(_t('SecurityAdmin.IMPORTGROUPS', 'Import groups'), 3),
	// 	// 		new LiteralField(
	// 	// 			'GroupImportFormIframe',
	// 	// 			sprintf(
	// 	// 					'<iframe src="%s" id="GroupImportFormIframe" width="100%%" height="250px" frameBorder="0">'
	// 	// 				. '</iframe>',
	// 	// 				$this->Link('groupimport')
	// 	// 			)
	// 	// 		)
	// 	// 	));
	// 	// }

	// 	// // Tab nav in CMS is rendered through separate template		
	// 	// $root->setTemplate('CMSTabSet');
		
	// 	// // Add roles editing interface
	// 	// if(Permission::check('APPLY_ROLES')) {
	// 	// 	$rolesField = GridField::create('Roles',
	// 	// 		false,
	// 	// 		PermissionRole::get(),
	// 	// 		GridFieldConfig_RecordEditor::create()
	// 	// 	);

	// 	// 	$rolesTab = $fields->findOrMakeTab('Root.Roles', _t('SecurityAdmin.TABROLES', 'Roles'));
	// 	// 	$rolesTab->push($rolesField);
	// 	// }

	// 	// $actionParam = $this->request->param('Action');
	// 	// if($actionParam == 'groups') {
	// 	// 	$groupsTab->addExtraClass('ui-state-active');
	// 	// } elseif($actionParam == 'users') {
	// 	// 	$usersTab->addExtraClass('ui-state-active');
	// 	// } elseif($actionParam == 'roles') {
	// 	// 	$rolesTab->addExtraClass('ui-state-active');
	// 	// }

	// 	// $actions = new FieldList();
		
	// 	// $form = CMSForm::create( 
	// 	// 	$this,
	// 	// 	'EditForm',
	// 	// 	$fields,
	// 	// 	$actions
	// 	// )->setHTMLID('Form_EditForm');
	// 	// $form->setResponseNegotiator($this->getResponseNegotiator());
	// 	// $form->addExtraClass('cms-edit-form');
	// 	// $form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
	// 	// // Tab nav in CMS is rendered through separate template
	// 	// if($form->Fields()->hasTabset()) {
	// 	// 	$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
	// 	// }
	// 	// $form->addExtraClass('center ss-tabset cms-tabset ' . $this->BaseCSSClasses());
	// 	// $form->setAttribute('data-pjax-fragment', 'CurrentForm');

	// 	// $this->extend('updateEditForm', $form);

	// 	// return $form;
	// }
}