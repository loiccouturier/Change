(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	function changeEditorServiceFn ($timeout, $rootScope, $log, $location, FormsManager, MainMenu, Utils, ArrayUtils, Actions, Breadcrumb) {

		// Used internally to store compiled informations in data attributes.
		var FIELDS_DATA_KEY_NAME = 'chg-form-fields';


		/**
		 * Prepares the scope to be a Document editor.
		 *
		 * @param scope
		 * @param element
		 */
		function prepareScope (scope, element) {

			// scope.original has been set via the data-bindings of the editor's directive.
			// We copy the original document into the 'document' property of the scope:
			// scope.document is now the working copy, on which the form controls are working on.
			scope.document = angular.copy(scope.original);

			if (scope._isPrepared) {
				return;
			}

			scope.$on('Change:UpdateDocumentProperties', function onUpdateDocumentPropertiesFn (event, properties) {
				angular.extend(scope.document, properties);
				scope.submit();
			});


			// Are we in a "forms cascading" process?
			if (FormsManager.isCascading()) {
				scope.cascadeContext = angular.copy(FormsManager.cascadeContext);
			}

			/**
			 * Reset the form back to the originally loaded document (scope.original).
			 */
			scope.reset = function resetFn () {
				scope.document = angular.copy(scope.original);
			};

			/**
			 * Tells wether the editor has changes or not.
			 * @return Boolean
			 */
			scope.isUnchanged = function isUnchangedFn () {
				return angular.equals(scope.document, scope.original);
			};


			// Computes a list of changes on the fields in each digest cycle.
			scope.changes = [];
			scope.$watch(function scopeWatchFn () {
				ArrayUtils.clear(scope.changes);
				angular.forEach(scope.document, function (value, name) {
					var original = angular.isDefined(scope.original[name]) ? scope.original[name] : '';
					if (name !== 'META$' && ! angular.equals(original, value)) {
						scope.changes.push(name);
					}
				});
			});


			// Ask confirmation when leaving a form with unsaved changes.
			var locationChangeStartDeregistrationFn = $rootScope.$on('$locationChangeStart', function (event) {
				if (! scope.isUnchanged() && ! window.confirm("Des données n'ont pas été enregistrées. Si vous quittez la page, ces données seront perdues.\nSouhaitez-vous réellement quitter cette page ?")) {
					event.preventDefault();
				}
			});
			// De-register the $locationChangeStart handler when this scope is destroyed.
			scope.$on('$destroy', function () {
				locationChangeStartDeregistrationFn();
			});


			/**
			 * Sends the changes to the server, via a POST (creation) or a PUT (update) request.
			 */
			scope.submit = function submitFn () {
				var promise = null,
				    hadCorrection = scope.document.hasCorrection();

				function doSubmit () {

					function getSectionOfField (fieldName) {
						var result = null;
						angular.forEach(scope.menu, function (entry) {
							if (entry.type === 'section') {
								angular.forEach(entry.fields, function (field) {
									if (field.id === fieldName) {
										result = entry;
									}
								});
							}
						});
						return result;
					}

					function markFieldAsInvalid (fieldName, messages) {
						getSectionOfField(fieldName).invalid.push(fieldName);
					}

					function clearInvalidFields () {
						$(element).find('.control-group.property.error').removeClass('error');
						angular.forEach(scope.menu, function (entry) {
							if (entry.type === 'section') {
								ArrayUtils.clear(entry.invalid);
							}
						});
					}

					function saveSuccessHandler (docs) {
						var doc = docs[0];

						scope.original = angular.copy(doc);

						if (doc.hasCorrection() !== hadCorrection) {
							scope.$emit('Change:DocumentUpdated', doc);
						}

						clearInvalidFields();

						if (FormsManager.isCascading()) {
							scope.cascadeContext.saveCallback(doc);
							FormsManager.uncascade();
						} else {
							$rootScope.$broadcast('Change:DocumentSaved', doc);
							if (angular.isFunction(scope.onSave)) {
								scope.onSave(doc);
							}
						}
					}

					function saveErrorHandler (reason) {
						clearInvalidFields();
						if (angular.isObject(reason) && angular.isObject(reason.data) && angular.isObject(reason.data['properties-errors'])) {
							angular.forEach(reason.data['properties-errors'], function (messages, propertyName) {
								$(element).find('label[for="'+propertyName+'"]').each(function () {
									$(this).closest('.control-group.property').addClass('error');
									$(this).nextAll('.controls').find(':input').first().focus();
								});
								markFieldAsInvalid(propertyName, messages);
							});
						}
					}

					if (angular.isFunction(scope.beforeSave)) {
						scope.beforeSave(scope.document);
					}

					//console.log("Editor: save(): currentTreeNode=", Breadcrumb.getCurrentNode());

					Actions.execute(
						'save',
						{
							'$docs'                : [ scope.document ],
							'$propertyInfoProvider': element ? element.data(FIELDS_DATA_KEY_NAME) : null,
							'$scope'               : scope,
							'$currentTreeNode'     : Breadcrumb.getCurrentNode()
						}
					).then(saveSuccessHandler, saveErrorHandler);
				}

				if (angular.isFunction(scope.preSubmit)) {
					promise = scope.preSubmit(scope.document);
				}

				if (promise) {
					promise.then(doSubmit);
				} else {
					doSubmit();
				}

			};


			scope.canCancelCascade = function canCancelCascadeFn () {
				return FormsManager.isCascading();
			};

			scope.cancelCascade = function cancelCascadeFn () {
				if (FormsManager.isCascading()) {
					FormsManager.uncascade();
				}
			};

			scope.canGoBack = function canGoBackFn () {
				return scope.isUnchanged();
			};

			scope.goBack = function goBackFn () {
				if (angular.isFunction(scope.onCancel)) {
					scope.onCancel();
				}
			};

			scope.isNew = function isNewFn () {
				return Utils.isNew(scope.original);
			};

			scope.hasStatus = function hasStatusFn (status) {
				if (!scope.document) {
					return false;
				}
				var args = [scope.document];
				ArrayUtils.append(args, arguments);
				return Utils.hasStatus.apply(Utils, args);
			};

			scope.hasCorrectionOnProperty = function hasCorrectionOnPropertyFn (property) {
				return scope.document && scope.document.META$ && scope.document.META$.correction && ArrayUtils.inArray(property, scope.document.META$.correction.propertiesNames) !== -1;
			};

			scope.hasCorrection = function hasCorrectionFn () {
				return Utils.hasCorrection(scope.document);
			};

			scope.isCascading = function isCascadingFn () {
				return FormsManager.isCascading();
			};

			scope.onCancel = function onCancelFn () {
				Breadcrumb.goParent();
			};

			scope._isPrepared = true;

		}


		/**
		 * Parses the editor and finds sections used to automatically build the MainMenu on the left.
		 */
		function compileSectionsAndFields (tElement) {
			var menu = [],
			    fields = {},
			    groups = {},
			    matches;

			tElement.data(FIELDS_DATA_KEY_NAME, fields);

			tElement.find('fieldset').each(function (index, fieldset) {
				var $fs = jQuery(fieldset),
				    fsData = $fs.data(),
				    section,
				    entry;

				if (angular.isDefined(fsData.formSectionGroup) && angular.isUndefined(groups[fsData.formSectionGroup])) {
					groups[fsData.formSectionGroup] = true;
					menu.push({
						'label': fsData.formSectionGroup,
						'type' : 'group'
					});
				}

				section = fsData.ngShow || $fs.attr('ng-show') || $fs.attr('x-ng-show');
				if (section) {
					matches = (/section\s*==\s*'([\w\d\\-]*)'/).exec(section);
					if (matches.length !== 2) {
						console.error("Could not find section ID on fieldset.");
					}
					section = matches[1];
				} else {
					section = fsData.ngSwitchWhen || $fs.attr('ng-switch-when') || $fs.attr('x-ng-switch-when');
				}

				entry = {
					'type'     : 'section',
					'id'       : section || '',
					'label'    : fsData.formSectionLabel,
					'fields'   : [],
					'required' : [],
					'invalid'  : [],
					'corrected': []
				};

				if ( ! FormsManager.isCascading() ) {
					if (section && section.length) {
						entry.url = Utils.makeUrl($location.absUrl(), { 'section': section });
					} else {
						entry.url = Utils.makeUrl($location.absUrl(), { 'section': null });
					}
				}

				menu.push(entry);

				$fs.find('div.control-group.property').each(function (index, ctrlGrp) {
					var $ctrlGrp = $(ctrlGrp),
					    $lbl = $ctrlGrp.find('label[for]').first();

					fields[$lbl.attr('for')] = {
						'label'   : $lbl.text(),
						'section' :
						{
							'id'    : section,
							'label' : entry.label
						}
					};

					entry.fields.push({
						'id'    : $lbl.attr('for'),
						'label' : $lbl.text()
					});
					if ($ctrlGrp.hasClass('required')) {
						entry.required.push($lbl.attr('for'));
					}
					if ($ctrlGrp.hasClass('success')) { // TODO Change class name?
						entry.corrected.push($lbl.attr('for'));
					}
				});

			});

			return menu;
		}


		this.initScope = function scopeWatchOriginal (scope, element, callback) {
			scope.$watch('original', function () {
				prepareScope(scope, element);
				if (element) {
					$timeout(function () {
						scope.menu = compileSectionsAndFields(element);
						MainMenu.build(scope);
					});
				}
				if (angular.isFunction(callback)) {
					callback.apply(scope);
				}
			}, true);
		};

	}

	changeEditorServiceFn.$inject = [
		'$timeout', '$rootScope', '$log', '$location',
		'RbsChange.FormsManager',
		'RbsChange.MainMenu',
		'RbsChange.Utils',
		'RbsChange.ArrayUtils',
		'RbsChange.Actions',
		'RbsChange.Breadcrumb'
	];

	app.service('RbsChange.Editor', changeEditorServiceFn);

})(window.jQuery);