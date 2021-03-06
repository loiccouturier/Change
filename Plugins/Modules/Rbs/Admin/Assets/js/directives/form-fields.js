/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	var	app = angular.module('RbsChange'),
		fieldIdCounter = 0;


	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldText
	 * @name Field text
	 * @restrict E
	 *
	 * @description
	 * Displays a simple text field in a Document editor.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Text', '<input type="text" class="form-control"/>', 'input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldText
	 * @name Field text
	 * @restrict E
	 *
	 * @description
	 * Displays a simple text field in a Document editor.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('LongText', '<textarea class="form-control" rows="5" style="max-width: 100%;"></textarea>', 'textarea');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldEmail
	 * @name Field email
	 * @restrict E
	 *
	 * @description
	 * Displays a simple text field in a Document editor with email validation.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Email', '<input type="email" class="form-control"/>', 'input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldUrl
	 * @name Field URL
	 * @restrict E
	 *
	 * @description
	 * Displays a simple text field in a Document editor with URL validation.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Url', '<input type="url" class="form-control"/>', 'input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldInteger
	 * @name Field integer
	 * @restrict E
	 *
	 * @description
	 * Displays a simple text field in a Document editor with integer number validation.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Integer', '<input type="number" class="form-control" ng-pattern="/^\\-?[0-9]+$/"/>', 'input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldFloat
	 * @name Field float
	 * @restrict E
	 *
	 * @description
	 * Displays a simple text field in a Document editor with floating number validation.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Float', '<input type="text" class="form-control" rbs-smart-float=""/>', 'input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldBoolean
	 * @name Field boolean
	 * @restrict E
	 *
	 * @description
	 * Displays a field in a Document editor to select a boolean value.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Boolean', '<rbs-switch></rbs-switch>', 'rbs-switch');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldRichText
	 * @name Field rich text
	 * @restrict E
	 *
	 * @description
	 * Displays a rich text field in a Document editor.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('RichText', '<rbs-rich-text-input></rbs-rich-text-input>', 'rbs-rich-text-input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldPicker
	 * @name Field document picker
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select another Document.
	 *
	 * For more information, see the {@link change/RbsChange.directive:rbsDocumentPickerSingle `rbs-document-picker-single` Directive}.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 * @param {String} accepted-model Model name of the Documents that can be selected.
	 */
	registerFieldDirective('Picker', '<rbs-document-picker-single></rbs-document-picker-single>', 'rbs-document-picker-single');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldPickerMultiple
	 * @name Field multiple documents picker
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select other Documents.
	 *
	 * For more information, see the {@link change/RbsChange.directive:rbsDocumentPickerMultiple `rbs-document-picker-multiple` Directive}.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 * @param {String} accepted-model Model name of the Documents that can be selected.
	 */
	registerFieldDirective('PickerMultiple', '<rbs-document-picker-multiple></rbs-document-picker-multiple>', 'rbs-document-picker-multiple');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldDate
	 * @name Field date
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select a date.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Date', '<rbs-date-selector></rbs-date-selector>', 'rbs-date-selector');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldPrice
	 * @name Field price
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to enter a price.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Price', '<rbs-price-input></rbs-price-input>', 'rbs-price-input');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldImage
	 * @name Field image (upload)
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select an image to upload.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Image', '<rbs-uploader rbs-image-uploader="" storage-name="images" file-accept="image/*"></div>', '[rbs-image-uploader]');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldImage
	 * @name Field video (upload)
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select a video to upload.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Video', '<rbs-uploader rbs-video-uploader="" storage-name="videos" file-accept="video/*"></div>', '[rbs-video-uploader]');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldFile
	 * @name Field file (upload)
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select a file to upload.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('File', '<rbs-uploader rbs-file-uploader="" storage-name="files" file-accept="*"></div>', '[rbs-file-uploader]');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldSelectFromCollection
	 * @name Field select item from Collection
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select a value that comes from a Collection (see Rbs/Collection plugin).
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 * @param {String} rbs-items-from-collection Identifier of the Collection to use.
	 * @param {String} empty-value-label Text to display when nothing is selected.
	 */
	registerFieldDirective('SelectFromCollection', '<select class="form-control"></select>', 'select');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldAddress
	 * @name Field address
	 * @restrict E
	 *
	 * @description
	 * Displays the controls in a Document editor to enter an address.
	 *
	 * For more information, see the {@link change/RbsChange.directive:rbsAddressFields `rbs-address-fields` Directive}.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('Address', '<rbs-address-fields></rbs-address-fields>', 'rbs-address-fields', true);

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldChainedSelect
	 * @name Field chained selects
	 * @restrict E
	 *
	 * @description
	 * Displays the controls in a Document editor to select a Document via multiple chained listboxes.
	 *
	 * For more information, see the {@link change/RbsChange.directive:rbsDocumentChainedSelect `rbs-document-chained-select` Directive}.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 * @param {String} chain The chain definition.
	 */
	registerFieldDirective('ChainedSelect', '<rbs-document-chained-select></rbs-document-chained-select>', 'rbs-document-chained-select');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldDocumentSelect
	 * @name Field Document selector (listbox)
	 * @restrict E
	 *
	 * @description
	 * Displays a control in a Document editor to select a Document via a listbox.
	 *
	 * For more information, see the {@link change/RbsChange.directive:rbsDocumentSelect `rbs-document-select` Directive}.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	registerFieldDirective('DocumentSelect', '<rbs-document-select></rbs-document-select>', 'rbs-document-select');

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsField
	 * @name Field (generic container)
	 * @restrict E
	 *
	 * @description
	 * Basic Directive to wrap custom fields.
	 */
	app.directive('rbsField', function ()
	{
		return {
			restrict   : 'E',
			replace    : true,
			transclude : true,
			template   : fieldTemplate(''),

			compile : function (tElement, tAttrs)
			{
				var propertyName = tAttrs.property,
					$lbl = tElement.find('label').first(),
					fieldId = 'rbs_field_' + (propertyName ? propertyName.replace(/[^a-z0-9]/ig, '_') + '_' : '') + (++fieldIdCounter),
					required = (tAttrs.required === 'true');
				$lbl.html(tAttrs.label);
				$lbl.attr('for', fieldId);

				return function link (scope, iElement, iAttrs, controller, transcludeFn)
				{
					transcludeFn(function (clone) {
						iElement.find('.controls').append(clone);
						var $input = iElement.find('.controls [ng-model], .controls [data-ng-model]').attr('id', fieldId);
						if (propertyName) {
							$input.attr('input-id', fieldId).attr('name', propertyName);
						}
						if (required) {
							iElement.addClass('required');
							$input.attr('required', 'required');
						}
					});
				};
			}
		};
	});


	function fieldTemplate (contents, omitLabel)
	{
		if (omitLabel) {
			return '<div class="form-group property"><div class="col-lg-12 controls">' + contents + '</div></div>';
		}
		return '<div class="form-group property">' +
				'<label class="col-lg-3 control-label"></label>' +
				'<div class="col-lg-9 controls">' + contents + '</div>' +
			'</div>';
	}


	function registerFieldDirective (name, tpl, selector, omitLabel)
	{
		app.directive('rbsField' + name, ['RbsChange.Utils', function (Utils)
		{
			return {
				restrict   : 'E',
				replace    : true,
				transclude : true,
				template   : fieldTemplate(tpl + '<div ng-transclude=""></div>', omitLabel),

				compile : function (tElement, tAttrs) {
					rbsFieldCompile(tElement, tAttrs, selector, Utils);
					return function () {};
				}
			};
		}]);
	}


	/**
	 * Generic compile function for all field Directives.
	 * @param tElement
	 * @param tAttrs
	 * @param inputSelector
	 * @param Utils
	 */
	function rbsFieldCompile (tElement, tAttrs, inputSelector, Utils)
	{
		if (! tAttrs.property) {
			throw new Error("Missing 'property' attribute on <rbs-field-*/> directive");
		}

		var $lbl = tElement.find('label').first(),
			$ipt = tElement.find(inputSelector).first(),
			fieldId, property, ngModel, p;

		// Determine property's name and ngModel value.
		if ((p = tAttrs.property.indexOf('.')) === -1) {
			property = tAttrs.property;
			ngModel = 'document.' + property;
		} else {
			ngModel = tAttrs.property;
			property = tAttrs.property.substr(p + 1);
		}

		// Bind label and input field (unique 'for' attribute).
		fieldId = 'rbs_field_' + property.replace(/[^a-z0-9]/ig, '_') + '_' + (++fieldIdCounter);
		$lbl.html(tAttrs.label).attr('for', fieldId);
		$ipt.attr('id', fieldId).attr('input-id', fieldId).attr('name', property);

		// Init input field
		$ipt.attr('ng-model', ngModel);

		// Transfer most attributes to the input field
		angular.forEach(tAttrs, function (value, name) {
			if (name === 'required') {
				if (value === 'true' || value === 'required') {
					$ipt.attr('required', 'required');
					tElement.addClass('required');
				}
				tElement.removeAttr(name);
			}
			else if (name === 'inputClass') {
				$ipt.addClass(value);
				tElement.removeAttr(name);
			}
			else if (name === 'label') {
				$ipt.attr('property-label', value);
			}
			else if (shouldTransferAttribute(name)) {
				name = Utils.normalizeAttrName(name);
				$ipt.attr(name, value);
				tElement.removeAttr(name);
			}
		});
	}


	function shouldTransferAttribute (name)
	{
		return name !== 'id'
			&& name !== 'class'
			&& name !== 'property'
			&& name !== 'label'
			&& name !== 'ngHide' && name !== 'dataNgHide'
			&& name !== 'ngShow' && name !== 'dataNgShow'
			&& name !== 'ngIf' && name !== 'dataNgIf'
			&& name !== 'ngSwitchWhen' && name !== 'dataNgSwitchWhen'
			&& name.charAt(0) !== '$';
	}


	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsFieldLabelTitle
	 * @name Field label and title
	 * @restrict E
	 *
	 * @description
	 * Displays two text fields suitable to enter the `label` and `title` properties of publishable Documents. Both
	 * fields can be synchronized.
	 *
	 * @param {String} property Name of the property of the Document.
	 * @param {String} label Label of the field.
	 */
	app.directive('rbsFieldLabelTitle', [function ()
	{
		return {
			restrict : 'E',
			replace : true,
			scope : true,
			template : fieldTemplate(
				'<table class="field-label-and-title">' +
				'<tr>' +
					'<td>' +
						'<input type="text" class="form-control" name="label" ng-model="document.label"/>' +
						'<input type="text" class="form-control" name="title" ng-model="document.title" ng-readonly="isSync()"/>' +
					'</td>' +
					'<td valign="middle" class="sync-button-cell">' +
						'<button type="button" class="btn btn-sm" ng-class="{true:\'btn-info\', false:\'btn-default\'}[isSync()]" ng-click="toggleSync()">' +
							'<i ng-class="{true:\'icon-lock\', false:\'icon-unlock-alt\'}[isSync()]" class="icon-large"></i>' +
						'</button>' +
						'<div class="decorator" ng-class="{\'locked\':isSync()}"></div>' +
					'</td>' +
				'</tr>' +
				'</table>'
			),

			compile : function (tElement, tAttrs)
			{
				var $lbl = tElement.find('label').first(),
					fieldId = 'rbs_field_label_title_' + (++fieldIdCounter);
				$lbl.html(tAttrs.label).attr('for', fieldId);
				tElement.find('input').first()
					.attr('id', fieldId)
					.attr('input-id', fieldId)
					.attr('name', 'label');
				if (tAttrs.required === 'true') {
					tElement.find('input').attr('required', 'required');
					tElement.addClass('required');
				}

				return function rbsFieldLabelTitleLink (scope)
				{
					scope.toggleSync = function ()
					{
						var doc = scope.document;
						if (! doc) {
							return;
						}

						doc.META$.labelAndTitleSync = ! doc.META$.labelAndTitleSync;
						// When locking both values, 'label' is copied into 'title' property.
						if (doc.META$.labelAndTitleSync) {
							scope.document.title = scope.document.label;
						}
					};

					// Are 'label' and 'title' synchronized?
					scope.isSync = function ()
					{
						return (scope.document && scope.document.META$ && scope.document.META$.labelAndTitleSync) ? true : false;
					};

					// Wait for the Document to become ready, and determine if the 'label' and 'title' are
					// equal (synchronized). When done, watching the Document becomes useless, so we remove the watch.
					var unwatchDoc = scope.$watchCollection('document', function (doc)
					{
						if (angular.isDefined(doc.id)) {
							doc.META$.labelAndTitleSync = (doc.label === doc.title);
							unwatchDoc();
						}
					});

					// Watch for 'label' changes and copy value into 'title' if sync is ON.
					scope.$watch('document.label', function (label, oldLabel)
					{
						if (scope.isSync() && angular.isDefined(label) && label !== oldLabel) {
							scope.document.title = label;
						}
					});

					// Watch for 'title' changes and copy value into 'label' if sync is ON.
					scope.$watch('document.title', function (title, oldTitle)
					{
						if (scope.isSync() && angular.isDefined(title) && title !== oldTitle) {
							scope.document.label = title;
						}
					});

				};
			}
		};
	}]);

})();
